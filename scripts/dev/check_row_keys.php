<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT * FROM renewals LIMIT 1");
$row = $stmt->fetch();
if ($row) {
    echo "Keys in a renewal row:\n";
    print_r(array_keys($row));
} else {
    echo "No rows found in 'renewals' table.\n";
}
?>