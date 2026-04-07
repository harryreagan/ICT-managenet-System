<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$source_id = $_POST['source_id'] ?? null;
$target_id = $_POST['target_id'] ?? null;
$type = $_POST['type'] ?? 'relates_to';

if (!$source_id || !$target_id || $source_id == $target_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid issue IDs']);
    exit;
}

try {
    // Check if link already exists
    $checkStmt = $pdo->prepare("SELECT id FROM issue_links WHERE (source_issue_id = ? AND target_issue_id = ?) OR (source_issue_id = ? AND target_issue_id = ?)");
    $checkStmt->execute([$source_id, $target_id, $target_id, $source_id]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Issues are already linked']);
        exit;
    }

    // Create Link
    $stmt = $pdo->prepare("INSERT INTO issue_links (source_issue_id, target_issue_id, link_type, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$source_id, $target_id, $type, $_SESSION['user_id']]);

    // Log Activity
    $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'linked', ?)");
    $activityStmt->execute([$source_id, $_SESSION['user_id'], "Linked to issue #$target_id"]);

    // Log on target too
    $activityStmt->execute([$target_id, $_SESSION['user_id'], "Linked from issue #$source_id"]);

    echo json_encode(['success' => true, 'message' => 'Link created successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>