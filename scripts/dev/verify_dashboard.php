<?php
require_once 'config/database.php';

// Insert test data
$pdo->exec("INSERT INTO ip_assignments (network_id, ip_address, device_name, status) VALUES (1, '172.172.88.200', 'DASHBOARD-TEST-IP', 'static')");

// 26. Static Devices Count (Unified)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT ip_address FROM static_devices
        UNION
        SELECT ip_address FROM ip_assignments WHERE status = 'static'
    ) as unified_ips
");
$count = $stmt->fetchColumn();
echo "Unified Count: $count\n";

// 27. Recent (should show the test one)
$stmt = $pdo->query("
    (SELECT device_name, ip_address, 'device' as source, created_at FROM static_devices)
    UNION 
    (SELECT device_name, ip_address, 'assignment' as source, created_at FROM ip_assignments WHERE status = 'static' AND ip_address NOT IN (SELECT ip_address FROM static_devices))
    ORDER BY created_at DESC LIMIT 1
");
$recent = $stmt->fetch();
echo "Most Recent: {$recent['device_name']} ({$recent['ip_address']})\n";

// Cleanup
$pdo->exec("DELETE FROM ip_assignments WHERE device_name = 'DASHBOARD-TEST-IP'");
echo "Cleanup done.\n";
