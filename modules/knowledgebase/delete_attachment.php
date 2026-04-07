<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$attachment_id = $_POST['attachment_id'] ?? null;

if (!$attachment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Attachment ID is required']);
    exit;
}

try {
    // Get attachment details
    $stmt = $pdo->prepare("SELECT * FROM issue_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch();

    if (!$attachment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Attachment not found']);
        exit;
    }

    // Delete file from filesystem
    $file_path = '../../' . $attachment['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete from database
    $deleteStmt = $pdo->prepare("DELETE FROM issue_attachments WHERE id = ?");
    $deleteStmt->execute([$attachment_id]);

    // Log activity
    $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'attachment_deleted', ?)");
    $activityStmt->execute([$attachment['issue_id'], $_SESSION['user_id'], "Deleted file: {$attachment['file_name']}"]);

    echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>