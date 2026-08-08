<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

requireCanDelete(); // Admin only

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id = intval($data['part_replacement_id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing part_replacement_id"]);
    exit();
}

try {
    $db->prepare('DELETE FROM part_replacements WHERE part_replacement_id = ?')->execute([$id]);
    echo json_encode(["success" => true, "message" => "Part deleted successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
