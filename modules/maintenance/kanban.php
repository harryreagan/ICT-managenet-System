<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "ICT Project Board";

// Handle status updates via AJAX/POST
if (isset($_POST['task_id']) && isset($_POST['new_status'])) {
    $stmt = $pdo->prepare("UPDATE maintenance_tasks SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['task_id']]);
    exit;
}

$stmt = $pdo->query("SELECT * FROM maintenance_tasks ORDER BY priority DESC, created_at DESC");
$tasks = $stmt->fetchAll();

$todo = array_filter($tasks, fn($t) => $t['status'] === 'pending');
$doing = array_filter($tasks, fn($t) => $t['status'] === 'in_progress');
$done = array_filter($tasks, fn($t) => $t['status'] === 'completed');

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-8 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Project Board</h1>
        <p class="text-slate-500 mt-2 text-sm uppercase font-bold tracking-widest">Digital Kanban for ICT Improvements
        </p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-2">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-bold uppercase tracking-widest rounded-lg shadow-lg shadow-primary-500/20 transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Task
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[70vh]">

    <!-- Pending / To-Do Column -->
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                <span class="w-2 h-2 rounded-full bg-slate-300 mr-2"></span>
                To-Do Backlog
                <span class="ml-2 bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full text-[10px]">
                    <?php echo count($todo); ?>
                </span>
            </h3>
        </div>
        <div class="flex-1 bg-slate-50/50 rounded-2xl border-2 border-dashed border-slate-200 p-4 space-y-4 overflow-y-auto custom-scrollbar kanban-column"
            data-status="pending">
            <?php foreach ($todo as $t): ?>
                <?php renderKanbanCard($t); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- In Progress Column -->
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-xs font-black text-amber-500 uppercase tracking-widest flex items-center">
                <span class="w-2 h-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                In Progress
                <span class="ml-2 bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full text-[10px]">
                    <?php echo count($doing); ?>
                </span>
            </h3>
        </div>
        <div class="flex-1 bg-amber-50/30 rounded-2xl border-2 border-dashed border-amber-200/50 p-4 space-y-4 overflow-y-auto custom-scrollbar kanban-column"
            data-status="in_progress">
            <?php foreach ($doing as $t): ?>
                <?php renderKanbanCard($t); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Completed Column -->
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-xs font-black text-emerald-500 uppercase tracking-widest flex items-center">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                Completed
                <span class="ml-2 bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full text-[10px]">
                    <?php echo count($done); ?>
                </span>
            </h3>
        </div>
        <div class="flex-1 bg-emerald-50/30 rounded-2xl border-2 border-dashed border-emerald-200/50 p-4 space-y-4 overflow-y-auto custom-scrollbar kanban-column"
            data-status="completed">
            <?php foreach ($done as $t): ?>
                <?php renderKanbanCard($t); ?>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php
function renderKanbanCard($task)
{
    ?>
    <div class="saas-card p-4 cursor-grab active:cursor-grabbing hover:shadow-xl transition-all group" draggable="true"
        data-id="<?php echo $task['id']; ?>">
        <div class="flex justify-between items-start mb-2">
            <span class="text-[9px] font-black uppercase tracking-widest border px-1.5 py-0.5 rounded <?php
            echo match ($task['priority']) {
                'critical' => 'text-red-600 border-red-100 bg-red-50',
                'high' => 'text-amber-600 border-amber-100 bg-amber-50',
                'medium' => 'text-primary-600 border-primary-100 bg-primary-50',
                default => 'text-slate-400 border-slate-100 bg-slate-50'
            };
            ?>">
                <?php echo $task['priority']; ?>
            </span>
            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <a href="edit.php?id=<?php echo $task['id']; ?>" class="text-slate-400 hover:text-primary-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                        </path>
                    </svg>
                </a>
            </div>
        </div>
        <h4 class="text-xs font-bold text-slate-800 mb-1 leading-snug">
            <?php echo htmlspecialchars($task['description']); ?>
        </h4>
        <div class="flex items-center justify-between mt-4">
            <div class="flex -space-x-1.5 overflow-hidden">
                <div class="inline-block h-5 w-5 rounded-full ring-2 ring-white bg-primary-100 flex items-center justify-center text-[8px] font-black text-primary-600"
                    title="Assigned to: <?php echo htmlspecialchars($task['assigned_to'] ?: 'Unassigned'); ?>">
                    <?php echo strtoupper(substr($task['assigned_to'] ?: '?', 0, 1)); ?>
                </div>
            </div>
            <span class="text-[9px] text-slate-400 font-medium">
                <?php echo date('M d', strtotime($task['created_at'])); ?>
            </span>
        </div>
    </div>
    <?php
}
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('[draggable="true"]');
        const columns = document.querySelectorAll('.kanban-column');

        cards.forEach(card => {
            card.addEventListener('dragstart', () => card.classList.add('opacity-50'));
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
        });

        columns.forEach(column => {
            column.addEventListener('dragover', e => {
                e.preventDefault();
                column.classList.add('bg-primary-50/20');
            });

            column.addEventListener('dragleave', () => {
                column.classList.remove('bg-primary-50/20');
            });

            column.addEventListener('drop', async e => {
                e.preventDefault();
                column.classList.remove('bg-primary-50/20');
                const card = document.querySelector('.opacity-50');
                const newStatus = column.dataset.status;
                const taskId = card.dataset.id;

                column.appendChild(card);

                // Save to DB
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('new_status', newStatus);

                await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>