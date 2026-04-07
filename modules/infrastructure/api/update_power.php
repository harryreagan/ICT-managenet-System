<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $load = $_POST['current_load_kw'] ?? 0;
    $charge = $_POST['battery_percentage'] ?? null;
    $status = $_POST['status'] ?? 'operational';
    $notes = $_POST['notes'] ?? '';

    if (!$id) {
        header("Location: ../index.php?error=Missing system ID");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE power_systems SET 
                                current_load_kw = ?, 
                                battery_percentage = ?, 
                                status = ?, 
                                notes = ?, 
                                last_updated = NOW() 
                                WHERE id = ?");
        $stmt->execute([$load, $charge !== '' ? $charge : null, $status, $notes, $id]);

        // Get system name for logging
        $nameStmt = $pdo->prepare("SELECT name FROM power_systems WHERE id = ?");
        $nameStmt->execute([$id]);
        $sysName = $nameStmt->fetchColumn();

        // Audit Log
        logActivity($pdo, $_SESSION['user_id'], "UPDATE_POWER", "Updated power system $sysName status to $status");

        // Notification for alerts
        if ($status !== 'operational') {
            $msg = "[POWER ALERT] $sysName marked as " . strtoupper($status) . " by " . $_SESSION['username'];
            $type = ($status === 'faulty') ? 'alert' : 'warning';
            createNotification($pdo, $msg, $type, '/ict/modules/infrastructure/index.php', 'admin');
        }

        header("Location: ../index.php?success=Power system updated successfully");
    } catch (PDOException $e) {
        die("Error updating power system: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
}
?>