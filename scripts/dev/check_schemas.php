<?php
require_once 'config/database.php';

echo "--- USERS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- NOTIFICATIONS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE notifications");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
