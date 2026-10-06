<?php
// api/dashboard.php - Multi-Period Analytics (Harian, Bulanan, Tahunan) & Live Tracker
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getConnection();
    $today = date('Y-m-d');
    $currentYear = date('Y');

    // 1. Total Pelanggan & Omset Hari Ini
    $stmtToday = $db->prepare("
        SELECT 
            COUNT(id) as total_customers,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(SUM(discount_amount), 0) as total_discount
        FROM transactions 
        WHERE DATE(created_at) = ?
    ");
    $stmtToday->execute([$today]);
    $todayStats = $stmtToday->fetch();

    // 2. Total Komisi Kapster Hari Ini
    $stmtCommission = $db->prepare("
        SELECT 
            COALESCE(SUM(td.barber_commission_amount), 0) as total_barber_payout
        FROM transaction_details td
        JOIN transactions t ON td.transaction_id = t.id
        WHERE DATE(t.created_at) = ?
    ");
    $stmtCommission->execute([$today]);
    $commissionStats = $stmtCommission->fetch();

    $todayRevenue = (float)$todayStats['total_revenue'];
    $todayCommission = (float)$commissionStats['total_barber_payout'];
    $todayNetOwner = $todayRevenue - $todayCommission;

    // 3. Performa Kapster Hari Ini
    $stmtBarberPerf = $db->prepare("
        SELECT 
            b.id,
            b.name,
            COUNT(td.id) as service_count,
            COALESCE(SUM(td.barber_commission_amount), 0) as total_commission,
            COALESCE(SUM(td.service_price), 0) as gross_revenue
        FROM barbers b
        LEFT JOIN (
            SELECT td2.* 
            FROM transaction_details td2
            JOIN transactions t2 ON td2.transaction_id = t2.id
            WHERE DATE(t2.created_at) = ?
        ) td ON b.id = td.barber_id
        WHERE b.status = 'active'
        GROUP BY b.id, b.name
        ORDER BY service_count DESC, total_commission DESC
    ");
    $stmtBarberPerf->execute([$today]);
    $barberPerformances = $stmtBarberPerf->fetchAll();

    // 4. GRAFIK HARIAN (14 Hari Terakhir) - Grouping strict MySQL 8
    $stmtDaily = $db->query("
        SELECT 
            DATE(t.created_at) as period_key,
            DATE_FORMAT(t.created_at, '%d %b') as label,
            COUNT(t.id) as total_customers,
            COALESCE(SUM(t.total_amount), 0) as gross_revenue,
            COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (
            SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
            FROM transaction_details
            GROUP BY transaction_id
        ) comm ON t.id = comm.transaction_id
        WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(t.created_at), DATE_FORMAT(t.created_at, '%d %b')
        ORDER BY period_key ASC
    ");
    $dailyRaw = $stmtDaily->fetchAll();
    $dailyData = array_map(function($row) {
        $row['net_owner'] = (float)$row['gross_revenue'] - (float)$row['total_commission'];
        return $row;
    }, $dailyRaw);

    // 5. GRAFIK BULANAN (12 Bulan di Tahun Ini)
    $stmtMonthly = $db->prepare("
        SELECT 
            DATE_FORMAT(t.created_at, '%Y-%m') as period_key,
            DATE_FORMAT(t.created_at, '%b %Y') as label,
            COUNT(t.id) as total_customers,
            COALESCE(SUM(t.total_amount), 0) as gross_revenue,
            COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (
            SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
            FROM transaction_details
            GROUP BY transaction_id
        ) comm ON t.id = comm.transaction_id
        WHERE YEAR(t.created_at) = ?
        GROUP BY DATE_FORMAT(t.created_at, '%Y-%m'), DATE_FORMAT(t.created_at, '%b %Y')
        ORDER BY period_key ASC
    ");
    $stmtMonthly->execute([$currentYear]);
    $monthlyRaw = $stmtMonthly->fetchAll();
    $monthlyData = array_map(function($row) {
        $row['net_owner'] = (float)$row['gross_revenue'] - (float)$row['total_commission'];
        return $row;
    }, $monthlyRaw);

    // 6. GRAFIK TAHUNAN (Semua Tahun)
    $stmtYearly = $db->query("
        SELECT 
            CAST(YEAR(t.created_at) AS CHAR) as period_key,
            CAST(YEAR(t.created_at) AS CHAR) as label,
            COUNT(t.id) as total_customers,
            COALESCE(SUM(t.total_amount), 0) as gross_revenue,
            COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (
            SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
            FROM transaction_details
            GROUP BY transaction_id
        ) comm ON t.id = comm.transaction_id
        GROUP BY YEAR(t.created_at), CAST(YEAR(t.created_at) AS CHAR)
        ORDER BY period_key ASC
    ");
    $yearlyRaw = $stmtYearly->fetchAll();
    $yearlyData = array_map(function($row) {
        $row['net_owner'] = (float)$row['gross_revenue'] - (float)$row['total_commission'];
        return $row;
    }, $yearlyRaw);

    sendJsonResponse(true, 'Data dashboard berhasil dimuat.', [
        'summary' => [
            'today_customers' => (int)$todayStats['total_customers'],
            'today_gross_revenue' => $todayRevenue,
            'today_commission_payout' => $todayCommission,
            'today_net_owner' => $todayNetOwner,
            'today_discount' => (float)$todayStats['total_discount']
        ],
        'barber_performances' => $barberPerformances,
        'charts' => [
            'daily' => $dailyData,
            'monthly' => $monthlyData,
            'yearly' => $yearlyData
        ]
    ]);

} catch (Exception $e) {
    sendJsonResponse(false, 'Gagal memuat dashboard: ' . $e->getMessage(), null, 500);
}
