<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE maintenance_tasks ADD COLUMN start_time DATETIME AFTER next_due_date");
    $pdo->exec("ALTER TABLE maintenance_tasks ADD COLUMN end_time DATETIME AFTER start_time");
    $pdo->exec("ALTER TABLE maintenance_tasks ADD COLUMN show_on_portal TINYINT(1) DEFAULT 0 AFTER end_time");
    $pdo->exec("ALTER TABLE maintenance_tasks ADD COLUMN impact ENUM('none', 'low', 'medium', 'high', 'outage') DEFAULT 'none' AFTER show_on_portal");
    echo "Successfully updated maintenance_tasks table schema.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>