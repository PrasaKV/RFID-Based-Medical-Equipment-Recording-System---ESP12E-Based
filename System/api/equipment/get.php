<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing id']); exit(); }

$stmt = $db->prepare('SELECT * FROM equipment WHERE equipment_id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { echo json_encode(['error'=>'Not found']); exit(); }
echo json_encode($row);
