<?php
require_once __DIR__ . '/layout.php';

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Determine visibility based on role
$is_staff = in_array($_SESSION['role'], ['admin', 'manager', 'tech_support']);
$visibility_sql = $is_staff ? "" : "AND visibility = 'public'";

try {
    // Count total verified solutions for pagination
    if ($search) {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') $visibility_sql AND (title LIKE ? OR symptoms LIKE ? OR system_affected LIKE ?)");
        $count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') $visibility_sql");
    }
    $total_fixes = $count_stmt->fetchColumn();
    $total_pages = ceil($total_fixes / $per_page);

    // Fetch Resolved Incidents with search filter and pagination
    if ($search) {
        $stmt = $pdo->prepare("SELECT id, title, symptoms, system_affected, solution_image, created_at, technician_name, visibility FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') $visibility_sql AND (title LIKE ? OR symptoms LIKE ? OR system_affected LIKE ?) ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(["%$search%", "%$search%", "%$search%", $per_page, $offset]);
    } else {
        $stmt = $pdo->prepare("SELECT id, title, symptoms, system_affected, solution_image, created_at, technician_name, visibility FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') $visibility_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$per_page, $offset]);
    }
    $solutions = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='bg-red-50 p-4 rounded-lg border border-red-200 text-red-700'>";
    echo "<strong>Database Connection Debug:</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "User: " . DB_USER . "<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br><br>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "The 'visibility' column is missing from the table in ONLY this environment.<br>";
    echo "This usually means the Web Server is connecting to a different database than the CLI.<br>";
    echo "Please compare these details with your local setup.";
    echo "</div>";
    exit;
}

renderPortalHeader("Verified Solutions");
?>

<div class="space-y-8">
    <!-- Back Navigation -->
    <a href="index.php"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Back to Dashboard
    </a>

    <!-- Header & Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex-grow">
            <h1 class="text-3xl font-bold text-slate-800">Verified Solutions</h1>
            <p class="text-slate-500 mt-2">Browse past issues and their verified solutions.</p>
        </div>

        <form action="" method="GET" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <div class="relative group flex-grow md:w-80">
                <span
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search solutions..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm outline-none text-sm">
            </div>
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap">
                Search
            </button>
        </form>
    </div>

    <?php if (empty($solutions)): ?>
        <div class="bg-white p-20 rounded-xl border border-gray-100 text-center shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800">No Solutions Found</h3>
            <p class="text-slate-500 mt-2">No verified solutions matched your search. Try different keywords.</p>
            <a href="verified_solutions.php" class="text-primary-600 font-bold mt-6 inline-block hover:underline">Clear
                Search</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($solutions as $sol): ?>
                <a href="view_solution.php?id=<?= $sol['id'] ?>" class="group">
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-primary-200 transition-all flex flex-col h-full overflow-hidden">
                        <?php if ($sol['solution_image']): ?>
                            <div class="h-48 overflow-hidden bg-gray-100">
                                <img src="/ict/<?= $sol['solution_image'] ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        <?php else: ?>
                            <div class="h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex gap-2">
                                    <span
                                        class="bg-emerald-50 text-emerald-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-emerald-100">Resolved</span>
                                    <?php if (($sol['visibility'] ?? 'public') === 'internal'): ?>
                                        <span
                                            class="bg-amber-50 text-amber-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-100 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                                </path>
                                            </svg>
                                            Internal
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span
                                    class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= date('M j, Y', strtotime($sol['created_at'])) ?></span>
                            </div>

                            <h3
                                class="text-lg font-bold text-slate-900 mb-3 group-hover:text-primary-600 transition-colors line-clamp-2 leading-tight">
                                <?= htmlspecialchars($sol['title']) ?>
                            </h3>

                            <p class="text-sm text-slate-600 mb-6 line-clamp-2 leading-relaxed">
                                <?= strip_tags($sol['symptoms']) ?>
                            </p>

                            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center text-xs font-bold text-primary-600 uppercase">
                                        <?= strtoupper(substr($sol['technician_name'] ?: 'T', 0, 1)) ?>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-slate-700"><?= htmlspecialchars($sol['technician_name'] ?: 'ICT Team') ?></span>
                                </div>
                                <div
                                    class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white group-hover:bg-primary-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex items-center justify-center gap-2 mt-12">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-bold text-slate-700 hover:bg-gray-50 transition-colors">
                        Previous
                    </a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                        class="px-4 py-2 <?= $i == $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200 text-slate-700 hover:bg-gray-50' ?> rounded-lg text-sm font-bold transition-colors">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-bold text-slate-700 hover:bg-gray-50 transition-colors">
                        Next
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
renderPortalFooter();
?>