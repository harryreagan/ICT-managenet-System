<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Mock session
session_start();
$_SESSION['user_id'] = 1; // Assuming admin ID 1
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Mock server vars
$_SERVER['REQUEST_URI'] = '/ict/index.php';
$_SERVER['PHP_SELF'] = '/ict/index.php';

echo "Running index.php test...\n";
ob_start();
try {
    include 'index.php';
} catch (Throwable $t) {
    echo "CAUGHT THROWABLE: " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine() . "\n";
    echo $t->getTraceAsString() . "\n";
}
$output = ob_get_clean();
echo "Execution finished. Output length: " . strlen($output) . "\n";
if (strlen($output) < 100) {
    echo "OUTPUT TOO SHORT, MIGHT HAVE CRASHED. Output: \n" . $output;
}
