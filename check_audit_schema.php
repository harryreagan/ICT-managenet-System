<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE audit_logs");
$columns = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($columns, JSON_PRETTY_PRINT);
unlink(__FILE__);
