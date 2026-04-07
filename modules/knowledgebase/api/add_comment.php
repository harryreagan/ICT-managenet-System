<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$issue_id = $_POST['issue_id'] ?? null;
$comment_text = $_POST['comment_text'] ?? '';
$is_internal = isset($_POST['is_internal']) ? (int) $_POST['is_internal'] : 0;

if (!$issue_id || empty(trim($comment_text))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Issue ID and comment text are required']);
    exit;
}

// Verify issue exists
$stmt = $pdo->prepare("SELECT id FROM troubleshooting_logs WHERE id = ?");
$stmt->execute([$issue_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Issue not found']);
    exit;
}

try {
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO issue_comments (issue_id, user_id, comment_text, is_internal) VALUES (?, ?, ?, ?)");
    $stmt->execute([$issue_id, $_SESSION['user_id'], $comment_text, $is_internal]);

    $comment_id = $pdo->lastInsertId();

    // Log activity
    $activity_type = $is_internal ? 'Internal note added' : 'Comment added';
    $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'commented', ?)");
    $activityStmt->execute([$issue_id, $_SESSION['user_id'], $activity_type]);

    // Get the created comment with user info
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name, u.username 
        FROM issue_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'comment' => $comment
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>