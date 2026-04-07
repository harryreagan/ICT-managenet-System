<?php
require_once __DIR__ . '/../../config/database.php';

try {
    echo "Starting Asset Tracking Guest Issuance Migration...<br>";

    // Add guest tracking fields to hardware_assets
    $hardware_columns = [
        "assigned_guest_name" => "VARCHAR(255) DEFAULT NULL AFTER assigned_to_user_id",
        "assigned_guest_contact" => "VARCHAR(255) DEFAULT NULL AFTER assigned_guest_name",
        "assigned_conference" => "VARCHAR(255) DEFAULT NULL AFTER assigned_guest_contact"
    ];

    foreach ($hardware_columns as $col => $def) {
        try {
            // Check if column exists
            $result = $pdo->query("SHOW COLUMNS FROM hardware_assets LIKE '$col'");
            if ($result->rowCount() == 0) {
                echo "Adding column '$col' to hardware_assets... ";
                $pdo->exec("ALTER TABLE hardware_assets ADD COLUMN $col $def");
                echo "Done.<br>";
            } else {
                echo "Column '$col' already exists in hardware_assets. Skipping.<br>";
            }
        } catch (PDOException $e) {
            echo "Error adding column '$col' to hardware_assets: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br>Migration completed successfully!<br>";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "<br>";
    die();
}
?>