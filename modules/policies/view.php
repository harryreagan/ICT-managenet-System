<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc)
    redirect('/ict/modules/policies');

$pageTitle = htmlspecialchars($doc['title']);
include '../../includes/header.php';
?>

<div class="max-w-4xl mx-auto fade-in-up">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <a href="index.php"
                class="text-primary-600 hover:text-primary-800 text-sm font-medium flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Documentation Center
            </a>
            <h1 class="text-3xl font-bold text-slate-800">
                <?php echo htmlspecialchars($doc['title']); ?>
            </h1>
            <div class="flex items-center space-x-3 mt-2 text-xs text-slate-500 font-medium">
                <span class="px-2 py-0.5 bg-primary-50 text-primary-600 rounded uppercase tracking-wider font-bold">V
                    <?php echo $doc['version']; ?>
                </span>
                <span>Category:
                    <?php echo htmlspecialchars($doc['category']); ?>
                </span>
                <span>&bull;</span>
                <span>By
                    <?php echo htmlspecialchars($doc['author']); ?>
                </span>
                <span>&bull;</span>
                <span>Updated
                    <?php echo date('M d, Y', strtotime($doc['last_updated'])); ?>
                </span>
            </div>
        </div>
        <div class="flex space-x-2">
            <a href="/ict/modules/policies/manage.php?id=<?php echo $doc['id']; ?>"
                class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Edit
                Documentation</a>
            <button onclick="window.print()"
                class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm font-medium hover:bg-primary-600 shadow-primary-500/20">Print
                / PDF</button>
        </div>
    </div>

    <?php if ($doc['image_path']): ?>
        <div class="mb-8 rounded-2xl overflow-hidden border border-slate-100 shadow-sm bg-white p-2">
            <img src="/ict/<?php echo $doc['image_path']; ?>" alt="Reference Image"
                class="w-full object-contain max-h-[500px] rounded-xl">
            <div
                class="px-4 py-2 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center">
                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Document Reference Image
            </div>
        </div>
    <?php endif; ?>

    <div class="saas-card p-8 md:p-12 mb-10 prose max-w-none">
        <div class="text-slate-700 leading-relaxed quill-content ql-editor">
            <?php echo $doc['content']; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>