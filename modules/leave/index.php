<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Leave Management";

// Handle filtering
$view = $_GET['view'] ?? 'my_leaves'; // 'my_leaves' or 'all_requests' (for managers)

// Helper to get status color
function getStatusColor($status)
{
    switch ($status) {
        case 'approved':
            return 'bg-emerald-100 text-emerald-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-amber-100 text-amber-800';
    }
}

// Fetch My Leaves
if ($view === 'my_leaves') {
    $stmt = $pdo->prepare("SELECT * FROM ict_leave_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $myLeaves = $stmt->fetchAll();
}

// Fetch All Requests (Manager Only)
$allRequests = [];
if (isAdmin() || $_SESSION['role'] === 'manager') {
    if ($view === 'all_requests') {
        $stmt = $pdo->query("SELECT l.*, u.full_name, u.department FROM ict_leave_requests l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
        $allRequests = $stmt->fetchAll();

        // Fetch Staff Utilization Stats
        $stmt = $pdo->query("SELECT id, full_name FROM users WHERE department = 'IT' AND status = 'active' ORDER BY full_name ASC");
        $allStaff = $stmt->fetchAll();
        
        $staffUtilization = [];
        foreach ($allStaff as $s) {
            $stmt = $pdo->prepare("SELECT SUM(DATEDIFF(end_date, start_date) + 1) FROM ict_leave_requests WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())");
            $stmt->execute([$s['id']]);
            $days = $stmt->fetchColumn() ?: 0;
            
            $staffUtilization[] = [
                'id' => $s['id'],
                'name' => $s['full_name'],
                'days' => $days,
                'percent' => min(100, round(($days / 21) * 100))
            ];
        }
        // Sort by utilization (desc)
        usort($staffUtilization, function($a, $b) {
            return $b['percent'] <=> $a['percent'];
        });
    }
}

// --- ANALYTICS ---

$stats = [
    'card1' => ['label' => 'Leaves Taken (2025)', 'value' => 0, 'color' => 'primary'],
    'card2' => ['label' => 'Pending Requests', 'value' => 0, 'color' => 'amber'],
    'card3' => ['label' => 'Next Leave', 'value' => '-', 'color' => 'emerald'],
];

if ($view === 'my_leaves') {
    // 1. Leaves Taken (Days) - Estimate
    $stmt = $pdo->prepare("SELECT SUM(DATEDIFF(end_date, start_date) + 1) FROM ict_leave_requests WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())");
    $stmt->execute([$_SESSION['user_id']]);
    $daysTaken = $stmt->fetchColumn() ?: 0;
    $stats['card1']['value'] = $daysTaken . " days";

    // 2. Pending
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ict_leave_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['card2']['value'] = $stmt->fetchColumn();

    // 3. Next Leave
    $stmt = $pdo->prepare("SELECT start_date FROM ict_leave_requests WHERE user_id = ? AND status = 'approved' AND start_date > CURDATE() ORDER BY start_date ASC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $nextDate = $stmt->fetchColumn();
    $stats['card3']['value'] = $nextDate ? date('M d', strtotime($nextDate)) : '-';

} elseif ($view === 'all_requests') {
    $stats['card1']['label'] = 'On Leave Today';
    $stats['card3']['label'] = 'Total Requests (Mo)';

    // 1. On Leave Today
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM ict_leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
    $stats['card1']['value'] = $stmt->fetchColumn();

    // 2. Pending Global
    $stmt = $pdo->query("SELECT COUNT(*) FROM ict_leave_requests WHERE status = 'pending'");
    $stats['card2']['value'] = $stmt->fetchColumn();

    // 3. Total Requests This Month
    $stmt = $pdo->query("SELECT COUNT(*) FROM ict_leave_requests WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['card3']['value'] = $stmt->fetchColumn();
} elseif ($view === 'staff_history' && (isAdmin() || $_SESSION['role'] === 'manager')) {
    $staffId = $_GET['user_id'] ?? 0;

    // Get Staff Details
    $stmt = $pdo->prepare("SELECT full_name, department, email FROM users WHERE id = ?");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch();

    if ($staff) {
        $pageTitle = "Leave History: " . $staff['full_name'];

        // Get Leave History
        $stmt = $pdo->prepare("SELECT * FROM ict_leave_requests WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$staffId]);
        $staffLeaves = $stmt->fetchAll();

        // Calculate Stats for this user
        $stmt = $pdo->prepare("SELECT SUM(DATEDIFF(end_date, start_date) + 1) FROM ict_leave_requests WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())");
        $stmt->execute([$staffId]);
        $staffDaysTaken = $stmt->fetchColumn() ?: 0;
    }
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">
            <?php echo $view === 'staff_history' && $staff ? 'History: ' . htmlspecialchars($staff['full_name']) : 'Leave Management'; ?>
        </h1>
        <p class="text-slate-500 mt-2">
            <?php echo $view === 'staff_history' && $staff ? 'Department: ' . htmlspecialchars($staff['department']) : 'Track and manage staff leave requests.'; ?>
        </p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        <?php if (isAdmin() || $_SESSION['role'] === 'manager'): ?>
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <a href="?view=my_leaves"
                    class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-primary-700 focus:text-primary-700 <?php echo $view === 'my_leaves' ? 'bg-white text-primary-700' : 'bg-white text-gray-900'; ?>">
                    My Leaves
                </a>
                <a href="?view=all_requests"
                    class="px-4 py-2 text-sm font-medium border border-gray-200 border-l-0 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-primary-700 focus:text-primary-700 <?php echo $view === 'all_requests' ? 'bg-white text-primary-700' : 'bg-white text-gray-900'; ?>">
                    All Requests
                </a>
                <a href="calendar.php"
                    class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-r-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-primary-700 focus:text-primary-700 bg-white text-gray-900">
                    Calendar
                </a>
            </div>
        <?php endif; ?>

        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Request Leave
        </a>
    </div>
</div>

<!-- Analytics Cards -->
<?php if ($view !== 'staff_history'): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in-up" style="animation-delay: 0.05s">
        <?php foreach ($stats as $key => $stat): ?>
            <div class="saas-card p-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1"><?php echo $stat['label']; ?>
                    </p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo $stat['value']; ?></h3>
                </div>
                <div class="p-3 bg-<?php echo $stat['color']; ?>-50 text-<?php echo $stat['color']; ?>-600 rounded-xl">
                    <?php if ($key === 'card1'): ?>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    <?php elseif ($key === 'card2'): ?>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <?php if ($view === 'my_leaves'): ?>
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-semibold text-slate-800">My Leave History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Days
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Requested On</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <?php if (count($myLeaves) > 0): ?>
                        <?php foreach ($myLeaves as $leave): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                    <?php echo htmlspecialchars(ucfirst($leave['leave_type'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> -
                                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    echo $end->diff($start)->days + 1;
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate"
                                    title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                    <?php echo htmlspecialchars($leave['reason']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($leave['status']); ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?php echo date('M d, Y H:i', strtotime($leave['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No leave requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($view === 'all_requests'): ?>
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-semibold text-slate-800">Department Leave Requests</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Dates
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <?php if (count($allRequests) > 0): ?>
                        <?php foreach ($allRequests as $leave): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold mr-3">
                                            <?php echo strtoupper(substr($leave['full_name'], 0, 2)); ?>
                                        </div>
                                        <div class="text-sm font-medium text-slate-900">
                                            <?php echo htmlspecialchars($leave['full_name']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo htmlspecialchars(ucfirst($leave['leave_type'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo date('M d', strtotime($leave['start_date'])); ?> -
                                    <?php echo date('M d', strtotime($leave['end_date'])); ?>
                                    <span class="text-xs text-slate-400 ml-1">(
                                        <?php echo (new DateTime($leave['end_date']))->diff(new DateTime($leave['start_date']))->days + 1; ?>d)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">
                                    <?php echo htmlspecialchars($leave['reason']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($leave['status']); ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($leave['status'] === 'pending'): ?>
                                        <a href="manage.php?action=approve&id=<?php echo $leave['id']; ?>"
                                            class="text-emerald-600 hover:text-emerald-900 mr-3">Approve</a>
                                        <a href="manage.php?action=reject&id=<?php echo $leave['id']; ?>"
                                            class="text-red-600 hover:text-red-900">Reject</a>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">No actions</span>
                                    <?php endif; ?>
                                    <a href="?view=staff_history&user_id=<?php echo $leave['user_id']; ?>"
                                        class="text-slate-400 hover:text-primary-600 ml-2 text-xs">History</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Staff Utilization Card -->
    <?php if ($view === 'all_requests'): ?>
        <div class="saas-card overflow-hidden fade-in-up mt-8" style="animation-delay: 0.2s">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-lg font-semibold text-slate-800">Staff Leave Utilization</h2>
                <p class="text-xs text-slate-500 mt-1">Leave days taken vs. Annual entitlement (21 Days)</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($staffUtilization as $util): 
                    $color = 'bg-emerald-500';
                    if ($util['percent'] > 50) $color = 'bg-amber-500';
                    if ($util['percent'] > 80) $color = 'bg-red-500';
                ?>
                    <div class="bg-white border border-slate-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-bold text-slate-700 text-sm truncate"><?php echo htmlspecialchars($util['name']); ?></h3>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-full">
                                <?php echo $util['days']; ?> / 21
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="<?php echo $color; ?> h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $util['percent']; ?>%"></div>
                        </div>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Used</span>
                            <span class="text-[10px] font-bold text-slate-600"><?php echo $util['percent']; ?>%</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-50 text-center">
                            <a href="?view=staff_history&user_id=<?php echo $util['id']; ?>" class="text-[11px] font-bold text-primary-600 hover:text-primary-800 uppercase tracking-widest">
                                View History
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php if ($view === 'staff_history' && $staff): ?>
    <div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-slate-800">
                Performance & Leave Record
            </h2>
            <span class="text-sm font-bold text-slate-500">
                Total Taken (This Year): <span class="text-slate-800"><?php echo $staffDaysTaken; ?> Days</span>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Dates
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Days
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Requested</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <?php if (count($staffLeaves) > 0): ?>
                        <?php foreach ($staffLeaves as $leave): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium">
                                    <?php echo htmlspecialchars(ucfirst($leave['leave_type'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> -
                                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo (new DateTime($leave['end_date']))->diff(new DateTime($leave['start_date']))->days + 1; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">
                                    <?php echo htmlspecialchars($leave['reason']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($leave['status']); ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?php echo date('M d, Y', strtotime($leave['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No history found for this user.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>