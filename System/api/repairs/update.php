<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

requireCanEdit(); // Admin and engineer only

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

$fields = [];
$params = [];

$updatable = ['machine_name', 'serial_number', 'department', 'brought_by', 'intake_date', 'machine_problem', 'received_by', 'delivery_department', 'delivery_remarks', 'return_date'];

foreach ($updatable as $f) {
    if (isset($data[$f])) {
        $fields[] = "$f = ?";
        $params[] = $data[$f] === '' ? null : $data[$f];
    }
}

if (empty($fields)) {
    echo json_encode(["error" => "Nothing to update"]);
    exit();
}

$fields[] = 'updated_at = NOW()';
$params[] = $id;

try {
    $sql = 'UPDATE repair_logs SET ' . implode(', ', $fields) . ' WHERE repair_id = ?';
    $db->prepare($sql)->execute($params);
    echo json_encode(["success" => true, "message" => "Repair record updated successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
