<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;
$id   = intval($data['equipment_id'] ?? $_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing equipment_id']); exit(); }

try {
    $db->prepare('DELETE FROM equipment WHERE equipment_id = ?')->execute([$id]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
