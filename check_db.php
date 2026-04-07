<?php
require_once 'config/database.php';

try {
    echo "<pre>";
    $stmt = $pdo->query("SHOW COLUMNS FROM handover_notes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_on_duty'");
    $userColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($userColumn);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
