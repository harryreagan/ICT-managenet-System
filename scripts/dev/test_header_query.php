<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

echo "Testing notification query...\n";
$start = microtime(true);
try {
    $notifStmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 AND (target_role = 'admin' OR target_role = 'all') ORDER BY created_at DESC LIMIT 5");
    $recentNotifs = $notifStmt->fetchAll();
    echo "Query successful. Found " . count($recentNotifs) . " notifications.\n";
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage() . "\n";
}
echo "Time taken: " . round(microtime(true) - $start, 4) . "s\n";
