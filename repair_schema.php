<?php
// Direct repair script to ensure target_user_id column exists
require_once 'config/database.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'target_user_id'");
    if ($check->rowCount() == 0) {
        echo "Adding target_user_id column...\n";
        $pdo->exec("ALTER TABLE notifications ADD COLUMN target_user_id INT DEFAULT NULL AFTER target_role");
        echo "Successfully added target_user_id column.\n";
    } else {
        echo "target_user_id column already exists.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
