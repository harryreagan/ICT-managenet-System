<?php
require_once 'config/database.php';

try {
    echo "<style>body{font-family:sans-serif; line-height:1.6; color:#334155; max-width:800px; margin:40px auto; padding:20px;} pre{background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0; font-size:14px; overflow-x:auto;}</style>";
    echo "<h1>IT Handover Migration</h1>";
    echo "<pre>Starting migration...\n";

    // 1. Add is_on_duty to users if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_on_duty'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_on_duty BOOLEAN DEFAULT FALSE");
        echo "[SUCCESS] Added 'is_on_duty' column to users table.\n";
    } else {
        echo "[INFO] 'is_on_duty' column already exists in users table.\n";
    }

    // 2. Ensure handover_notes table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS handover_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shift_type ENUM('Morning', 'Afternoon', 'Night', 'Custom') DEFAULT 'Custom',
        content TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        status ENUM('active', 'archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "[SUCCESS] Handover notes table exists.\n";

    // 3. Handle column renaming: shift_type -> note_category
    $stmt = $pdo->query("SHOW COLUMNS FROM handover_notes LIKE 'shift_type'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE handover_notes CHANGE COLUMN shift_type note_category ENUM('Daily Update', 'Weekend Handover', 'Out of Office', 'Custom') DEFAULT 'Daily Update'");
        echo "[SUCCESS] Renamed 'shift_type' to 'note_category' for your 8-to-5 schedule.\n";
    } else {
        // If note_category already exists, just ensure the ENUM is correct
        $stmt = $pdo->query("SHOW COLUMNS FROM handover_notes LIKE 'note_category'");
        if ($stmt->fetch()) {
            $pdo->exec("ALTER TABLE handover_notes MODIFY COLUMN note_category ENUM('Daily Update', 'Weekend Handover', 'Out of Office', 'Custom') DEFAULT 'Daily Update'");
            echo "[SUCCESS] Verified 'note_category' column structure.\n";
        } else {
            // This shouldn't happen with CREATE TABLE IF NOT EXISTS above, but for safety:
            $pdo->exec("ALTER TABLE handover_notes ADD COLUMN note_category ENUM('Daily Update', 'Weekend Handover', 'Out of Office', 'Custom') DEFAULT 'Daily Update' AFTER user_id");
            echo "[SUCCESS] Added 'note_category' column.\n";
        }
    }

    echo "\nMigration completed successfully!\n</pre>";
    echo '<div style="margin-top:20px; display:flex; gap:12px;">';
    echo '<a href="modules/handover/index.php" style="padding:12px 24px; background:#0ea5e9; color:white; text-decoration:none; border-radius:8px; font-weight:bold;">Return to Handover</a>';
    echo '<a href="index.php" style="padding:12px 24px; background:#64748b; color:white; text-decoration:none; border-radius:8px; font-weight:bold;">Go to Dashboard</a>';
    echo '</div>';
} catch (Exception $e) {
    echo "<pre style='border-color:#f43f5e; background:#fff1f2; color:#be123c;'>";
    echo "<h1>Migration Failed</h1>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "</pre>";
}
