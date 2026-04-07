<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Request Leave";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    if (empty($leave_type) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill in all required fields.';
    } elseif ($end_date < $start_date) {
        $error = 'End date cannot be before start date.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ict_leave_requests (user_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $leave_type, $start_date, $end_date, $reason]);
            $success = 'Leave request submitted successfully. Waiting for approval.';

            // Send Email to ICT Team
            $email_body = "A new leave request has been submitted.\n\n";
            $email_body .= "User: " . $_SESSION['username'] . "\n";
            $email_body .= "Type: $leave_type\n";
            $email_body .= "Dates: $start_date to $end_date\n";
            $email_body .= "Reason: $reason";
            sendICTEmail("New Leave Request: " . $_SESSION['username'], $email_body);
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-in-up">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Request Leave</h1>
            <p class="text-slate-500">Submit a new leave request for approval.</p>
        </div>
        <a href="index.php" class="text-sm text-slate-500 hover:text-primary-600 transition-colors">
            &larr; Back to Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm mb-6">
            <p>
                <?php echo $error; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-r shadow-sm mb-6">
            <p>
                <?php echo $success; ?>
            </p>
            <div class="mt-2">
                <a href="index.php" class="text-sm font-bold underline hover:text-emerald-800">View Requests</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="saas-card p-6">
        <form action="" method="POST" class="space-y-6">
            <div>
                <label for="leave_type" class="block text-sm font-medium text-slate-700 mb-1">Leave Type</label>
                <select id="leave_type" name="leave_type" required
                    class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-shadow">
                    <option value="">Select a type...</option>
                    <option value="annual">Annual Leave</option>
                    <option value="sick">Sick Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="unpaid">Unpaid Leave</option>
                    <option value="maternity_paternity">Maternity/Paternity</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-shadow">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-shadow">
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700 mb-1">Reason (Optional)</label>
                <textarea id="reason" name="reason" rows="3"
                    class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-shadow"
                    placeholder="Briefly describe the reason for your leave..."></textarea>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>