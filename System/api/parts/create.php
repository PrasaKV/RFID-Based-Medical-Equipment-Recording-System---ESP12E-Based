<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$department         = trim($data['department'] ?? '');
$part_serial_number = trim($data['part_serial_number'] ?? '');
$part_name          = trim($data['part_name'] ?? '');
$description        = trim($data['description'] ?? '');
$replacement_date   = trim($data['replacement_date'] ?? date('Y-m-d'));
$repair_id          = !empty($data['repair_id']) ? intval($data['repair_id']) : null;
$ticket_id          = !empty($data['ticket_id']) ? intval($data['ticket_id']) : null;
$equipment_id       = !empty($data['equipment_id']) ? intval($data['equipment_id']) : null;
$notes              = trim($data['notes'] ?? '');

if (!$part_serial_number || !$part_name) {
    http_response_code(400);
    echo json_encode(["error" => "Part serial number and part name are required."]);
    exit();
}

try {
    // If equipment_id not provided but repair_id is, try to auto-link
    if (!$equipment_id && $repair_id) {
        $eqStmt = $db->prepare('SELECT equipment_id FROM repair_logs WHERE repair_id = ? LIMIT 1');
        $eqStmt->execute([$repair_id]);
        $eqRow = $eqStmt->fetch();
        if ($eqRow && $eqRow['equipment_id']) {
            $equipment_id = intval($eqRow['equipment_id']);
        }
    }

    // If equipment_id not provided but ticket_id is, try to auto-link
    if (!$equipment_id && $ticket_id) {
        $eqStmt = $db->prepare('SELECT equipment_id FROM issues_tickets WHERE ticket_id = ? LIMIT 1');
        $eqStmt->execute([$ticket_id]);
        $eqRow = $eqStmt->fetch();
        if ($eqRow && $eqRow['equipment_id']) {
            $equipment_id = intval($eqRow['equipment_id']);
        }
    }

    $stmt = $db->prepare('INSERT INTO part_replacements (repair_id, ticket_id, equipment_id, department, part_serial_number, part_name, description, replacement_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$repair_id, $ticket_id, $equipment_id, $department, $part_serial_number, $part_name, $description ?: null, $replacement_date, $notes ?: null]);
    $id = $db->lastInsertId();

    echo json_encode([
        "success" => true,
        "part_replacement_id" => intval($id),
        "message" => "Part replacement logged successfully."
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
