<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$asset_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch asset details and calculate available quantity
$stmt = $pdo->prepare("
    SELECT h.*, 
        h.quantity - COALESCE((SELECT SUM(quantity_issued) FROM asset_issuances WHERE asset_id = h.id AND status = 'issued'), 0) as available_quantity
    FROM hardware_assets h 
    WHERE h.id = ?
");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch();

if (!$asset) {
    $_SESSION['error'] = "Asset not found.";
    redirect('/ict/modules/hardware/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity_issued = isset($_POST['quantity_issued']) ? (int) $_POST['quantity_issued'] : 0;
    $assignment_type = sanitize($_POST['assignment_type']);
    $notes = sanitize($_POST['notes']);

    // Assignment Logic
    $assigned_to_user_id = ($assignment_type === 'internal' && !empty($_POST['assigned_to_user_id'])) ? $_POST['assigned_to_user_id'] : null;
    $assigned_guest_name = ($assignment_type === 'external' && !empty($_POST['assigned_guest_name'])) ? sanitize($_POST['assigned_guest_name']) : null;
    $assigned_guest_contact = ($assignment_type === 'external' && !empty($_POST['assigned_guest_contact'])) ? sanitize($_POST['assigned_guest_contact']) : null;
    $assigned_conference = ($assignment_type === 'external' && !empty($_POST['assigned_conference'])) ? sanitize($_POST['assigned_conference']) : null;

    // Validation
    if ($quantity_issued <= 0) {
        $error = "Quantity must be at least 1.";
    } elseif ($quantity_issued > $asset['available_quantity']) {
        $error = "Cannot issue more than available stock ({$asset['available_quantity']}).";
    } elseif ($assignment_type === 'internal' && empty($assigned_to_user_id)) {
        $error = "Please select a staff member.";
    } elseif ($assignment_type === 'external' && empty($assigned_guest_name)) {
        $error = "Please provide the guest or client name.";
    } else {
        try {
            $insert = $pdo->prepare("
                INSERT INTO asset_issuances (
                    asset_id, quantity_issued, assignment_type, assigned_to_user_id, 
                    assigned_guest_name, assigned_guest_contact, assigned_conference, 
                    issued_at, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'issued', ?)
            ");
            $insert->execute([
                $asset_id,
                $quantity_issued,
                $assignment_type,
                $assigned_to_user_id,
                $assigned_guest_name,
                $assigned_guest_contact,
                $assigned_conference,
                $notes
            ]);

            $_SESSION['success'] = "Asset successfully issued.";
            redirect('/ict/modules/hardware/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch active users
$users = $pdo->query("SELECT id, username, full_name, department FROM users WHERE status = 'active' ORDER BY full_name")->fetchAll();

include '../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Issue Asset</h1>
        <a href="index.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">
                    <?php echo htmlspecialchars($asset['name']); ?>
                </h2>
                <div class="flex items-center space-x-3 mt-2 text-sm text-slate-500">
                    <?php if (!empty($asset['manufacturer'])): ?>
                        <span>Brand: <strong>
                                <?php echo htmlspecialchars($asset['manufacturer']); ?>
                            </strong></span>
                        <span>•</span>
                    <?php endif; ?>
                    <span>S/N: <strong>
                            <?php echo htmlspecialchars($asset['serial_number']); ?>
                        </strong></span>
                    <span>•</span>
                    <span>Total Stock: <strong>
                            <?php echo $asset['quantity']; ?>
                        </strong></span>
                </div>
            </div>

            <div class="mt-4 md:mt-0 text-right">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Available For
                    Issue</span>
                <span
                    class="text-3xl font-black <?php echo $asset['available_quantity'] > 0 ? 'text-primary-600' : 'text-rose-600'; ?>">
                    <?php echo $asset['available_quantity']; ?>
                </span>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($asset['available_quantity'] > 0): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Quantity -->
                    <div class="md:col-span-2">
                        <label class="block text-slate-700 text-sm font-bold mb-2">Quantity to Issue</label>
                        <input type="number" name="quantity_issued" min="1"
                            max="<?php echo $asset['available_quantity']; ?>" value="1" required
                            class="w-full md:w-48 px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none font-bold text-lg">
                    </div>

                    <!-- Assignment Type -->
                    <div class="md:col-span-2 border-t border-slate-100 pt-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">Recipient Type</h3>
                        <div class="flex items-center gap-6">
                            <label
                                class="flex items-center bg-slate-50 border border-slate-200 px-4 py-3 rounded-lg cursor-pointer hover:bg-white transition-colors">
                                <input type="radio" name="assignment_type" value="internal" checked
                                    class="mr-3 w-4 h-4 text-primary-600 focus:ring-primary-500"
                                    onchange="toggleAssignmentType()">
                                <span class="font-medium text-slate-700">Internal Staff</span>
                            </label>
                            <label
                                class="flex items-center bg-slate-50 border border-slate-200 px-4 py-3 rounded-lg cursor-pointer hover:bg-white transition-colors">
                                <input type="radio" name="assignment_type" value="external"
                                    class="mr-3 w-4 h-4 text-primary-600 focus:ring-primary-500"
                                    onchange="toggleAssignmentType()">
                                <span class="font-medium text-slate-700">External Guest / Conference</span>
                            </label>
                        </div>
                    </div>

                    <!-- Internal Staff Select -->
                    <div id="internal_assignment" class="md:col-span-2 mt-2">
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="assigned_to_user_id">Select Staff
                            Member *</label>
                        <select name="assigned_to_user_id" id="assigned_to_user_id"
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                            <option value="">-- Choose Staff Directory --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?>
                                    (
                                    <?php echo htmlspecialchars($u['department']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- External Guest Fields -->
                    <div id="external_assignment" class="md:col-span-2 hidden">
                        <div
                            class="bg-amber-50/50 border border-amber-100 rounded-lg p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-slate-700 text-sm font-bold mb-2">Guest / Client Name *</label>
                                <input type="text" name="assigned_guest_name" id="assigned_guest_name"
                                    placeholder="E.g., John Doe"
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-slate-700 text-sm font-bold mb-2">Contact Info (Optional)</label>
                                <input type="text" name="assigned_guest_contact" id="assigned_guest_contact"
                                    placeholder="Phone or Email"
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-slate-700 text-sm font-bold mb-2">Conference / Event Name
                                    (Optional)</label>
                                <input type="text" name="assigned_conference" id="assigned_conference"
                                    placeholder="E.g., Annual Tech Summit"
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- Issuance Notes -->
                    <div class="md:col-span-2 mt-4">
                        <label class="block text-slate-700 text-sm font-bold mb-2">Additional Notes</label>
                        <textarea name="notes" rows="3"
                            placeholder="Condition notes upon checkout, specific adapters included, etc."
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-5 border-t border-slate-100">
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg shadow-primary-500/30 transition-all transform hover:-translate-y-0.5 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        Issue Asset
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-12 text-center border-t-4 border-rose-500">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Out of Stock</h3>
            <p class="text-slate-500">All quantities for this asset have been issued. Someone must return an item before it
                can be issued again.</p>
        </div>
    <?php endif; ?>

</div>

<script>
    function toggleAssignmentType() {
        const isInternal = document.querySelector('input[name="assignment_type"]:checked').value === 'internal';
        const internalEl = document.getElementById('internal_assignment');
        const externalEl = document.getElementById('external_assignment');

        if (isInternal) {
            internalEl.classList.remove('hidden');
            externalEl.classList.add('hidden');
        } else {
            internalEl.classList.add('hidden');
            externalEl.classList.remove('hidden');
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>