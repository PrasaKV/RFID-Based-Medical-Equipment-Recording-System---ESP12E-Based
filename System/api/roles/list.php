<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$stmt = $db->query('SELECT * FROM roles ORDER BY role_id ASC');
echo json_encode($stmt->fetchAll());
