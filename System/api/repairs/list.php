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

$status       = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');
$equipment_id = intval($_GET['equipment_id'] ?? 0);

$sql = 'SELECT r.*, e.rfid_tag_id 
        FROM repair_logs r
        LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
        WHERE 1=1';
$params = [];

if ($equipment_id) {
    $sql .= ' AND r.equipment_id = ?';
    $params[] = $equipment_id;
}

if ($status !== '') {
    $sql .= ' AND r.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $sql .= ' AND (r.machine_name LIKE ? OR r.serial_number LIKE ? OR r.department LIKE ? OR r.brought_by LIKE ? OR r.received_by LIKE ? OR r.machine_problem LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
}

$sql .= ' ORDER BY r.repair_id DESC';

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $repairs = $stmt->fetchAll();
    echo json_encode($repairs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
