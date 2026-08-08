<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }
$stmt = $db->query('SELECT user_id, username, email, password, role, created_at FROM users ORDER BY created_at DESC');
echo json_encode($stmt->fetchAll());
