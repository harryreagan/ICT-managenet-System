<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$issue_id = $_GET['issue_id'] ?? null;

if (!$issue_id) {
    http_response_code(400);
    exit;
}

try {
    // Fetch links where this issue is source OR target
    $stmt = $pdo->prepare("
        SELECT l.id as link_id, l.link_type, l.created_at,
               CASE 
                   WHEN l.source_issue_id = ? THEN t2.id 
                   ELSE t1.id 
               END as linked_issue_id,
               CASE 
                   WHEN l.source_issue_id = ? THEN t2.title 
                   ELSE t1.title 
               END as linked_issue_title,
               CASE 
                   WHEN l.source_issue_id = ? THEN t2.status 
                   ELSE t1.status 
               END as linked_issue_status,
               CASE 
                   WHEN l.source_issue_id = ? THEN t2.priority 
                   ELSE t1.priority 
               END as linked_issue_priority,
               CASE 
                   WHEN l.source_issue_id = ? THEN 'outgoing' 
                   ELSE 'incoming' 
               END as direction
        FROM issue_links l
        LEFT JOIN troubleshooting_logs t1 ON l.source_issue_id = t1.id
        LEFT JOIN troubleshooting_logs t2 ON l.target_issue_id = t2.id
        WHERE l.source_issue_id = ? OR l.target_issue_id = ?
    ");

    // We strictly passing the issue_id 6 times for the CASE statements and WHERE clause
    $stmt->execute([$issue_id, $issue_id, $issue_id, $issue_id, $issue_id, $issue_id, $issue_id]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'links' => $links]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>