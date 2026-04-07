<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE floors");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
