<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Incident Management";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM troubleshooting_logs WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    redirect($_SERVER['REQUEST_URI']);
}

// Filters
$view = $_GET['view'] ?? 'active';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned = $_GET['assigned_to'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$whereClauses = [];
$params = [];

// Base View Logic (only if Status filter is NOT set)
if (empty($status)) {
    if ($view === 'active') {
        $whereClauses[] = "status IN ('open', 'in_progress')";
    } elseif ($view === 'solved') {
        $whereClauses[] = "status IN ('resolved', 'closed')";
    }
} else {
    if ($status !== 'all') {
        $whereClauses[] = "status = ?";
        $params[] = $status;
    }
}

// Advanced Filters
if ($search) {
    $whereClauses[] = "(title LIKE ? OR system_affected LIKE ? OR symptoms LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($priority && $priority !== 'all') {
    $whereClauses[] = "priority = ?";
    $params[] = $priority;
}

if ($assigned && $assigned !== 'all') {
    $whereClauses[] = "assigned_to = ?";
    $params[] = $assigned;
}

if ($date_from) {
    $whereClauses[] = "created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $whereClauses[] = "created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

// Pagination Logic
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1)
    $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get Total Count for Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM troubleshooting_logs $whereSQL");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Order by Priority for Active, Date for Solved
$orderBy = "ORDER BY created_at DESC";
if ($view === 'active') {
    $orderBy = "ORDER BY 
    CASE 
        WHEN priority = 'critical' THEN 1 
        WHEN priority = 'high' THEN 2 
        WHEN priority = 'medium' THEN 3 
        ELSE 4 
    END, created_at ASC";
}

$stmt = $pdo->prepare("SELECT * FROM troubleshooting_logs $whereSQL $orderBy LIMIT $itemsPerPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Handle AJAX Request for Live Search
if (isset($_GET['ajax'])) {
    include 'rows.php';
    exit;
}

include '../../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-end gap-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Incident Management</h1>
            <p class="text-slate-500 mt-2">Track active hotel issues and manage the knowledge base.</p>
        </div>
        <div>
            <div class="flex gap-3">
                <a href="create.php"
                    class="inline-flex items-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-sm transition-colors text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Add New Issue
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col gap-6">
            <!-- Advanced Search & Filters -->
            <form id="searchForm" action="" method="GET" class="w-full">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">

                <div class="bg-slate-50 p-4 rounded-xl border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-1">
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Search
                                Keywords</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Search details..."
                                    class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm">
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</label>
                            <select name="status"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm appearance-none cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In
                                    Progress</option>
                                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved
                                </option>
                                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed
                                </option>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Priority</label>
                            <select name="priority"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm appearance-none cursor-pointer">
                                <option value="">All Priorities</option>
                                <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>
                                    Critical</option>
                                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium
                                </option>
                                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>

                        <!-- Assigned To -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Assigned
                                To</label>
                            <select name="assigned_to"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm appearance-none cursor-pointer">
                                <option value="">All Staff</option>
                                <option value="tech_support" <?php echo $assigned === 'tech_support' ? 'selected' : ''; ?>>Tech Support</option>
                                <option value="manager" <?php echo $assigned === 'manager' ? 'selected' : ''; ?>>Manager
                                </option>
                                <option value="admin" <?php echo $assigned === 'admin' ? 'selected' : ''; ?>>Administrator
                                </option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">From
                                Date</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm text-slate-600">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">To
                                Date</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm text-slate-600">
                        </div>

                        <!-- Actions -->
                        <div class="lg:col-span-2 flex items-end gap-2">
                            <button type="submit"
                                class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                                Apply Filters
                            </button>
                            <a href="index.php?view=<?php echo $view; ?>"
                                class="px-4 py-2 bg-white border border-gray-200 text-slate-600 hover:text-red-600 hover:border-red-200 text-sm font-bold rounded-lg transition-colors">
                                Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Tabs -->
            <div class="flex space-x-2">
                <a href="?view=active"
                    class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?php echo $view === 'active' ? 'bg-primary-50 text-primary-600 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50'; ?>">Active</a>
                <a href="?view=solved"
                    class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?php echo $view === 'solved' ? 'bg-primary-50 text-primary-600 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50'; ?>">Solutions</a>
                <a href="?view=all"
                    class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?php echo $view === 'all' ? 'bg-primary-50 text-primary-600 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50'; ?>">Archive</a>
            </div>
        </div>

        <?php if ($view === 'solved'): ?>
            <!-- Card View for Resolved/KB -->
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($logs as $log): ?>
                    <a href="view.php?id=<?php echo $log['id']; ?>" class="group">
                        <div
                            class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col h-full overflow-hidden">
                            <?php if ($log['solution_image']): ?>
                                <div class="h-48 overflow-hidden bg-slate-100">
                                    <img src="/ict/<?php echo $log['solution_image']; ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                            <?php else: ?>
                                <div class="h-48 bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-4">
                                    <span
                                        class="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full border border-emerald-100">Solution</span>
                                    <span
                                        class="text-xs text-slate-400 font-medium"><?php echo date('M j, Y', strtotime($log['incident_date'])); ?></span>
                                </div>
                                <h3
                                    class="text-lg font-bold text-slate-800 mb-3 group-hover:text-primary-600 transition-colors line-clamp-2">
                                    <?php echo htmlspecialchars($log['title']); ?>
                                </h3>
                                <div class="text-sm text-slate-500 mb-6 line-clamp-2 leading-relaxed">
                                    <?php echo strip_tags($log['symptoms']); ?>
                                </div>
                                <div class="mt-auto pt-5 border-t border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center text-xs font-bold text-primary-600 border border-primary-100">
                                            <?php echo substr($log['technician_name'], 0, 1); ?>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700">
                                            <?php echo htmlspecialchars($log['technician_name']); ?></span>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 group-hover:text-primary-600 group-hover:translate-x-1 transition-all"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Standard Table View for Active/All -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="pl-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider w-12">
                                <input type="checkbox" id="selectAll"
                                    class="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500 cursor-pointer accent-primary-600 transition-transform hover:scale-110">
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Priority / Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Issue Title
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Assigned To</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Date</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody" class="bg-white divide-y divide-gray-200">
                        <?php include 'rows.php'; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $itemsPerPage, $totalItems); ?> of
                    <?php echo $totalItems; ?> issues
                </div>
                <div class="flex gap-2">
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseQuery = http_build_query($queryParams);
                    for ($i = 1; $i <= $totalPages; $i++):
                        ?>
                        <a href="?<?php echo $baseQuery; ?>&page=<?php echo $i; ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg text-sm font-bold transition-all <?php echo $i === $currentPage ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Actions Toolbar -->
<div id="bulkActionsToolbar"
    class="fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-slate-900/90 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-6 z-50 opacity-0 translate-y-20 pointer-events-none transition-all duration-300 border border-white/10 ring-1 ring-black/5">
    <div class="flex items-center gap-3">
        <span class="bg-indigo-500 text-white text-xs font-bold px-2.5 py-1 rounded-lg shadow-sm"
            id="selectedCount">0</span>
        <span class="text-sm font-medium text-slate-300">Selected</span>
    </div>

    <div class="h-8 w-px bg-white/10"></div>

    <div class="flex items-center gap-3">
        <!-- Update Status -->
        <select id="bulkStatus" onchange="runBulkAction('status', this.value)"
            class="bg-slate-800/50 border border-slate-600/50 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 hover:bg-slate-800 transition-colors cursor-pointer outline-none">
            <option value="">Set Status...</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        </select>

        <!-- Update Priority -->
        <select id="bulkPriority" onchange="runBulkAction('priority', this.value)"
            class="bg-slate-800/50 border border-slate-600/50 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 hover:bg-slate-800 transition-colors cursor-pointer outline-none">
            <option value="">Set Priority...</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
        </select>

        <!-- Assign To -->
        <select id="bulkAssign" onchange="runBulkAction('assign', this.value)"
            class="bg-slate-800/50 border border-slate-600/50 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 hover:bg-slate-800 transition-colors cursor-pointer outline-none">
            <option value="">Assign To...</option>
            <option value="tech_support">Tech Support</option>
            <option value="manager">Manager</option>
            <option value="admin">Administrator</option>
        </select>

        <div class="h-8 w-px bg-white/10 mx-1"></div>

        <!-- Delete -->
        <button onclick="runBulkAction('delete')"
            class="group flex items-center gap-2 px-4 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg transition-all border border-transparent hover:border-red-500/30">
            <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
            </svg>
            <span class="text-sm font-bold">Delete</span>
        </button>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Existing Search Logic ---
        const searchInput = document.getElementById('searchInput');
        // ... (rest of search logic assumed to be preserved if not replaced, but here I am creating a merged script block)
        // Actually, I am replacing the script block to include bulk actions
        const viewSelector = document.getElementById('viewSelector'); // might be null if view selector was replaced? No, it's in top form.
        // Wait, line 405 was viewSelector event listener.
        // I will re-implement the search logic + bulk logic here.

        const resultsBody = document.getElementById('resultsBody');
        const searchSpinner = document.getElementById('searchSpinner');
        let debounceTimer;

        // Search Function
        function performSearch() {
            // ... (Same as before) ...
            // But we need to re-bind checkboxes after content reload!
            const query = document.querySelector('input[name="search"]').value;
            const status = document.querySelector('select[name="status"]').value;
            const priority = document.querySelector('select[name="priority"]').value;
            const assigned = document.querySelector('select[name="assigned_to"]').value;
            const date_from = document.querySelector('input[name="date_from"]').value;
            const date_to = document.querySelector('input[name="date_to"]').value;
            // We need to grab the current view from URL or hidden input
            const view = document.querySelector('input[name="view"]').value;

            // Only show spinner if we have one
            if (searchSpinner) searchSpinner.classList.remove('hidden');

            const params = new URLSearchParams({
                ajax: 1,
                view: view,
                search: query,
                status: status,
                priority: priority,
                assigned_to: assigned,
                date_from: date_from,
                date_to: date_to
            });

            fetch('?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    resultsBody.innerHTML = html;
                    if (searchSpinner) searchSpinner.classList.add('hidden');

                    // Re-bind Selection Logic
                    bindCheckboxEvents();
                    updateToolbar();
                });
        }

        // Attach listeners to new filter form inputs
        const filterInputs = document.querySelectorAll('#searchForm input, #searchForm select');
        filterInputs.forEach(input => {
            input.addEventListener('change', performSearch);
            if (input.type === 'text') {
                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(performSearch, 500);
                });
            }
        });

        // --- Bulk Actions Logic ---
        const selectAll = document.getElementById('selectAll');
        const toolbar = document.getElementById('bulkActionsToolbar');
        const countSpan = document.getElementById('selectedCount');
        let selectedIds = new Set();

        function bindCheckboxEvents() {
            const checkboxes = document.querySelectorAll('.issue-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    if (this.checked) selectedIds.add(this.value);
                    else selectedIds.delete(this.value);
                    updateToolbar();
                });
                // Maintain state
                if (selectedIds.has(cb.value)) cb.checked = true;
            });
        }

        selectAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.issue-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                if (this.checked) selectedIds.add(cb.value);
                else selectedIds.delete(cb.value);
            });
            updateToolbar();
        });

        function updateToolbar() {
            countSpan.textContent = selectedIds.size;
            if (selectedIds.size > 0) {
                toolbar.classList.remove('opacity-0', 'translate-y-20', 'pointer-events-none');
            } else {
                toolbar.classList.add('opacity-0', 'translate-y-20', 'pointer-events-none');
                // Reset dropdowns
                document.getElementById('bulkStatus').value = "";
                document.getElementById('bulkPriority').value = "";
                document.getElementById('bulkAssign').value = "";
            }

            // Update Select All state
            const checkboxes = document.querySelectorAll('.issue-checkbox');
            if (checkboxes.length > 0) {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        }

        // Initial binding
        bindCheckboxEvents();

        // Global function for toolbar actions
        window.runBulkAction = function (action, value) {
            if (!value && action !== 'delete') return; // Don't run if placeholder selected

            if (action === 'delete' && !confirm('Are you sure you want to delete ' + selectedIds.size + ' issues? This cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            if (value) formData.append('value', value);

            selectedIds.forEach(id => formData.append('ids[]', id));

            fetch('bulk_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // alert(data.message);
                        selectedIds.clear();
                        updateToolbar();
                        selectAll.checked = false;

                        // Refresh table
                        performSearch();

                        // Reset dropdowns
                        document.getElementById('bulkStatus').value = "";
                        document.getElementById('bulkPriority').value = "";
                        document.getElementById('bulkAssign').value = "";

                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred');
                });
        };
    });
</script>

<?php include '../../includes/footer.php'; ?>