<?php
// api/queue.php - Manajemen Antrean Pelanggan
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = Database::getConnection();

// GET: Ambil antrean hari ini
if ($method === 'GET') {
    requireAuth();
    $date = $_GET['date'] ?? date('Y-m-d');
    $stmt = $db->prepare("
        SELECT q.*, b.name as barber_name
        FROM queue q
        LEFT JOIN barbers b ON q.barber_id = b.id
        WHERE DATE(q.created_at) = ?
        ORDER BY q.queue_number ASC
    ");
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    // Hitung stats
    $waiting  = count(array_filter($rows, fn($r) => $r['status'] === 'waiting'));
    $serving  = count(array_filter($rows, fn($r) => $r['status'] === 'serving'));
    $done     = count(array_filter($rows, fn($r) => $r['status'] === 'done'));
    $skipped  = count(array_filter($rows, fn($r) => $r['status'] === 'skipped'));

    sendJsonResponse(true, 'Data antrean berhasil dimuat.', [
        'queue'   => $rows,
        'stats'   => compact('waiting', 'serving', 'done', 'skipped'),
        'next_number' => count($rows) + 1
    ]);
}

// POST: Tambah antrean baru
if ($method === 'POST') {
    requireAuth();
    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $custName  = trim($input['customer_name'] ?? 'Pelanggan');
    $barberId  = !empty($input['barber_id']) ? (int)$input['barber_id'] : null;
    $serviceNote = trim($input['service_note'] ?? '');
    $date      = date('Y-m-d');

    // Hitung nomor antrean hari ini
    $stmtNum = $db->prepare("SELECT COUNT(*) FROM queue WHERE DATE(created_at) = ?");
    $stmtNum->execute([$date]);
    $todayCount = (int)$stmtNum->fetchColumn();
    $queueNum = $todayCount + 1;

    $stmt = $db->prepare("INSERT INTO queue (queue_number, customer_name, barber_id, service_note, status) VALUES (?,?,?,?,'waiting')");
    $stmt->execute([$queueNum, $custName, $barberId, $serviceNote]);

    sendJsonResponse(true, 'Antrean berhasil ditambahkan.', [
        'id'           => $db->lastInsertId(),
        'queue_number' => $queueNum,
        'customer_name'=> $custName
    ], 201);
}

// PUT: Update status antrean
if ($method === 'PUT') {
    requireAuth();
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';

    if (!$id) sendJsonResponse(false, 'ID antrean tidak valid.', null, 400);
    $allowed = ['waiting', 'serving', 'done', 'skipped'];
    if (!in_array($status, $allowed)) sendJsonResponse(false, 'Status tidak valid.', null, 400);

    $db->prepare("UPDATE queue SET status = ? WHERE id = ?")->execute([$status, $id]);
    sendJsonResponse(true, 'Status antrean diperbarui.');
}

// DELETE: Hapus antrean
if ($method === 'DELETE') {
    requireAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if (!$id) sendJsonResponse(false, 'ID tidak valid.', null, 400);
    $db->prepare("DELETE FROM queue WHERE id = ?")->execute([$id]);
    sendJsonResponse(true, 'Antrean berhasil dihapus.');
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
