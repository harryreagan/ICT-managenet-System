<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE inventory_items");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Hardware Assets ---\n";
$stmt = $pdo->query("DESCRIBE hardware_assets");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Troubleshooting Logs ---\n";
$stmt = $pdo->query("DESCRIBE troubleshooting_logs");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Networks ---\n";
$stmt = $pdo->query("DESCRIBE networks");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Notifications ---\n";
$stmt = $pdo->query("DESCRIBE notifications");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- ICT Leave Requests ---\n";
$stmt = $pdo->query("DESCRIBE ict_leave_requests");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- External Systems ---\n";
$stmt = $pdo->query("DESCRIBE external_systems");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Maintenance Tasks ---\n";
$stmt = $pdo->query("DESCRIBE maintenance_tasks");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Audit Logs ---\n";
$stmt = $pdo->query("DESCRIBE audit_logs");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Power Systems ---\n";
$stmt = $pdo->query("DESCRIBE power_systems");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Static Devices ---\n";
$stmt = $pdo->query("DESCRIBE static_devices");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- Renewals ---\n";
$stmt = $pdo->query("DESCRIBE renewals");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
