<?php
// test_db.php - Skrip Cek Koneksi & Data BARDIR
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getConnection();
    
    // Check tables
    $tables = ['users', 'barbers', 'services', 'transactions', 'transaction_details'];
    $tableCounts = [];
    
    foreach ($tables as $tbl) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM `$tbl`");
        $tableCounts[$tbl] = (int)$stmt->fetch()['cnt'];
    }

    echo json_encode([
        'status' => 'SUCCESS',
        'message' => 'Koneksi ke database MySQL (barberos_db) BERHASIL!',
        'database' => DB_NAME,
        'table_statistics' => $tableCounts,
        'server_time' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'hint' => 'Pastikan Laragon/MySQL aktif dan telah menjalankan import `schema.sql`.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
