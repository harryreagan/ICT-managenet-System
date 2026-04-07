<?php
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = (int) $_POST['ticket_id'];
    $username = $_SESSION['username'];

    // Security: Only allow closing if the ticket belongs to the logged-in user
    $stmt = $pdo->prepare("UPDATE troubleshooting_logs SET status = 'resolved' WHERE id = ? AND requester_username = ? AND status != 'closed'");

    if ($stmt->execute([$ticket_id, $username])) {
        // Log the activity
        logActivity($pdo, $_SESSION['user_id'], "User confirmed resolution for ticket #$ticket_id");

        // Redirect back with success (or just redirect)
        header("Location: index.php?msg=Ticket confirmed as resolved");
        exit;
    }
}

header("Location: index.php");
exit;
?>