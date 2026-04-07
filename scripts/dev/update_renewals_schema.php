<?php
require_once 'config/database.php';

try {
    // Add billing_cycle
    $pdo->exec("ALTER TABLE renewals ADD COLUMN IF NOT EXISTS billing_cycle ENUM('monthly', 'yearly') DEFAULT 'yearly'");
    echo "✅ Added billing_cycle column\n";

    // Add is_recurring
    $pdo->exec("ALTER TABLE renewals ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) DEFAULT 0");
    echo "✅ Added is_recurring column\n";

    // Add payment_status
    $pdo->exec("ALTER TABLE renewals ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'");
    echo "✅ Added payment_status column\n";

} catch (PDOException $e) {
    echo "❌ Error updating schema: " . $e->getMessage() . "\n";
}
?>