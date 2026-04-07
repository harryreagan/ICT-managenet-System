<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = "User Management";

// Handle Filters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$whereClauses = [];
$params = [];

if ($startDate) {
    $whereClauses[] = "DATE(created_at) >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $whereClauses[] = "DATE(created_at) <= ?";
    $params[] = $endDate;
}

$sql = "SELECT * FROM users";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');

    // Headers
    fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'Role', 'Department', 'Extension', 'Duty Phone', 'Status', 'Joined Date']);

    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['full_name'],
            $user['email'],
            $user['role'],
            $user['department'],
            $user['extension'],
            $user['duty_number'],
            $user['status'],
            $user['created_at']
        ]);
    }

    fclose($output);
    exit();
}

include '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">User Management</h1>
        <p class="text-slate-500 text-sm">Manage system access and permissions</p>
    </div>
    <a href="create.php"
        class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add New User
    </a>
</div>

<div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-slate-100">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition-all">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition-all">
        </div>
        <div class="flex gap-2">
            <button type="submit" 
                class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center shadow-lg shadow-slate-800/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filter
            </button>
            <?php if ($startDate || $endDate): ?>
                <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center">
                    Reset
                </a>
            <?php endif; ?>
            <button type="submit" name="export" value="csv" 
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center shadow-lg shadow-emerald-500/20 ml-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </button>
        </div>
    </form>
</div>

<div class="saas-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">User</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Role</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Department</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ext / Duty</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Joined</th>
                    <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div
                                    class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-xs mr-3">
                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700">
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-medium">@
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                <?php echo $user['role'] === 'admin' ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-primary-50 text-primary-600 border border-primary-100'; ?> shadow-sm">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?php echo htmlspecialchars($user['department'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <div class="font-bold text-slate-700"><?php echo htmlspecialchars($user['extension'] ?: '-'); ?>
                            </div>
                            <div class="text-[10px]"><?php echo htmlspecialchars($user['duty_number'] ?: '-'); ?></div>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <span
                                class="flex items-center <?php echo $user['status'] === 'active' ? 'text-emerald-500' : 'text-slate-400'; ?>">
                                <span
                                    class="w-1.5 h-1.5 rounded-full <?php echo $user['status'] === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'; ?> mr-2"></span>
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="edit.php?id=<?php echo $user['id']; ?>"
                                    class="p-1.5 text-slate-400 hover:text-primary-600 transition-colors rounded-md hover:bg-primary-50"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form id="delete-form-<?php echo $user['id']; ?>" action="delete.php" method="POST"
                                        class="inline">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <button type="button"
                                            @click="$store.modal.trigger('delete-form-<?php echo $user['id']; ?>', 'Are you sure you want to delete this user? This action cannot be undone.')"
                                            class="p-1.5 text-slate-400 hover:text-red-600 transition-colors rounded-md hover:bg-red-50"
                                            title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>