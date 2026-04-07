<?php
require_once 'config/database.php';

echo "<h1>External Links Table Installer</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    echo "<div style='color:green; font-weight:bold'>Success: Table 'external_links' created or already exists.</div>";

    // Insert Default Data
    $count = $pdo->query("SELECT COUNT(*) FROM external_links")->fetchColumn();
    if ($count == 0) {
        $insert = "INSERT INTO external_links (name, url, category, icon) VALUES 
            ('Unifi Controller', 'https://unifi.ui.com', 'Network', 'wifi'),
            ('PBX Portal', 'http://172.16.1.10', 'Network', 'phone'),
            ('CCTV NVR', 'http://172.16.1.20', 'Security', 'video-camera')";
        $pdo->exec($insert);
        echo "<div style='color:blue'>Default data inserted.</div>";
    } else {
        echo "<div style='color:gray'>Table already has $count records.</div>";
    }

} catch (PDOException $e) {
    echo "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
}

echo "<br><a href='index.php'>Return to Dashboard</a>";
?>