<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$id = intval($data['equipment_id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing equipment_id']); exit(); }

$fields  = ['name','rfid_tag_id','serial_number','model','description','category','status',
            'current_location','pm_interval_days','last_maintenance_date',
            'next_pm_date','procurement_date','notes'];
$sets    = [];
$params  = [];

foreach ($fields as $f) {
    if (isset($data[$f])) {
        $sets[]   = "$f = ?";
        $params[] = $data[$f] === '' ? null : $data[$f];
    }
}

if (empty($sets)) { echo json_encode(['error'=>'Nothing to update']); exit(); }

// Recompute next_pm_date if pm_interval_days or last_maintenance_date changed
if (isset($data['last_maintenance_date']) && isset($data['pm_interval_days'])) {
    $lm = $data['last_maintenance_date'];
    $pi = intval($data['pm_interval_days']);
    if ($lm && $pi > 0) {
        $np = (new DateTime($lm))->modify("+{$pi} days")->format('Y-m-d');
        $sets[]   = "next_pm_date = ?";
        $params[] = $np;
    }
}

$params[] = $id;
try {
    $db->prepare('UPDATE equipment SET ' . implode(', ', $sets) . ' WHERE equipment_id = ?')
       ->execute($params);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'RFID Tag ID already in use.' : $e->getMessage();
    echo json_encode(['error'=>$msg]);
}
