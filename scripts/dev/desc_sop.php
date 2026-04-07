<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("DESC sop_documents");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
