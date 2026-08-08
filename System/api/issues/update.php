<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only admin and engineer can edit
requireCanEdit();

$db = getDB();
if (!$db) { echo json_encode(['error' => 'DB unavailable']); exit(); }

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? $_POST;
$id     = intval($data['ticket_id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'ticket_id is required.']);
    exit();
}

// Build dynamic update
$fields = [];
$params = [];

// Status update
$allowedStatuses = ['OPEN', 'IN_PROGRESS', 'RESOLVED'];
if (isset($data['status']) && in_array($data['status'], $allowedStatuses)) {
    $fields[] = 'status = ?';
    $params[] = $data['status'];
}

// Title / Name
if (isset($data['title']) || isset($data['issue_name'])) {
    $fields[] = 'title = ?';
    $params[] = trim($data['title'] ?? $data['issue_name'] ?? '');
}

// Reporter Name
if (isset($data['reporter_name'])) {
    $fields[] = 'reporter_name = ?';
    $params[] = trim($data['reporter_name']);
}

// Issue description
if (isset($data['issue_description'])) {
    $fields[] = 'issue_description = ?';
    $params[] = trim($data['issue_description']);
}

// Resolution notes
if (isset($data['resolution_notes'])) {
    $fields[] = 'resolution_notes = ?';
    $params[] = trim($data['resolution_notes']);
}

// Repair method
if (isset($data['repair_method'])) {
    $fields[] = 'repair_method = ?';
    $params[] = trim($data['repair_method']);
}

if (empty($fields)) {
    echo json_encode(['error' => 'Nothing to update.']);
    exit();
}

$fields[] = 'updated_at = NOW()';
$params[] = $id;

try {
    $sql = 'UPDATE issues_tickets SET ' . implode(', ', $fields) . ' WHERE ticket_id = ?';
    $db->prepare($sql)->execute($params);

    // If resolved, check if equipment should revert to OPERATIONAL
    if (isset($data['status']) && $data['status'] === 'RESOLVED') {
        $stmt = $db->prepare('SELECT equipment_id FROM issues_tickets WHERE ticket_id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && $row['equipment_id']) {
            // Check if any other open tickets remain for this device
            $cnt = $db->prepare("SELECT COUNT(*) FROM issues_tickets WHERE equipment_id=? AND status IN ('OPEN','IN_PROGRESS')");
            $cnt->execute([$row['equipment_id']]);
            if ($cnt->fetchColumn() == 0) {
                $db->prepare("UPDATE equipment SET status='OPERATIONAL', updated_at=NOW() WHERE equipment_id=? AND status='NEEDS_ATTENTION'")
                   ->execute([$row['equipment_id']]);
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
