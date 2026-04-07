<?php
require_once '../../config/database.php';

try {
    // 1. Clear existing leaves for a clean state (Optional, maybe comment out if preserving data)
    // $pdo->exec("TRUNCATE TABLE ict_leave_requests");

    // 2. Get a valid user ID (assuming id 1 is admin/current user)
    // Or just pick the first user
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        die("No users found to assign leave to.");
    }

    // 3. Insert specific records for dashboard testing

    // Record A: Active Leave (Today is inside range) -> Should show on "ICT Staff" card as "on leave"
    // Assuming today matches CURDATE()
    $stmt = $pdo->prepare("INSERT INTO ict_leave_requests 
        (user_id, leave_type, start_date, end_date, reason, status) 
        VALUES (?, 'annual', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Annual Break', 'approved')");
    $stmt->execute([$userId]);

    // Record B: Pending Request -> Should show on "Leave Requests" card
    $stmt = $pdo->prepare("INSERT INTO ict_leave_requests 
        (user_id, leave_type, start_date, end_date, reason, status) 
        VALUES (?, 'sick', DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Medical checkup', 'pending')");
    $stmt->execute([$userId]);

    // Record C: Upcoming Approved -> Should show on "Upcoming Approved Leaves" if widget exists (though I didn't see that specific widget in index.php view, only query)
    $stmt = $pdo->prepare("INSERT INTO ict_leave_requests 
        (user_id, leave_type, start_date, end_date, reason, status) 
        VALUES (?, 'emergency', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'Emergency', 'approved')");
    $stmt->execute([$userId]);

    echo "Seed data inserted successfully.";

} catch (PDOException $e) {
    die("Seed Error: " . $e->getMessage());
}
?>