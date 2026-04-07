<?php
require_once 'config/database.php';

try {
    $sql = "ALTER TABLE backup_logs ADD COLUMN destination_disk VARCHAR(255) AFTER backup_type";
    $pdo->exec($sql);
    echo "Column 'destination_disk' added successfully to 'backup_logs'.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'destination_disk' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>