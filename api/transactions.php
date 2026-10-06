<?php
// api/transactions.php - POS, Checkout, & Struk Cetak
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

// GET: Ambil daftar riwayat transaksi atau detail struk per ID
if ($method === 'GET') {
    $trxId = isset($_GET['id']) ? (int)$_GET['id'] : null;

    // Ambil detail spesifik untuk struk
    if ($trxId) {
        $stmtTrx = $db->prepare("
            SELECT 
                t.*,
                u.name as cashier_name
            FROM transactions t
            JOIN users u ON t.cashier_id = u.id
            WHERE t.id = ?
            LIMIT 1
        ");
        $stmtTrx->execute([$trxId]);
        $trx = $stmtTrx->fetch();

        if (!$trx) {
            sendJsonResponse(false, 'Transaksi tidak ditemukan.', null, 404);
        }

        // Ambil item details
        $stmtItems = $db->prepare("
            SELECT 
                td.*,
                s.name as service_name,
                b.name as barber_name
            FROM transaction_details td
            JOIN services s ON td.service_id = s.id
            JOIN barbers b ON td.barber_id = b.id
            WHERE td.transaction_id = ?
            ORDER BY td.id ASC
        ");
        $stmtItems->execute([$trxId]);
        $trx['items'] = $stmtItems->fetchAll();

        sendJsonResponse(true, 'Detail transaksi berhasil dimuat.', $trx);
    }

    // Ambil daftar riwayat transaksi
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $date = $_GET['date'] ?? null;

    $sql = "
        SELECT 
            t.id,
            t.transaction_code,
            t.subtotal,
            t.discount_amount,
            t.total_amount,
            t.payment_method,
            t.notes,
            t.created_at,
            u.name as cashier_name,
            COUNT(td.id) as total_items,
            SUM(td.barber_commission_amount) as total_commission,
            GROUP_CONCAT(DISTINCT b.name SEPARATOR ', ') as barbers_involved
        FROM transactions t
        JOIN users u ON t.cashier_id = u.id
        LEFT JOIN transaction_details td ON t.id = td.transaction_id
        LEFT JOIN barbers b ON td.barber_id = b.id
    ";

    $params = [];
    if ($date) {
        $sql .= " WHERE DATE(t.created_at) = ? ";
        $params[] = $date;
    }

    $sql .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT " . $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    sendJsonResponse(true, 'Data transaksi berhasil dimuat.', $transactions);
}

// POST: Buat Transaksi Baru (POS Checkout) & return detail struk
if ($method === 'POST') {
    $user = requireAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $paymentMethod = in_array($input['payment_method'] ?? '', ['cash', 'qris', 'transfer', 'debit']) 
        ? $input['payment_method'] 
        : 'cash';
    $discountAmount = max(0, (float)($input['discount_amount'] ?? 0));
    $notes = trim($input['notes'] ?? '');
    $items = $input['items'] ?? [];

    if (empty($items) || !is_array($items)) {
        sendJsonResponse(false, 'Harap pilih minimal satu layanan dan kapster.', null, 400);
    }

    try {
        $db->beginTransaction();

        // 1. Ambil data layanan
        $serviceIds = array_column($items, 'service_id');
        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $stmtServices = $db->prepare("SELECT id, name, price, default_commission FROM services WHERE id IN ($placeholders)");
        $stmtServices->execute($serviceIds);
        $serviceCatalog = [];
        foreach ($stmtServices->fetchAll() as $s) {
            $serviceCatalog[$s['id']] = $s;
        }

        // Ambil data barber
        $barberIds = array_column($items, 'barber_id');
        $bPlaceholders = implode(',', array_fill(0, count($barberIds), '?'));
        $stmtBarbers = $db->prepare("SELECT id, name FROM barbers WHERE id IN ($bPlaceholders)");
        $stmtBarbers->execute($barberIds);
        $barberCatalog = [];
        foreach ($stmtBarbers->fetchAll() as $b) {
            $barberCatalog[$b['id']] = $b['name'];
        }

        $subtotal = 0.00;
        $preparedDetails = [];

        foreach ($items as $item) {
            $sId = (int)($item['service_id'] ?? 0);
            $bId = (int)($item['barber_id'] ?? 0);

            if (!isset($serviceCatalog[$sId])) {
                throw new Exception("Layanan dengan ID $sId tidak ditemukan.");
            }

            $service = $serviceCatalog[$sId];
            $servicePrice = (float)$service['price'];
            $commission = isset($item['commission']) && is_numeric($item['commission']) 
                ? (float)$item['commission'] 
                : (float)$service['default_commission'];

            $subtotal += $servicePrice;
            $preparedDetails[] = [
                'service_id' => $sId,
                'service_name' => $service['name'],
                'barber_id' => $bId,
                'barber_name' => $barberCatalog[$bId] ?? 'Kapster',
                'service_price' => $servicePrice,
                'barber_commission_amount' => $commission
            ];
        }

        // Hitung total setelah diskon
        $totalAmount = max(0, $subtotal - $discountAmount);

        // 2. Insert Header Transaksi
        $trxCode = generateTransactionCode($db);
        $stmtTrx = $db->prepare("
            INSERT INTO transactions (transaction_code, subtotal, discount_amount, total_amount, payment_method, notes, cashier_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtTrx->execute([$trxCode, $subtotal, $discountAmount, $totalAmount, $paymentMethod, $notes, $user['id']]);
        $transactionId = $db->lastInsertId();

        // 3. Insert Details Transaksi
        $stmtDetail = $db->prepare("
            INSERT INTO transaction_details (transaction_id, service_id, barber_id, service_price, barber_commission_amount, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        foreach ($preparedDetails as $detail) {
            $stmtDetail->execute([
                $transactionId,
                $detail['service_id'],
                $detail['barber_id'],
                $detail['service_price'],
                $detail['barber_commission_amount']
            ]);
        }

        $db->commit();

        // Return full structure for instant receipt printing
        sendJsonResponse(true, 'Transaksi berhasil disimpan!', [
            'id'               => $transactionId,
            'transaction_code' => $trxCode,
            'subtotal'         => $subtotal,
            'discount_amount'  => $discountAmount,
            'total_amount'     => $totalAmount,
            'payment_method'   => $paymentMethod,
            'notes'            => $notes,
            'cashier_name'     => $user['name'],
            'created_at'       => date('Y-m-d H:i:s'),
            'items'            => $preparedDetails
        ], 201);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendJsonResponse(false, 'Gagal menyimpan transaksi: ' . $e->getMessage(), null, 500);
    }
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
