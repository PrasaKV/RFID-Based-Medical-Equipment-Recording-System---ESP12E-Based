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

$repair_id            = intval($data['repair_id'] ?? 0);
$return_date          = trim($data['return_date'] ?? date('Y-m-d'));
$received_by          = trim($data['received_by'] ?? '');
$delivery_department  = trim($data['delivery_department'] ?? '');
$delivery_remarks     = trim($data['delivery_remarks'] ?? '');

if (!$repair_id || !$return_date || !$received_by) {
    http_response_code(400);
    echo json_encode(["error" => "Repair ID, Return Date, and Person who collected the machine are required."]);
    exit();
}

try {
    $stmtFetch = $db->prepare('SELECT * FROM repair_logs WHERE repair_id = ? LIMIT 1');
    $stmtFetch->execute([$repair_id]);
    $repair = $stmtFetch->fetch();

    if (!$repair) {
        http_response_code(404);
        echo json_encode(["error" => "Repair record not found."]);
        exit();
    }

    $stmt = $db->prepare('UPDATE repair_logs SET status = "RETURNED", return_date = ?, received_by = ?, delivery_department = ?, delivery_remarks = ?, updated_at = NOW() WHERE repair_id = ?');
    $stmt->execute([$return_date, $received_by, $delivery_department ?: null, $delivery_remarks ?: null, $repair_id]);

    // If equipment is linked, update status back to OPERATIONAL
    if (!empty($repair['equipment_id'])) {
        $upStmt = $db->prepare("UPDATE equipment SET status = 'OPERATIONAL', updated_at = NOW() WHERE equipment_id = ?");
        $upStmt->execute([$repair['equipment_id']]);
    }

    // Also log in maintenance_logs for history audit
    if (!empty($repair['equipment_id'])) {
        $maintStmt = $db->prepare('INSERT INTO maintenance_logs (equipment_id, type, service_date, technician, remarks) VALUES (?, "REPAIR", ?, ?, ?)');
        $remarks = "Returned from repair. Collected by: $received_by. Dept: $delivery_department. Problem: {$repair['machine_problem']}";
        if ($delivery_remarks) $remarks .= ". Remarks: $delivery_remarks";
        $maintStmt->execute([$repair['equipment_id'], $return_date, $received_by, $remarks]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Machine delivery recorded successfully."
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
