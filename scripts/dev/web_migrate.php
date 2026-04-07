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

echo "<h1>Database Migration Tool</h1>";

try {
    echo "Connecting to database...<br>";
    $columns = [
        'billing_cycle' => "ALTER TABLE renewals ADD COLUMN billing_cycle ENUM('monthly', 'yearly') DEFAULT 'yearly'",
        'is_recurring' => "ALTER TABLE renewals ADD COLUMN is_recurring TINYINT(1) DEFAULT 0",
        'payment_status' => "ALTER TABLE renewals ADD COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'"
    ];

    foreach ($columns as $col => $sql) {
        if (!checkColumn($pdo, 'renewals', $col)) {
            echo "⚠️ Column '$col' is MISSING. Attempting to add... ";
            $pdo->exec($sql);
            echo "✅ SUCCESS<br>";
        } else {
            echo "✅ Column '$col' already exists.<br>";
        }
    }

    echo "<h3>Final Table Structure:</h3><pre>";
    $stmt = $pdo->query("DESCRIBE renewals");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>ERROR: " . $e->getMessage() . "</h3>";
}
?>