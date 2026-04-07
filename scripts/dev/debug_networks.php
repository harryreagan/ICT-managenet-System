<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id, name FROM networks");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
