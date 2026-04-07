<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Network Management (IPAM)";

// Handle WiFi Password Update
if (isset($_POST['update_wifi'])) {
    $stmt = $pdo->prepare("UPDATE networks SET wifi_password = ?, password_last_changed = NOW() WHERE id = ?");
    $stmt->execute([$_POST['wifi_password'], $_POST['network_id']]);
    $_SESSION['success'] = "WiFi password updated and logged successfully!";
    redirect('/ict/modules/networks');
}

// Handle Network Delete
if (isset($_POST['delete_network'])) {
    // Check for dependent devices first? Assuming cascade or just warn.
    // For now, simple delete.
    $stmt = $pdo->prepare("DELETE FROM networks WHERE id = ?");
    $stmt->execute([$_POST['network_id']]);
    $_SESSION['success'] = "Network deleted successfully!";
    redirect('/ict/modules/networks/index.php');
}

// Handle Add Network
if (isset($_POST['add_network'])) {
    $name = sanitize($_POST['name']);
    $vlan_tag = (int) $_POST['vlan_tag'];
    $subnet = sanitize($_POST['subnet']);
    $gateway = sanitize($_POST['gateway']);
    $wifi_password = sanitize($_POST['wifi_password']);
    $is_wifi_hotspot = isset($_POST['is_wifi_hotspot']) ? 1 : 0;
    $hotspot_ssid = sanitize($_POST['hotspot_ssid']);

    $stmt = $pdo->prepare("INSERT INTO networks (name, vlan_tag, subnet, gateway, wifi_password, is_wifi_hotspot, hotspot_ssid) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $vlan_tag, $subnet, $gateway, $wifi_password, $is_wifi_hotspot, $hotspot_ssid]);

    $_SESSION['success'] = "New network added successfully!";
    redirect('/ict/modules/networks/index.php');
}

// Fetch all networks with static device count
$stmt = $pdo->query("
    SELECT n.*, COUNT(sd.id) as static_device_count 
    FROM networks n 
    LEFT JOIN static_devices sd ON n.id = sd.network_id 
    GROUP BY n.id 
    ORDER BY n.vlan_tag ASC
");
$networks = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Network Directory</h1>
        <p class="text-slate-500 mt-2">Manage VLANs, Subnets, and IP assignments across the hotel property.</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        <a href="static_devices.php"
            class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                </path>
            </svg>
            Static Devices
        </a>
        <button onclick="document.getElementById('addNetworkModal').classList.remove('hidden')"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add VLAN
        </button>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Network</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Subnet</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Gateway</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Static Devices</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">WiFi
                        Access</th>
                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($networks as $net):
                    // Simple heuristic for icon/color
                    $isGuest = strpos(strtolower($net['name']), 'guest') !== false;
                    $isCritical = (strpos(strtolower($net['name']), 'cctv') !== false || strpos(strtolower($net['name']), 'voice') !== false);

                    $cardColor = $isGuest ? 'primary' : ($isCritical ? 'rose' : 'emerald');
                    $iconPath = $isGuest ? 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0' : 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z';
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-8 w-8 rounded-lg bg-<?php echo $cardColor; ?>-50 text-<?php echo $cardColor; ?>-600 flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="<?php echo $iconPath; ?>"></path>
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($net['name']); ?></span>
                                    <span
                                        class="text-[9px] font-bold text-<?php echo $cardColor; ?>-600 uppercase tracking-widest">VLAN
                                        <?php echo $net['vlan_tag'] ?: '0'; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="text-xs font-mono font-bold text-slate-600"><?php echo htmlspecialchars($net['subnet']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="text-xs font-mono text-slate-500"><?php echo htmlspecialchars($net['gateway']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($net['static_device_count'] > 0): ?>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-<?php echo $cardColor; ?>-50 text-<?php echo $cardColor; ?>-700">
                                    <?php echo $net['static_device_count']; ?> devices
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-300 italic">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($isGuest): ?>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-mono font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded">
                                        <?php echo htmlspecialchars($net['wifi_password'] ?: 'NOT SET'); ?>
                                    </span>
                                    <button
                                        onclick="openWifiModal(<?php echo $net['id']; ?>, '<?php echo addslashes($net['wifi_password']); ?>')"
                                        class="p-1 text-primary-400 hover:text-primary-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400 uppercase tracking-tighter">— No Auth Needed —</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="view.php?id=<?php echo $net['id']; ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-primary-500 hover:text-white text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                                Manage
                                <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a>
                            <a href="edit_network.php?id=<?php echo $net['id']; ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all ml-2">
                                </path>
                                </svg>
                            </a>
                            <button
                                onclick="openDeleteModal(<?php echo $net['id']; ?>, '<?php echo addslashes($net['name']); ?>')"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-500 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all ml-2"
                                title="Delete Network">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Network Modal -->
<div id="addNetworkModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden fade-in-up">
        <form method="POST" class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-slate-800">Add New VLAN / Network</h3>
                <button type="button" onclick="document.getElementById('addNetworkModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Network Name</label>
                    <input type="text" name="name" required placeholder="e.g. Finance VLAN"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">VLAN ID</label>
                    <input type="number" name="vlan_tag" required placeholder="e.g. 10"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Subnet</label>
                    <input type="text" name="subnet" required placeholder="e.g. 192.168.10.0/24"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Gateway</label>
                    <input type="text" name="gateway" required placeholder="e.g. 192.168.10.1"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">WiFi Password</label>
                    <input type="text" name="wifi_password" placeholder="Optional"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all font-mono">
                </div>
                <div class="md:col-span-2 space-y-4 pt-2">
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="is_wifi_hotspot" value="1" class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500 transition-all">
                        <span class="text-sm font-medium text-slate-700 group-hover:text-primary-600 transition-colors">This is a WiFi Hotspot network</span>
                    </label>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Hotspot SSID (if applicable)</label>
                        <input type="text" name="hotspot_ssid" placeholder="e.g. Hotel-Guest-WiFi"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all">
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addNetworkModal').classList.add('hidden')"
                    class="px-4 py-2 text-slate-400 hover:text-slate-600 font-bold transition-colors">Cancel</button>
                <button type="submit" name="add_network"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-lg shadow-primary-500/20 transition-all transform hover:-translate-y-0.5">
                    Save Network
                </button>
            </div>
        </form>
    </div>
</div>

<!-- WiFi Update Modal -->
<div id="wifiModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden fade-in-up">
        <form method="POST" class="p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Rotate WiFi Password</h3>
            <input type="hidden" name="network_id" id="wifi_network_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">New
                        Password</label>
                    <input type="text" name="wifi_password" id="wifi_password_input" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('wifiModal').classList.add('hidden')"
                    class="px-4 py-2 text-slate-400 hover:text-slate-600 font-medium">Cancel</button>
                <button type="submit" name="update_wifi"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-md shadow-primary-200 transition-all">Update
                    & Log</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Network Modal -->
<div id="deleteModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden fade-in-up">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Network?</h3>
            <p class="text-slate-500 text-sm mb-6">
                Are you sure you want to delete <strong id="delete_network_name" class="text-slate-800"></strong>?<br>
                This action cannot be undone.
            </p>

            <form method="POST" class="flex justify-center space-x-3">
                <input type="hidden" name="network_id" id="delete_network_id">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')"
                    class="px-5 py-2.5 text-slate-500 hover:text-slate-700 font-bold bg-slate-100 hover:bg-slate-200 rounded-lg transition-all">
                    Cancel
                </button>
                <button type="submit" name="delete_network"
                    class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-lg font-bold shadow-lg shadow-rose-500/30 transition-all transform hover:-translate-y-0.5">
                    Yes, Delete It
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openWifiModal(id, currentPass) {
        document.getElementById('wifi_network_id').value = id;
        document.getElementById('wifi_password_input').value = currentPass;
        document.getElementById('wifiModal').classList.remove('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('delete_network_id').value = id;
        document.getElementById('delete_network_name').textContent = name;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>