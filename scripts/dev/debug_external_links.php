<?php
require_once 'config/database.php';

echo "<h1>External Links Debugger</h1>";

try {
    // 1. Check Connection
    echo "<h2>1. Database Connection</h2>";
    echo "Host: " . DB_HOST . "<br>";
    echo "DB Name: " . DB_NAME . "<br>";
    echo "Status: Connected<br>";

    // 2. Check Table Existence
    echo "<h2>2. Table Existence</h2>";
    $tables = $pdo->query("SHOW TABLES LIKE 'external_links'")->fetchAll();
    if (count($tables) > 0) {
        echo "<div style='color:green'>Table 'external_links' FOUND.</div>";
    } else {
        echo "<div style='color:red'>Table 'external_links' NOT FOUND.</div>";
        exit;
    }

    // 3. Check Data
    echo "<h2>3. Data Check</h2>";
    $count = $pdo->query("SELECT COUNT(*) FROM external_links")->fetchColumn();
    echo "Total Rows: $count<br>";

    $activeCount = $pdo->query("SELECT COUNT(*) FROM external_links WHERE is_active = 1")->fetchColumn();
    echo "Active Rows: $activeCount<br>";

    if ($activeCount > 0) {
        echo "<h3>First 5 Active Rows:</h3>";
        $stmt = $pdo->query("SELECT * FROM external_links WHERE is_active = 1 LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($rows, true) . "</pre>";
    } else {
        echo "<div style='color:orange'>No active links found. This is why the card is hidden.</div>";
    }

} catch (PDOException $e) {
    echo "<div style='color:red'><h3>Error:</h3>" . $e->getMessage() . "</div>";
}
?>