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

$search       = trim($_GET['search'] ?? '');
$department   = trim($_GET['department'] ?? '');
$equipment_id = intval($_GET['equipment_id'] ?? 0);
$ticket_id    = intval($_GET['ticket_id'] ?? 0);

$sql = 'SELECT p.*, 
               r.machine_name as repair_machine, r.serial_number as repair_serial,
               e.name as equip_name, e.serial_number as equip_serial,
               it.issue_description as issue_description, it.ticket_id as linked_ticket_id
        FROM part_replacements p
        LEFT JOIN repair_logs r ON p.repair_id = r.repair_id
        LEFT JOIN equipment e ON p.equipment_id = e.equipment_id
        LEFT JOIN issues_tickets it ON p.ticket_id = it.ticket_id
        WHERE 1=1';
$params = [];

if ($equipment_id) {
    $sql .= ' AND p.equipment_id = ?';
    $params[] = $equipment_id;
}

if ($ticket_id) {
    $sql .= ' AND p.ticket_id = ?';
    $params[] = $ticket_id;
}

if ($department !== '') {
    $sql .= ' AND p.department = ?';
    $params[] = $department;
}

if ($search !== '') {
    $sql .= ' AND (p.department LIKE ? OR p.part_serial_number LIKE ? OR p.part_name LIKE ? OR p.description LIKE ? OR r.machine_name LIKE ? OR e.name LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
}

$sql .= ' ORDER BY p.part_replacement_id DESC';

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $parts = $stmt->fetchAll();
    echo json_encode($parts);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
