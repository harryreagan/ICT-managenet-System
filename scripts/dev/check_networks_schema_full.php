<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE networks");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
