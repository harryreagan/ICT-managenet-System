<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE procurement_requests");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
