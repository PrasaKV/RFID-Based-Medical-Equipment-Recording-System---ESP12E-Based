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

$id = intval($_GET['ticket_id'] ?? $_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Missing ticket_id']); exit(); }

try {
    // Get issue with reporter info
    $stmt = $db->prepare(
        'SELECT it.*, u.username as reporter_name
         FROM issues_tickets it
         LEFT JOIN users u ON it.reported_by = u.user_id
         WHERE it.ticket_id = ?'
    );
    $stmt->execute([$id]);
    $issue = $stmt->fetch();

    if (!$issue) {
        http_response_code(404);
        echo json_encode(['error' => 'Issue not found']);
        exit();
    }

    // Get linked parts
    $partStmt = $db->prepare(
        'SELECT p.*, e.name as equip_name
         FROM part_replacements p
         LEFT JOIN equipment e ON p.equipment_id = e.equipment_id
         WHERE p.ticket_id = ?
         ORDER BY p.replacement_date DESC'
    );
    $partStmt->execute([$id]);
    $issue['linked_parts'] = $partStmt->fetchAll();

    echo json_encode($issue);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
