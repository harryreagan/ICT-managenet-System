<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE maintenance_tasks");
print_r($stmt->fetchAll());
?>