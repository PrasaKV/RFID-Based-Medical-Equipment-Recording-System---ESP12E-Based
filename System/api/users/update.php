<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$id       = intval($data['user_id'] ?? 0);
$username = trim($data['username'] ?? '');
$email    = trim($data['email']    ?? '');
$password = $data['password']      ?? '';
$role     = trim($data['role']     ?? '');

if (!$id || !$username || !$email || !$password || !$role) {
    echo json_encode(['error'=>'user_id, username, email, password, and role are required.']); exit();
}

try {
    $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE user_id = ?');
    $stmt->execute([$username, $email, $password, $role, $id]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'Username or email already in use.' : $e->getMessage();
    echo json_encode(['error'=>$msg]);
}
