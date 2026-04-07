<?php
require_once 'config/database.php';
echo "Current Database: ";
$res = $pdo->query("SELECT DATABASE()")->fetchColumn();
echo $res . "\n\n";

echo "All 'renewals' tables in the system:\n";
$stmt = $pdo->query("SELECT table_schema, table_name FROM information_schema.tables WHERE table_name = 'renewals'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nColumns in current 'renewals' table:\n";
$stmt = $pdo->query("SHOW COLUMNS FROM renewals");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>