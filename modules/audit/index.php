<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "System Audit Logs";

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Search
$search = $_GET['search'] ?? '';
$whereClause = "";
$params = [];

if ($search) {
    $whereClause = "WHERE action LIKE ? OR details LIKE ? OR user_id IN (SELECT id FROM users WHERE username LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Get Logs with Usernames
$stmt = $pdo->prepare("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $whereClause ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a $whereClause");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Audit Trail</h1>
        <p class="text-slate-500 mt-2">Security and operational accountability log.</p>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">
            <?php echo count($logs); ?> Events Viewed
        </div>

        <form action="" method="GET" id="auditSearchForm" data-live-search class="flex items-center w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" placeholder="Search logs..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-full focus:ring-primary-500 focus:border-primary-500 transition-shadow outline-none">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Time
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Action
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Details</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500 font-mono">
                            <?php echo $log['created_at']; ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-slate-900">
                            <div class="flex items-center">
                                <span
                                    class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold mr-2">
                                    <?php echo strtoupper(substr($log['username'] ?? 'S', 0, 1)); ?>
                                </span>
                                <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            <span
                                class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600 font-mono text-xs">
                            <?php echo htmlspecialchars($log['details']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($logs) === 0): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-slate-500">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between sm:px-6 bg-slate-50/50">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs text-slate-500">
                        Page <span class="font-medium text-slate-700"><?php echo $page; ?></span> of <span
                            class="font-medium text-slate-700"><?php echo $totalPages; ?></span>
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                                class="relative inline-flex items-center px-2 py-1.5 rounded-l-md border border-slate-300 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                                class="relative inline-flex items-center px-2 py-1.5 rounded-r-md border border-slate-300 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>