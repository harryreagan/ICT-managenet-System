<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$item_filter = $_GET['item'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build Query
$sql = "SELECT l.*, u.full_name, u.username 
        FROM facility_check_logs l 
        LEFT JOIN users u ON l.checked_by = u.id";
$where = [];
$params = [];

if ($item_filter) {
    $where[] = "l.item_key = ?";
    $params[] = $item_filter;
}
if ($status_filter) {
    $where[] = "l.status = ?";
    $params[] = $status_filter;
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY l.checked_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = "Facility History";
include '../../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <a href="index.php"
            class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center mb-2">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Hub
        </a>
        <h1 class="text-2xl font-bold text-slate-800">Facility Check History</h1>
        <p class="text-slate-500 text-xs">Track incidents, status changes, and resolutions across all core service
            areas.</p>
    </div>
</div>

<!-- Filters -->
<div class="saas-card p-4 mb-8 bg-slate-50/50">
    <form method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Facility
                Area</label>
            <select name="item"
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                <option value="">All Areas</option>
                <option value="solar" <?php echo $item_filter == 'solar' ? 'selected' : ''; ?>>Solar Power</option>
                <option value="charging" <?php echo $item_filter == 'charging' ? 'selected' : ''; ?>>EV Charging</option>
                <option value="gym" <?php echo $item_filter == 'gym' ? 'selected' : ''; ?>>Gym Hub</option>
                <option value="playground" <?php echo $item_filter == 'playground' ? 'selected' : ''; ?>>Playground WiFi
                </option>
                <option value="ac" <?php echo $item_filter == 'ac' ? 'selected' : ''; ?>>Server AC</option>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</label>
            <select name="status"
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                <option value="">All Statuses</option>
                <option value="operational" <?php echo $status_filter == 'operational' ? 'selected' : ''; ?>>Operational
                </option>
                <option value="warning" <?php echo $status_filter == 'warning' ? 'selected' : ''; ?>>Warning</option>
                <option value="faulty" <?php echo $status_filter == 'faulty' ? 'selected' : ''; ?>>Faulty</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit"
                class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-2 rounded-lg text-sm font-bold uppercase tracking-wider transition-all shadow-md shadow-primary-500/20">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- History Table -->
<div class="saas-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date & Time
                    </th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Facility</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Inspector</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No checkup history found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log):
                        $color = $log['status'] == 'operational' ? 'emerald' : ($log['status'] == 'warning' ? 'amber' : 'rose');
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <p class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                    <?php echo date('M d, Y', strtotime($log['checked_at'])); ?>
                                </p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                    <?php echo date('H:i', strtotime($log['checked_at'])); ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center">
                                    <div
                                        class="p-2 bg-slate-50 rounded-lg mr-3 group-hover:bg-white transition-colors border border-transparent group-hover:border-slate-100">
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-primary-500 transition-colors"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-black text-slate-700 tracking-tight">
                                        <?php echo htmlspecialchars($log['item_name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-600 border border-<?php echo $color; ?>-100 shadow-sm">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-<?php echo $color; ?>-500 mr-1.5 animate-pulse"></span>
                                    <?php echo $log['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-[11px] font-black mr-3 border border-primary-100 uppercase tracking-tighter">
                                        <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                    </div>
                                    <span class="text-[11px] text-slate-600 font-black uppercase tracking-tight">
                                        <?php echo htmlspecialchars($log['full_name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="max-w-xs transition-all duration-300">
                                    <p class="text-xs text-slate-500 leading-relaxed italic"
                                        title="<?php echo htmlspecialchars($log['notes']); ?>">
                                        "<?php echo htmlspecialchars($log['notes'] ?: 'No findings recorded.'); ?>"
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div
                                    class="flex items-center justify-end space-x-1 sm:opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($log)); ?>)"
                                        class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all"
                                        title="Edit Log">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $log['id']; ?>)"
                                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all"
                                        title="Delete Entry">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Log Modal -->
<div id="editModal"
    class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div
        class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">EDIT LOG ENTRY
                    </h3>
                    <h2 id="editLogName" class="text-xl font-black text-slate-800 tracking-tight">Facility Name</h2>
                </div>
                <button onclick="closeEditModal()"
                    class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <form action="api/manage_logs.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editLogId">

                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Operational
                        Status</label>
                    <select name="status" id="editLogStatus"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all appearance-none uppercase font-bold tracking-tighter">
                        <option value="operational">Operational</option>
                        <option value="warning">Warning</option>
                        <option value="faulty">Faulty</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Checkup
                        Observations</label>
                    <textarea name="notes" id="editLogNotes" rows="4"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all"
                        placeholder="Describe findings..."></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-slate-800 hover:bg-slate-900 text-white py-4 rounded-xl text-sm font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-slate-800/20">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" action="api/manage_logs.php" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
    function openEditModal(log) {
        document.getElementById('editLogId').value = log.id;
        document.getElementById('editLogName').innerText = log.item_name;
        document.getElementById('editLogStatus').value = log.status;
        document.getElementById('editLogNotes').value = log.notes || '';

        document.getElementById('editModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this log entry? This cannot be undone.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    // Close on backdrop click
    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditModal();
    });
</script>

<?php include '../../includes/footer.php'; ?>