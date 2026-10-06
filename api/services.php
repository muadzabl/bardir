<?php
// api/services.php - Manajemen Layanan & Tarif Komisi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

// GET: Ambil daftar layanan
if ($method === 'GET') {
    $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === '1';
    if ($activeOnly) {
        $stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name ASC");
    } else {
        $stmt = $db->query("SELECT * FROM services ORDER BY is_active DESC, name ASC");
    }
    $services = $stmt->fetchAll();
    sendJsonResponse(true, 'Data layanan berhasil dimuat.', $services);
}

// POST: Tambah layanan baru (Owner only)
if ($method === 'POST') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $name = trim($input['name'] ?? '');
    $price = (float)($input['price'] ?? 0);
    $commission = (float)($input['default_commission'] ?? 0);
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    if (empty($name) || $price <= 0) {
        sendJsonResponse(false, 'Nama layanan dan harga valid wajib diisi.', null, 400);
    }

    $stmt = $db->prepare("INSERT INTO services (name, price, default_commission, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $commission, $isActive]);
    $newId = $db->lastInsertId();

    sendJsonResponse(true, 'Layanan baru berhasil ditambahkan.', [
        'id' => $newId, 
        'name' => $name, 
        'price' => $price, 
        'default_commission' => $commission,
        'is_active' => $isActive
    ], 201);
}

// PUT / PATCH: Update layanan & komisi (Owner only)
if ($method === 'PUT' || $method === 'PATCH') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $price = (float)($input['price'] ?? 0);
    $commission = (float)($input['default_commission'] ?? 0);
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    if ($id <= 0 || empty($name) || $price <= 0) {
        sendJsonResponse(false, 'ID, nama layanan, dan harga valid wajib diisi.', null, 400);
    }

    $stmt = $db->prepare("UPDATE services SET name = ?, price = ?, default_commission = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$name, $price, $commission, $isActive, $id]);

    sendJsonResponse(true, 'Layanan & tarif komisi berhasil diperbarui.');
}

// DELETE: Hapus layanan (Owner only)
if ($method === 'DELETE') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        sendJsonResponse(false, 'ID layanan tidak valid.', null, 400);
    }

    // Check if service has transaction history
    $stmtCheck = $db->prepare("SELECT COUNT(*) as cnt FROM transaction_details WHERE service_id = ?");
    $stmtCheck->execute([$id]);
    $hasTrx = (int)$stmtCheck->fetch()['cnt'];

    if ($hasTrx > 0) {
        $stmt = $db->prepare("UPDATE services SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        sendJsonResponse(true, 'Layanan memiliki riwayat transaksi, status telah dinonaktifkan.');
    } else {
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        sendJsonResponse(true, 'Layanan berhasil dihapus.');
    }
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
