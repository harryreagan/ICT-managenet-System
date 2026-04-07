<?php
require_once '../../config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ict_leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        leave_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_by INT DEFAULT NULL,
        rejection_reason TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Table 'ict_leave_requests' created successfully.";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>