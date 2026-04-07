<?php
require_once 'config/database.php';

try {
    echo "Updating Documentation Schema...\n";

    // 1. Update sop_documents
    $pdo->exec("ALTER TABLE sop_documents ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) AFTER author");
    $pdo->exec("ALTER TABLE sop_documents ADD COLUMN IF NOT EXISTS visibility ENUM('public', 'private') DEFAULT 'public' AFTER image_path");
    echo "Successfully updated 'sop_documents' table.\n";

    // 2. Update troubleshooting_logs
    $pdo->exec("ALTER TABLE troubleshooting_logs ADD COLUMN IF NOT EXISTS requester_username VARCHAR(255) AFTER id");
    $pdo->exec("ALTER TABLE troubleshooting_logs ADD COLUMN IF NOT EXISTS solution_image VARCHAR(255) AFTER resolution");
    echo "Successfully updated 'troubleshooting_logs' table.\n";

    echo "Documentation schema update complete.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
