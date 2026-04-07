<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE sop_documents");
print_r($stmt->fetchAll());
?>