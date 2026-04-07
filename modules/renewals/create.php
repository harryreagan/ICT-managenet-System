<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Fetch Vendors for dropdown
$vendors = $pdo->query("SELECT id, name FROM vendors ORDER BY name ASC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = sanitize($_POST['service_name']);
    $vendor_id = !empty($_POST['vendor_id']) ? $_POST['vendor_id'] : null;
    $contact_details = sanitize($_POST['contact_details']);
    $amount_paid = sanitize($_POST['amount_paid']);
    $renewal_date = sanitize($_POST['renewal_date']);
    $status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes']);
    $billing_cycle = sanitize($_POST['billing_cycle']);
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $payment_status = sanitize($_POST['payment_status']);

    if (empty($service_name) || empty($renewal_date)) {
        $error = "Service Name and Renewal Date are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO renewals (service_name, vendor_id, contact_details, amount_paid, renewal_date, status, notes, billing_cycle, is_recurring, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$service_name, $vendor_id, $contact_details, $amount_paid, $renewal_date, $status, $notes, $billing_cycle, $is_recurring, $payment_status]);
            redirect('/ict/modules/renewals/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Add Renewal</h1>
        <a href="index.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Service Name -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="service_name">Service Name *</label>
                    <input type="text" name="service_name" id="service_name" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Vendor -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="vendor_id">Vendor</label>
                    <select name="vendor_id" id="vendor_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="">-- Select Vendor --</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>">
                                <?php echo htmlspecialchars($vendor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Contact Details -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="contact_details">Contact Details
                        (Optional)</label>
                    <input type="text" name="contact_details" id="contact_details"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="amount_paid">Amount Paid
                        (KES)</label>
                    <input type="number" step="0.01" name="amount_paid" id="amount_paid"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"
                        placeholder="KES 0.00">
                </div>

                <!-- Renewal Date -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="renewal_date">Renewal Date *</label>
                    <input type="date" name="renewal_date" id="renewal_date" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="status">Status</label>
                    <select name="status" id="status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="billing_cycle">Billing Cycle</label>
                    <select name="billing_cycle" id="billing_cycle"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="yearly">Yearly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="payment_status">Payment
                        Status</label>
                    <select name="payment_status" id="payment_status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>

                <!-- Recurring -->
                <div class="flex items-center space-x-2 mt-2">
                    <input type="checkbox" name="is_recurring" id="is_recurring" value="1"
                        class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500 cursor-pointer">
                    <label class="text-slate-700 text-sm font-bold cursor-pointer" for="is_recurring">Recurring
                        Subscription</label>
                </div>

                <!-- Notes -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Save Renewal
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>