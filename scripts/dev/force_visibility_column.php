<?php
require_once 'config/database.php';

echo "Running forced migration...\n";

try {
    // 1. Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM troubleshooting_logs LIKE 'visibility'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Column 'visibility' missing. Adding it...\n";
        $pdo->exec("ALTER TABLE troubleshooting_logs ADD COLUMN visibility ENUM('public', 'internal') DEFAULT 'public' AFTER status");
        echo "Column 'visibility' added successfully.\n";
    } else {
        echo "Column 'visibility' already exists.\n";
    }

    // 2. Double check by running a query that uses it
    $stmt = $pdo->query("SELECT id, visibility FROM troubleshooting_logs LIMIT 1");
    echo "Query test successful.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
