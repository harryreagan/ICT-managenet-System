<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Increase execution time for extraction and DB rebuild
set_time_limit(600);
ini_set('memory_limit', '512M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['backup_zip'])) {
    header("Location: index.php");
    exit;
}

$zipFile = $_FILES['backup_zip']['tmp_name'];
$workDir = '../../backups/restore_temp_' . time();

try {
    if (!mkdir($workDir, 0777, true)) {
        throw new Exception("Failed to create temporary directory for restoration.");
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== TRUE) {
        throw new Exception("Failed to open backup ZIP file.");
    }

    $zip->extractTo($workDir);
    $zip->close();

    // 1. Restore Database
    $sqlFiles = glob($workDir . '/*.sql');
    if (empty($sqlFiles)) {
        throw new Exception("No database dump (.sql) found in the backup.");
    }

    $sqlFile = $sqlFiles[0];
    $sqlContent = file_get_contents($sqlFile);

    // Disable foreign key checks for the restoration session
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // Execute the SQL dump
    // Note: If the dump is very large, this might need to be split. 
    // But for this scale, pdo->exec works fine.
    $pdo->exec($sqlContent);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // 2. Restore Uploads
    $backupUploadsDir = $workDir . '/uploads';
    $mainUploadsDir = '../../uploads';

    if (is_dir($backupUploadsDir)) {
        // Recursive helper to copy and replace
        function recursiveCopy($source, $dest)
        {
            if (!is_dir($dest))
                mkdir($dest, 0777, true);
            foreach (scandir($source) as $file) {
                if ($file === '.' || $file === '..')
                    continue;
                $srcPath = "$source/$file";
                $destPath = "$dest/$file";
                if (is_dir($srcPath)) {
                    recursiveCopy($srcPath, $destPath);
                } else {
                    copy($srcPath, $destPath);
                }
            }
        }

        // We don't wipe the main uploads entirely (safer), just overwrite/merge
        recursiveCopy($backupUploadsDir, $mainUploadsDir);
    }

    // Cleanup
    function deleteDir($dirPath)
    {
        if (!is_dir($dirPath))
            return;
        foreach (scandir($dirPath) as $file) {
            if ($file === '.' || $file === '..')
                continue;
            $fullPath = "$dirPath/$file";
            if (is_dir($fullPath))
                deleteDir($fullPath);
            else
                unlink($fullPath);
        }
        rmdir($dirPath);
    }
    deleteDir($workDir);

    logActivity($pdo, $_SESSION['user_id'], "System restoration performed from backup zip.");

    $_SESSION['success'] = "System restored successfully! Your data and files have been recovered.";
    header("Location: index.php");
    exit;

} catch (Exception $e) {
    // Attempt cleanup
    if (is_dir($workDir))
        deleteDir($workDir);
    die("<h2 style='color:red'>Restoration Failed</h2><p>" . htmlspecialchars($e->getMessage()) . "</p><a href='index.php'>Back to Maintenance</a>");
}
