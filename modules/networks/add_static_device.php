<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Add Static Device";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO static_devices 
        (device_name, device_type, ip_address, location, network_id, mac_address, manufacturer, model, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['device_name'],
        $_POST['device_type'],
        $_POST['ip_address'],
        $_POST['location'],
        $_POST['network_id'] ?: null,
        $_POST['mac_address'] ?: null,
        $_POST['manufacturer'] ?: null,
        $_POST['model'] ?: null,
        $_POST['notes'] ?: null,
        $_POST['status']
    ]);

    $_SESSION['success'] = "Static device added successfully!";
    redirect('/ict/modules/networks/static_devices.php');
}

// Get networks for dropdown
$networks = $pdo->query("SELECT id, name, vlan_tag FROM networks ORDER BY vlan_tag")->fetchAll();

include '../../includes/header.php';
?>

<div class="max-w-3xl mx-auto fade-in-up">
    <div class="flex items-center space-x-3 mb-6">
        <a href="static_devices.php" class="text-slate-400 hover:text-primary-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h1 class="text-3xl font-bold text-slate-800">Add Static Device</h1>
    </div>

    <div class="saas-card p-6">
        <form method="POST" class="space-y-6">
            <!-- Device Name -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Device Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="device_name" required
                    placeholder="e.g., Kitchen POS Terminal, Reception Printer"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <!-- Device Type -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Device Type <span class="text-rose-500">*</span>
                </label>
                <select name="device_type" required
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="Printer">Printer</option>
                    <option value="POS">POS System</option>
                    <option value="Scanner">Scanner</option>
                    <option value="IP Phone">IP Phone</option>
                    <option value="Camera">Camera</option>
                    <option value="Access Point">Access Point</option>
                    <option value="Computer/PC">Computer/PC</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- IP Address and Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        IP Address <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ip_address" required placeholder="192.168.10.50"
                        pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        Location <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="location" required placeholder="e.g., Kitchen POS, Front Desk"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <!-- Network/VLAN and MAC Address -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        Network / VLAN
                    </label>
                    <select name="network_id"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">-- Select Network --</option>
                        <?php foreach ($networks as $net): ?>
                            <option value="<?php echo $net['id']; ?>">
                                <?php echo htmlspecialchars($net['name']); ?> (VLAN
                                <?php echo $net['vlan_tag']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        MAC Address
                    </label>
                    <input type="text" name="mac_address" placeholder="AA:BB:CC:DD:EE:FF"
                        pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
                </div>
            </div>

            <!-- Manufacturer and Model -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        Manufacturer
                    </label>
                    <input type="text" name="manufacturer" placeholder="e.g., HP, Canon, Epson"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                        Model
                    </label>
                    <input type="text" name="model" placeholder="e.g., LaserJet Pro M404dn"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select name="status" required
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Notes
                </label>
                <textarea name="notes" rows="3" placeholder="Additional information about this device..."
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none resize-none"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="static_devices.php"
                    class="px-6 py-2 text-slate-400 hover:text-slate-600 font-medium transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-md shadow-primary-200 transition-all transform hover:-translate-y-0.5">
                    Add Device
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>