<?php
require_once 'config/database.php';

echo "<h1>Web Database Diagnostic</h1>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";

try {
    $attributes = [
        "SERVER_INFO" => PDO::ATTR_SERVER_INFO,
        "SERVER_VERSION" => PDO::ATTR_SERVER_VERSION,
        "CLIENT_VERSION" => PDO::ATTR_CLIENT_VERSION,
        "CONNECTION_STATUS" => PDO::ATTR_CONNECTION_STATUS
    ];

    echo "<h2>Connection Details</h2>";
    foreach ($attributes as $name => $attr) {
        try {
            echo "$name: " . $pdo->getAttribute($attr) . "<br>";
        } catch (Exception $e) {
            echo "$name: " . $e->getMessage() . "<br>";
        }
    }

    echo "<h2>Tables in " . DB_NAME . "</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        $color = ($table === 'external_links') ? 'green' : 'black';
        $weight = ($table === 'external_links') ? 'bold' : 'normal';
        echo "<li style='color:$color; font-weight:$weight'>$table</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h3>Connection Failed: " . $e->getMessage() . "</h3>";
}
?>