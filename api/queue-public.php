<?php
// api/queue-public.php - Public read-only queue endpoint (no auth required)
// Used by the display screen visible to customers
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $db   = Database::getConnection();
    $stmt = $db->prepare("
        SELECT q.id, q.queue_number, q.customer_name, q.status,
               q.call_event_id, q.updated_at,
               b.name as barber_name, s.name as service_name
        FROM queue q
        LEFT JOIN barbers b ON q.barber_id = b.id
        LEFT JOIN services s ON q.service_id = s.id
        WHERE DATE(q.created_at) = CURDATE()
        ORDER BY q.queue_number ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $waiting = count(array_filter($rows, fn($r) => $r["status"] === "waiting"));
    $serving = count(array_filter($rows, fn($r) => $r["status"] === "serving"));
    $done    = count(array_filter($rows, fn($r) => $r["status"] === "done"));
    $skipped = count(array_filter($rows, fn($r) => $r["status"] === "skipped"));

    echo json_encode([
        "success" => true,
        "message" => "OK",
        "data"    => [
            "queue" => $rows,
            "stats" => compact("waiting", "serving", "done", "skipped"),
        ],
        "timestamp" => date("Y-m-d H:i:s")
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
