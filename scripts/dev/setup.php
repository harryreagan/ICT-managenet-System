<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_ict');

try {
    // 1. Connect without DB name
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Dallas Premiere Hotel Setup</h1>";
    echo "<ul>";

    // 2. Create Database
    echo "<li>Creating Database '" . DB_NAME . "'... ";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    echo "<span style='color:green'>Done</span></li>";

    // 3. Select Database
    $pdo->exec("USE `" . DB_NAME . "`");

    // 4. Import Schema
    echo "<li>Importing Schema... ";
    $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($schemaSql) {
        // Split by semicolon via regex to handle multiple statements safely-ish
        // Note: For complex dumps this is fragile, but for our schema it should be fine.
        $pdo->exec($schemaSql);
        echo "<span style='color:green'>Done</span></li>";
    } else {
        echo "<span style='color:red'>Error: schema.sql not found</span></li>";
    }

    // 5. Import Seed Data (Optional)
    echo "<li>Importing Sample Data... ";
    $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
    if ($seedSql) {
        $pdo->exec($seedSql);
        echo "<span style='color:green'>Done</span></li>";
    } else {
        echo "<span style='color:orange'>Skipped (seed.sql not found or empty)</span></li>";
    }

    echo "</ul>";
    echo "<h2>Setup Complete!</h2>";
    echo "<p>You can now <a href='index.php'>Go to Dashboard</a>.</p>";
    echo "<p style='color:red'><strong>Security Warning:</strong> Please delete <code>setup.php</code> before deploying to production.</p>";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>