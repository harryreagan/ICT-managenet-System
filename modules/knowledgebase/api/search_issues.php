<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$exclude_id = $_GET['exclude_id'] ?? 0;

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'issues' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, title, status, priority 
        FROM troubleshooting_logs 
        WHERE (title LIKE ? OR id = ?) 
        AND id != ? 
        LIMIT 10
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $query, $exclude_id]);
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'issues' => $issues]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>