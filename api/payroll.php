<?php
// api/payroll.php - Hitung Komisi & Payroll Kapster Sinkron dengan Transaksi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

requireAuth();

$db = Database::getConnection();

// Mendukung parameter 'start'/'end' maupun 'start_date'/'end_date'
$startDate = $_GET['start'] ?? $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end']   ?? $_GET['end_date']   ?? date('Y-m-d');
$barberId  = !empty($_GET['barber_id']) ? (int)$_GET['barber_id'] : null;

// Validasi format tanggal sederhana
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))   $endDate   = date('Y-m-d');

// 1. Ringkasan performa & komisi per kapster dalam rentang tanggal
$sql = "
    SELECT 
        b.id,
        b.id as barber_id,
        b.name,
        b.name as barber_name,
        b.phone as barber_phone,
        b.status as barber_status,
        COUNT(td.id) as service_count,
        COUNT(td.id) as total_jobs,
        COALESCE(SUM(td.service_price), 0) as gross_revenue,
        COALESCE(SUM(td.service_price), 0) as total_gross_generated,
        COALESCE(SUM(td.barber_commission_amount), 0) as total_commission,
        COALESCE(SUM(td.barber_commission_amount), 0) as total_commission_earned
    FROM barbers b
    LEFT JOIN (
        SELECT td2.* 
        FROM transaction_details td2
        JOIN transactions t2 ON td2.transaction_id = t2.id
        WHERE DATE(t2.created_at) BETWEEN ? AND ?
    ) td ON b.id = td.barber_id
";

$params = [$startDate, $endDate];

if ($barberId) {
    $sql .= " WHERE b.id = ? ";
    $params[] = $barberId;
} else {
    $sql .= " WHERE b.status = 'active' ";
}

$sql .= " GROUP BY b.id, b.name, b.phone, b.status ORDER BY total_commission DESC, service_count DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payrollSummary = $stmt->fetchAll();

// 2. Rincian detail pekerjaan transaksi per kapster dalam rentang tanggal
$sqlDetails = "
    SELECT 
        td.id,
        t.transaction_code,
        t.created_at,
        b.name as barber_name,
        s.name as service_name,
        td.service_price,
        td.barber_commission_amount,
        td.barber_commission_amount as commission_amount
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    JOIN barbers b ON td.barber_id = b.id
    JOIN services s ON td.service_id = s.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
";

$paramsDetails = [$startDate, $endDate];

if ($barberId) {
    $sqlDetails .= " AND b.id = ? ";
    $paramsDetails[] = $barberId;
}

$sqlDetails .= " ORDER BY t.created_at DESC, td.id DESC";

$stmtDetails = $db->prepare($sqlDetails);
$stmtDetails->execute($paramsDetails);
$details = $stmtDetails->fetchAll();

sendJsonResponse(true, 'Data payroll & komisi berhasil dimuat.', [
    'filter' => [
        'start_date' => $startDate,
        'end_date'   => $endDate,
        'barber_id'  => $barberId
    ],
    'summary'     => $payrollSummary,
    'details'     => $details,
    'job_details' => $details
]);
