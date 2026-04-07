<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bypassing config to be absolutely sure
$host = 'localhost';
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

echo "Connecting to database... ";
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected.<br>";

    echo "Checking for 'target_role' in 'notifications'... ";
    $stmt = $pdo->query("DESCRIBE notifications");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('target_role', $cols)) {
        echo "MISSING. Adding column... ";
        $pdo->exec("ALTER TABLE notifications ADD COLUMN target_role ENUM('admin', 'staff', 'all') DEFAULT 'all' AFTER type");
        echo "DONE.<br>";
    } else {
        echo "EXISTS. No action needed.<br>";
    }

    echo "<br><b>Dashboard should work now!</b> Try loading index.php";

} catch (\PDOException $e) {
    echo "FAILED: " . $e->getMessage();
}
?>