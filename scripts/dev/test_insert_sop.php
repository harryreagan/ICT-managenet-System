<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->prepare("INSERT INTO sop_documents (title, category, content, version, author, visibility) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Test CLI', 'Test', 'Test Content', '1.0', 'Admin', 'public']);
    echo "Success. Inserted ID: " . $pdo->lastInsertId();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
