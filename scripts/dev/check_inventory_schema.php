<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE inventory_items");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
