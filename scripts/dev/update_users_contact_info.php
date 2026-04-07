<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN extension VARCHAR(20) AFTER department");
    $pdo->exec("ALTER TABLE users ADD COLUMN duty_number VARCHAR(20) AFTER extension");
    echo "Successfully updated users table schema.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>