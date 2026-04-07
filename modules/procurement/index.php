<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "ICT Procurement Tracking";

// Handle Status Updates
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE procurement_requests SET status = ?, date_received = ? WHERE id = ?");
    $dateReceived = ($_POST['status'] === 'received' || $_POST['status'] === 'installed') ? date('Y-m-d') : null;
    $stmt->execute([$_POST['status'], $dateReceived, $_POST['request_id']]);
    header("Location: index.php?updated=1");
    exit;
}

// Fetch requests with vendor names
$stmt = $pdo->query("SELECT p.*, v.name as vendor_name FROM procurement_requests p LEFT JOIN vendors v ON p.vendor_id = v.id ORDER BY p.date_requested DESC");
$requests = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Procurement Lifecycle</h1>
        <p class="text-slate-500 mt-2">Track ICT purchase requests from initial approval through to final installation.
        </p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="manage.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Request
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in-up" style="animation-delay: 0.1s">
    <?php
    $stats = ['requested' => 0, 'approved' => 0, 'ordered' => 0];
    foreach ($requests as $r) {
        if (isset($stats[$r['status']]))
            $stats[$r['status']]++;
    }
    ?>
    <div class="saas-card p-4 border-l-4 border-slate-200">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">New Requests</div>
        <div class="text-2xl font-bold text-slate-800">
            <?php echo $stats['requested']; ?>
        </div>
    </div>
    <div class="saas-card p-4 border-l-4 border-amber-400">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">In Pipeline</div>
        <div class="text-2xl font-bold text-slate-800">
            <?php echo $stats['approved'] + $stats['ordered']; ?>
        </div>
    </div>
    <div class="saas-card p-4 border-l-4 border-emerald-400">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Spent (Current Requests)</div>
        <div class="text-2xl font-bold text-slate-800">
            KES
            <?php
            $total = 0;
            foreach ($requests as $r) {
                if ($r['status'] !== 'cancelled')
                    $total += $r['estimated_cost'];
            }
            echo number_format($total, 2);
            ?>
        </div>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.2s">
    <div class="overflow-x-auto text-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="px-6 py-4 border-b border-slate-100">Item Description</th>
                    <th class="px-6 py-4 border-b border-slate-100">Vendor</th>
                    <th class="px-6 py-4 border-b border-slate-100">Cost</th>
                    <th class="px-6 py-4 border-b border-slate-100">Status</th>
                    <th class="px-6 py-4 border-b border-slate-100">Requested</th>
                    <th class="px-6 py-4 border-b border-slate-100 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($requests as $r): ?>
                    <tr class="group hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">
                                <?php echo htmlspecialchars($r['item_name']); ?>
                            </div>
                            <div class="text-[10px] text-slate-400">By
                                <?php echo htmlspecialchars($r['requester']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($r['vendor_name'] ?: 'None Selected'); ?>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-700">
                            KES
                            <?php echo number_format($r['estimated_cost'], 2); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $statusMap = [
                                'requested' => 'bg-slate-100 text-slate-600',
                                'approved' => 'bg-primary-50 text-primary-600',
                                'ordered' => 'bg-amber-50 text-amber-600',
                                'received' => 'bg-emerald-50 text-emerald-600',
                                'installed' => 'bg-primary-50 text-primary-600',
                                'cancelled' => 'bg-red-50 text-red-600'
                            ];
                            $class = $statusMap[$r['status']] ?? 'bg-slate-50 text-slate-400';
                            ?>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?php echo $class; ?>">
                                <?php echo htmlspecialchars($r['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            <?php echo formatDate($r['date_requested']); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                    <select name="status" onchange="this.form.submit()"
                                        class="text-[10px] bg-white border border-slate-200 rounded px-1 py-1 focus:ring-1 focus:ring-primary-500 outline-none">
                                        <option value="">Update Status</option>
                                        <option value="requested">Requested</option>
                                        <option value="approved">Approved</option>
                                        <option value="ordered">Ordered</option>
                                        <option value="received">Received</option>
                                        <option value="installed">Installed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <a href="/ict/modules/procurement/manage.php?id=<?php echo $r['id']; ?>"
                                    class="p-1 text-slate-400 hover:text-primary-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($requests) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No procurement requests found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>