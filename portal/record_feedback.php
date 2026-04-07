<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 1. Check if table exists, create if not (Auto-migration)
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'portal_feedback'")->rowCount() > 0;

    if (!$tableExists) {
        $sql = "CREATE TABLE portal_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            rating VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    // If permission denied to CREATE TABLE, we might log it but for now just try to proceed or fail
    // We assume the DB user has permissions
}

// 2. Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $rating = $input['rating'] ?? '';

    $valid_ratings = ['bad', 'okay', 'great'];

    if (in_array($rating, $valid_ratings)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO portal_feedback (user_id, rating) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $rating]);

            echo json_encode(['success' => true, 'message' => 'Feedback recorded']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rating']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
