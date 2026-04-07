<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Check if database structure is ready
$column_exists = true;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM handover_notes LIKE 'note_category'");
    if (!$stmt->fetch()) {
        $column_exists = false;
    }
} catch (PDOException $e) {
    $column_exists = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $column_exists) {
    $note_category = $_POST['note_category'] ?? 'Daily Update';
    $content = $_POST['content'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';

    if (!empty(trim($content))) {
        try {
            $stmt = $pdo->prepare("INSERT INTO handover_notes (user_id, note_category, content, priority, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$_SESSION['user_id'], $note_category, $content, $priority]);

            $_SESSION['success'] = "Handover note saved successfully!";
            redirect('index.php');
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please provide some content for the handover note.";
    }
}

$pageTitle = "Write Handover Note";
include '../../includes/header.php';
?>

<div class="mb-6">
    <a href="index.php"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Handover
    </a>
</div>

<div class="max-w-3xl mx-auto mb-12">
    <div class="saas-card overflow-hidden">
        <div class="px-8 py-10 border-b border-slate-100 bg-white">
            <h2 class="text-3xl font-extrabold text-slate-800">New Handover Report</h2>
            <p class="text-slate-500 mt-2 text-sm leading-relaxed">Pass critical information to the incoming members
                clearly and concisely.</p>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-lg text-sm mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!$column_exists): ?>
                <div class="bg-amber-50 border-l-4 border-amber-400 p-6 mb-8 rounded-r-xl shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider">Database Update Required
                            </h3>
                            <div class="mt-1 text-sm text-amber-700">
                                <p>A quick database update is needed before you can write notes. This will enable the new
                                    categories for your 8-to-5 schedule.</p>
                            </div>
                            <div class="mt-4">
                                <a href="../../migrate_handover.php"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none transition-colors uppercase tracking-widest">
                                    Fix Database Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="opacity-50 pointer-events-none">
                <?php endif; ?>

                <form action="create.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-slate-700 text-sm font-bold mb-2" for="note_category">Note
                                Category</label>
                            <select name="note_category" id="note_category" required
                                class="shadow-sm border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all bg-white">
                                <option value="Daily Update" selected>Daily Update (End of Day)</option>
                                <option value="Weekend Handover">Weekend Handover</option>
                                <option value="Out of Office">Out of Office / Leave</option>
                                <option value="Custom">Custom / Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-700 text-sm font-bold mb-2" for="priority">Priority
                                Level</label>
                            <select name="priority" id="priority" required
                                class="shadow-sm border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all bg-white">
                                <option value="low">Low - General Info</option>
                                <option value="medium" selected>Medium - Action Required</option>
                                <option value="high">High - Urgent</option>
                                <option value="critical">Critical - System Outage</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="content">Handover
                            Details</label>
                        <textarea name="content" id="content" rows="10" required
                            class="shadow-sm border border-slate-200 rounded-lg w-full py-3 px-4 text-slate-700 leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                            placeholder="Detail which tasks are pending, any system issues, or important guest requests..."></textarea>
                        <p class="mt-2 text-[11px] text-slate-400 italic">Be clear and concise. Use bullet points for
                            readability.</p>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-4">
                        <a href="index.php"
                            class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
                        <button type="submit"
                            class="bg-primary-600 hover:bg-primary-700 text-white px-10 py-3.5 rounded-xl text-sm font-bold uppercase tracking-widest transition-all shadow-md hover:shadow-lg active:scale-95">
                            Submit Report
                        </button>
                    </div>
                </form>
                <?php if (!$column_exists): ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>