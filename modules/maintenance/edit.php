<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/maintenance/index.php');

$stmt = $pdo->prepare("SELECT * FROM maintenance_tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task)
    redirect('/ict/modules/maintenance/index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = sanitize($_POST['description']);
    $priority = sanitize($_POST['priority']);
    $proposed_solution = sanitize($_POST['proposed_solution']);
    $estimated_cost = sanitize($_POST['estimated_cost']);
    $status = sanitize($_POST['status']);
    $assigned_to = sanitize($_POST['assigned_to']);

    // Scheduler Fields
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $frequency = $is_recurring ? sanitize($_POST['frequency']) : null;
    $next_due_date = sanitize($_POST['next_due_date']); // YYYY-MM-DD
    $start_time = !empty($_POST['start_time']) ? sanitize($_POST['start_time']) : null;
    $end_time = !empty($_POST['end_time']) ? sanitize($_POST['end_time']) : null;
    $show_on_portal = isset($_POST['show_on_portal']) ? 1 : 0;
    $impact = sanitize($_POST['impact']);

    if (empty($description)) {
        $error = "Description is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE maintenance_tasks SET description=?, priority=?, proposed_solution=?, estimated_cost=?, status=?, assigned_to=?, is_recurring=?, frequency=?, next_due_date=?, start_time=?, end_time=?, show_on_portal=?, impact=? WHERE id=?");
            $stmt->execute([$description, $priority, $proposed_solution, $estimated_cost ?: 0, $status, $assigned_to, $is_recurring, $frequency, $next_due_date, $start_time, $end_time, $show_on_portal, $impact, $id]);
            $_SESSION['success'] = "Maintenance task updated successfully!";
            redirect('/ict/modules/maintenance/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Edit Task</h1>
        <a href="index.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" x-data="{ recurring: <?php echo $task['is_recurring'] ? 'true' : 'false'; ?> }">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="description">Task Description
                        *</label>
                    <input type="text" name="description" id="description"
                        value="<?php echo htmlspecialchars($task['description']); ?>" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="priority">Priority</label>
                    <select name="priority" id="priority"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium
                        </option>
                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>
                            Critical</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="status">Status</label>
                    <select name="status" id="status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In
                            Progress</option>
                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>
                            Completed</option>
                    </select>
                </div>

                <!-- Recurring Toggle -->
                <div class="col-span-2 bg-slate-50 p-4 rounded border border-slate-200">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" name="is_recurring" id="is_recurring" x-model="recurring" <?php echo $task['is_recurring'] ? 'checked' : ''; ?>
                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="is_recurring" class="ml-2 block text-sm font-bold text-slate-700">
                            Recurring / Scheduled Task
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div x-show="recurring">
                            <label class="block text-slate-700 text-sm font-bold mb-2" for="frequency">Frequency</label>
                            <select name="frequency" id="frequency"
                                class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                                <option value="daily" <?php echo $task['frequency'] === 'daily' ? 'selected' : ''; ?>>
                                    Daily</option>
                                <option value="weekly" <?php echo $task['frequency'] === 'weekly' ? 'selected' : ''; ?>>
                                    Weekly</option>
                                <option value="monthly" <?php echo $task['frequency'] === 'monthly' ? 'selected' : ''; ?>>
                                    Monthly</option>
                                <option value="quarterly" <?php echo $task['frequency'] === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                <option value="yearly" <?php echo $task['frequency'] === 'yearly' ? 'selected' : ''; ?>>
                                    Yearly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-700 text-sm font-bold mb-2" for="next_due_date">Due Date /
                                Next Run</label>
                            <input type="date" name="next_due_date" id="next_due_date"
                                value="<?php echo $task['next_due_date']; ?>"
                                class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                        </div>
                    </div>
                </div>

                <!-- Action Plan -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="proposed_solution">Proposed Solution
                        / Notes</label>
                    <textarea name="proposed_solution" id="proposed_solution" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"><?php echo htmlspecialchars($task['proposed_solution']); ?></textarea>
                </div>

                <!-- Est Cost -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="estimated_cost">Estimated Cost
                        (KES)</label>
                    <input type="number" step="0.01" name="estimated_cost" id="estimated_cost"
                        value="<?php echo $task['estimated_cost']; ?>"
                        class="shadow appearance-none border border-slate-200 rounded w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                        placeholder="KES 0.00">
                </div>

                <!-- Assigned To -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="assigned_to">Assign To</label>
                    <input type="text" name="assigned_to" id="assigned_to"
                        value="<?php echo htmlspecialchars($task['assigned_to']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Portal Visibility & Impact -->
                <div class="col-span-2 bg-indigo-50 p-4 rounded border border-indigo-100 mt-4">
                    <h3 class="text-indigo-800 text-xs font-bold uppercase tracking-widest mb-4">Service Portal
                        Visibility</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="show_on_portal" id="show_on_portal" <?php echo $task['show_on_portal'] ? 'checked' : ''; ?>
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="show_on_portal" class="ml-2 block text-sm font-bold text-indigo-900">
                                Show on Service Portal Calendar
                            </label>
                        </div>
                        <div>
                            <label class="block text-indigo-700 text-sm font-bold mb-2" for="impact">Impact
                                Level</label>
                            <select name="impact" id="impact"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-indigo-500 outline-none text-sm bg-white">
                                <option value="none" <?php echo $task['impact'] === 'none' ? 'selected' : ''; ?>>None
                                    (Routine)</option>
                                <option value="low" <?php echo $task['impact'] === 'low' ? 'selected' : ''; ?>>Low
                                    (Degraded Perf.)</option>
                                <option value="medium" <?php echo $task['impact'] === 'medium' ? 'selected' : ''; ?>>
                                    Medium (Partial Outage)</option>
                                <option value="high" <?php echo $task['impact'] === 'high' ? 'selected' : ''; ?>>High
                                    (Significant Affect)</option>
                                <option value="outage" <?php echo $task['impact'] === 'outage' ? 'selected' : ''; ?>>
                                    OUTAGE (All Staff Affected)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-indigo-700 text-sm font-bold mb-2"
                                for="start_time">Actual/Scheduled Start</label>
                            <input type="datetime-local" name="start_time" id="start_time"
                                value="<?php echo $task['start_time'] ? date('Y-m-d\TH:i', strtotime($task['start_time'])) : ''; ?>"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-indigo-700 text-sm font-bold mb-2" for="end_time">Scheduled
                                End</label>
                            <input type="datetime-local" name="end_time" id="end_time"
                                value="<?php echo $task['end_time'] ? date('Y-m-d\TH:i', strtotime($task['end_time'])) : ''; ?>"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg shadow-lg shadow-primary-500/20 transition-all hover:scale-105">
                    Update Task
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>