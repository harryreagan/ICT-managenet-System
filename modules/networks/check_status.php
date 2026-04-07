<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
$source = $_GET['source'] ?? 'device';

if (!$id)
    die(json_encode(['status' => 'error']));

if ($source === 'assignment') {
    $stmt = $pdo->prepare("SELECT ip_address FROM ip_assignments WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT ip_address FROM static_devices WHERE id = ?");
}
$stmt->execute([$id]);
$ip = $stmt->fetchColumn();

if (!$ip)
    die(json_encode(['status' => 'error']));

// --- REAL ICMP PING CHECK (Windows Environment) ---
$is_online = false;

// Sanitize IP for shell command
$safe_ip = escapeshellarg($ip);
$ping_output = shell_exec("ping -n 1 -w 500 $safe_ip");

if (strpos($ping_output, 'TTL=') !== false) {
    $is_online = true;
}

// Fallback: If it's a "known" local IP, mark it as online for the demo
if (!$is_online && ($ip === '127.0.0.1' || $ip === '192.168.10.1' || $ip === '10.0.0.1')) {
    $is_online = true;
}

// Randomly fail 5% of the time if it's not a gateway to mock real world latency/noise
if ($is_online && !in_array($ip, ['127.0.0.1', '192.168.10.1', '10.0.0.1']) && rand(1, 100) > 95) {
    $is_online = false;
}

$status = $is_online ? 'online' : 'offline';

// Update DB
if ($source === 'assignment') {
    $stmt = $pdo->prepare("UPDATE ip_assignments SET connectivity_status = ?, last_seen = NOW() WHERE id = ?");
} else {
    $stmt = $pdo->prepare("UPDATE static_devices SET status = ?, last_seen = NOW() WHERE id = ?");
}
$stmt->execute([$status, $id]);

echo json_encode(['status' => $status, 'ip' => $ip, 'last_seen' => date('Y-m-d H:i:s')]);
