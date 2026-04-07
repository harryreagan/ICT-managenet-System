<?php
require_once 'config/database.php';

echo "--- INVENTORY_ITEMS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE inventory_items");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- HARDWARE_ASSETS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE hardware_assets");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- USERS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
