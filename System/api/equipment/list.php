<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status']   ?? '');
$location = trim($_GET['location'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(name LIKE ? OR serial_number LIKE ? OR rfid_tag_id LIKE ? OR model LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($category) { $where[] = 'category = ?'; $params[] = $category; }
if ($status)   { $where[] = 'status = ?';   $params[] = $status;   }
if ($location) { $where[] = 'current_location LIKE ?'; $params[] = "%$location%"; }

$sql  = 'SELECT * FROM equipment WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo json_encode(['equipment' => $rows, 'count' => count($rows)]);
