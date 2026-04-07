<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("DESC renewals");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
