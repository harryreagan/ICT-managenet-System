<?php
require_once 'config/database.php';

try {
    $sql = file_get_contents('database/refine_locations.sql');
    $pdo->exec($sql);
    echo "Infrastructure locations refined successfully!\n";
} catch (Exception $e) {
    echo "Error refining locations: " . $e->getMessage() . "\n";
}
?>