<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;
$id   = intval($data['role_id'] ?? 0);
$name = strtolower(trim($data['role_name'] ?? ''));

if (in_array($name, ['admin', 'engineer'])) {
    echo json_encode(['error'=>'System default roles (admin, engineer) cannot be deleted.']); exit();
}

try {
    if ($id > 0) {
        $db->prepare('DELETE FROM roles WHERE role_id = ? AND role_name NOT IN (\'admin\',\'engineer\')')->execute([$id]);
    } else if ($name !== '') {
        $db->prepare('DELETE FROM roles WHERE role_name = ? AND role_name NOT IN (\'admin\',\'engineer\')')->execute([$name]);
    }
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
