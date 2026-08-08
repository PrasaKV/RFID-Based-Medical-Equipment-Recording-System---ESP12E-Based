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

$id = intval($data['part_replacement_id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing part_replacement_id"]);
    exit();
}

$fields = [];
$params = [];

$updatable = ['department', 'part_serial_number', 'part_name', 'description', 'replacement_date', 'notes', 'repair_id', 'ticket_id', 'equipment_id'];

foreach ($updatable as $f) {
    if (isset($data[$f])) {
        $fields[] = "$f = ?";
        $val = $data[$f];
        if ($val === '' && in_array($f, ['repair_id', 'ticket_id', 'equipment_id'])) {
            $params[] = null;
        } else {
            $params[] = $val === '' ? null : $val;
        }
    }
}

if (empty($fields)) {
    echo json_encode(["error" => "Nothing to update"]);
    exit();
}

$params[] = $id;

try {
    $sql = 'UPDATE part_replacements SET ' . implode(', ', $fields) . ' WHERE part_replacement_id = ?';
    $db->prepare($sql)->execute($params);
    echo json_encode(["success" => true, "message" => "Part updated successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
