<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session set. Including index.php...\n";
ob_start();
include 'index.php';
$output = ob_get_clean();
echo "Execution finished. Output length: " . strlen($output) . "\n";
if (strlen($output) < 500) {
    echo "Output too short. Content: \n" . $output . "\n";
} else {
    echo "Output seems OK (starts with " . substr($output, 0, 50) . "...)\n";
}
