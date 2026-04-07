<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "External Systems";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM external_systems WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    redirect($_SERVER['REQUEST_URI']);
}

$stmt = $pdo->query("SELECT * FROM external_systems ORDER BY name ASC");
$links = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">External Systems</h1>
        <p class="text-slate-500 mt-2">Quick access links to other management portals.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Link
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in-up" style="animation-delay: 0.1s">
    <?php foreach ($links as $link): ?>
        <div
            class="saas-card p-6 flex flex-col h-full hover:shadow-lg transition-all transform hover:-translate-y-1 group relative">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <div
                        class="h-10 w-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center mr-3 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 line-clamp-1">
                        <?php echo htmlspecialchars($link['name']); ?>
                    </h3>
                </div>

                <div
                    class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex bg-white/90 backdrop-blur rounded-lg shadow-sm border border-slate-100 p-1">
                    <a href="/ict/modules/external/edit.php?id=<?php echo $link['id']; ?>"
                        class="p-1.5 text-slate-400 hover:text-primary-600 transition-colors rounded-md hover:bg-primary-50"
                        title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                            </path>
                        </svg>
                    </a>
                    <form method="POST" action="" id="delete-link-<?php echo $link['id']; ?>" class="inline-block">
                        <input type="hidden" name="delete_id" value="<?php echo $link['id']; ?>">
                        <button type="button"
                            @click="$store.modal.trigger('delete-link-<?php echo $link['id']; ?>', 'Delete this link?', 'Delete Link')"
                            class="p-1.5 text-slate-400 hover:text-red-600 transition-colors rounded-md hover:bg-red-50"
                            title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex-grow">
                <?php if ($link['notes']): ?>
                    <p class="text-sm text-slate-500 mb-3 line-clamp-2">
                        <?php echo htmlspecialchars($link['notes']); ?>
                    </p>
                <?php else: ?>
                    <p class="text-sm text-slate-400 mb-3 italic">No description provided.</p>
                <?php endif; ?>

                <?php if ($link['owner']): ?>
                    <div class="flex items-center text-xs text-slate-400 mt-2 mb-4">
                        <span class="font-medium mr-1">Owner:</span> <?php echo htmlspecialchars($link['owner']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-50">
                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"
                    class="flex items-center justify-center w-full bg-slate-50 hover:bg-primary-50 text-primary-600 font-medium py-2 rounded-lg transition-colors border border-slate-200 hover:border-primary-100 group-hover:shadow-sm">
                    Launch Portal <span class="ml-1 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </a>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Add New Placeholder -->
    <a href="create.php"
        class="border-2 border-dashed border-slate-200 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:text-primary-600 hover:border-primary-300 hover:bg-primary-50/30 transition-all group h-full min-h-[200px]">
        <div
            class="h-14 w-14 rounded-full bg-slate-50 group-hover:bg-primary-100 flex items-center justify-center mb-4 transition-colors">
            <svg class="w-8 h-8 group-hover:scale-110 transition-transform duration-300" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </div>
        <span class="font-medium text-lg">Add New System</span>
        <span class="text-xs text-slate-400 mt-1">Link an external portal</span>
    </a>
</div>

<?php include '../../includes/footer.php'; ?>