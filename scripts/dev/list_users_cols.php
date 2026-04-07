<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE users");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols) . "\n";
