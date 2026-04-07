<?php
require_once __DIR__ . '/../../config/database.php';

try {
    echo "Starting Bulk Asset Tracking Migration (v4)...<br>";

    // 1. Add fields to hardware_assets if they don't exist
    $hardware_columns = [
        "manufacturer" => "VARCHAR(255) DEFAULT NULL AFTER serial_number",
        "quantity" => "INT DEFAULT 1 AFTER condition_notes"
    ];

    foreach ($hardware_columns as $col => $def) {
        try {
            $result = $pdo->query("SHOW COLUMNS FROM hardware_assets LIKE '$col'");
            if ($result->rowCount() == 0) {
                echo "Adding column '$col' to hardware_assets... ";
                $pdo->exec("ALTER TABLE hardware_assets ADD COLUMN $col $def");
                echo "Done.<br>";
            } else {
                echo "Column '$col' already exists in hardware_assets.<br>";
            }
        } catch (PDOException $e) {
            echo "Error adding column '$col' to hardware_assets: " . $e->getMessage() . "<br>";
        }
    }

    // 2. Create asset_issuances table for tracking individual/bulk signouts
    $create_issuances_table = "
        CREATE TABLE IF NOT EXISTS asset_issuances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            quantity_issued INT DEFAULT 1,
            assignment_type ENUM('internal', 'external') DEFAULT 'internal',
            assigned_to_user_id INT NULL,
            assigned_guest_name VARCHAR(255) NULL,
            assigned_guest_contact VARCHAR(255) NULL,
            assigned_conference VARCHAR(255) NULL,
            issued_at DATETIME NOT NULL,
            status ENUM('issued', 'returned') DEFAULT 'issued',
            returned_at DATETIME NULL,
            returned_by_name VARCHAR(255) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (asset_id) REFERENCES hardware_assets(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    try {
        echo "Creating asset_issuances table... ";
        $pdo->exec($create_issuances_table);
        echo "Done.<br>";
    } catch (PDOException $e) {
        echo "Error creating asset_issuances table: " . $e->getMessage() . "<br>";
    }

    // 3. Migrate any existing issued assets into the new table
    echo "Migrating existing assignments... ";
    $stmt = $pdo->query("SELECT * FROM hardware_assets WHERE assignment_status = 'issued'");
    $issued_assets = $stmt->fetchAll();

    foreach ($issued_assets as $asset) {
        // Check if already migrated to avoid duplicates on re-run
        $check = $pdo->prepare("SELECT id FROM asset_issuances WHERE asset_id = ? AND status = 'issued'");
        $check->execute([$asset['id']]);

        if ($check->rowCount() == 0) {
            $assignment_type = empty($asset['assigned_guest_name']) ? 'internal' : 'external';
            $issued_at = $asset['assignment_date'] ?? date('Y-m-d H:i:s');

            $insert = $pdo->prepare("
                INSERT INTO asset_issuances (
                    asset_id, quantity_issued, assignment_type, assigned_to_user_id, 
                    assigned_guest_name, assigned_guest_contact, assigned_conference, 
                    issued_at, status
                ) VALUES (?, 1, ?, ?, ?, ?, ?, ?, 'issued')
            ");

            $insert->execute([
                $asset['id'],
                $assignment_type,
                $asset['assigned_to_user_id'],
                $asset['assigned_guest_name'],
                $asset['assigned_guest_contact'],
                $asset['assigned_conference'],
                $issued_at
            ]);
        }
    }
    echo "Done.<br>";

    // We do NOT drop the old columns immediately just in case, but they are technically superseded.

    echo "<br>Migration (v4) completed successfully!<br>";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "<br>";
    die();
}
?>