<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = "System Maintenance";
include '../../includes/header.php';

// Flash Messages
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<div class="space-y-8">
    <?php if ($success): ?>
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl border border-emerald-100 animate-vibrant-pop">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php
    function getDirSize($path)
    {
        if (!is_dir($path))
            return 0;
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    // Stats
    $dbSize = 0;
    $stmt = $pdo->query("SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
    $dbSize = $stmt->fetchColumn();

    $uploadsSize = getDirSize('../../uploads');
    $backupDir = '../../backups';
    if (!is_dir($backupDir))
        mkdir($backupDir, 0777, true);

    $backups = array_diff(scandir($backupDir), array('.', '..'));
    rsort($backups); // Newest first
    ?>

    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">System Maintenance</h1>
            <p class="text-slate-500 mt-2">Manage system backups and monitor data health.</p>
        </div>
        <a href="generate_backup.php"
            class="inline-flex items-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-lg shadow-primary-600/20 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            Generate Full Backup
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Database Size</p>
            <p class="text-2xl font-black text-slate-800"><?= number_format($dbSize / 1024 / 1024, 2) ?> MB</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Uploads Size</p>
            <p class="text-2xl font-black text-slate-800"><?= number_format($uploadsSize / 1024 / 1024, 2) ?> MB</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Backups Found</p>
            <p class="text-2xl font-black text-slate-800"><?= count($backups) ?></p>
        </div>
    </div>

    <!-- Backups Table & Restore Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 uppercase text-xs tracking-wider">Available Backups</h3>
                <span class="text-[10px] text-slate-400 italic">Store these externally</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase">Filename</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase">Size</th>
                            <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400">No backups found locally.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $file): ?>
                                <?php
                                $filePath = $backupDir . '/' . $file;
                                $size = filesize($filePath);
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors text-xs">
                                    <td class="px-6 py-4 font-medium text-slate-700"><?= htmlspecialchars($file) ?></td>
                                    <td class="px-6 py-4 text-slate-500"><?= number_format($size / 1024 / 1024, 2) ?> MB</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="download.php?file=<?= urlencode($file) ?>"
                                            class="text-primary-600 hover:text-primary-800 font-bold">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-red-50 bg-red-50/30">
                <h3 class="font-bold text-red-700 uppercase text-xs tracking-wider">Restore from Backup</h3>
            </div>
            <div class="p-6">
                <div class="bg-red-50 text-red-700 p-4 rounded-xl text-xs mb-6 border border-red-100">
                    <p class="font-bold mb-2 uppercase flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        Critical Warning
                    </p>
                    Restoring will <strong>completely overwrite</strong> your current database and uploaded files. This
                    action cannot be undone.
                </div>

                <form action="restore_process.php" method="POST" enctype="multipart/form-data"
                    onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This will wipe all current data and replace it with the backup content.')">
                    <div class="space-y-4">
                        <div class="relative group cursor-pointer">
                            <input type="file" name="backup_zip" accept=".zip" required
                                class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer">
                            <div
                                class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center group-hover:border-primary-400 transition-all">
                                <svg class="w-8 h-8 text-slate-300 mx-auto mb-2 group-hover:text-primary-500"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                                <p class="text-xs font-bold text-slate-600">Click to upload .zip backup</p>
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-200">
                            Perform Recovery
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>