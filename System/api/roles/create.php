<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$roleName    = strtolower(trim($data['role_name'] ?? ''));
$description = trim($data['description'] ?? '');

if (!$roleName) {
    echo json_encode(['error'=>'Role name is required.']); exit();
}

try {
    $stmt = $db->prepare('INSERT INTO roles (role_name, description) VALUES (?,?)');
    $stmt->execute([$roleName, $description ?: null]);
    echo json_encode(['success'=>true, 'role_id'=>$db->lastInsertId(), 'role_name'=>$roleName]);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'Role already exists.' : $e->getMessage();
    echo json_encode(['error'=>$msg]);
}
