<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
$request = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM procurement_requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
}

// Fetch vendors for selection
$stmt = $pdo->query("SELECT id, name FROM vendors ORDER BY name ASC");
$vendors = $stmt->fetchAll();

$pageTitle = ($id ? "Edit Request" : "New Procurement Request");

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['item_name'];
    $vendor = $_POST['vendor_id'] ?: null;
    $cost = $_POST['estimated_cost'];
    $status = $_POST['status'];
    $requester = $_POST['requester'];
    $dateReq = $_POST['date_requested'];
    $notes = $_POST['notes'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE procurement_requests SET item_name = ?, vendor_id = ?, estimated_cost = ?, status = ?, requester = ?, date_requested = ?, notes = ? WHERE id = ?");
        $stmt->execute([$name, $vendor, $cost, $status, $requester, $dateReq, $notes, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO procurement_requests (item_name, vendor_id, estimated_cost, status, requester, date_requested, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $vendor, $cost, $status, $requester, $dateReq, $notes]);
        $new_id = $pdo->lastInsertId();

        // Send Email to ICT Team (only for new requests)
        $email_body = "A new procurement request has been submitted.\n\n";
        $email_body .= "Item: $name\n";
        $email_body .= "Estimated Cost: KES " . number_format($cost, 2) . "\n";
        $email_body .= "Requester: $requester\n";
        $email_body .= "Purpose: $notes";
        sendICTEmail("New Procurement Request: $name", $email_body);
    }
    $_SESSION['success'] = "Procurement request updated successfully!";
    header("Location: /ict/modules/procurement");
    exit;
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-in-up">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">
            <?php echo $id ? "Edit ICT Request" : "New ICT Request"; ?>
        </h1>
        <a href="index.php" class="text-slate-400 hover:text-slate-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="saas-card p-8">
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Item Name /
                    Description</label>
                <input type="text" name="item_name" required placeholder="e.g. 5x Cisco Access Points (Aironet)"
                    value="<?php echo $request ? htmlspecialchars($request['item_name']) : ''; ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Preferred
                        Vendor</label>
                    <select name="vendor_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="">Select Vendor...</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $request && $request['vendor_id'] == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Estimated
                        Cost (KES)</label>
                    <input type="number" step="0.01" name="estimated_cost" required placeholder="KES 0.00"
                        value="<?php echo $request ? $request['estimated_cost'] : ''; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Current
                        Status</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="requested" <?php echo $request && $request['status'] === 'requested' ? 'selected' : ''; ?>>Requested</option>
                        <option value="approved" <?php echo $request && $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="ordered" <?php echo $request && $request['status'] === 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                        <option value="received" <?php echo $request && $request['status'] === 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="installed" <?php echo $request && $request['status'] === 'installed' ? 'selected' : ''; ?>>Installed</option>
                        <option value="cancelled" <?php echo $request && $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Requester
                        Name</label>
                    <input type="text" name="requester" required placeholder="e.g. IT Manager"
                        value="<?php echo $request ? htmlspecialchars($request['requester']) : $_SESSION['username']; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Date
                        Requested</label>
                    <input type="date" name="date_requested" required
                        value="<?php echo $request ? $request['date_requested'] : date('Y-m-d'); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Purpose /
                    Justification</label>
                <textarea name="notes" rows="3" placeholder="Explain why this equipment is needed..."
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"><?php echo $request ? htmlspecialchars($request['notes']) : ''; ?></textarea>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-50">
                <a href="index.php"
                    class="text-sm font-medium text-slate-400 hover:text-slate-600 transition-colors">Discard</a>
                <button type="submit"
                    class="px-8 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all hover:scale-105">
                    <?php echo $id ? "Update Request" : "Submit Request"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>