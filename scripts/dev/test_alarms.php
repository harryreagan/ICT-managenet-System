<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "Testing checkAlarms...\n";
checkAlarms($pdo);
echo "checkAlarms finished.\n";
