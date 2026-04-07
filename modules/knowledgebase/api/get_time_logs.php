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
    $stmt = $pdo->prepare("
        SELECT t.*, u.full_name, u.username 
        FROM time_logs t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.issue_id = ? 
        ORDER BY t.logged_at DESC
    ");
    $stmt->execute([$issue_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    $total = 0;
    foreach ($logs as $log) {
        $total += $log['hours_spent'];
    }

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total_hours' => $total
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>