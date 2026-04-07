<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// 1. Auto-migration: Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_status (
        id INT PRIMARY KEY DEFAULT 1,
        status ENUM('operational', 'partial_outage', 'major_outage') NOT NULL DEFAULT 'operational',
        message TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Ensure one row exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_status");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_status (id, status, message) VALUES (1, 'operational', 'Network and core services are running normally.')");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$error = '';
$success = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status']);
    $message = sanitize($_POST['message']);

    try {
        $stmt = $pdo->prepare("UPDATE system_status SET status = ?, message = ? WHERE id = 1");
        $stmt->execute([$status, $message]);
        $success = "System status updated successfully!";

        // Log activity
        logActivity($pdo, $_SESSION['user_id'], "Updated system status to: " . strtoupper($status));
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current status
$stmt = $pdo->query("SELECT * FROM system_status WHERE id = 1");
$current = $stmt->fetch();

$pageTitle = "System Status Manager";
include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-in-up">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-800">System Status Manager</h1>
        <p class="text-slate-500 mt-1">Update the operational status shown to all portal users.</p>
    </div>

    <div class="saas-card p-8">
        <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-slate-700 text-sm font-bold mb-2">Current Health Status</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="status" value="operational" class="peer sr-only" <?php echo $current['status'] === 'operational' ? 'checked' : ''; ?>>
                        <div
                            class="p-4 border rounded-xl bg-slate-50 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 transition-all text-center">
                            <span class="block text-xl mb-1">✅</span>
                            <span
                                class="text-xs font-bold text-slate-600 peer-checked:text-emerald-700 uppercase tracking-widest">Operational</span>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="status" value="partial_outage" class="peer sr-only" <?php echo $current['status'] === 'partial_outage' ? 'checked' : ''; ?>>
                        <div
                            class="p-4 border rounded-xl bg-slate-50 peer-checked:bg-amber-50 peer-checked:border-amber-500 transition-all text-center">
                            <span class="block text-xl mb-1">⚠️</span>
                            <span
                                class="text-xs font-bold text-slate-600 peer-checked:text-amber-700 uppercase tracking-widest">Partial</span>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="status" value="major_outage" class="peer sr-only" <?php echo $current['status'] === 'major_outage' ? 'checked' : ''; ?>>
                        <div
                            class="p-4 border rounded-xl bg-slate-50 peer-checked:bg-red-50 peer-checked:border-red-500 transition-all text-center">
                            <span class="block text-xl mb-1">🛑</span>
                            <span
                                class="text-xs font-bold text-slate-600 peer-checked:text-red-700 uppercase tracking-widest">Major
                                Outage</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-slate-700 text-sm font-bold mb-2" for="message">Public Notice Message</label>
                <textarea id="message" name="message" rows="3"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all"
                    placeholder="Describe the current situation for users..."><?php echo htmlspecialchars($current['message']); ?></textarea>
                <p class="text-[10px] text-slate-400 mt-2 italic">Keep it short and informative. This appears on the
                    portal home page.</p>
            </div>

            <div class="pt-4 border-t border-slate-50 flex justify-end">
                <button type="submit"
                    class="bg-slate-900 hover:bg-black text-white px-8 py-3 rounded-xl text-sm font-bold uppercase tracking-widest transition-all shadow-lg hover:shadow-xl">
                    Push Update
                </button>
            </div>
        </form>
    </div>

    <div class="mt-8 p-4 bg-primary-50 rounded-xl border border-primary-100/50 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="p-2 bg-white rounded-lg text-primary-500 shadow-sm border border-primary-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-primary-600 uppercase tracking-widest">Last Updated</p>
                <p class="text-xs text-slate-600">
                    <?php echo timeAgo($current['updated_at']); ?>
                </p>
            </div>
        </div>
        <a href="/ict/portal/index.php" target="_blank"
            class="text-xs font-bold text-primary-700 hover:underline">Preview Portal &rarr;</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>