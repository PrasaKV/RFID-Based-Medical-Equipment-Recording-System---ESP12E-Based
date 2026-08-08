<?php
// ─── CORS: Disable restrictions for local IoT devices ───────────────────────
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ─── Only accept POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit();
}

header("Content-Type: application/json");

// ─── Parse incoming payload (JSON or form-data) ──────────────────────────────
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = [];

if (str_contains($contentType, 'application/json')) {
    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?? [];
} else {
    $payload = $_POST;
}

// ─── Append to the shared event log (flat JSON array file) ───────────────────
$logFile = __DIR__ . '/../../data/rfid_events.json';

if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

$events = [];
if (file_exists($logFile)) {
    $raw    = file_get_contents($logFile);
    $events = json_decode($raw, true) ?? [];
}

$event = [
    "id"        => uniqid('evt_', true),
    "timestamp" => date('c'),
    "ip"        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    "data"      => $payload,
];

array_unshift($events, $event);
$events = array_slice($events, 0, 200);
file_put_contents($logFile, json_encode($events, JSON_PRETTY_PRINT));

// ─── MySQL enrichment (graceful fallback if DB unavailable) ──────────────────
$equipmentInfo = null;
$dbError       = null;

try {
    require_once __DIR__ . '/../../includes/db.php';
    $db = getDB();

    if ($db) {
        // Normalise tag ID field — IoT devices may send tag_id, cardId, uid, rfid, etc.
        $tagId = $payload['tag_id'] ?? $payload['cardId'] ?? $payload['uid'] ?? $payload['rfid'] ?? null;
        $mode  = strtolower($payload['mode'] ?? 'scan');

        if ($tagId) {
            // Look up equipment by RFID tag
            $stmt = $db->prepare(
                'SELECT equipment_id, name, status, current_location, pm_interval_days, next_pm_date
                 FROM equipment WHERE rfid_tag_id = ? LIMIT 1'
            );
            $stmt->execute([$tagId]);
            $equipment = $stmt->fetch();

            if ($equipment && $mode === 'scan') {
                $equipmentInfo = $equipment;

                // Log the location event using equipment's recorded department
                $readerId = $payload['reader_id'] ?? $payload['device'] ?? $event['ip'];
                $location = $equipment['current_location'] ?? 'Department Scanner';

                $db->prepare(
                    'INSERT INTO location_logs (equipment_id, reader_id, location) VALUES (?,?,?)'
                )->execute([$equipment['equipment_id'], $readerId, $location]);

                // Auto-flag PM_DUE if overdue
                if ($equipment['next_pm_date']) {
                    $threshold = (new DateTime())->modify('+7 days')->format('Y-m-d');
                    if ($equipment['next_pm_date'] <= $threshold && $equipment['status'] === 'OPERATIONAL') {
                        $db->prepare("UPDATE equipment SET status='PM_DUE', updated_at=NOW() WHERE equipment_id=?")
                           ->execute([$equipment['equipment_id']]);
                        $equipmentInfo['status'] = 'PM_DUE';
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

// ─── Respond ─────────────────────────────────────────────────────────────────
http_response_code(200);
echo json_encode([
    "success"   => true,
    "event_id"  => $event['id'],
    "received"  => $event['data'],
    "timestamp" => $event['timestamp'],
    "equipment" => $equipmentInfo,
    "db_error"  => $dbError, // null in normal operation
]);
