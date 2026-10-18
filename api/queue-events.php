<?php
// api/queue-events.php - Server-Sent Events endpoint for real-time queue updates
require_once __DIR__ . "/../config/database.php";

// SSE headers
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");
header("X-Accel-Buffering: no");
header("Access-Control-Allow-Origin: *");

if (ob_get_level()) ob_end_clean();
ini_set("output_buffering", "off");
ini_set("zlib.output_compression", false);

$db = Database::getConnection();

function sendSSE(string $event, array $data): void {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Read current max call_event_id to avoid firing stale events on first connect
$initStmt = $db->prepare("SELECT COALESCE(MAX(call_event_id), 0) FROM queue WHERE DATE(created_at) = CURDATE()");
$initStmt->execute();
$lastCallId = (int)$initStmt->fetchColumn();

sendSSE("connected", ["status" => "ok", "ts" => time()]);

$lastHash   = "";
$maxLoops   = 600;
$loop       = 0;

while (!connection_aborted() && $loop < $maxLoops) {
    $loop++;
    try {
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
        $queue = $stmt->fetchAll();

        $hash = md5(json_encode(array_map(fn($r) => [$r["id"], $r["status"], $r["call_event_id"], $r["updated_at"]], $queue)));

        $maxCallId = 0; $calledItem = null;
        foreach ($queue as $item) {
            if ((int)$item["call_event_id"] > $maxCallId) {
                $maxCallId  = (int)$item["call_event_id"];
                $calledItem = $item;
            }
        }

        if ($hash !== $lastHash) {
            $lastHash = $hash;
            $waiting  = count(array_filter($queue, fn($r) => $r["status"] === "waiting"));
            $serving  = count(array_filter($queue, fn($r) => $r["status"] === "serving"));
            $done     = count(array_filter($queue, fn($r) => $r["status"] === "done"));
            $skipped  = count(array_filter($queue, fn($r) => $r["status"] === "skipped"));
            sendSSE("queue_update", ["queue" => $queue, "stats" => compact("waiting","serving","done","skipped"), "ts" => time()]);
        }

        if ($maxCallId > $lastCallId && $calledItem) {
            $lastCallId = $maxCallId;
            sendSSE("queue_called", [
                "queue_number"  => $calledItem["queue_number"],
                "customer_name" => $calledItem["customer_name"],
                "barber_name"   => $calledItem["barber_name"] ?? "Kapster",
                "service_name"  => $calledItem["service_name"] ?? "",
                "call_event_id" => $maxCallId,
                "ts"            => time()
            ]);
        }
    } catch (Exception $e) {
        sendSSE("error", ["message" => $e->getMessage()]);
        break;
    }
    sleep(1);
}
sendSSE("close", ["reason" => "timeout"]);
