<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE networks");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in networks: " . implode(", ", $columns) . "\n";
