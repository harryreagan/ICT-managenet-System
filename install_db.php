<?php
// install_db.php - Consolidated Database Installer/Updater
// Usage: php install_db.php [force]

require_once 'config/database.php';

// Check for CLI or Web execution
$isCli = (php_sapi_name() === 'cli');
$force = false;

if ($isCli) {
    if (isset($argv[1]) && $argv[1] === 'force') {
        $force = true;
    }
} else {
    if (isset($_GET['force']) && $_GET['force'] === 'true') {
        $force = true;
    }
}

function logMsg($msg)
{
    global $isCli;
    if ($isCli) {
        echo $msg . "\n";
    } else {
        echo $msg . "<br>";
    }
}

try {
    // 1. Connect to MySQL Server (without DB selected) to ensure DB exists
    $pdoRoot = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdoRoot->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    logMsg("Connected to MySQL server.");

    // 2. Create Database if not exists
    $stmt = $pdoRoot->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($stmt->fetchColumn() == 0) {
        logMsg("Database '" . DB_NAME . "' does not exist. Creating...");
        $pdoRoot->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        logMsg("Database created successfully.");
    } else {
        logMsg("Database '" . DB_NAME . "' already exists.");
    }

    // 3. Connect to the specific database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchColumn();

    if ($tables && !$force) {
        logMsg("WARNING: Database is not empty. Tables detected.");
        logMsg("Skipping schema import to prevent data loss.");
        logMsg("To force a fresh install (DELETING ALL DATA), run with 'force' argument.");
        exit;
    }

    if ($force) {
        logMsg("FORCE mode enabled. Dropping all tables...");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $pdo->exec("DROP TABLE IF EXISTS " . $row[0]);
            logMsg("Dropped table: " . $row[0]);
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        logMsg("All tables dropped.");
    }

    // 5. Run Full Schema Update
    logMsg("Importing schema from database/full_schema_update.sql...");
    $sqlFile = __DIR__ . '/database/full_schema_update.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Split SQL by semicolons to execute statements individually (basic splitter)
    // mysqldump output is usually clean enough for this, but keeping it simple for now or using PDO::exec allows multiple statements if emulation is off?
    // PDO sometimes issues with multiple statements.
    // However, loading the whole file into exec() often works for dumps.

    $pdo->exec($sql);

    logMsg("Schema imported successfully.");

    // 6. Verify Critical Tables
    $requiredTables = ['users', 'troubleshooting_logs', 'hardware_assets', 'sop_documents'];
    foreach ($requiredTables as $table) {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
    }
    logMsg("Verification passed: Critical tables exist.");

    logMsg("Installation Complete!");

} catch (Exception $e) {
    logMsg("ERROR: " . $e->getMessage());
    exit(1);
}
?>