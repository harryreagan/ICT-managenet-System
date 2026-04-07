<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
$backup = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM backup_logs WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch();
}

$pageTitle = ($id ? "Edit Backup Entry" : "New Backup Entry");

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['asset_name'];
    $type = $_POST['backup_type'];
    $destination = $_POST['destination_disk'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE backup_logs SET asset_name = ?, backup_type = ?, destination_disk = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$name, $type, $destination, $status, $notes, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO backup_logs (asset_name, backup_type, destination_disk, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $destination, $status, $notes]);
    }
    $_SESSION['success'] = "Backup task updated successfully!";
    header("Location: /ict/modules/backups");
    exit;
}

include '../../includes/header.php';
?>

<div class="max-w-xl mx-auto fade-in-up">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">
            <?php echo $id ? "Edit Backup Task" : "Add Backup Task"; ?>
        </h1>
        <a href="index.php" class="text-slate-400 hover:text-slate-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="saas-card p-8">
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">System /
                    Asset Name</label>
                <input type="text" name="asset_name" required placeholder="e.g. Opera PMS DB Server"
                    value="<?php echo $backup ? htmlspecialchars($backup['asset_name']) : ''; ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Backup
                            Type</label>
                        <select name="backup_type"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                            <option value="Database" <?php echo $backup && $backup['backup_type'] === 'Database' ? 'selected' : ''; ?>>Database</option>
                            <option value="File Server" <?php echo $backup && $backup['backup_type'] === 'File Server' ? 'selected' : ''; ?>>File Server</option>
                            <option value="System Image" <?php echo $backup && $backup['backup_type'] === 'System Image' ? 'selected' : ''; ?>>System Image</option>
                            <option value="Cloud" <?php echo $backup && $backup['backup_type'] === 'Cloud' ? 'selected' : ''; ?>>Cloud Sync</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Destination
                            Disk / Storage</label>
                        <input type="text" name="destination_disk" placeholder="e.g. NAS-01, External HDD B"
                            value="<?php echo $backup ? htmlspecialchars($backup['destination_disk']) : ''; ?>"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Initial
                        Status</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="safe" <?php echo $backup && $backup['status'] === 'safe' ? 'selected' : ''; ?>>Safe
                            / Verified</option>
                        <option value="at_risk" <?php echo $backup && $backup['status'] === 'at_risk' ? 'selected' : ''; ?>>At Risk (Needs Verification)</option>
                        <option value="failed" <?php echo $backup && $backup['status'] === 'failed' ? 'selected' : ''; ?>>
                            Failed (Critical)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Description /
                    Schedule</label>
                <textarea name="notes" rows="3" placeholder="e.g. Nightly cron job @ 02:00. Stored on NAS-01."
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm"><?php echo $backup ? htmlspecialchars($backup['notes']) : ''; ?></textarea>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-50">
                <a href="index.php"
                    class="text-sm font-medium text-slate-400 hover:text-slate-600 transition-colors">Discard</a>
                <button type="submit"
                    class="px-8 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all hover:scale-105">
                    <?php echo $id ? "Update Entry" : "Add Entry"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>