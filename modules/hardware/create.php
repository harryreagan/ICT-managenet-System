<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

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
    $manufacturer = sanitize($_POST['manufacturer']);
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    if (empty($name)) {
        $error = "Asset Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO hardware_assets (name, manufacturer, serial_number, category, quantity, location, department, floor_id, condition_status, condition_notes, warranty_expiry, maintenance_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $manufacturer, $serial_number, $category, $quantity, $location, $department, $floor_id, $condition_status, $condition_notes, $warranty_expiry, $maintenance_log]);
            $_SESSION['success'] = "Hardware asset created successfully!";
            redirect('/ict/modules/hardware/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch floors for dropdown
$floors = $pdo->query("SELECT id, floor_number, label FROM floors ORDER BY floor_number DESC")->fetchAll();
$preselected_floor = $_GET['floor_id'] ?? null;

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Add Asset</h1>
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
                    <input type="text" name="name" id="name" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="category">Category</label>
                    <select name="category" id="category"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="Workstation">Workstation</option>
                        <option value="Access Point">Access Point</option>
                        <option value="Switch">Switch</option>
                        <option value="Server">Server</option>
                        <option value="Printer">Printer</option>
                        <option value="CCTV Camera">CCTV Camera</option>
                        <option value="Mixer">Mixer (Audio)</option>
                        <option value="Microphone">Microphone</option>
                        <option value="Extension">Extension Cable</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Floor -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="floor_id">Floor</label>
                    <select name="floor_id" id="floor_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="">No Specific Floor</option>
                        <?php foreach ($floors as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $preselected_floor == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['label']); ?> (Floor <?php echo $f['floor_number']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Manufacturer -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="manufacturer">Manufacturer /
                        Brand</label>
                    <input type="text" name="manufacturer" id="manufacturer" placeholder="HP, Cisco, Shure, etc."
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="serial_number">Serial Number /
                        Model</label>
                    <input type="text" name="serial_number" id="serial_number"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Total Quantity -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="quantity">Initial Quantity
                        <span class="text-xs font-normal text-slate-400 ml-1">(For bulk items like Microphones or
                            Cables)</span>
                    </label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="condition_status">Condition</label>
                    <select name="condition_status" id="condition_status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="working">Working</option>
                        <option value="needs_service">Needs Service</option>
                        <option value="faulty">Faulty</option>
                    </select>
                </div>

                <!-- Location -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="location">Detailed Location (e.g.,
                        Room 101, Cabinet A)</label>
                    <input type="text" name="location" id="location"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="department">Department</label>
                    <input type="text" name="department" id="department"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Warranty Expiry -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="warranty_expiry">Warranty
                        Expiry</label>
                    <input type="date" name="warranty_expiry" id="warranty_expiry"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Condition Notes -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="condition_notes">Fault Details /
                        Condition Notes (e.g., Bluetooth issue, Fan noise)</label>
                    <textarea name="condition_notes" id="condition_notes" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"
                        placeholder="Describe specific issues if not in perfect working condition..."></textarea>
                </div>

                <!-- Maintenance Log -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="maintenance_log">Initial Maintenance
                        Notes</label>
                    <textarea name="maintenance_log" id="maintenance_log" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Save Asset
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>