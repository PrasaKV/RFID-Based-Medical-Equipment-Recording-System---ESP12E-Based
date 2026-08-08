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
$id = intval($data['repair_id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing repair_id"]);
    exit();
}

try {
    // Unlink parts that reference this repair
    $db->prepare('UPDATE part_replacements SET repair_id = NULL WHERE repair_id = ?')->execute([$id]);
    
    // Delete the repair log
    $db->prepare('DELETE FROM repair_logs WHERE repair_id = ?')->execute([$id]);
    
    echo json_encode(["success" => true, "message" => "Repair record deleted successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
