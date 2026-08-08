<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();

$id = intval($_GET['repair_id'] ?? $_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing repair_id"]);
    exit();
}

try {
    $stmt = $db->prepare('SELECT r.*, e.rfid_tag_id, e.name as equip_name
                          FROM repair_logs r
                          LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
                          WHERE r.repair_id = ?');
    $stmt->execute([$id]);
    $repair = $stmt->fetch();

    if (!$repair) {
        http_response_code(404);
        echo json_encode(["error" => "Repair record not found"]);
        exit();
    }

    // Get linked parts
    $partStmt = $db->prepare('SELECT p.*, it.issue_description 
                               FROM part_replacements p 
                               LEFT JOIN issues_tickets it ON p.ticket_id = it.ticket_id
                               WHERE p.repair_id = ? ORDER BY p.replacement_date DESC');
    $partStmt->execute([$id]);
    $repair['parts'] = $partStmt->fetchAll();

    echo json_encode($repair);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
