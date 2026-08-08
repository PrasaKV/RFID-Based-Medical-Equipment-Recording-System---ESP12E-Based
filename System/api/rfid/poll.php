<?php
// ── RFID Poll Endpoint ─────────────────────────────────────────────────────
// Returns recent scan events enriched with equipment data from MySQL
// Frontend polls this every 2s to drive the global popup system

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../includes/db.php';

$logFile = __DIR__ . '/../../data/rfid_events.json';
$events  = [];

if (file_exists($logFile)) {
    $raw    = file_get_contents($logFile);
    $events = json_decode($raw, true) ?? [];
}

// Take latest 20 events
$events = array_slice($events, 0, 20);

$db = getDB();

// Enrich each event with equipment data
foreach ($events as &$event) {
    $data  = $event['data'] ?? [];
    $tagId = $data['tag_id'] ?? $data['cardId'] ?? $data['uid'] ?? null;
    $mode  = $data['mode']   ?? 'scan';

    $event['mode']   = $mode;
    $event['tag_id'] = $tagId;

    if ($db && $tagId) {
        try {
            $stmt = $db->prepare(
                'SELECT equipment_id, name, serial_number, status, current_location FROM equipment WHERE rfid_tag_id = ? LIMIT 1'
            );
            $stmt->execute([$tagId]);
            $eq = $stmt->fetch();

            if ($eq) {
                $event['equipment_id']       = $eq['equipment_id'];
                $event['equipment_name']     = $eq['name'];
                $event['equipment_serial']   = $eq['serial_number'];
                $event['equipment_status']   = $eq['status'];
                $event['equipment_location'] = $eq['current_location'];
            } else {
                $event['equipment_id']   = null;
                $event['equipment_name'] = null;
            }
        } catch (PDOException $e) {
            $event['equipment_id']   = null;
            $event['equipment_name'] = null;
        }
    } else {
        $event['equipment_id']   = null;
        $event['equipment_name'] = null;
    }
}
unset($event);

echo json_encode(['events' => $events]);
