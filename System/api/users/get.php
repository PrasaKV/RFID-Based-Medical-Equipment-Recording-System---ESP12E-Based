<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();
if (!$db) { echo json_encode(['error'=>'DB unavailable']); exit(); }

$id = intval($_GET['id'] ?? $_GET['user_id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing user_id']); exit(); }

$stmt = $db->prepare('SELECT user_id, username, email, password, role, created_at FROM users WHERE user_id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) { echo json_encode(['error'=>'User not found']); exit(); }

// Fetch user activity / tickets reported
$stmtTickets = $db->prepare(
    'SELECT it.*, e.name as device_name, e.equipment_id
     FROM issues_tickets it
     LEFT JOIN equipment e ON it.equipment_id = e.equipment_id
     WHERE it.reported_by = ?
     ORDER BY it.created_at DESC LIMIT 10'
);
$stmtTickets->execute([$id]);
$tickets = $stmtTickets->fetchAll();

// Fetch maintenance logs authored by technician name
$stmtMaint = $db->prepare(
    'SELECT ml.*, e.name as device_name
     FROM maintenance_logs ml
     LEFT JOIN equipment e ON ml.equipment_id = e.equipment_id
     WHERE ml.technician LIKE ?
     ORDER BY ml.created_at DESC LIMIT 10'
);
$stmtMaint->execute(['%' . $user['username'] . '%']);
$maintLogs = $stmtMaint->fetchAll();

echo json_encode([
    'user' => $user,
    'reported_tickets' => $tickets,
    'ticket_count' => count($tickets),
    'maintenance_logs' => $maintLogs,
    'maintenance_count' => count($maintLogs),
]);
