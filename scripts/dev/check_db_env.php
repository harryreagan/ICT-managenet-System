<?php
// Dump header for CLI or Browser
echo "<h1>Database Environment Check</h1>";
echo "<pre>";

require_once 'config/database.php';

try {
    echo "PHP Version: " . phpversion() . "\n";
    echo "User: " . get_current_user() . "\n";
    echo "Server API: " . php_sapi_name() . "\n\n";

    echo "--- Database Config Constants ---\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";

    echo "\n--- PDO Connection Attributes ---\n";
    echo "Client Version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n";
    echo "Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "Connection Status: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";

    echo "\n--- Schema Check: troubleshooting_logs ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM troubleshooting_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo str_pad($col['Field'], 20) . " | " . $col['Type'] . "\n";
    }

    echo "\n--- Test Select ---\n";
    try {
        $stmt = $pdo->query("SELECT id, visibility FROM troubleshooting_logs LIMIT 1");
        $row = $stmt->fetch();
        echo "Select successful. ID: " . ($row['id'] ?? 'none') . "\n";
    } catch (PDOException $e) {
        echo "SELECT FAILED: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>