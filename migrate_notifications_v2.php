<?php
require_once 'config/database.php';
try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN target_user_id INT DEFAULT NULL AFTER target_role");
    echo "SUCCESS";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ALREADY_EXISTS";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}
