<?php
// Clear all RFID events
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$logFile = __DIR__ . '/../../data/rfid_events.json';
file_put_contents($logFile, json_encode([]));

echo json_encode(["success" => true, "message" => "All events cleared."]);
