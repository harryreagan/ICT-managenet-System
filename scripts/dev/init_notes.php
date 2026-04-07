<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS quick_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        content TEXT NOT NULL,
        is_done BOOLEAN DEFAULT FALSE,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'quick_notes' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>