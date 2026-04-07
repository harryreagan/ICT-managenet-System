<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$link_id = $_POST['link_id'] ?? null;

if (!$link_id) {
    http_response_code(400);
    exit;
}

try {
    // Get link details for logging
    $stmt = $pdo->prepare("SELECT * FROM issue_links WHERE id = ?");
    $stmt->execute([$link_id]);
    $link = $stmt->fetch();

    if ($link) {
        // Delete link
        $deleteStmt = $pdo->prepare("DELETE FROM issue_links WHERE id = ?");
        $deleteStmt->execute([$link_id]);

        // Log Activity
        $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'updated', ?)");
        $activityStmt->execute([$link['source_issue_id'], $_SESSION['user_id'], "Unlinked from issue #{$link['target_issue_id']}"]);
    }

    echo json_encode(['success' => true, 'message' => 'Link removed']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>