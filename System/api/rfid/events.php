<?php
// Poll endpoint – returns all stored RFID events as JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$logFile = __DIR__ . '/../../data/rfid_events.json';

if (!file_exists($logFile)) {
    echo json_encode([]);
    exit();
}

$raw    = file_get_contents($logFile);
$events = json_decode($raw, true) ?? [];

echo json_encode($events);
