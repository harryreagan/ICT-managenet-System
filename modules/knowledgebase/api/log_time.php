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
$hours = $_POST['hours_spent'] ?? 0;
$description = $_POST['description'] ?? '';

if (!$issue_id || $hours <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Issue ID and valid hours are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert Log
    $stmt = $pdo->prepare("INSERT INTO time_logs (issue_id, user_id, hours_spent, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$issue_id, $_SESSION['user_id'], $hours, $description]);

    // Recalculate Total Time
    $stmt = $pdo->prepare("SELECT SUM(hours_spent) FROM time_logs WHERE issue_id = ?");
    $stmt->execute([$issue_id]);
    $total_hours = $stmt->fetchColumn() ?: 0;

    // Update Issue Total
    $updateStmt = $pdo->prepare("UPDATE troubleshooting_logs SET total_time_spent = ? WHERE id = ?");
    $updateStmt->execute([$total_hours, $issue_id]);

    // Log Activity
    $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'time_logged', ?)");
    $desc = "Logged " . $hours . " hours" . ($description ? ": $description" : "");
    $activityStmt->execute([$issue_id, $_SESSION['user_id'], $desc]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Time logged successfully',
        'total_time' => $total_hours
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>