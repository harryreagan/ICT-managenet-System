<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
echo "Core files included successfully. User ID: " . ($_SESSION['user_id'] ?? 'Not Logged In');
