<?php
require_once 'config/database.php';
echo "Total notifications: " . $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn() . "\n";
echo "Unread notifications: " . $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn() . "\n";
