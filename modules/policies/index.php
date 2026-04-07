<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Documentation Center";

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 7;
$offset = ($page - 1) * $per_page;

// Count total documents for pagination
if ($search) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM sop_documents WHERE title LIKE ? OR content LIKE ? OR category LIKE ?");
    $count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM sop_documents");
}
$total_docs = $count_stmt->fetchColumn();
$total_pages = ceil($total_docs / $per_page);

// Fetch SOPs with search filtering and pagination
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE title LIKE ? OR content LIKE ? OR category LIKE ? ORDER BY category ASC, title ASC LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", "%$search%", "%$search%", $per_page, $offset]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM sop_documents ORDER BY category ASC, title ASC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $offset]);
}
$docs = $stmt->fetchAll();

$categories = [];
foreach ($docs as $doc) {
    $categories[$doc['category']][] = $doc;
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Documentation Center</h1>
        <p class="text-slate-500 mt-2">Centralized repository for Standard Operating Procedures and Security Policies.
        </p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="manage.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Documentation
        </a>
    </div>
</div>

<div class="space-y-6 fade-in-up" style="animation-delay: 0.1s">
    <!-- Search Bar -->
    <div class="saas-card p-4">
        <form action="" method="GET" id="docSearchForm" data-live-search class="flex gap-4">
            <div class="relative flex-grow">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" id="docSearchInput" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by title, content or category..."
                    class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
            </div>
            <button type="submit"
                class="px-6 py-2 bg-slate-800 text-white font-bold rounded-lg text-sm hover:bg-slate-900 transition-colors">Search</button>
            <?php if ($search): ?>
                <a href="index.php"
                    class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg text-sm flex items-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- List View -->
    <div class="saas-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Document
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Category
                        </th>
                        <th
                            class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">
                            Version</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Visibility
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last
                            Updated</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($docs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic text-sm">No documents found
                                matching your criteria.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($docs as $doc): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary-50 text-primary-600 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </p>
                                        <p class="text-[10px] text-slate-400 line-clamp-1 max-w-xs">
                                            <?php echo strip_tags($doc['content']); ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase tracking-tight">
                                    <?php echo htmlspecialchars($doc['category'] ?: 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="text-xs font-mono text-slate-500">v<?php echo htmlspecialchars($doc['version']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (($doc['visibility'] ?? 'public') == 'public'): ?>
                                    <span class="flex items-center gap-1.5 text-emerald-600">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-tight">Public</span>
                                    </span>
                                <?php else: ?>
                                    <span class="flex items-center gap-1.5 text-amber-600">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-tight">Private</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <?php echo time_elapsed_string($doc['last_updated']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="view.php?id=<?php echo $doc['id']; ?>"
                                        class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="manage.php?id=<?php echo $doc['id']; ?>"
                                        class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button type="button" class="p-2 text-slate-400 hover:text-red-500 transition-colors"
                                        title="Delete"
                                        @click="$store.modal.trigger('deleteForm<?php echo $doc['id']; ?>', 'Are you sure you want to delete this document? This action cannot be undone.', 'Confirm Deletion')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <form id="deleteForm<?php echo $doc['id']; ?>" action="delete.php" method="POST"
                                        class="hidden">
                                        <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-slate-500">
                Showing <?php echo min($offset + 1, $total_docs); ?>-<?php echo min($offset + $per_page, $total_docs); ?> of
                <?php echo $total_docs; ?> documents
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                        class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Previous
                    </a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                        class="px-4 py-2 <?php echo $i == $page ? 'bg-primary-500 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'; ?> rounded-lg text-sm font-medium transition-colors">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                        class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Next
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>