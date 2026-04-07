<?php
require_once 'config/database.php';

try {
    echo "Updating 'notifications' table...\n";
    $pdo->exec("ALTER TABLE notifications ADD COLUMN target_role ENUM('all', 'admin') DEFAULT 'all' AFTER type");
    echo "✅ Added 'target_role' column.\n";

    // Update existing notifications to 'admin' as a safety measure for old alerts
    $pdo->exec("UPDATE notifications SET target_role = 'admin' WHERE type IN ('alert', 'warning')");
    echo "✅ Updated existing alerts/warnings to 'admin' role.\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>