<?php
// migrate_assets_v1.php
$host = '127.0.0.1';
$db = 'hotel_ict';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    echo "Starting migration...\n<br>";

    $queries = [
        "ALTER TABLE troubleshooting_logs ADD COLUMN staff_name VARCHAR(100) DEFAULT NULL AFTER requester_username",
        "ALTER TABLE troubleshooting_logs ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER staff_name",
        "ALTER TABLE hardware_assets MODIFY COLUMN category ENUM('Access Point', 'Switch', 'Workstation', 'Server', 'Printer', 'CCTV Camera', 'Computer/PC', 'EV Charger', 'AC Unit', 'Inverter', 'Mixer', 'Microphone', 'Extension', 'Other') DEFAULT 'Workstation'",
        "ALTER TABLE hardware_assets ADD COLUMN assigned_to_user_id INT DEFAULT NULL AFTER floor_id",
        "ALTER TABLE hardware_assets ADD COLUMN assignment_date DATETIME DEFAULT NULL AFTER assigned_to_user_id",
        "ALTER TABLE hardware_assets ADD COLUMN assignment_status ENUM('available', 'issued') DEFAULT 'available' AFTER assignment_date",
        "ALTER TABLE hardware_assets ADD CONSTRAINT fk_assets_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL",
        "CREATE TABLE IF NOT EXISTS asset_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            requester_username VARCHAR(100) NOT NULL,
            staff_name VARCHAR(100) NOT NULL,
            department VARCHAR(100) NOT NULL,
            asset_type ENUM('Mixer', 'Microphone', 'Extension', 'Other') NOT NULL,
            event_name VARCHAR(255),
            event_date DATE,
            details TEXT,
            status ENUM('pending', 'approved', 'issued', 'returned', 'rejected') DEFAULT 'pending',
            ict_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $i => $q) {
        try {
            $pdo->exec($q);
            echo "Query " . ($i + 1) . " executed successfully.<br>\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'Can\'t create table') !== false) {
                echo "Query " . ($i + 1) . " skipped (already applied/exists).<br>\n";
            } else {
                echo "Query " . ($i + 1) . " ERROR: " . $e->getMessage() . "<br>\n";
            }
        }
    }

    echo "Migration completed successfully!\n<br>";

} catch (\PDOException $e) {
    echo "CONNECTION ERROR: " . $e->getMessage() . "<br>\n";
}
