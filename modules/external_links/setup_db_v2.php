<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_ict;charset=utf8mb4', 'root', '');
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
    echo "Table 'external_links' created successfully.\n";

    // Check count and insert defaults if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM external_links");
    if ($stmt->fetchColumn() == 0) {
        $insert = "INSERT INTO external_links (name, url, category, icon) VALUES 
            ('Unifi Controller', 'https://unifi.ui.com', 'Network', 'wifi'),
            ('PBX Portal', 'http://172.16.1.10', 'Network', 'phone'),
            ('CCTV NVR', 'http://172.16.1.20', 'Security', 'video-camera')";
        $pdo->exec($insert);
        echo "Default data inserted.\n";
    } else {
        echo "Table already has data.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>