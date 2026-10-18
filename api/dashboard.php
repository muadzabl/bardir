<?php
// api/dashboard.php - Multi-Period Analytics & Live Tracker
// Role-based: Owner/Kasir = full stats | Kapster = personal performance only
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db          = Database::getConnection();
    $user        = requireAuth();
    $today       = date('Y-m-d');
    $currentYear = date('Y');
    $isKapster   = ($user['role'] === 'kapster');

    // MODE KAPSTER: hanya data pribadi (F-KAP-01)
    if ($isKapster) {
        $barberId = isset($user['barber_id']) ? (int)$user['barber_id'] : 0;
        if (!$barberId) {
            sendJsonResponse(false, 'Akun kapster belum terhubung ke data barber. Hubungi owner.', null, 403);
        }

        $stmtToday = $db->prepare("
            SELECT COUNT(td.id) as service_count,
                   COALESCE(SUM(td.barber_commission_amount), 0) as my_commission,
                   COALESCE(SUM(td.service_price), 0) as my_gross
            FROM transaction_details td
            JOIN transactions t ON td.transaction_id = t.id
            WHERE td.barber_id = ? AND DATE(t.created_at) = ?
        ");
        $stmtToday->execute([$barberId, $today]);
        $myToday = $stmtToday->fetch();

        $stmtDaily = $db->prepare("
            SELECT DATE(t.created_at) as period_key,
                   DATE_FORMAT(t.created_at, '%d %b') as label,
                   COUNT(td.id) as service_count,
                   COALESCE(SUM(td.barber_commission_amount), 0) as my_commission
            FROM transaction_details td
            JOIN transactions t ON td.transaction_id = t.id
            WHERE td.barber_id = ? AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
            GROUP BY DATE(t.created_at), DATE_FORMAT(t.created_at, '%d %b')
            ORDER BY period_key ASC
        ");
        $stmtDaily->execute([$barberId]);
        $dailyData = $stmtDaily->fetchAll();

        $stmtMonthly = $db->prepare("
            SELECT DATE_FORMAT(t.created_at, '%Y-%m') as period_key,
                   DATE_FORMAT(t.created_at, '%b %Y') as label,
                   COUNT(td.id) as service_count,
                   COALESCE(SUM(td.barber_commission_amount), 0) as my_commission
            FROM transaction_details td
            JOIN transactions t ON td.transaction_id = t.id
            WHERE td.barber_id = ? AND YEAR(t.created_at) = ?
            GROUP BY DATE_FORMAT(t.created_at, '%Y-%m'), DATE_FORMAT(t.created_at, '%b %Y')
            ORDER BY period_key ASC
        ");
        $stmtMonthly->execute([$barberId, $currentYear]);
        $monthlyData = $stmtMonthly->fetchAll();

        $stmtYearly = $db->prepare("
            SELECT CAST(YEAR(t.created_at) AS CHAR) as period_key,
                   CAST(YEAR(t.created_at) AS CHAR) as label,
                   COUNT(td.id) as service_count,
                   COALESCE(SUM(td.barber_commission_amount), 0) as my_commission
            FROM transaction_details td
            JOIN transactions t ON td.transaction_id = t.id
            WHERE td.barber_id = ?
            GROUP BY YEAR(t.created_at), CAST(YEAR(t.created_at) AS CHAR)
            ORDER BY period_key ASC
        ");
        $stmtYearly->execute([$barberId]);
        $yearlyData = $stmtYearly->fetchAll();

        $stmtQueue = $db->prepare("
            SELECT q.*, s.name as service_name
            FROM queue q
            LEFT JOIN services s ON q.service_id = s.id
            WHERE q.barber_id = ? AND DATE(q.created_at) = ?
            ORDER BY q.queue_number ASC
        ");
        $stmtQueue->execute([$barberId, $today]);
        $myQueue = $stmtQueue->fetchAll();

        sendJsonResponse(true, 'Data kapster berhasil dimuat.', [
            'mode'    => 'kapster',
            'summary' => [
                'today_service_count' => (int)$myToday['service_count'],
                'today_my_commission' => (float)$myToday['my_commission'],
                'today_my_gross'      => (float)$myToday['my_gross'],
            ],
            'my_queue' => $myQueue,
            'charts'   => ['daily' => $dailyData, 'monthly' => $monthlyData, 'yearly' => $yearlyData]
        ]);
    }

    // MODE OWNER / KASIR: full business stats
    $stmtToday = $db->prepare("
        SELECT COUNT(id) as total_customers,
               COALESCE(SUM(total_amount), 0) as total_revenue,
               COALESCE(SUM(discount_amount), 0) as total_discount
        FROM transactions WHERE DATE(created_at) = ?
    ");
    $stmtToday->execute([$today]);
    $todayStats = $stmtToday->fetch();

    $stmtCommission = $db->prepare("
        SELECT COALESCE(SUM(td.barber_commission_amount), 0) as total_barber_payout
        FROM transaction_details td
        JOIN transactions t ON td.transaction_id = t.id
        WHERE DATE(t.created_at) = ?
    ");
    $stmtCommission->execute([$today]);
    $commissionStats = $stmtCommission->fetch();

    $todayRevenue    = (float)$todayStats['total_revenue'];
    $todayCommission = (float)$commissionStats['total_barber_payout'];
    $todayNetOwner   = $todayRevenue - $todayCommission;

    $stmtBarberPerf = $db->prepare("
        SELECT b.id, b.name,
               COUNT(td.id) as service_count,
               COALESCE(SUM(td.barber_commission_amount), 0) as total_commission,
               COALESCE(SUM(td.service_price), 0) as gross_revenue
        FROM barbers b
        LEFT JOIN (SELECT td2.* FROM transaction_details td2
                   JOIN transactions t2 ON td2.transaction_id = t2.id
                   WHERE DATE(t2.created_at) = ?) td ON b.id = td.barber_id
        WHERE b.status = 'active'
        GROUP BY b.id, b.name
        ORDER BY service_count DESC, total_commission DESC
    ");
    $stmtBarberPerf->execute([$today]);
    $barberPerformances = $stmtBarberPerf->fetchAll();

    $stmtDaily = $db->query("
        SELECT DATE(t.created_at) as period_key,
               DATE_FORMAT(t.created_at, '%d %b') as label,
               COUNT(t.id) as total_customers,
               COALESCE(SUM(t.total_amount), 0) as gross_revenue,
               COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
                   FROM transaction_details GROUP BY transaction_id) comm ON t.id = comm.transaction_id
        WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(t.created_at), DATE_FORMAT(t.created_at, '%d %b')
        ORDER BY period_key ASC
    ");
    $dailyRaw  = $stmtDaily->fetchAll();
    $dailyData = array_map(fn($r) => array_merge($r, ['net_owner' => (float)$r['gross_revenue'] - (float)$r['total_commission']]), $dailyRaw);

    $stmtMonthly = $db->prepare("
        SELECT DATE_FORMAT(t.created_at, '%Y-%m') as period_key,
               DATE_FORMAT(t.created_at, '%b %Y') as label,
               COUNT(t.id) as total_customers,
               COALESCE(SUM(t.total_amount), 0) as gross_revenue,
               COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
                   FROM transaction_details GROUP BY transaction_id) comm ON t.id = comm.transaction_id
        WHERE YEAR(t.created_at) = ?
        GROUP BY DATE_FORMAT(t.created_at, '%Y-%m'), DATE_FORMAT(t.created_at, '%b %Y')
        ORDER BY period_key ASC
    ");
    $stmtMonthly->execute([$currentYear]);
    $monthlyRaw  = $stmtMonthly->fetchAll();
    $monthlyData = array_map(fn($r) => array_merge($r, ['net_owner' => (float)$r['gross_revenue'] - (float)$r['total_commission']]), $monthlyRaw);

    $stmtYearly = $db->query("
        SELECT CAST(YEAR(t.created_at) AS CHAR) as period_key,
               CAST(YEAR(t.created_at) AS CHAR) as label,
               COUNT(t.id) as total_customers,
               COALESCE(SUM(t.total_amount), 0) as gross_revenue,
               COALESCE(SUM(comm.daily_comm), 0) as total_commission
        FROM transactions t
        LEFT JOIN (SELECT transaction_id, SUM(barber_commission_amount) as daily_comm
                   FROM transaction_details GROUP BY transaction_id) comm ON t.id = comm.transaction_id
        GROUP BY YEAR(t.created_at), CAST(YEAR(t.created_at) AS CHAR)
        ORDER BY period_key ASC
    ");
    $yearlyRaw  = $stmtYearly->fetchAll();
    $yearlyData = array_map(fn($r) => array_merge($r, ['net_owner' => (float)$r['gross_revenue'] - (float)$r['total_commission']]), $yearlyRaw);

    sendJsonResponse(true, 'Data dashboard berhasil dimuat.', [
        'mode'    => 'full',
        'summary' => [
            'today_customers'         => (int)$todayStats['total_customers'],
            'today_gross_revenue'     => $todayRevenue,
            'today_commission_payout' => $todayCommission,
            'today_net_owner'         => $todayNetOwner,
            'today_discount'          => (float)$todayStats['total_discount']
        ],
        'barber_performances' => $barberPerformances,
        'charts' => ['daily' => $dailyData, 'monthly' => $monthlyData, 'yearly' => $yearlyData]
    ]);

} catch (Exception $e) {
    sendJsonResponse(false, 'Gagal memuat dashboard: ' . $e->getMessage(), null, 500);
}
