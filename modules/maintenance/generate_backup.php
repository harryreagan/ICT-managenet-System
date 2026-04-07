<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    die("<b>Error:</b> The PHP <code>zip</code> extension is not enabled on your server.<br><br>" .
        "<b>How to fix:</b><br>" .
        "1. Open your XAMPP Control Panel.<br>" .
        "2. Click 'Config' next to Apache and select <b>php.ini</b>.<br>" .
        "3. Search for <code>;extension=zip</code> and remove the semicolon (<code>extension=zip</code>).<br>" .
        "4. Save the file and <b>Restart Apache</b>.");
}

// Increase execution time for large databases/backups
set_time_limit(300);
ini_set('memory_limit', '512M');

$backupDir = '../../backups';
$timestamp = date('Y-m-d_H-i-s');
$dbFileName = "db_dump_$timestamp.sql";
$zipFileName = "ICT_FULL_BACKUP_$timestamp.zip";
$zipPath = "$backupDir/$zipFileName";

try {
    // 1. Database Dump (Pure PHP implementation)
    $sqlDump = "-- Dallas Premiere ICT Management System Database Backup\n";
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        // Structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sqlDump .= "\n\n-- Structure for $table\n";
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlDump .= $row[1] . ";\n\n";

        // Data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_values($row);
            $values = array_map(function ($v) use ($pdo) {
                if ($v === null)
                    return 'NULL';
                return $pdo->quote($v);
            }, $values);
            $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
    }
    $sqlDump .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    // 2. Create ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Cannot create ZIP file.");
    }

    // Add DB Dump
    $zip->addFromString($dbFileName, $sqlDump);

    // Add Uploads
    $uploadDir = realpath('../../uploads');
    if (is_dir($uploadDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'uploads/' . substr($filePath, strlen($uploadDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    $zip->close();

    // Log the activity
    logActivity($pdo, $_SESSION['user_id'], "Generated full system backup: $zipFileName");

    $_SESSION['success'] = "Backup created successfully: $zipFileName";
    header("Location: index.php");
    exit;

} catch (Exception $e) {
    die("Error during backup: " . $e->getMessage());
}
