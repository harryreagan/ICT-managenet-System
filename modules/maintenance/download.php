<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$file = $_GET['file'] ?? '';
$backupDir = realpath('../../backups');
$filePath = realpath($backupDir . '/' . $file);

// Security: Prevent Directory Traversal
if (!$filePath || strpos($filePath, $backupDir) !== 0 || !file_exists($filePath)) {
    die("Invalid file request.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
