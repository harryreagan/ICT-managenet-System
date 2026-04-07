<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM troubleshooting_logs LIKE 'visibility'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add visibility column
        $sql = "ALTER TABLE troubleshooting_logs ADD COLUMN visibility ENUM('public', 'internal') NOT NULL DEFAULT 'public' AFTER status";
        $pdo->exec($sql);
        echo "Column 'visibility' added to troubleshooting_logs successfully.\n";
    } else {
        echo "Column 'visibility' already exists.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>