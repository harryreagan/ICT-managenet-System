<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting test...\n";
require_once 'config/database.php';
echo "Database included.\n";
require_once 'includes/functions.php';
echo "Functions included.\n";
require_once 'includes/auth.php';
echo "Auth included.\n";
echo "Test finished successfully.\n";
