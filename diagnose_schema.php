<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Definitive Schema Diagnosis</h1>";
echo "<pre>";

try {
    echo "SAPI: " . php_sapi_name() . "\n";
    echo "Realpath of config: " . realpath('config/database.php') . "\n";

    // 1. List all databases this connection can see
    echo "\n--- Databases Available ---\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($dbs);

    // 2. Current Database
    echo "\n--- Current Database ---\n";
    echo $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

    // 3. Schema of troubleshooting_logs
    echo "\n--- Table Schema: troubleshooting_logs ---\n";
    $stmt = $pdo->query("DESC troubleshooting_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Default']}\n";
    }

    // 4. Check if visibility is in the list
    $has_visibility = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'visibility')
            $has_visibility = true;
    }

    if (!$has_visibility) {
        echo "\n[!!!] VISIBILITY COLUMN IS MISSING! Attempting to add it now...\n";
        try {
            $pdo->exec("ALTER TABLE troubleshooting_logs ADD COLUMN visibility ENUM('public', 'internal') DEFAULT 'public' AFTER status");
            echo "Successfully added visibility column.\n";
            echo "Refreshing schema...\n";
            $stmt = $pdo->query("DESC troubleshooting_logs");
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo "FAILED TO ADD COLUMN: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\n[OK] Visibility column exists.\n";
    }

    // 5. Schema of sop_documents
    echo "\n--- Table Schema: sop_documents ---\n";
    try {
        $stmt = $pdo->query("DESC sop_documents");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Default']}\n";
        }
    } catch (Exception $e) {
        echo "[!!!] Error accessing sop_documents: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "CRITICAL DIAGNOSTIC ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>