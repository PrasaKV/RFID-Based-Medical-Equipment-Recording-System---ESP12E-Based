<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

requireAdmin(); // Admin only

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$db = getDB();
if (!$db) { echo json_encode(['error' => 'DB unavailable']); exit(); }

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$name = trim($data['name'] ?? '');

if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Category name is required.']);
    exit();
}

try {
    $stmt = $db->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->execute([$name]);
    $id = $db->lastInsertId();
    echo json_encode(['success' => true, 'category_id' => intval($id), 'message' => 'Category added successfully.']);
} catch (PDOException $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'Category already exists.' : $e->getMessage();
    http_response_code(400);
    echo json_encode(['error' => $msg]);
}
