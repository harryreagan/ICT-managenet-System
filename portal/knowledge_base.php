<?php
require_once __DIR__ . '/layout.php';

$search = $_GET['search'] ?? '';
$sop_page = isset($_GET['sop_page']) ? max(1, (int) $_GET['sop_page']) : 1;
$fix_page = isset($_GET['fix_page']) ? max(1, (int) $_GET['fix_page']) : 1;
$per_page = 6;
$sop_offset = ($sop_page - 1) * $per_page;
$fix_offset = ($fix_page - 1) * $per_page;

// Count total SOPs for pagination
if ($search) {
    $sop_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM sop_documents WHERE visibility = 'public' AND (title LIKE ? OR content LIKE ? OR category LIKE ?)");
    $sop_count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $sop_count_stmt = $pdo->query("SELECT COUNT(*) FROM sop_documents WHERE visibility = 'public'");
}
$total_sops = $sop_count_stmt->fetchColumn();
$total_sop_pages = ceil($total_sops / $per_page);

// Fetch SOPs with search filter, public visibility, and pagination
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE visibility = 'public' AND (title LIKE ? OR content LIKE ? OR category LIKE ? ) ORDER BY category ASC, title ASC LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", "%$search%", "%$search%", $per_page, $sop_offset]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE visibility = 'public' ORDER BY category ASC, title ASC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $sop_offset]);
}
$docs = $stmt->fetchAll();

// Count total verified fixes for pagination
if ($search) {
    $fix_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND (title LIKE ? OR symptoms LIKE ? OR system_affected LIKE ?)");
    $fix_count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $fix_count_stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed')");
}
$total_fixes = $fix_count_stmt->fetchColumn();
$total_fix_pages = ceil($total_fixes / $per_page);

// Fetch Resolved Incidents (Public Knowledge Base) with search filter and pagination
if ($search) {
    $stmt = $pdo->prepare("SELECT id, title, symptoms, system_affected, solution_image, created_at FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND (title LIKE ? OR symptoms LIKE ? OR system_affected LIKE ?) ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", "%$search%", "%$search%", $per_page, $fix_offset]);
} else {
    $stmt = $pdo->prepare("SELECT id, title, symptoms, system_affected, solution_image, created_at FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $fix_offset]);
}
$solutions = $stmt->fetchAll();

$categories = [];
foreach ($docs as $doc) {
    $categories[$doc['category']][] = $doc;
}

renderPortalHeader("Knowledge Base");
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
            <h1 class="text-3xl font-bold text-slate-800">Knowledge Base</h1>
            <p class="text-slate-500 mt-2">Self-help guides and verified technical solutions.</p>
        </div>
        <a href="submit_ticket.php"
            class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition-colors text-sm whitespace-nowrap">
            + New Ticket
        </a>

        <form action="" method="GET" id="searchForm" data-live-search
            class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <div class="relative group flex-grow md:w-80">
                <span
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search articles and fixes..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm outline-none text-sm">
            </div>
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap">
                Search
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main Content Area: SOPs -->
        <div class="lg:col-span-4 space-y-8">
            <?php if (empty($docs) && $search): ?>
                <div class="bg-white p-12 rounded-xl border border-gray-100 text-center">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">No guides found</h3>
                    <p class="text-slate-500 mt-1">Try adjusting your search keywords.</p>
                    <a href="knowledge_base.php" class="text-primary-600 font-bold mt-4 inline-block">Clear Search</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($docs as $doc): ?>
                        <div
                            class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all group flex flex-col">
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="px-2.5 py-0.5 bg-primary-50 text-primary-700 text-[10px] font-bold uppercase tracking-wider rounded border border-primary-100">
                                    <?= htmlspecialchars($doc['category']) ?>
                                </span>
                                <span
                                    class="text-[10px] font-bold text-slate-400">v<?= htmlspecialchars($doc['version']) ?></span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-800 mb-2 group-hover:text-primary-600 transition-colors">
                                <?= htmlspecialchars($doc['title']) ?>
                            </h3>

                            <p class="text-slate-500 text-sm line-clamp-2 leading-relaxed mb-6 flex-grow">
                                <?= strip_tags($doc['content']) ?>
                            </p>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                                <span class="text-[10px] text-slate-400 font-bold">Updated
                                    <?= date('j M, Y', strtotime($doc['last_updated'])) ?></span>
                                <a href="view_sop.php?id=<?= $doc['id'] ?>"
                                    class="text-primary-600 text-xs font-bold group-hover:translate-x-1 transition-transform">Read
                                    Guide &rarr;</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- SOP Pagination -->
                <?php if ($total_sop_pages > 1): ?>
                    <div class="flex items-center justify-center gap-2 mt-8">
                        <?php if ($sop_page > 1): ?>
                            <a href="?sop_page=<?php echo $sop_page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $fix_page > 1 ? '&fix_page=' . $fix_page : ''; ?>"
                                class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $sop_page - 2);
                        $end_page = min($total_sop_pages, $sop_page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <a href="?sop_page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $fix_page > 1 ? '&fix_page=' . $fix_page : ''; ?>"
                                class="px-4 py-2 <?php echo $i == $sop_page ? 'bg-primary-500 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'; ?> rounded-lg text-sm font-medium transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($sop_page < $total_sop_pages): ?>
                            <a href="?sop_page=<?php echo $sop_page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $fix_page > 1 ? '&fix_page=' . $fix_page : ''; ?>"
                                class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

<?php
renderPortalFooter();
?>