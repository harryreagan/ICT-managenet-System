<?php
require_once 'config/database.php';

try {
    // 1. Create table if it doesn't exist (failsafe)
    $sql = "CREATE TABLE IF NOT EXISTS facility_checks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_key VARCHAR(50) NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        status ENUM('operational', 'warning', 'faulty') DEFAULT 'operational',
        last_check_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        checked_by INT,
        notes TEXT,
        FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_item_key (item_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Table facility_checks ensured.\n";

    // 2. Seed initial records
    $seeds = [
        ['solar', 'Solar & Battery Backups'],
        ['charging', 'Car Charging Station'],
        ['gym', 'Gym Equipment & Connectivity'],
        ['playground', 'Kids Playground (WiFi/POS)'],
        ['ac', 'Server Room AC Units']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO facility_checks (item_key, item_name, status) VALUES (?, ?, 'operational')");
    foreach ($seeds as $seed) {
        $stmt->execute($seed);
    }
    echo "Initial facility records seeded.\n";

    // 3. Update Enum columns (Power Systems system_type)
    // Note: This requires ALTER TABLE which varies by DB state, but we'll try to add the new ones
    $pdo->exec("ALTER TABLE power_systems MODIFY COLUMN system_type ENUM('Main Utility', 'UPS Cluster', 'Solar Array', 'Battery Storage', 'EV Charging', 'HVAC/AC') NOT NULL");
    echo "Power systems ENUM updated.\n";

    // 4. Update Hardware Assets category
    $pdo->exec("ALTER TABLE hardware_assets MODIFY COLUMN category ENUM('Access Point', 'Switch', 'Workstation', 'Server', 'Printer', 'CCTV Camera', 'Computer/PC', 'EV Charger', 'AC Unit', 'Inverter', 'Other') DEFAULT 'Workstation'");
    echo "Hardware assets ENUM updated.\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>