<?php
require_once 'config/database.php';

echo "Helper DB Host: " . DB_HOST . "\n";
echo "Helper DB Name: " . DB_NAME . "\n";

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in " . DB_NAME . ":\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    echo "\n-------------------\n";

    if (in_array('external_links', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM external_links")->fetchColumn();
        echo "external_links count: $count\n";
    } else {
        echo "external_links table NOT FOUND.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>