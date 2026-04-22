<?php
require_once __DIR__ . '/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    header("Location: knowledge_base.php");
    exit;
}

// Fetch SOP Details
$sop_visibility_filter = '';
try {
    $sopHasVisibility = $pdo->query("SHOW COLUMNS FROM sop_documents LIKE 'visibility'")->rowCount() > 0;
    if ($sopHasVisibility) {
        $sop_visibility_filter = " AND visibility = 'public'";
    }
} catch (PDOException $e) {
    $sop_visibility_filter = '';
}

$stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE id = ?{$sop_visibility_filter}");
$stmt->execute([$id]);
$sop = $stmt->fetch();

if (!$sop) {
    header("Location: knowledge_base.php");
    exit;
}

renderPortalHeader("Guide: " . htmlspecialchars($sop['title']));
?>

<div class="space-y-8">
    <!-- Back Navigation -->
    <a href="knowledge_base.php"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Back to Knowledge Base
    </a>

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-6">
            <span
                class="bg-primary-50 text-primary-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-primary-100">
                <?= htmlspecialchars($sop['category']) ?>
            </span>
            <span class="text-xs text-slate-400">•</span>
            <span class="text-xs text-slate-500">Version <?= htmlspecialchars($sop['version']) ?></span>
        </div>

        <h1 class="text-3xl font-bold text-slate-800 mb-4 leading-tight">
            <?= htmlspecialchars($sop['title']) ?>
        </h1>

        <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
            <div
                class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center text-sm font-bold text-primary-600">
                <?= strtoupper(substr($sop['author'] ?: 'D', 0, 1)) ?>
            </div>
            <div>
                <p class="text-xs text-slate-500">Author</p>
                <p class="text-sm font-bold text-slate-700">
                    <?= htmlspecialchars($sop['author'] ?: 'Dallas Premiere ICT') ?>
                </p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-slate-500">Last Updated</p>
                <p class="text-sm font-bold text-slate-700">
                    <?= date('M j, Y', strtotime($sop['last_updated'])) ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500">Reading Time</p>
                <p class="text-sm font-bold text-slate-700">
                    <?php
                    $wordCount = str_word_count(strip_tags($sop['content']));
                    $readingTime = ceil($wordCount / 200);
                    echo $readingTime . ' min';
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
        <div class="prose prose-slate max-w-none">
            <?= $sop['content'] ?>
        </div>
    </div>

    <!-- Help Card -->
    <div
        class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
        <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6">
            <div
                class="w-16 h-16 bg-white/10 backdrop-blur-xl rounded-xl flex items-center justify-center text-3xl shrink-0">
                ❓
            </div>
            <div class="flex-grow text-center lg:text-left">
                <h2 class="text-lg font-bold mb-2 uppercase tracking-wide">Need More Help?</h2>
                <p class="text-slate-300 text-sm leading-relaxed">
                    If you have questions about this guide, submit a ticket and our team will assist you.
                </p>
            </div>
            <a href="submit_ticket.php?category=<?= urlencode($sop['category']) ?>&subject=RE: <?= urlencode($sop['title']) ?>"
                class="px-6 py-3 bg-white text-slate-900 text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-gray-100 transition-colors shadow-lg whitespace-nowrap">
                Submit Ticket
            </a>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>
