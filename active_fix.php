<?php
require_once 'config/database.php';

echo "<html><body style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Database Repair Tool</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Force Create Table
    echo "<p>Attempting to create table 'external_links'...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS external_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        url VARCHAR(255) NOT NULL,
        category ENUM('Network', 'Security', 'Vendor', 'Other') DEFAULT 'Other',
        icon VARCHAR(50) DEFAULT 'link',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "<p style='color: green; font-weight: bold;'>✔ CREATE statement executed.</p>";

    // 2. Check if it actually exists now
    $check = $pdo->query("SHOW TABLES LIKE 'external_links'")->fetchAll();
    if (count($check) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✔ Verification Passed: Table exists.</p>";

        // 3. Insert Data if empty
        $count = $pdo->query("SELECT COUNT(*) FROM external_links")->fetchColumn();
        if ($count == 0) {
            echo "<p>Table is empty. Inserting default data...</p>";
            $insert = "INSERT INTO external_links (name, url, category, icon) VALUES 
                ('Unifi Controller', 'https://unifi.ui.com', 'Network', 'wifi'),
                ('PBX Portal', 'http://172.16.1.10', 'Network', 'phone'),
                ('CCTV NVR', 'http://172.16.1.20', 'Security', 'video-camera')";
            $pdo->exec($insert);
            echo "<p style='color: green;'>✔ Default data inserted.</p>";
        } else {
            echo "<p>Table already contains $count rows.</p>";
        }

    } else {
        echo "<h2 style='color: red;'>❌ ERROR: Table creation failed silently. Check database permissions.</h2>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ PDO Exception: " . $e->getMessage() . "</h2>";
}

echo "<hr>";
echo "<a href='index.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Return to Dashboard</a>";
echo "</body></html>";
?>