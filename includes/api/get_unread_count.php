<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

$role = $_SESSION['role'] ?? 'staff';
$user_id = $_SESSION['user_id'] ?? 0;
session_write_close();

try {
    // Check for target_user_id column existence first (fallback if migration is slow)
    $query = "SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (
        (target_role = 'all' OR target_role = :role)
        OR (target_user_id = :user_id)
    )";

    // Safety check: if target_user_id doesn't exist yet, fallback to roles only
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute(['role' => $role, 'user_id' => $user_id]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (target_role = 'all' OR target_role = ?)");
        $stmt->execute([$role]);
    }

    echo json_encode(['count' => (int) $stmt->fetchColumn()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
