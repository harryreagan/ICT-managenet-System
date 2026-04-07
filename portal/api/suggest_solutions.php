<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'suggestions' => []]);
    exit;
}

try {
    // Only suggest from RESOLVED or CLOSED tickets (Knowledge Base)
    // Also prioritizing matches in Title, then Symptoms
    $stmt = $pdo->prepare("
        SELECT id, title, symptoms, resolution, system_affected
        FROM troubleshooting_logs 
        WHERE status IN ('resolved', 'closed') 
        AND (title LIKE ? OR symptoms LIKE ?)
        ORDER BY 
            CASE 
                WHEN title LIKE ? THEN 1 
                ELSE 2 
            END,
            incident_date DESC
        LIMIT 3
    ");

    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clean up HTML/Markdown for better display in suggestions
    foreach ($suggestions as &$item) {
        $item['resolution_preview'] = strip_tags(substr($item['resolution'], 0, 200)) . '...';
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
