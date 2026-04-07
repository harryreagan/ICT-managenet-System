<?php
require_once 'z:/htdocs/ict/config/database.php';

try {
    echo "Checking tables...\n";

    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'issue_attachments'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'issue_attachments' ALREADY EXISTS.\n";
    } else {
        echo "Table 'issue_attachments' DOES NOT EXIST. Creating...\n";

        $sql = "CREATE TABLE issue_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_type VARCHAR(100),
            file_size INT,
            uploaded_by INT,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE
        )";

        // Note: uploaded_by FK might fail if users table doesn't have id or engine mismatch, so I'll skip FK for uploaded_by for now to be safe, or just add column.

        $pdo->exec($sql);
        echo "Table 'issue_attachments' CREATED successfully.\n";
    }

    // Check columns just in case
    $stmt = $pdo->query("DESCRIBE issue_attachments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>