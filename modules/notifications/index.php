<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (isset($_POST['mark_all_read'])) {
    $pdo->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    $_SESSION['success'] = "All notifications marked as read.";
    redirect('index.php');
}

$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = $stmt->fetchAll();

$pageTitle = "Notification Center";
include '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Notification Center</h1>
        <p class="text-slate-500 text-sm">Review system alerts and automated events</p>
    </div>
    <form action="index.php" method="POST">
        <button type="submit" name="mark_all_read"
            class="text-primary-600 bg-primary-50 px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-wider hover:bg-primary-100 transition-all">
            Mark All Read
        </button>
    </form>
</div>

<div class="saas-card overflow-hidden">
    <div class="divide-y divide-slate-100">
        <?php foreach ($notifications as $n): ?>
            <div
                class="p-6 hover:bg-slate-50/50 transition-colors <?php echo !$n['is_read'] ? 'bg-primary-50/20 border-l-4 border-l-primary-500' : ''; ?>">
                <div class="flex justify-between items-start">
                    <div class="flex items-start space-x-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center shadow-sm">
                            <?php if ($n['type'] === 'alert'): ?>
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            <?php elseif ($n['type'] === 'warning'): ?>
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            <?php else: ?>
                                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700 mb-1">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </p>
                            <div class="flex items-center space-x-3">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                    <?php echo $n['type']; ?>
                                </span>
                                <span class="text-slate-200">|</span>
                                <span class="text-[10px] text-slate-400 font-medium"
                                    title="<?php echo date('M d, Y H:i', strtotime($n['created_at'])); ?>">
                                    <?php echo time_elapsed_string($n['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php if ($n['link_url']): ?>
                        <a href="<?php echo $n['link_url']; ?>"
                            class="bg-primary-500 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-primary-600 transition-all shadow-sm">
                            View Action
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
            <div class="p-12 text-center">
                <p class="text-slate-400 italic text-sm">No notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>