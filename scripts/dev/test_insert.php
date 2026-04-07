<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO troubleshooting_logs (title, system_affected, priority, status, visibility, symptoms, technician_name, assigned_to, incident_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Test Visibility', 'Test System', 'low', 'open', 'internal', 'Testing visibility column', 'Admin', 'admin', date('Y-m-d')]);

    echo "Insert successful! ID: " . $pdo->lastInsertId() . "\n";

    // Clean up
    $pdo->exec("DELETE FROM troubleshooting_logs WHERE id = " . $pdo->lastInsertId());
    echo "Test record deleted.\n";

} catch (PDOException $e) {
    echo "Insert Failed: " . $e->getMessage() . "\n";
}
?>