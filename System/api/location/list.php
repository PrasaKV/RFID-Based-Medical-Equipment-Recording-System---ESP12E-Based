<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$id = intval($_GET['equipment_id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing equipment_id']); exit(); }

$stmt = $db->prepare(
    'SELECT * FROM location_logs WHERE equipment_id = ? ORDER BY scanned_at DESC LIMIT 100'
);
$stmt->execute([$id]);
echo json_encode($stmt->fetchAll());
