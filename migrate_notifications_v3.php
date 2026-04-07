<?php
// Try to connect with 127.0.0.1 if localhost fails
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
    $pdo->exec("ALTER TABLE notifications ADD COLUMN target_user_id INT DEFAULT NULL AFTER target_role");
    echo "SUCCESS";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ALREADY_EXISTS";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}
