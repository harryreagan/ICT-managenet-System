<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Backup Status Tracker";

// Handle Verification Update
if (isset($_POST['verify_id'])) {
    $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'safe', last_verified = NOW(), verified_by = ?, notes = ?, destination_disk = ? WHERE id = ?");
    $stmt->execute([$_SESSION['username'], $_POST['verify_notes'], $_POST['destination_disk'], $_POST['verify_id']]);
    header("Location: index.php?verified=1");
    exit;
}

// Fetch backup logs
$stmt = $pdo->query("SELECT * FROM backup_logs ORDER BY last_verified DESC");
$backups = $stmt->fetchAll();

// Calculate stats
$atRiskCount = 0;
foreach ($backups as $b) {
    if ($b['status'] !== 'safe')
        $atRiskCount++;
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Backup Verification</h1>
        <p class="text-slate-500 mt-2">Monitor Disaster Recovery health and verify system backups.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="manage.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Log Entry
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8 fade-in-up" style="animation-delay: 0.1s">
    <div class="saas-card p-4">
        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Protection Status</div>
        <div class="flex items-center">
            <h3 class="text-2xl font-bold <?php echo $atRiskCount > 0 ? 'text-amber-600' : 'text-emerald-600'; ?>">
                <?php echo $atRiskCount > 0 ? 'Action Required' : 'Fully Protected'; ?>
            </h3>
            <?php if ($atRiskCount === 0): ?>
                <svg class="w-5 h-5 ml-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            <?php endif; ?>
        </div>
    </div>
    <div class="saas-card p-4">
        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">At Risk Items</div>
        <h3 class="text-2xl font-bold text-slate-800">
            <?php echo $atRiskCount; ?>
        </h3>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.2s">
    <div class="overflow-x-auto text-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="px-6 py-4 border-b border-slate-100">System/Asset</th>
                    <th class="px-6 py-4 border-b border-slate-100">Type</th>
                    <th class="px-6 py-4 border-b border-slate-100">Destination</th>
                    <th class="px-6 py-4 border-b border-slate-100">Status</th>
                    <th class="px-6 py-4 border-b border-slate-100">Last Verified</th>
                    <th class="px-6 py-4 border-b border-slate-100">Verified By</th>
                    <th class="px-6 py-4 border-b border-slate-100 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($backups as $b): ?>
                    <tr class="group hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">
                                <?php echo htmlspecialchars($b['asset_name']); ?>
                            </div>
                            <div class="text-[10px] text-slate-400">
                                <?php echo htmlspecialchars($b['notes']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                <?php echo htmlspecialchars($b['backup_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="text-[10px] font-mono font-bold text-slate-500 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">
                                <?php echo htmlspecialchars($b['destination_disk'] ?: 'TBD'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($b['status'] === 'safe'): ?>
                                <span
                                    class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded uppercase tracking-wider">Verified</span>
                            <?php elseif ($b['status'] === 'at_risk'): ?>
                                <span
                                    class="px-2 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded uppercase tracking-wider">At
                                    Risk</span>
                            <?php else: ?>
                                <span
                                    class="px-2 py-0.5 bg-red-50 text-red-600 text-[10px] font-bold rounded uppercase tracking-wider">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            <?php echo $b['last_verified'] ? time_elapsed_string($b['last_verified']) : '<span class="italic text-red-400">Never</span>'; ?>
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-medium">
                            <?php echo htmlspecialchars($b['verified_by'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                onclick="openVerifyModal(<?php echo $b['id']; ?>, '<?php echo addslashes($b['asset_name']); ?>', '<?php echo addslashes($b['destination_disk']); ?>')"
                                class="text-primary-600 hover:text-primary-800 text-xs font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-opacity">
                                Verify Now
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($backups) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No backup logs found. Start by
                            adding your critical systems.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Verify Modal -->
<div id="verifyModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden fade-in-up">
        <form method="POST" class="p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-1">Confirm Backup Health</h3>
            <p class="text-slate-500 text-sm mb-4" id="modal_asset_name"></p>
            <input type="hidden" name="verify_id" id="modal_verify_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Destination
                        Disk / Storage</label>
                    <input type="text" name="destination_disk" id="modal_destination_disk"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm mb-4">

                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Verification
                        Notes</label>
                    <textarea name="verify_notes" rows="3" placeholder="e.g. Integrity check passed, files accessible."
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3 text-sm">
                <button type="button" onclick="document.getElementById('verifyModal').classList.add('hidden')"
                    class="px-4 py-2 text-slate-400 hover:text-slate-600 font-medium">Cancel</button>
                <button type="submit"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-md shadow-primary-200 transition-all">Mark
                    as Safe</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyModal(id, name, destination) {
        document.getElementById('modal_verify_id').value = id;
        document.getElementById('modal_asset_name').innerText = "Verifying backup for: " + name;
        document.getElementById('modal_destination_disk').value = destination;
        document.getElementById('verifyModal').classList.remove('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>