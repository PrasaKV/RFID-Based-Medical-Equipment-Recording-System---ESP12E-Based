<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = getDB();
if (!$db) { echo json_encode(['error' => 'DB unavailable']); exit(); }

$equipmentId = intval($_GET['equipment_id'] ?? 0);

if (!$equipmentId) {
    echo json_encode(['error' => 'Missing equipment_id']);
    exit();
}

try {
    $stmt = $db->prepare(
        'SELECT it.*, COALESCE(NULLIF(it.reporter_name, ""), u.username, "Unknown") as reporter_name
         FROM issues_tickets it
         LEFT JOIN users u ON it.reported_by = u.user_id
         WHERE it.equipment_id = ?
         ORDER BY it.created_at DESC'
    );
    $stmt->execute([$equipmentId]);
    $issues = $stmt->fetchAll();

    // For each issue, get linked parts count
    foreach ($issues as &$issue) {
        $partStmt = $db->prepare('SELECT COUNT(*) FROM part_replacements WHERE ticket_id = ?');
        $partStmt->execute([$issue['ticket_id']]);
        $issue['linked_parts_count'] = intval($partStmt->fetchColumn());
    }
    unset($issue);

    echo json_encode($issues);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
