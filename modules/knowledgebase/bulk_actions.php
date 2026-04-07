<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];
$value = $_POST['value'] ?? '';

if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No issues selected']);
    exit;
}

$count = 0;

try {
    $pdo->beginTransaction();

    foreach ($ids as $id) {
        $id = (int) $id;

        // Fetch current state for logging
        $stmt = $pdo->prepare("SELECT * FROM troubleshooting_logs WHERE id = ?");
        $stmt->execute([$id]);
        $log = $stmt->fetch();

        if (!$log)
            continue;

        if ($action === 'delete') {
            $delStmt = $pdo->prepare("DELETE FROM troubleshooting_logs WHERE id = ?");
            $delStmt->execute([$id]);
            // No activity log possible for deleted issue, maybe audit log?
            // Legacy audit
            $auditStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'DELETE', ?)");
            $auditStmt->execute([$_SESSION['user_id'], "Deleted ticket #$id via bulk action"]);

        } elseif ($action === 'status') {
            if ($log['status'] !== $value) {
                $updStmt = $pdo->prepare("UPDATE troubleshooting_logs SET status = ? WHERE id = ?");
                $updStmt->execute([$value, $id]);

                // Log
                $actStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, 'status_changed', ?, ?, ?)");
                $desc = "Bulk update: Status changed to " . ucfirst(str_replace('_', ' ', $value));
                $actStmt->execute([$id, $_SESSION['user_id'], $desc, $log['status'], $value]);
            }

        } elseif ($action === 'priority') {
            if ($log['priority'] !== $value) {
                $updStmt = $pdo->prepare("UPDATE troubleshooting_logs SET priority = ? WHERE id = ?");
                $updStmt->execute([$value, $id]);

                // Log
                $actStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, 'updated', ?, ?, ?)");
                $desc = "Bulk update: Priority changed to " . ucfirst($value);
                $actStmt->execute([$id, $_SESSION['user_id'], $desc, $log['priority'], $value]);
            }

        } elseif ($action === 'assign') {
            if ($log['assigned_to'] !== $value) {
                $updStmt = $pdo->prepare("UPDATE troubleshooting_logs SET assigned_to = ? WHERE id = ?");
                $updStmt->execute([$value, $id]);

                // Log
                $actStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, 'assigned', ?, ?, ?)");
                $desc = "Bulk update: Assigned to " . ucfirst(str_replace('_', ' ', $value));
                $actStmt->execute([$id, $_SESSION['user_id'], $desc, $log['assigned_to'], $value]);
            }
        }
        $count++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Successfully updated $count issues"]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>