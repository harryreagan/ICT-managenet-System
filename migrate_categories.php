<?php
require_once 'config/database.php';

try {
    // Create categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        type ENUM('documentation', 'ticket', 'inventory', 'hardware') NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#6B7280',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insert default categories for documentation
    $defaults = [
        ['Network', 'documentation', 'Network infrastructure and connectivity', '#3B82F6'],
        ['Security', 'documentation', 'Security policies and procedures', '#EF4444'],
        ['Hardware', 'documentation', 'Hardware management and maintenance', '#8B5CF6'],
        ['Policy', 'documentation', 'General policies and guidelines', '#F59E0B'],
        ['SOP', 'documentation', 'Standard Operating Procedures', '#10B981'],
        ['Software Installation', 'documentation', 'Software deployment guides', '#06B6D4'],
        ['Software Documentation', 'documentation', 'Software usage and configuration', '#EC4899'],

        // Ticket categories
        ['Hardware Issue', 'ticket', 'Hardware problems and failures', '#EF4444'],
        ['Software Issue', 'ticket', 'Software bugs and errors', '#F59E0B'],
        ['Network Issue', 'ticket', 'Network connectivity problems', '#3B82F6'],
        ['Access Request', 'ticket', 'Access and permission requests', '#10B981'],
        ['General Inquiry', 'ticket', 'General questions and support', '#6B7280'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, type, description, color) VALUES (?, ?, ?, ?)");
    foreach ($defaults as $cat) {
        $stmt->execute($cat);
    }

    echo "✓ Categories table created successfully!\n";
    echo "✓ Default categories inserted!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>