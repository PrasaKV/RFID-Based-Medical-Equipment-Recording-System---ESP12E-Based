<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$name        = trim($data['name']               ?? '');
$rfid        = trim($data['rfid_tag_id']        ?? '');
$serial      = trim($data['serial_number']      ?? '');
$model       = trim($data['model']              ?? '');
$description = trim($data['description']        ?? '');
$category    = trim($data['category']           ?? 'Other');
$status      = trim($data['status']             ?? 'OPERATIONAL');
$location    = trim($data['current_location']   ?? '');
$pmInterval  = intval($data['pm_interval_days'] ?? 90);
$lastPm      = $data['last_maintenance_date']   ?? null;
$procDate    = $data['procurement_date']        ?? null;
$notes       = trim($data['notes']              ?? '');

if (!$name) { echo json_encode(['error'=>'Device name is required.']); exit(); }

// Compute next_pm_date if last maintenance date is known
$nextPm = null;
if ($lastPm && $pmInterval > 0) {
    $nextPm = (new DateTime($lastPm))->modify("+{$pmInterval} days")->format('Y-m-d');
}

try {
    $stmt = $db->prepare(
        'INSERT INTO equipment
           (rfid_tag_id, name, serial_number, model, description, category, status,
            current_location, pm_interval_days, last_maintenance_date,
            next_pm_date, procurement_date, notes)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $rfid ?: null, $name, $serial ?: null, $model ?: null, $description ?: null,
        $category, $status, $location ?: null, $pmInterval,
        $lastPm ?: null, $nextPm, $procDate ?: null, $notes ?: null,
    ]);
    $id = $db->lastInsertId();
    echo json_encode(['success'=>true, 'equipment_id'=>$id]);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'RFID Tag ID already registered.' : $e->getMessage();
    echo json_encode(['error'=>$msg]);
}
