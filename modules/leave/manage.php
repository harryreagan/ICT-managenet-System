<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Only admins or managers can access this
if (!isAdmin() && $_SESSION['role'] !== 'manager') {
    header("Location: index.php");
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    header("Location: index.php");
    exit;
}

try {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    $stmt = $pdo->prepare("UPDATE ict_leave_requests SET status = ?, approved_by = ? WHERE id = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $id]);

    // Redirect with message? For now just back to list
    header("Location: index.php?view=all_requests&msg=processed");
    exit;
} catch (PDOException $e) {
    die("Error processing request: " . $e->getMessage());
}
?>