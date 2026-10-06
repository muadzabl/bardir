<?php
// api/barbers.php - Manajemen Pegawai / Kapster
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

// GET: Ambil daftar kapster
if ($method === 'GET') {
    $statusFilter = $_GET['status'] ?? null;
    if ($statusFilter === 'active') {
        $stmt = $db->prepare("SELECT * FROM barbers WHERE status = 'active' ORDER BY name ASC");
        $stmt->execute();
    } else {
        $stmt = $db->query("SELECT * FROM barbers ORDER BY status ASC, name ASC");
    }
    $barbers = $stmt->fetchAll();
    sendJsonResponse(true, 'Data kapster berhasil dimuat.', $barbers);
}

// POST: Tambah kapster baru (Owner only)
if ($method === 'POST') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $status = in_array($input['status'] ?? '', ['active', 'inactive']) ? $input['status'] : 'active';

    if (empty($name)) {
        sendJsonResponse(false, 'Nama kapster wajib diisi.', null, 400);
    }

    $stmt = $db->prepare("INSERT INTO barbers (name, phone, status) VALUES (?, ?, ?)");
    $stmt->execute([$name, $phone, $status]);
    $newId = $db->lastInsertId();

    sendJsonResponse(true, 'Kapster berhasil ditambahkan.', [
        'id' => $newId, 
        'name' => $name, 
        'phone' => $phone, 
        'status' => $status
    ], 201);
}

// PUT / PATCH: Update data kapster (Owner only)
if ($method === 'PUT' || $method === 'PATCH') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $status = in_array($input['status'] ?? '', ['active', 'inactive']) ? $input['status'] : 'active';

    if ($id <= 0 || empty($name)) {
        sendJsonResponse(false, 'ID dan Nama kapster wajib diisi.', null, 400);
    }

    $stmt = $db->prepare("UPDATE barbers SET name = ?, phone = ?, status = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $status, $id]);

    sendJsonResponse(true, 'Data kapster berhasil diperbarui.');
}

// DELETE: Hapus kapster (Owner only)
if ($method === 'DELETE') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        sendJsonResponse(false, 'ID kapster tidak valid.', null, 400);
    }

    // Check if barber has transaction history
    $stmtCheck = $db->prepare("SELECT COUNT(*) as cnt FROM transaction_details WHERE barber_id = ?");
    $stmtCheck->execute([$id]);
    $hasTrx = (int)$stmtCheck->fetch()['cnt'];

    if ($hasTrx > 0) {
        // Soft delete / set to inactive if has history to maintain financial audit integrity
        $stmt = $db->prepare("UPDATE barbers SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        sendJsonResponse(true, 'Kapster memiliki riwayat transaksi, status telah diubah menjadi non-aktif.');
    } else {
        $stmt = $db->prepare("DELETE FROM barbers WHERE id = ?");
        $stmt->execute([$id]);
        sendJsonResponse(true, 'Kapster berhasil dihapus.');
    }
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
