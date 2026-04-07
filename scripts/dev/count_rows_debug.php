<?php
require 'config/database.php';
$tables = ['troubleshooting_logs', 'inventory_items', 'hardware_assets', 'networks', 'notifications', 'audit_logs', 'quick_notes'];
foreach ($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "$t: $c\n";
    } catch (Exception $e) {
        echo "$t: ERROR (" . $e->getMessage() . ")\n";
    }
}
?>