<?php
require_once 'config/database.php';

function checkColumn($pdo, $table, $column)
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

try {
    echo "Checking 'renewals' table structure...\n";
    $columns = ['billing_cycle', 'is_recurring', 'payment_status'];

    foreach ($columns as $col) {
        if (!checkColumn($pdo, 'renewals', $col)) {
            echo "⚠️ Column '$col' is MISSING. Attempting to add...\n";
            $sql = "";
            if ($col === 'billing_cycle') {
                $sql = "ALTER TABLE renewals ADD COLUMN billing_cycle ENUM('monthly', 'yearly') DEFAULT 'yearly'";
            } else if ($col === 'is_recurring') {
                $sql = "ALTER TABLE renewals ADD COLUMN is_recurring TINYINT(1) DEFAULT 0";
            } else if ($col === 'payment_status') {
                $sql = "ALTER TABLE renewals ADD COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'";
            }

            if ($sql) {
                $pdo->exec($sql);
                echo "✅ Added '$col' successfully.\n";
            }
        } else {
            echo "✅ Column '$col' already exists.\n";
        }
    }

    echo "\nFinal structure of 'renewals':\n";
    $stmt = $pdo->query("DESCRIBE renewals");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
}
?>