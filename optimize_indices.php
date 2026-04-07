<?php
// Attempt to add indices for performance
try {
    require_once 'config/database.php';
} catch (PDOException $e) {
    // Fallback if requires fail or DB is unreachable from here
    echo "Initial connection failed: " . $e->getMessage() . "\n";
    echo "Attempting fallback connection to 127.0.0.1...\n";
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel_ict', 'root', '');
}

$queries = [
    // Notifications indices
    "CREATE INDEX idx_notifications_is_read ON notifications(is_read)",
    "CREATE INDEX idx_notifications_created_at ON notifications(created_at)",
    "CREATE INDEX idx_notifications_target_role ON notifications(target_role)",

    // Audit logs indices
    "CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id)",
    "CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at)",

    // Troubleshooting logs indices
    "CREATE INDEX idx_troubleshooting_logs_requester ON troubleshooting_logs(requester_username)",
];

echo "Starting optimization...\n";

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Success: $sql\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "Skipped (Already exists): $sql\n";
        } else {
            echo "Error: " . $e->getMessage() . " for query: $sql\n";
        }
    }
}

echo "Optimization complete.\n";
unlink(__FILE__); // Self-destruct
