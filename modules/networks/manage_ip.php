<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
$networkId = $_GET['network_id'] ?? null;

if (!$networkId) {
    redirect('/ict/modules/networks');
}

// Fetch network details
$stmt = $pdo->prepare("SELECT * FROM networks WHERE id = ?");
$stmt->execute([$networkId]);
$network = $stmt->fetch();

$assignment = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM ip_assignments WHERE id = ? AND network_id = ?");
    $stmt->execute([$id, $networkId]);
    $assignment = $stmt->fetch();
}

$pageTitle = ($id ? "Edit IP Assignment" : "New IP Assignment") . " - " . htmlspecialchars($network['name']);

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip_address'];
    $device = $_POST['device_name'];
    $mac = $_POST['mac_address'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE ip_assignments SET ip_address = ?, device_name = ?, mac_address = ?, status = ?, notes = ? WHERE id = ? AND network_id = ?");
        $stmt->execute([$ip, $device, $mac, $status, $notes, $id, $networkId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ip_assignments (network_id, ip_address, device_name, mac_address, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$networkId, $ip, $device, $mac, $status, $notes]);
    }
    $_SESSION['success'] = "IP assignment updated successfully!";
    header("Location: /ict/modules/networks/view.php?id=$networkId");
    exit;
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-in-up">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                <?php echo $id ? "Edit Assignment" : "Assign New IP"; ?>
            </h1>
            <p class="text-slate-500 text-sm">Target VLAN: <span class="font-bold text-primary-600">
                    <?php echo htmlspecialchars($network['name']); ?>
                </span></p>
        </div>
        <a href="view.php?id=<?php echo $networkId; ?>" class="text-slate-400 hover:text-slate-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="saas-card p-8">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">IP
                        Address</label>
                    <input type="text" name="ip_address" required placeholder="e.g. 192.168.10.50"
                        value="<?php echo $assignment ? htmlspecialchars($assignment['ip_address']) : ''; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono text-sm">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Assignment
                        Status</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="static" <?php echo $assignment && $assignment['status'] === 'static' ? 'selected' : ''; ?>>Static IP</option>
                        <option value="dhcp_reserved" <?php echo $assignment && $assignment['status'] === 'dhcp_reserved' ? 'selected' : ''; ?>>DHCP Reservation</option>
                        <option value="dynamic" <?php echo $assignment && $assignment['status'] === 'dynamic' ? 'selected' : ''; ?>>Dynamic (Discovery View Only)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Device Name /
                    Label</label>
                <input type="text" name="device_name" required placeholder="e.g. Front Desk Server"
                    value="<?php echo $assignment ? htmlspecialchars($assignment['device_name']) : ''; ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">MAC Address
                    (Optional)</label>
                <input type="text" name="mac_address" placeholder="e.g. 00:0A:95:9D:68:16"
                    value="<?php echo $assignment ? htmlspecialchars($assignment['mac_address']) : ''; ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono text-sm">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Notes /
                    Location / Port</label>
                <textarea name="notes" rows="3" placeholder="Additional details..."
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm"><?php echo $assignment ? htmlspecialchars($assignment['notes']) : ''; ?></textarea>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-50">
                <a href="view.php?id=<?php echo $networkId; ?>"
                    class="text-sm font-medium text-slate-400 hover:text-slate-600 transition-colors">Discard</a>
                <button type="submit"
                    class="px-8 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all hover:scale-105">
                    <?php echo $id ? "Update Assignment" : "Create Assignment"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>