<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$equipmentId  = intval($data['equipment_id'] ?? 0);
$title        = trim($data['title'] ?? $data['issue_name'] ?? '');
$desc         = trim($data['issue_description'] ?? '');
$reportedBy   = intval($data['reported_by'] ?? ($_SESSION['user_id'] ?? 0));
$reporterName = trim($data['reporter_name'] ?? '');

if (!$reporterName && isLoggedIn()) {
    $u = currentUser();
    $reporterName = $u['username'] ?? '';
}

if (!$equipmentId || !$desc) {
    echo json_encode(['error'=>'equipment_id and issue_description are required.']); exit();
}

try {
    $db->prepare(
        'INSERT INTO issues_tickets (equipment_id, title, reported_by, reporter_name, issue_description) VALUES (?,?,?,?,?)'
    )->execute([$equipmentId, $title ?: null, $reportedBy ?: null, $reporterName ?: null, $desc]);
    $ticketId = $db->lastInsertId();

    // Update equipment status to NEEDS_ATTENTION
    $db->prepare("UPDATE equipment SET status='NEEDS_ATTENTION', updated_at=NOW() WHERE equipment_id=?")
       ->execute([$equipmentId]);

    echo json_encode(['success'=>true, 'ticket_id'=>$ticketId]);
} catch (PDOException $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
