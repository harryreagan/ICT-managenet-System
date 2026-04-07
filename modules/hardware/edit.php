<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/hardware/index.php');

$stmt = $pdo->prepare("SELECT * FROM hardware_assets WHERE id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset)
    redirect('/ict/modules/hardware/index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $serial_number = sanitize($_POST['serial_number']);
    $category = sanitize($_POST['category']);
    $location = sanitize($_POST['location']);
    $department = sanitize($_POST['department']);
    $floor_id = !empty($_POST['floor_id']) ? $_POST['floor_id'] : null;
    $condition_status = sanitize($_POST['condition_status']);
    $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
    $maintenance_log = sanitize($_POST['maintenance_log']);

    $condition_notes = sanitize($_POST['condition_notes']);

    $assigned_to_user_id = !empty($_POST['assigned_to_user_id']) ? $_POST['assigned_to_user_id'] : null;
    $assignment_status = sanitize($_POST['assignment_status']);

    if (empty($name)) {
        $error = "Asset Name is required.";
    } else {
        try {
            // Update assignment date if it changes from available to issued
            $assignment_date_sql = "";
            $params_update = [$name, $serial_number, $category, $location, $department, $floor_id, $condition_status, $condition_notes, $warranty_expiry, $maintenance_log, $assigned_to_user_id, $assignment_status];

            if ($assignment_status === 'issued' && $asset['assignment_status'] !== 'issued') {
                $assignment_date_sql = ", assignment_date = NOW()";
            } elseif ($assignment_status === 'available') {
                $assignment_date_sql = ", assignment_date = NULL";
                $assigned_to_user_id = null;
                $params_update = [$name, $serial_number, $category, $location, $department, $floor_id, $condition_status, $condition_notes, $warranty_expiry, $maintenance_log, $assigned_to_user_id, $assignment_status];
            }

            $stmt = $pdo->prepare("UPDATE hardware_assets SET name=?, serial_number=?, category=?, location=?, department=?, floor_id=?, condition_status=?, condition_notes=?, warranty_expiry=?, maintenance_log=?, assigned_to_user_id=?, assignment_status=? $assignment_date_sql WHERE id=?");
            $params_update[] = $id;
            $stmt->execute($params_update);
            $_SESSION['success'] = "Hardware asset updated successfully!";
            redirect('/ict/modules/hardware/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch floors for dropdown
$floors = $pdo->query("SELECT id, floor_number, label FROM floors ORDER BY floor_number DESC")->fetchAll();

// Fetch users for assignment
$users = $pdo->query("SELECT id, full_name, username, department FROM users WHERE status = 'active' ORDER BY full_name ASC")->fetchAll();

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Edit Asset</h1>
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
                <!-- Name -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="name">Asset Name *</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($asset['name']); ?>"
                        required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="category">Category</label>
                    <select name="category" id="category"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="Workstation" <?php echo $asset['category'] === 'Workstation' ? 'selected' : ''; ?>>
                            Workstation</option>
                        <option value="Access Point" <?php echo $asset['category'] === 'Access Point' ? 'selected' : ''; ?>>Access Point</option>
                        <option value="Switch" <?php echo $asset['category'] === 'Switch' ? 'selected' : ''; ?>>Switch
                        </option>
                        <option value="Server" <?php echo $asset['category'] === 'Server' ? 'selected' : ''; ?>>Server
                        </option>
                        <option value="Printer" <?php echo $asset['category'] === 'Printer' ? 'selected' : ''; ?>>Printer
                        </option>
                        <option value="CCTV Camera" <?php echo $asset['category'] === 'CCTV Camera' ? 'selected' : ''; ?>>
                            CCTV Camera</option>
                        <option value="Mixer" <?php echo $asset['category'] === 'Mixer' ? 'selected' : ''; ?>>Mixer
                            (Audio)</option>
                        <option value="Microphone" <?php echo $asset['category'] === 'Microphone' ? 'selected' : ''; ?>>
                            Microphone</option>
                        <option value="Extension" <?php echo $asset['category'] === 'Extension' ? 'selected' : ''; ?>>
                            Extension Cable</option>
                        <option value="Other" <?php echo $asset['category'] === 'Other' ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>

                <!-- Floor -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="floor_id">Floor</label>
                    <select name="floor_id" id="floor_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="">No Specific Floor</option>
                        <?php foreach ($floors as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $asset['floor_id'] == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['label']); ?> (Floor <?php echo $f['floor_number']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="serial_number">Serial Number</label>
                    <input type="text" name="serial_number" id="serial_number"
                        value="<?php echo htmlspecialchars($asset['serial_number']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="condition_status">Condition</label>
                    <select name="condition_status" id="condition_status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="working" <?php echo $asset['condition_status'] === 'working' ? 'selected' : ''; ?>>
                            Working</option>
                        <option value="needs_service" <?php echo $asset['condition_status'] === 'needs_service' ? 'selected' : ''; ?>>Needs Service</option>
                        <option value="faulty" <?php echo $asset['condition_status'] === 'faulty' ? 'selected' : ''; ?>>
                            Faulty</option>
                    </select>
                </div>

                <!-- Location -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="location">Detailed Location</label>
                    <input type="text" name="location" id="location"
                        value="<?php echo htmlspecialchars($asset['location']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="department">Department</label>
                    <input type="text" name="department" id="department"
                        value="<?php echo htmlspecialchars($asset['department']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Assignment Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="assignment_status">Assignment
                        Status</label>
                    <select name="assignment_status" id="assignment_status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="available" <?php echo $asset['assignment_status'] === 'available' ? 'selected' : ''; ?>>Available in Stock</option>
                        <option value="issued" <?php echo $asset['assignment_status'] === 'issued' ? 'selected' : ''; ?>>
                            Issued / In Use</option>
                    </select>
                </div>

                <!-- Assigned To -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="assigned_to_user_id">Assigned Staff
                        Member</label>
                    <select name="assigned_to_user_id" id="assigned_to_user_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $asset['assigned_to_user_id'] == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?>
                                (<?php echo htmlspecialchars($u['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Warranty Expiry -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="warranty_expiry">Warranty
                        Expiry</label>
                    <input type="date" name="warranty_expiry" id="warranty_expiry"
                        value="<?php echo htmlspecialchars($asset['warranty_expiry']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Condition Notes -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="condition_notes">Fault Details /
                        Condition Notes</label>
                    <textarea name="condition_notes" id="condition_notes" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"><?php echo htmlspecialchars($asset['condition_notes']); ?></textarea>
                </div>

                <!-- Maintenance Log -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="maintenance_log">Maintenance
                        Log</label>
                    <textarea name="maintenance_log" id="maintenance_log" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"><?php echo htmlspecialchars($asset['maintenance_log']); ?></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Update Asset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAssignmentFields() {
        const assignmentStatus = document.getElementById('assignment_status').value;
        const assignmentDetails = document.getElementById('assignment_details');

        if (assignmentStatus === 'issued') {
            assignmentDetails.classList.remove('hidden');
        } else {
            assignmentDetails.classList.add('hidden');
            // Clear inputs if marked as available
            document.getElementById('assigned_to_user_id').value = '';
            document.getElementById('assigned_guest_name').value = '';
            document.getElementById('assigned_guest_contact').value = '';
            document.getElementById('assigned_conference').value = '';
        }
    }

    function toggleAssignmentType() {
        const isInternal = document.querySelector('input[name="assignment_type"]:checked').value === 'internal';
        const internalEl = document.getElementById('internal_assignment');
        const externalEl = document.getElementById('external_assignment');

        if (isInternal) {
            internalEl.classList.remove('hidden');
            externalEl.classList.add('hidden');
            // Clear external fields when switching back to internal
            document.getElementById('assigned_guest_name').value = '';
            document.getElementById('assigned_guest_contact').value = '';
            document.getElementById('assigned_conference').value = '';
        } else {
            internalEl.classList.add('hidden');
            externalEl.classList.remove('hidden');
            // Clear internal staff selection when switching to external
            document.getElementById('assigned_to_user_id').value = '';
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>