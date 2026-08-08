<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$equipmentId = intval($data['equipment_id'] ?? 0);
$type        = $data['type']         ?? 'PM';
$serviceDate = $data['service_date'] ?? date('Y-m-d');
$technician  = trim($data['technician'] ?? '');
$remarks     = trim($data['remarks']    ?? '');
$nextPm      = $data['next_pm_date']  ?? null;

if (!$equipmentId) { echo json_encode(['error'=>'Missing equipment_id']); exit(); }

try {
    // Insert maintenance log
    $db->prepare(
        'INSERT INTO maintenance_logs (equipment_id, type, service_date, technician, remarks, next_pm_date)
         VALUES (?,?,?,?,?,?)'
    )->execute([$equipmentId, $type, $serviceDate, $technician ?: null, $remarks ?: null, $nextPm ?: null]);

    $logId = $db->lastInsertId();

    // Update equipment status and maintenance dates
    $newStatus = ($type === 'REPAIR') ? 'OPERATIONAL' : 'OPERATIONAL';
    $updates   = [
        'last_maintenance_date = ?', 'status = ?', 'updated_at = NOW()',
    ];
    $params = [$serviceDate, $newStatus];

    if ($nextPm) {
        $updates[] = 'next_pm_date = ?';
        $params[]  = $nextPm;
    }
    $params[] = $equipmentId;

    $db->prepare('UPDATE equipment SET ' . implode(', ', $updates) . ' WHERE equipment_id = ?')
       ->execute($params);

    echo json_encode(['success'=>true, 'maintenance_id'=>$logId]);
} catch (PDOException $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
