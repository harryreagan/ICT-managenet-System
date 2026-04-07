<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Shift Handover";

// Handle archiving
if (isset($_POST['archive_id'])) {
    $stmt = $pdo->prepare("UPDATE handover_notes SET status = 'archived' WHERE id = ?");
    $stmt->execute([$_POST['archive_id']]);
    $_SESSION['success'] = "Handover note archived.";
    redirect('index.php');
}

// Handle deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM handover_notes WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $_SESSION['success'] = "Handover note permanently deleted.";
    redirect('index.php');
}

// Pagination settings
$limit = 7;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch active notes
$notes = [];
$table_exists = true;
$column_exists = true;
$total_pages = 0;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'handover_notes'");
    if (!$stmt->fetch()) {
        $table_exists = false;
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM handover_notes LIKE 'note_category'");
        if (!$stmt->fetch()) {
            $column_exists = false;
        } else {
            // Count total records for pagination (now including all records for future reference)
            $countStmt = $pdo->query("SELECT COUNT(*) FROM handover_notes");
            $total_records = $countStmt->fetchColumn();
            $total_pages = ceil($total_records / $limit);

            // Fetch records with limit and offset
            $stmt = $pdo->prepare("
                SELECT h.*, u.full_name, u.username 
                FROM handover_notes h 
                JOIN users u ON h.user_id = u.id 
                ORDER BY h.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $notes = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    $table_exists = false;
}

// Fetch on duty staff
$onDutyStaff = [];
try {
    $stmt = $pdo->query("SELECT full_name, username, role FROM users WHERE is_on_duty = 1 AND status = 'active'");
    $onDutyStaff = $stmt->fetchAll();
} catch (PDOException $e) {
    // Column might be missing
}

include '../../includes/header.php';

if (!$table_exists || !$column_exists): ?>
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
                <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider">Database Update Required</h3>
                <div class="mt-1 text-sm text-amber-700">
                    <p>The Handover module needs a small database update to work with your refined 8-to-5 schedule. This
                        will only take a second.</p>
                </div>
                <div class="mt-4">
                    <a href="../../migrate_handover.php"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none transition-colors uppercase tracking-widest">
                        Fix Database Structure
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Shift Handover</h1>
        <p class="text-slate-500 mt-2">Pass important information to the next shift and track on-duty status.</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                </path>
            </svg>
            Write Handover
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 fade-in-up">
    <!-- Main Handover Feed -->
    <div class="lg:col-span-2 space-y-6">
        <?php if (empty($notes)): ?>
            <div class="saas-card p-12 text-center text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <p class="text-lg font-bold text-slate-600">No records found</p>
                <p class="text-sm">New notes will appear here once submitted.</p>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php
                $currentGroupDate = null;
                foreach ($notes as $note):
                    $noteDate = date('F d, Y', strtotime($note['created_at']));
                    if ($noteDate !== $currentGroupDate):
                        $currentGroupDate = $noteDate;
                        $isToday = $noteDate === date('F d, Y');
                        ?>
                        <div class="flex items-center gap-4 py-2 mt-4 first:mt-0">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 whitespace-nowrap">
                                <?php echo $isToday ? 'Today' : $noteDate; ?>
                            </h3>
                            <div class="h-px w-full bg-slate-100"></div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $priorityColors = [
                        'low' => 'slate',
                        'medium' => 'blue',
                        'high' => 'amber',
                        'critical' => 'rose'
                    ];
                    $color = $priorityColors[$note['priority']] ?? 'slate';
                    ?>
                    <div onclick="openNoteModal(<?php echo htmlspecialchars(json_encode($note)); ?>)"
                        class="saas-card p-5 border-slate-200 hover:border-primary-400 cursor-pointer transition-all flex items-center justify-between group">

                        <div class="flex items-center space-x-4">
                            <!-- Minimal Avatar -->
                            <div
                                class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 text-xs shrink-0">
                                <?php echo strtoupper(substr($note['username'], 0, 2)); ?>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">
                                    <?php echo htmlspecialchars($note['full_name']); ?>
                                </h4>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span
                                        class="text-[10px] text-slate-400 font-bold uppercase tracking-widest whitespace-nowrap">
                                        <?php echo $note['note_category'] ?? 'General'; ?> •
                                        <?php echo date('H:i', strtotime($note['created_at'])); ?>
                                    </span>
                                    <span
                                        class="px-1.5 py-0.5 rounded-[4px] text-[9px] font-black uppercase tracking-tight bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-600 border border-<?php echo $color; ?>-100">
                                        <?php echo $note['priority']; ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-1">
                                    <?php echo htmlspecialchars($note['content']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="edit.php?id=<?php echo $note['id']; ?>"
                                class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Edit Note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Archive this note?')">
                                <input type="hidden" name="archive_id" value="<?php echo $note['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors"
                                    title="Archive Note">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('PERMANENTLY DELETE this note?')">
                                <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition-colors"
                                    title="Delete Permanently">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex items-center justify-between pt-8 border-t border-slate-100">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                                        </path>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a href="?page=<?php echo $i; ?>"
                                    class="px-3.5 py-2 rounded-lg border <?php echo $i === $page ? 'bg-primary-600 text-white border-primary-600 shadow-lg shadow-primary-500/20' : 'border-slate-200 text-slate-600 hover:bg-slate-50'; ?> text-xs font-bold transition-all">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                        </path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <!-- On Duty Quick Status -->
        <div class="saas-card p-6 border-slate-200">
            <div class="relative z-10">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">Available Staff On-Duty</h3>
                <div class="space-y-4">
                    <?php if (empty($onDutyStaff)): ?>
                        <p class="text-slate-400 text-sm italic">No staff currently marked as on-duty.</p>
                    <?php else: ?>
                        <?php foreach ($onDutyStaff as $staff): ?>
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs border border-emerald-100">
                                        <?php echo strtoupper(substr($staff['username'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-bold text-slate-800 group-hover:text-primary-600 transition-colors">
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                        </p>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest">
                                            <?php echo $staff['role']; ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <button onclick="toggleDutyStatus()" id="dutyToggleButton"
                        class="w-full py-3 px-4 rounded-xl text-sm font-bold uppercase tracking-wider transition-all
                        <?php echo isUserOnDuty($pdo) ? 'bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100' : 'bg-primary-600 text-white shadow-lg shadow-primary-500/20 hover:bg-primary-700'; ?>">
                        <?php echo isUserOnDuty($pdo) ? 'Clock Off Duty' : 'Go On Duty'; ?>
                    </button>
                    <p class="text-[10px] text-slate-400 mt-3 text-center">Your status is visible to all IT staff.</p>
                </div>
            </div>
        </div>

        <!-- Office Schedule Reminder -->
        <div class="saas-card p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Office Hours</h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Standard Day</span>
                    <span class="font-bold text-slate-700">08:00 - 17:00</span>
                </div>
                <div class="flex justify-between text-sm italic text-slate-400">
                    <span>Handover notes are essential for after-hours tasks and team leave updates.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Handover Note Modal -->
<div id="noteModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div onclick="closeNoteModal()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
            <!-- Modal Header -->
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-start">
                <div class="flex items-center space-x-4">
                    <div id="modalUserAvatar"
                        class="w-12 h-12 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center font-bold text-lg border border-primary-100">
                        <!-- JS fills this -->
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800" id="modalUserName">Handover Report</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest" id="modalMeta">
                            <!-- JS fills this -->
                        </p>
                    </div>
                </div>
                <button onclick="closeNoteModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="px-8 py-8">
                <div id="modalPriorityBadge"
                    class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-6">
                    <!-- JS fills this -->
                </div>
                <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed" id="modalContent">
                    <!-- JS fills this -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeNoteModal()"
                    class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold uppercase tracking-widest hover:bg-slate-50 transition-all">
                    Close Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    async function toggleDutyStatus() {
        const button = document.getElementById('dutyToggleButton');
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        try {
            const response = await fetch('toggle_duty.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                window.location.reload();
            } else {
                alert('Failed to update status: ' + result.message);
                window.location.reload();
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred. Please try again.');
            window.location.reload();
        }
    }

    function openNoteModal(note) {
        document.getElementById('modalUserName').textContent = note.full_name;
        document.getElementById('modalUserAvatar').textContent = note.username.substring(0, 2).toUpperCase();

        // Format meta data
        const date = new Date(note.created_at);
        const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        document.getElementById('modalMeta').textContent = `${note.note_category} • ${dateStr}`;

        // Content
        document.getElementById('modalContent').innerHTML = note.content.replace(/\n/g, '<br>');

        // Priority Badge
        const badge = document.getElementById('modalPriorityBadge');
        badge.textContent = note.priority;
        badge.className = 'inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 ';

        const colors = {
            'low': 'bg-slate-100 text-slate-600 border border-slate-200',
            'medium': 'bg-blue-50 text-blue-600 border border-blue-100',
            'high': 'bg-amber-50 text-amber-600 border border-amber-100',
            'critical': 'bg-rose-50 text-rose-600 border border-rose-100'
        };
        badge.className += colors[note.priority] || colors['low'];

        // Show modal
        document.getElementById('noteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeNoteModal() {
        document.getElementById('noteModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeNoteModal();
    });
</script>

<?php include '../../includes/footer.php'; ?>