<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Change ENUM to VARCHAR
    $sql = "ALTER TABLE external_links MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Other'";

    $pdo->exec($sql);
    echo "Column 'category' updated to VARCHAR(50) successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>