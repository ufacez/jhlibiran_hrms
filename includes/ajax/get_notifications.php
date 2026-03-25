<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get';

// Stubbed notification payload to match dashboard.js expectations
$payload = [
    'notifications' => [],
    'unread_count' => 0
];

echo json_encode([
    'success' => true,
    'data' => $payload
]);
