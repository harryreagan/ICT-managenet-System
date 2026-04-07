<?php
require_once '../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS issue_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_issue_id INT NOT NULL,
        target_issue_id INT NOT NULL,
        link_type ENUM('relates_to', 'blocks', 'blocked_by', 'duplicates', 'duplicated_by') DEFAULT 'relates_to',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (source_issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (target_issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_link (source_issue_id, target_issue_id)
    )";
    $pdo->exec($sql);
    echo "Table issue_links created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>