<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('index.php');
}

// Fetch existing note
$stmt = $pdo->prepare("SELECT * FROM handover_notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    $_SESSION['error'] = "Note not found.";
    redirect('index.php');
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_category = $_POST['note_category'] ?? 'Daily Update';
    $content = $_POST['content'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';

    if (!empty(trim($content))) {
        try {
            $stmt = $pdo->prepare("UPDATE handover_notes SET note_category = ?, content = ?, priority = ? WHERE id = ?");
            $stmt->execute([$note_category, $content, $priority, $id]);

            $_SESSION['success'] = "Handover note updated successfully!";
            redirect('index.php');
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please provide some content for the handover note.";
    }
}

$pageTitle = "Edit Handover Note";
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
            <h2 class="text-3xl font-extrabold text-slate-800">Edit Handover Report</h2>
            <p class="text-slate-500 mt-2 text-sm leading-relaxed">Update the report details to ensure accurate
                information handover.</p>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-lg text-sm mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="note_category">Note
                            Category</label>
                        <select name="note_category" id="note_category" required
                            class="shadow-sm border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all bg-white">
                            <option value="Daily Update" <?php echo $note['note_category'] === 'Daily Update' ? 'selected' : ''; ?>>Daily Update (End of Day)</option>
                            <option value="Weekend Handover" <?php echo $note['note_category'] === 'Weekend Handover' ? 'selected' : ''; ?>>Weekend Handover</option>
                            <option value="Out of Office" <?php echo $note['note_category'] === 'Out of Office' ? 'selected' : ''; ?>>Out of Office / Leave</option>
                            <option value="Custom" <?php echo $note['note_category'] === 'Custom' ? 'selected' : ''; ?>
                                >Custom / Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="priority">Priority
                            Level</label>
                        <select name="priority" id="priority" required
                            class="shadow-sm border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all bg-white">
                            <option value="low" <?php echo $note['priority'] === 'low' ? 'selected' : ''; ?>>Low -
                                General Info</option>
                            <option value="medium" <?php echo $note['priority'] === 'medium' ? 'selected' : ''; ?>>Medium
                                - Action Required</option>
                            <option value="high" <?php echo $note['priority'] === 'high' ? 'selected' : ''; ?>>High -
                                Urgent</option>
                            <option value="critical" <?php echo $note['priority'] === 'critical' ? 'selected' : ''; ?>
                                >Critical - System Outage</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="content">Handover
                        Details</label>
                    <textarea name="content" id="content" rows="10" required
                        class="shadow-sm border border-slate-200 rounded-lg w-full py-3 px-4 text-slate-700 leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="Detail which tasks are pending, any system issues, or important guest requests..."><?php echo htmlspecialchars($note['content']); ?></textarea>
                </div>

                <div class="flex items-center justify-end space-x-4 pt-4">
                    <a href="index.php"
                        class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white px-10 py-3.5 rounded-xl text-sm font-bold uppercase tracking-widest transition-all shadow-md hover:shadow-lg active:scale-95">
                        Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>