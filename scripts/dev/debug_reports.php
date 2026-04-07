<?php
require_once 'config/database.php';

function checkTable($pdo, $table)
{
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "✅ Table '$table' exists.\n";
    } catch (PDOException $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "\n";
    }
}

try {
    echo "Checking tables for Reports...\n";
    checkTable($pdo, 'troubleshooting_logs');
    checkTable($pdo, 'hardware_assets');
    checkTable($pdo, 'inventory_items');
    checkTable($pdo, 'procurement_requests');

    echo "\nChecking Columns...\n";
    // Check specific columns used
    $pdo->query("SELECT system_affected FROM troubleshooting_logs LIMIT 1");
    echo "✅ troubleshooting_logs.system_affected exists\n";
    $pdo->query("SELECT condition_status FROM hardware_assets LIMIT 1");
    echo "✅ hardware_assets.condition_status exists\n";
    $pdo->query("SELECT stock_level FROM inventory_items LIMIT 1");
    echo "✅ inventory_items.stock_level exists\n";
    $pdo->query("SELECT estimated_cost FROM procurement_requests LIMIT 1");
    echo "✅ procurement_requests.estimated_cost exists\n";

} catch (PDOException $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
}
?>