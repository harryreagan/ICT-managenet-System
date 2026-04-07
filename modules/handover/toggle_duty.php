<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check current status
    $stmt = $pdo->prepare("SELECT is_on_duty FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentStatus = $stmt->fetchColumn();

    $newStatus = $currentStatus ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE users SET is_on_duty = ? WHERE id = ?");
    $stmt->execute([$newStatus, $_SESSION['user_id']]);

    // Log activity
    $action = $newStatus ? "Clocked on duty" : "Clocked off duty";
    logActivity($pdo, $_SESSION['user_id'], $action);

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'is_on_duty' => $newStatus
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
