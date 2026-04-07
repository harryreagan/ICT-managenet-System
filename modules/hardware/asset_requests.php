<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Asset Requests";

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = $_POST['request_id'];
    $status = sanitize($_POST['status']);

    $stmt = $pdo->prepare("UPDATE asset_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);

    $_SESSION['success'] = "Request status updated to " . ucfirst($status) . ".";
    redirect($_SERVER['REQUEST_URI']);
}

// Filters
$statusFilter = $_GET['status'] ?? 'pending';

$whereClauses = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereClauses[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

$stmt = $pdo->prepare("SELECT * FROM asset_requests $whereSQL ORDER BY created_at DESC");
$stmt->execute($params);
$requests = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Event Asset Requests</h1>
        <p class="text-slate-500 mt-2">Manage staff requests for mixers, microphones, and extensions.</p>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <!-- Filters -->
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <form action="" method="GET"
            class="flex flex-col md:flex-row md:items-center space-y-3 md:space-y-0 md:space-x-4">
            <div>
                <select name="status"
                    class="w-full md:w-48 border border-slate-200 rounded-lg py-2 px-3 text-sm text-slate-700 focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                    onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved
                    </option>
                    <option value="issued" <?php echo $statusFilter === 'issued' ? 'selected' : ''; ?>>Issued</option>
                    <option value="returned" <?php echo $statusFilter === 'returned' ? 'selected' : ''; ?>>Returned
                    </option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected
                    </option>
                </select>
            </div>
            <button type="submit"
                class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-2 px-6 rounded-lg text-sm transition-all shadow-sm hover:shadow-primary-500/20 transform hover:-translate-y-0.5">
                Filter
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date /
                        Event</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Requester</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Asset
                        / Details</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($requests as $req): ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-800">
                                <?php echo htmlspecialchars($req['event_name'] ?: 'N/A'); ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                <?php echo $req['event_date'] ? date('M j, Y', strtotime($req['event_date'])) : 'No date'; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-800">
                                <?php echo htmlspecialchars($req['staff_name']); ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                <?php echo htmlspecialchars($req['department']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-primary-600 mb-1">
                                <?php echo htmlspecialchars($req['asset_type']); ?>
                            </div>
                            <div class="text-xs text-slate-600 max-w-xs break-words line-clamp-2"
                                title="<?php echo htmlspecialchars($req['details']); ?>">
                                <?php echo htmlspecialchars($req['details']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $badgeClass = 'bg-slate-100 text-slate-600';
                            if ($req['status'] === 'pending')
                                $badgeClass = 'bg-amber-100 text-amber-700';
                            if ($req['status'] === 'approved')
                                $badgeClass = 'bg-blue-100 text-blue-700';
                            if ($req['status'] === 'issued')
                                $badgeClass = 'bg-indigo-100 text-indigo-700';
                            if ($req['status'] === 'returned')
                                $badgeClass = 'bg-emerald-100 text-emerald-700';
                            if ($req['status'] === 'rejected')
                                $badgeClass = 'bg-red-100 text-red-700';
                            ?>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $badgeClass; ?> border border-opacity-20 shadow-sm uppercase tracking-wider">
                                <?php echo $req['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="review_request.php?id=<?php echo $req['id']; ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-colors">
                                Review
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($requests) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-lg font-medium text-slate-700">No requests found</span>
                                <p class="text-slate-500 text-sm mt-1 mb-4">You're all caught up.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>