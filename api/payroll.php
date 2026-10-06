<?php
// api/payroll.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01'); // default: start of current month
$endDate   = $_GET['end_date'] ?? date('Y-m-d');   // default: today
$barberId  = !empty($_GET['barber_id']) ? (int)$_GET['barber_id'] : null;

$sql = "
    SELECT 
        b.id as barber_id,
        b.name as barber_name,
        b.phone as barber_phone,
        b.status as barber_status,
        COUNT(td.id) as total_jobs,
        COALESCE(SUM(td.service_price), 0) as total_gross_generated,
        COALESCE(SUM(td.barber_commission_amount), 0) as total_commission_earned
    FROM barbers b
    LEFT JOIN transaction_details td ON b.id = td.barber_id
    LEFT JOIN transactions t ON td.transaction_id = t.id AND DATE(t.created_at) BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if ($barberId) {
    $sql .= " WHERE b.id = ? ";
    $params[] = $barberId;
}

$sql .= " GROUP BY b.id ORDER BY total_commission_earned DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payrollSummary = $stmt->fetchAll();

// Detailed breakdown items per job
$stmtDetails = $db->prepare("
    SELECT 
        td.id,
        t.transaction_code,
        t.created_at,
        b.name as barber_name,
        s.name as service_name,
        td.service_price,
        td.barber_commission_amount
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    JOIN barbers b ON td.barber_id = b.id
    JOIN services s ON td.service_id = s.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    " . ($barberId ? "AND b.id = ?" : "") . "
    ORDER BY t.created_at DESC
");
$stmtDetails->execute($params);
$details = $stmtDetails->fetchAll();

sendJsonResponse(true, 'Data payroll & komisi berhasil dimuat.', [
    'filter' => [
        'start_date' => $startDate,
        'end_date'   => $endDate,
        'barber_id'  => $barberId
    ],
    'summary' => $payrollSummary,
    'job_details' => $details
]);
