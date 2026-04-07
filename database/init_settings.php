<?php
require_once 'config/database.php';

try {
    // 1. Create Table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'system_settings' created successfully.\n";

    // 2. Insert Default Values
    $defaults = [
        'contact_back_office_ext' => '104',
        'contact_duty_mobile' => '0743 606 108',
        'contact_duty_mobile_note' => 'Calls only when unavailable in office'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    echo "Default settings populated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
