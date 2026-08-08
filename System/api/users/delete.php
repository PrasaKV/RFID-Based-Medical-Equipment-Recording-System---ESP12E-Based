<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;
$id   = intval($data['user_id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing user_id']); exit(); }

try {
    $db->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
