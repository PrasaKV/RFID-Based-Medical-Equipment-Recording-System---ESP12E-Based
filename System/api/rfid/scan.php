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

$raw = file_get_contents('php://input');
if ($raw !== false && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (empty($payload)) {
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
        $rawTag = $payload['tag_id'] ?? $payload['cardId'] ?? $payload['uid'] ?? $payload['rfid'] ?? null;
        $tagId  = $rawTag !== null ? trim((string)$rawTag) : null;
        $mode   = strtolower((string)($payload['mode'] ?? 'scan'));

        if ($tagId !== null && $tagId !== '') {
            // Look up equipment by RFID tag
            $stmt = $db->prepare(
                'SELECT equipment_id, name, serial_number, status, current_location, pm_interval_days, next_pm_date
                 FROM equipment WHERE rfid_tag_id = ? LIMIT 1'
            );
            $stmt->execute([$tagId]);
            $equipment = $stmt->fetch();

            if ($equipment) {
                $equipmentInfo = $equipment;

                // Reader information sent in JSON
                $sentLocation = trim((string)($payload['location'] ?? $payload['dept'] ?? $payload['department'] ?? ''));
                $readerId     = trim((string)($payload['reader_id'] ?? $payload['device'] ?? $payload['reader'] ?? ($event['ip'] ?? 'READER')));
                $effectiveLocation = $sentLocation !== '' ? $sentLocation : ($equipment['current_location'] ?? 'Department Scanner');

                if ($mode === 'scan') {
                    // 1. Tag equipment with the scanned reader location
                    if ($sentLocation !== '') {
                        $updateLoc = $db->prepare('UPDATE equipment SET current_location = ?, updated_at = NOW() WHERE equipment_id = ?');
                        $updateLoc->execute([$sentLocation, $equipment['equipment_id']]);
                        $equipment['current_location'] = $sentLocation;
                    }

                    // 2. Record this movement event in location history
                    $db->prepare(
                        'INSERT INTO location_logs (equipment_id, reader_id, location) VALUES (?,?,?)'
                    )->execute([$equipment['equipment_id'], $readerId, $effectiveLocation]);

                    // 3. Auto-flag PM_DUE if overdue
                    if ($equipment['next_pm_date']) {
                        $threshold = (new DateTime())->modify('+7 days')->format('Y-m-d');
                        if ($equipment['next_pm_date'] <= $threshold && $equipment['status'] === 'OPERATIONAL') {
                            $db->prepare("UPDATE equipment SET status='PM_DUE', updated_at=NOW() WHERE equipment_id=?")
                               ->execute([$equipment['equipment_id']]);
                            $equipment['status'] = 'PM_DUE';
                        }
                    }

                    $equipmentInfo = $equipment;
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
