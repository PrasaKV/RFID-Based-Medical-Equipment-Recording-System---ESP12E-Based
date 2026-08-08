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

$machine_name    = trim($data['machine_name'] ?? '');
$serial_number   = trim($data['serial_number'] ?? '');
$department      = trim($data['department'] ?? '');
$brought_by      = trim($data['brought_by'] ?? '');
$intake_date     = trim($data['intake_date'] ?? date('Y-m-d'));
$machine_problem = trim($data['machine_problem'] ?? '');
$equipment_id    = !empty($data['equipment_id']) ? intval($data['equipment_id']) : null;

// If equipment_id is provided, auto-fill machine_name and serial_number from equipment table
if ($equipment_id && (!$machine_name || !$serial_number)) {
    try {
        $eqStmt = $db->prepare('SELECT name, serial_number, current_location FROM equipment WHERE equipment_id = ? LIMIT 1');
        $eqStmt->execute([$equipment_id]);
        $eq = $eqStmt->fetch();
        if ($eq) {
            if (!$machine_name) $machine_name = $eq['name'];
            if (!$serial_number) $serial_number = $eq['serial_number'] ?? 'N/A';
            if (!$department && $eq['current_location']) $department = $eq['current_location'];
        }
    } catch (Throwable $ignored) {}
}

if (!$machine_name || !$serial_number || !$department || !$brought_by || !$intake_date || !$machine_problem) {
    http_response_code(400);
    echo json_encode(["error" => "Machine name, Serial NO, Department, Name of person who brought machine, Date, and Machine problem are required."]);
    exit();
}

try {
    // If equipment_id is not explicitly provided, attempt to auto-match by serial number
    if (!$equipment_id) {
        $stmtEq = $db->prepare('SELECT equipment_id FROM equipment WHERE serial_number = ? LIMIT 1');
        $stmtEq->execute([$serial_number]);
        $eq = $stmtEq->fetch();
        if ($eq) {
            $equipment_id = intval($eq['equipment_id']);
        }
    }

    $stmt = $db->prepare('INSERT INTO repair_logs (equipment_id, machine_name, serial_number, department, brought_by, intake_date, machine_problem, status) VALUES (?, ?, ?, ?, ?, ?, ?, "IN_REPAIR")');
    $stmt->execute([$equipment_id, $machine_name, $serial_number, $department, $brought_by, $intake_date, $machine_problem]);
    $repair_id = $db->lastInsertId();

    // If equipment is linked, update equipment status to IN_REPAIR
    if ($equipment_id) {
        $upStmt = $db->prepare("UPDATE equipment SET status = 'IN_REPAIR', updated_at = NOW() WHERE equipment_id = ?");
        $upStmt->execute([$equipment_id]);
    }

    echo json_encode([
        "success"   => true,
        "repair_id" => intval($repair_id),
        "message"   => "Machine repair intake logged successfully."
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
