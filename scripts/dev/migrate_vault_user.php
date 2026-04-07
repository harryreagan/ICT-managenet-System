<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Add user_id column if it doesn't exist
    $pdo->exec("ALTER TABLE credential_vault ADD COLUMN user_id INT NULL AFTER id");

    // Add foreign key constraint if users table exists and we want to be strict
    // $pdo->exec("ALTER TABLE credential_vault ADD CONSTRAINT fk_vault_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");

    echo "Migration successful: user_id column added to credential_vault.\n";
} catch (PDOException $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}
