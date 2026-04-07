<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Renewals & Subscriptions";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM renewals WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    redirect($_SERVER['REQUEST_URI']);
}

// Handle Mark as Paid
if (isset($_POST['mark_paid'])) {
    $id = $_POST['mark_paid'];
    $stmt = $pdo->prepare("SELECT * FROM renewals WHERE id = ?");
    $stmt->execute([$id]);
    $renewal = $stmt->fetch();

    if ($renewal) {
        $next_date = $renewal['renewal_date'] ?? date('Y-m-d');
        $new_payment_status = 'paid';

        if (!empty($renewal['is_recurring'])) {
            $date = new DateTime($next_date);
            if (($renewal['billing_cycle'] ?? 'yearly') === 'monthly') {
                $date->modify('+1 month');
            } else {
                $date->modify('+1 year');
            }
            $next_date = $date->format('Y-m-d');
            $new_payment_status = 'unpaid'; // Reset for next cycle
        }

        $stmt = $pdo->prepare("UPDATE renewals SET renewal_date = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$next_date, $new_payment_status, $id]);
    }
    redirect($_SERVER['REQUEST_URI']);
}

// Sorting and Search Settings
$orderBy = $_GET['sort'] ?? 'renewal_date';
$orderDir = $_GET['dir'] ?? 'ASC';
$allowedSorts = ['service_name', 'renewal_date', 'status', 'amount_paid'];

if (!in_array($orderBy, $allowedSorts))
    $orderBy = 'renewal_date';
if (!in_array(strtoupper($orderDir), ['ASC', 'DESC']))
    $orderDir = 'ASC';

$search = $_GET['search'] ?? '';
$whereClause = "";
$params = [];

if ($search) {
    $whereClause = "WHERE service_name LIKE ? OR status LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Pagination Settings
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total renewals for pagination
if ($search) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM renewals WHERE service_name LIKE ? OR status LIKE ?");
    $count_stmt->execute(["%$search%", "%$search%"]);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM renewals");
}
$total_renewals = $count_stmt->fetchColumn();
$total_pages = ceil($total_renewals / $per_page);

// Fetch Renewals with pagination
$stmt = $pdo->prepare("SELECT * FROM renewals $whereClause ORDER BY $orderBy $orderDir LIMIT " . (int) $per_page . " OFFSET " . (int) $offset);
$stmt->execute($params);
$renewals = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up relative z-[100]">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Renewals & Subscriptions</h1>
        <p class="text-slate-500 mt-2">Manage contracts, licenses, and recurring operational costs.</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-2 relative z-[110]">
        <!-- Export Dropdown -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-all focus:ring-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export
                <svg class="w-3 h-3 ml-2 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open" @click.away="open = false" x-cloak
                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-2xl border border-slate-100 z-[120] overflow-hidden"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100">
                <a href="../reports/export_csv.php?type=subscriptions"
                    class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20,2H8C6.9,2,6,2.9,6,4v12c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V4C22,2.9,21.1,2,20,2z M20,16H8V4h12V16z M4,6H2v14c0,1.1,0.9,2,2,2h14v-2H4V6z">
                        </path>
                    </svg>
                    Download Excel
                </a>
                <a href="export_pdf.php" target="_blank"
                    class="flex items-center px-4 py-3 text-sm text-slate-700 border-t border-slate-50 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20,2H4C2.9,2,2,2.9,2,4v16c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V4C22,2.9,21.1,2,20,2z M20,20H4V4h16V20z M11.5,14L11,15.5h-1.5V17H8v-6h3c1.1,0,2,0.9,2,2V14z M11.5,12.5H9.5 v1.5h2V12.5z M18,17l-1.5,0l-1-2.5l-1,2.5H13v-6h1.5v3l1-3l1,3.5l1-3.5h1.5V17z">
                        </path>
                    </svg>
                    Save as PDF
                </a>
            </div>
        </div>

        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Subscription
        </a>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <!-- Toolbar -->
    <div
        class="p-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center bg-slate-50/50 gap-4">
        <form action="" method="GET" id="renewalSearchForm" data-live-search class="flex items-center w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" placeholder="Search services..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-full focus:ring-primary-500 focus:border-primary-500 transition-shadow">
            </div>
        </form>
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">
            <?php echo $total_renewals; ?> Active Contracts
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <a href="?sort=service_name&dir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                            class="group inline-flex items-center hover:text-primary-600 transition-colors">
                            Service Name
                            <span class="ml-1 text-slate-300 group-hover:text-primary-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </span>
                        </a>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendor
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <a href="?sort=renewal_date&dir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                            class="hover:text-primary-600 transition-colors">Renewal Date</a>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <a href="?sort=amount_paid&dir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                            class="hover:text-primary-600 transition-colors">Cost</a>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Cycle
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Payment
                    </th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($renewals as $renewal):
                    // Fetch Vendor Name (Simple query for now, could be JOINed above)
                    $vendorName = 'N/A';
                    if ($renewal['vendor_id']) {
                        $vStmt = $pdo->prepare("SELECT name FROM vendors WHERE id = ?");
                        $vStmt->execute([$renewal['vendor_id']]);
                        $vendorName = $vStmt->fetchColumn() ?: 'Unknown';
                    }
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 h-8 w-8 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xs ring-2 ring-white">
                                    <?php echo strtoupper(substr($renewal['service_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div
                                        class="text-sm font-medium text-slate-900 group-hover:text-primary-600 transition-colors">
                                        <?php echo htmlspecialchars($renewal['service_name']); ?>
                                    </div>
                                    <?php if ($renewal['notes']): ?>
                                        <div class="text-xs text-slate-400 mt-0.5 max-w-[150px] truncate">
                                            <?php echo htmlspecialchars($renewal['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                <?php echo htmlspecialchars($vendorName); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <?php
                            $date = new DateTime($renewal['renewal_date']);
                            $now = new DateTime();
                            $interval = $now->diff($date);
                            $isUrgent = $date < $now || $interval->days < 30;
                            ?>
                            <div class="flex items-center">
                                <?php echo formatDate($renewal['renewal_date']); ?>
                                <?php if ($renewal['status'] != 'cancelled' && $isUrgent): ?>
                                    <span class="ml-2 flex h-2 w-2 relative">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-700">
                            <?php echo formatCurrency($renewal['amount_paid']); ?>
                            <span class="text-slate-400 font-normal text-xs">/
                                <?php echo ($renewal['billing_cycle'] ?? 'yearly') == 'monthly' ? 'mo' : 'yr'; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs font-bold text-slate-600 uppercase"><?php echo ucfirst($renewal['billing_cycle'] ?? 'yearly'); ?></span>
                                <?php if (!empty($renewal['is_recurring'])): ?>
                                    <span
                                        class="text-[10px] text-emerald-600 font-black tracking-widest flex items-center mt-0.5">
                                        <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        RECURRING
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($renewal['status']); ?> border border-opacity-20 shadow-sm">
                                <?php echo ucfirst($renewal['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo ($renewal['payment_status'] ?? 'unpaid') == 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100'; ?> border shadow-sm uppercase tracking-tighter">
                                <?php echo htmlspecialchars($renewal['payment_status'] ?? 'unpaid'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if (($renewal['payment_status'] ?? 'unpaid') == 'unpaid'): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="mark_paid" value="<?php echo $renewal['id']; ?>">
                                        <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm transition-all hover:-translate-y-0.5"
                                            title="Mark as Paid">
                                            Paid
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="/ict/modules/renewals/edit.php?id=<?php echo $renewal['id']; ?>"
                                    class="text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-md hover:bg-primary-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                                <form method="POST" action="" id="delete-renewal-<?php echo $renewal['id']; ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo $renewal['id']; ?>">
                                    <button type="button"
                                        @click="$store.modal.trigger('delete-renewal-<?php echo $renewal['id']; ?>', 'Delete this subscription permanently?', 'Delete Renewal')"
                                        class="text-slate-400 hover:text-red-600 transition-colors p-1 rounded-md hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($renewals) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-slate-900 font-medium">No subscriptions found</h3>
                                <p class="text-slate-500 text-sm mt-1 mb-4">Get started by tracking your first renewal.</p>
                                <a href="create.php"
                                    class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    Add Subscription
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div
            class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center bg-slate-50/30 gap-4">
            <div class="text-sm text-slate-500">
                Showing
                <?php echo min($offset + 1, $total_renewals); ?>-<?php echo min($offset + $per_page, $total_renewals); ?> of
                <?php echo $total_renewals; ?> contracts
            </div>
            <div class="flex gap-2">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryStr = http_build_query($queryParams);
                if ($queryStr)
                    $queryStr = '&' . $queryStr;
                ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $queryStr; ?>"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Previous
                    </a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?><?php echo $queryStr; ?>"
                        class="px-3 py-1.5 <?php echo $i == $page ? 'bg-primary-500 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'; ?> rounded-lg text-xs font-medium transition-colors">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $queryStr; ?>"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Next
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>