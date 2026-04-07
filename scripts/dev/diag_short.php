<?php
require_once 'config/database.php';

function checkTable($pdo, $table)
{
    echo "--- $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "{$c['Field']} ({$c['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

checkTable($pdo, 'inventory_items');
checkTable($pdo, 'hardware_assets');
