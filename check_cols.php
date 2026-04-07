<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE troubleshooting_logs");
$cols = $stmt->fetchAll();
foreach ($cols as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
