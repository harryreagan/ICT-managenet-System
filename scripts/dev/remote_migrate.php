<?php
// HARDCODED REMOTE CONNECTION TEST
try {
    $dsn = "mysql:host=172.16.1.132;dbname=hotel_ict;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "CONNECTED TO REMOTE DB\n";

    $cols = [
        'billing_cycle' => "ALTER TABLE renewals ADD COLUMN IF NOT EXISTS billing_cycle ENUM('monthly', 'yearly') DEFAULT 'yearly'",
        'is_recurring' => "ALTER TABLE renewals ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) DEFAULT 0",
        'payment_status' => "ALTER TABLE renewals ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'"
    ];

    foreach ($cols as $col => $sql) {
        $pdo->exec($sql);
        echo "PROCESSED $col\n";
    }

    echo "REMOTE MIGRATION COMPLETE\n";
} catch (Exception $e) {
    echo "REMOTE DB ERROR: " . $e->getMessage() . "\n";
}
?>