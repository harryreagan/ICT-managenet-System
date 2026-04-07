<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

header('Content-Type: application/json');

// Only allow logged in users
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$ip = $_GET['ip'] ?? '';

if (empty($ip)) {
    echo json_encode(['error' => 'IP address is required']);
    exit;
}

// Basic IP validation
if (!filter_var($ip, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.-]+$/', $ip)) {
    echo json_encode(['error' => 'Invalid IP address or hostname']);
    exit;
}

// Sanitize for shell command
$safe_ip = escapeshellarg($ip);

// Execute ping command (Windows)
// -n 1: 1 packet
// -w 1000: 1 second timeout
$output = shell_exec("ping -n 1 -w 1000 $safe_ip");

$is_online = false;
$latency = 'N/A';
$ttl = 'N/A';

if (strpos($output, 'TTL=') !== false) {
    $is_online = true;

    // Extract latency (time=...ms)
    if (preg_match('/time[=<]([\d.]+)ms/', $output, $matches)) {
        $latency = $matches[1] . 'ms';
    }

    // Extract TTL (TTL=...)
    if (preg_match('/TTL=(\d+)/i', $output, $matches)) {
        $ttl = $matches[1];
    }
}

echo json_encode([
    'ip' => $ip,
    'status' => $is_online ? 'online' : 'offline',
    'latency' => $latency,
    'ttl' => $ttl,
    'raw_output' => $output
]);
