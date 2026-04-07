<?php
require_once 'config/database.php';

try {
    // Check again to be safe
    $stmt = $pdo->prepare("SELECT id FROM networks WHERE name LIKE '%Guest%' LIMIT 1");
    $stmt->execute();
    $id = $stmt->fetchColumn();

    if (!$id) {
        $pdo->prepare("INSERT INTO networks (name, gateway, subnet, vlan_tag, hotspot_ssid, wifi_password) VALUES ('Guest WiFi', '10.0.0.1', '10.0.0.0/16', 20, 'Premiere-Guest', 'Welcome2026')")->execute();
        echo "Guest WiFi network restored successfully.\n";
    } else {
        echo "Guest WiFi already exists with ID: $id\n";
    }

    // Verify
    $stmt = $pdo->query("SELECT id, name, hotspot_ssid FROM networks WHERE name LIKE '%Guest%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>