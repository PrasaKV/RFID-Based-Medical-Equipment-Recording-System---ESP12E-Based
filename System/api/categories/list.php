<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$db = getDB();
if (!$db) { echo json_encode(['error' => 'DB unavailable']); exit(); }

try {
    $stmt = $db->query('SELECT * FROM categories ORDER BY name ASC');
    $rows = $stmt->fetchAll();
    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
