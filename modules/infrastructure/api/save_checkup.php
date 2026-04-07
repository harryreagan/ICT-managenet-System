<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_key = $_POST['item_key'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $status = $_POST['status'] ?? 'operational';
    $notes = $_POST['notes'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($item_key)) {
        header("Location: ../index.php");
        exit();
    }

    try {
        // 1. Update the latest status (Upsert)
        $stmt = $pdo->prepare("INSERT INTO facility_checks (item_key, item_name, status, notes, checked_by, last_check_at) 
                                VALUES (?, ?, ?, ?, ?, NOW()) 
                                ON DUPLICATE KEY UPDATE 
                                status = VALUES(status), 
                                notes = VALUES(notes), 
                                checked_by = VALUES(checked_by), 
                                last_check_at = NOW()");
        $stmt->execute([$item_key, $item_name, $status, $notes, $user_id]);

        // 2. Record the history log (Always Insert)
        $stmt = $pdo->prepare("INSERT INTO facility_check_logs (item_key, item_name, status, notes, checked_by, checked_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$item_key, $item_name, $status, $notes, $user_id]);

        // 3. Also update handover notes and trigger notifications if it's a critical or warning check
        if ($status !== 'operational') {
            $handover_content = "[FACILITY ALERT] " . $item_name . " marked as " . strtoupper($status) . ". Notes: " . $notes;
            $stmt = $pdo->prepare("INSERT INTO handover_notes (user_id, note_category, content, priority, status) VALUES (?, 'Daily Update', ?, ?, 'active')");
            $priority = ($status === 'faulty') ? 'high' : 'medium';
            $stmt->execute([$user_id, $handover_content, $priority]);

            // Create system-wide notification for admins
            $notif_msg = "[FACILITY ALERT] " . $item_name . " marked as " . strtoupper($status) . " by " . $_SESSION['username'];
            $notif_type = ($status === 'faulty') ? 'alert' : 'warning';
            createNotification($pdo, $notif_msg, $notif_type, '/ict/modules/infrastructure/index.php', 'admin');

            // Audit Log for alerts
            logActivity($pdo, $user_id, "FACILITY_ALERT", "Facility item $item_name marked as $status. Notes: $notes");
        } else {
            // General Audit Log for normal checkups (optional, but good for attribution)
            logActivity($pdo, $user_id, "FACILITY_CHECK", "Checked $item_name - Status: $status");
        }

        header("Location: ../index.php?success=Checkup recorded successfully");
    } catch (PDOException $e) {
        // Table creation fails if they don't exist
        if ($e->getCode() == '42S02') {
            // Table doesn't exist, try to create both then retry
            $pdo->exec("CREATE TABLE IF NOT EXISTS facility_checks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_key VARCHAR(50) NOT NULL UNIQUE,
                item_name VARCHAR(100) NOT NULL,
                status ENUM('operational', 'warning', 'faulty') DEFAULT 'operational',
                last_check_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                checked_by INT,
                notes TEXT,
                FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_item_key (item_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS facility_check_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_key VARCHAR(50) NOT NULL,
                item_name VARCHAR(100) NOT NULL,
                status ENUM('operational', 'warning', 'faulty') DEFAULT 'operational',
                checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                checked_by INT,
                notes TEXT,
                FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_item_key_history (item_key),
                INDEX idx_checked_at (checked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Retry BOTH logs
            $stmt = $pdo->prepare("INSERT INTO facility_checks (item_key, item_name, status, notes, checked_by, last_check_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW()) 
                                    ON DUPLICATE KEY UPDATE 
                                    status = VALUES(status), 
                                    notes = VALUES(notes), 
                                    checked_by = VALUES(checked_by), 
                                    last_check_at = NOW()");
            $stmt->execute([$item_key, $item_name, $status, $notes, $user_id]);

            $stmt = $pdo->prepare("INSERT INTO facility_check_logs (item_key, item_name, status, notes, checked_by, checked_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$item_key, $item_name, $status, $notes, $user_id]);
            header("Location: ../index.php?success=Checkup recorded successfully");
        } else {
            die("Error saving checkup: " . $e->getMessage());
        }
    }
} else {
    header("Location: ../index.php");
}
?>