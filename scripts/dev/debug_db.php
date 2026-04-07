<?php
require_once 'config/database.php';

echo "<h1>Database Diagnostic Tool</h1>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<p><strong>User:</strong> " . DB_USER . "</p>";

try {
    echo "<h2>Checking Connectivity...</h2>";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected to database successfully.</p>";

    echo "<h2>Checking Table: issue_attachments</h2>";

    // Check if table exists using SHOW TABLES
    $stmt = $pdo->query("SHOW TABLES LIKE 'issue_attachments'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Table 'issue_attachments' exists.</p>";

        // Describe structure
        echo "<h3>Table Structure:</h3><pre>";
        $stmt = $pdo->query("DESCRIBE issue_attachments");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";

    } else {
        echo "<p style='color:red'>❌ Table 'issue_attachments' DOES NOT EXIST.</p>";
        echo "<p>Attempting to create it now...</p>";

        $sql = "CREATE TABLE issue_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(50),
            file_size INT,
            uploaded_by INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Table 'issue_attachments' CREATED successfully.</p>";
            echo "<p>Please refresh the edit page now.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Failed to create table: " . $e->getMessage() . "</p>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>