<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$issue_id = $_GET['issue_id'] ?? null;

if (!$issue_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Issue ID is required']);
    exit;
}

try {
    // Get comments
    $commentsStmt = $pdo->prepare("
        SELECT c.*, u.full_name, u.username 
        FROM issue_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.issue_id = ? 
        ORDER BY c.created_at ASC
    ");
    $commentsStmt->execute([$issue_id]);
    $comments = $commentsStmt->fetchAll();

    // Get activity timeline
    $activityStmt = $pdo->prepare("
        SELECT a.*, u.full_name, u.username 
        FROM issue_activity a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.issue_id = ? 
        ORDER BY a.created_at DESC
    ");
    $activityStmt->execute([$issue_id]);
    $activities = $activityStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'activities' => $activities
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>