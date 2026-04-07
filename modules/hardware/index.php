<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Hardware Asset Management";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM hardware_assets WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    redirect($_SERVER['REQUEST_URI']);
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$whereClauses = [];
$params = [];

if ($statusFilter) {
    $whereClauses[] = "condition_status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $whereClauses[] = "(name LIKE ? OR serial_number LIKE ? OR location LIKE ? OR department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

$stmt = $pdo->prepare("
    SELECT h.*, 
        h.quantity - COALESCE((SELECT SUM(quantity_issued) FROM asset_issuances WHERE asset_id = h.id AND status = 'issued'), 0) as available_quantity,
        (SELECT COUNT(*) FROM asset_issuances WHERE asset_id = h.id AND status = 'issued') as active_issues
    FROM hardware_assets h 
    $whereSQL 
    ORDER BY h.name ASC
");
$stmt->execute($params);
$assets = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Hardware Assets</h1>
        <p class="text-slate-500 mt-2">Track servers, computers, network gear, and more.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Asset
        </a>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <!-- Filters -->
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <form action="" method="GET" id="hardwareSearchForm" data-live-search
            class="flex flex-col md:flex-row md:items-center space-y-3 md:space-y-0 md:space-x-4">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" id="hardwareSearchInput"
                    placeholder="Search by name, serial, location..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 transition-shadow">
            </div>
            <div>
                <select name="status"
                    class="w-full md:w-48 border border-slate-200 rounded-lg py-2 px-3 text-sm text-slate-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                    onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="working" <?php echo $statusFilter === 'working' ? 'selected' : ''; ?>>Working</option>
                    <option value="needs_service" <?php echo $statusFilter === 'needs_service' ? 'selected' : ''; ?>>Needs
                        Service</option>
                    <option value="faulty" <?php echo $statusFilter === 'faulty' ? 'selected' : ''; ?>>Faulty</option>
                </select>
            </div>
            <button type="submit"
                class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-2 px-6 rounded-lg text-sm transition-all shadow-sm hover:shadow-primary-500/20 transform hover:-translate-y-0.5">
                Filter
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Asset
                        Name</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Serial
                        Number</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Location / Dept.</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Warranty</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Availability</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                    </th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($assets as $asset): ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="text-sm font-medium text-slate-900 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($asset['name']); ?>
                                </div>
                                <?php if ($asset['condition_notes']): ?>
                                    <div class="ml-2 text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded border border-red-100 font-bold"
                                        title="<?php echo htmlspecialchars($asset['condition_notes']); ?>">
                                        ALERT
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($asset['condition_notes']): ?>
                                <p class="text-[9px] text-slate-400 mt-1 italic">
                                    <?php echo htmlspecialchars($asset['condition_notes']); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono">
                            <?php if (!empty($asset['manufacturer'])): ?>
                                <div class="text-xs text-slate-400 mb-0.5">
                                    <?php echo htmlspecialchars($asset['manufacturer']); ?></div>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($asset['serial_number']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <div><?php echo htmlspecialchars($asset['location']); ?></div>
                            <div class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars($asset['department']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <?php echo formatDate($asset['warranty_expiry']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-bold <?php echo $asset['available_quantity'] > 0 ? 'text-slate-800' : 'text-rose-600'; ?>">
                                    <?php echo $asset['available_quantity']; ?> / <?php echo $asset['quantity']; ?>
                                    Available
                                </span>
                                <?php if ($asset['active_issues'] > 0): ?>
                                    <span class="text-[10px] text-slate-500 uppercase mt-0.5">
                                        <?php echo $asset['active_issues']; ?> Active Issuance(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($asset['condition_status']); ?> border border-opacity-20 shadow-sm">
                                <?php echo ucfirst(str_replace('_', ' ', $asset['condition_status'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">

                                <a href="/ict/modules/hardware/issue.php?id=<?php echo $asset['id']; ?>"
                                    class="text-amber-500 hover:text-amber-600 transition-colors p-1 rounded-md hover:bg-amber-50"
                                    title="Issue / Return Asset">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                                        </path>
                                    </svg>
                                </a>
                                <a href="/ict/modules/hardware/view.php?id=<?php echo $asset['id']; ?>"
                                    class="text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-md hover:bg-primary-50"
                                    title="View & QR identity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </a>
                                <a href="/ict/modules/hardware/edit.php?id=<?php echo $asset['id']; ?>"
                                    class="text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-md hover:bg-primary-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                                <form method="POST" action="" id="delete-form-<?php echo $asset['id']; ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo $asset['id']; ?>">
                                    <button type="button"
                                        @click="$store.modal.trigger('delete-form-<?php echo $asset['id']; ?>', 'Are you sure you want to delete this asset? This action cannot be undone.', 'Delete Asset')"
                                        class="text-slate-400 hover:text-red-600 transition-colors p-1 rounded-md hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($assets) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-lg font-medium text-slate-700">No assets found</span>
                                <p class="text-slate-500 text-sm mt-1 mb-4">Start tracking your hardware.</p>
                                <a href="create.php"
                                    class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    Add Asset
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>