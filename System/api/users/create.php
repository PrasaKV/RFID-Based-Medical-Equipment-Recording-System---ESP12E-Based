<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw      = file_get_contents('php://input');
$data     = json_decode($raw, true) ?? $_POST;
$username = trim($data['username'] ?? '');
$email    = trim($data['email']    ?? '');
$password = $data['password']      ?? '';
$role     = $data['role']          ?? 'engineer';

if (!$username || !$password || !$email) {
    echo json_encode(['error'=>'username, email, and password are required.']); exit();
}

try {
    $db->prepare('INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)')
       ->execute([$username, $email, $password, $role]);
    echo json_encode(['success'=>true, 'user_id'=>$db->lastInsertId()]);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(),'Duplicate') ? 'Username or email already exists.' : $e->getMessage();
    echo json_encode(['error'=>$msg]);
}
