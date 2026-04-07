<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    if (!$id) {
        header("Location: ../history.php?error=Missing record ID");
        exit();
    }

    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM facility_check_logs WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ../history.php?success=Log entry deleted");
        } elseif ($action === 'edit') {
            $notes = $_POST['notes'] ?? '';
            $status = $_POST['status'] ?? 'operational';

            $stmt = $pdo->prepare("UPDATE facility_check_logs SET notes = ?, status = ? WHERE id = ?");
            $stmt->execute([$notes, $status, $id]);
            header("Location: ../history.php?success=Log entry updated");
        } else {
            header("Location: ../history.php?error=Invalid action");
        }
    } catch (PDOException $e) {
        die("Error managing logs: " . $e->getMessage());
    }
} else {
    header("Location: ../history.php");
}
?>