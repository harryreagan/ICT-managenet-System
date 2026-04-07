<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Static Devices";

// Handle delete
if (isset($_POST['delete_device'])) {
    $source = $_POST['device_source'] ?? 'device';
    $id = $_POST['device_id'];

    if ($source === 'assignment') {
        $stmt = $pdo->prepare("DELETE FROM ip_assignments WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("DELETE FROM static_devices WHERE id = ?");
    }

    $stmt->execute([$id]);
    $_SESSION['success'] = "Entry deleted successfully!";
    redirect('/ict/modules/networks/static_devices.php');
}

// Fetch all static devices + static IP assignments with network info
$stmt = $pdo->query("
    (SELECT sd.id, sd.device_name, sd.device_type, sd.ip_address, sd.location, sd.network_id, 
           sd.mac_address, sd.status, sd.notes, n.name as network_name, n.vlan_tag, 'device' as source
    FROM static_devices sd 
    LEFT JOIN networks n ON sd.network_id = n.id)
    UNION 
    (SELECT ia.id, ia.device_name, 'Other' as device_type, ia.ip_address, ia.notes as location, ia.network_id, 
           ia.mac_address, 'online' as status, ia.notes, n.name as network_name, n.vlan_tag, 'assignment' as source
    FROM ip_assignments ia
    LEFT JOIN networks n ON ia.network_id = n.id
    WHERE ia.status = 'static' 
    AND ia.ip_address NOT IN (SELECT ip_address FROM static_devices))
    ORDER BY vlan_tag ASC, device_type ASC, location ASC
");
$devices = $stmt->fetchAll();

// Get networks for filter dropdown
$networks = $pdo->query("SELECT id, name, vlan_tag FROM networks ORDER BY vlan_tag")->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <div class="flex items-center space-x-3 mb-2">
            <a href="index.php" class="text-slate-400 hover:text-primary-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-slate-800">Static Devices</h1>
        </div>
        <p class="text-slate-500 mt-2">Manage printers, POS systems, and other static network devices.</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        <select id="deviceTypeFilter" onchange="filterDevices()"
            class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
            <option value="">All Types</option>
            <option value="Printer">Printers</option>
            <option value="POS">POS Systems</option>
            <option value="Scanner">Scanners</option>
            <option value="IP Phone">IP Phones</option>
            <option value="Camera">Cameras</option>
            <option value="Access Point">Access Points</option>
            <option value="Computer/PC">Computer/PCs</option>
            <option value="Other">Other</option>
        </select>
        <a href="add_static_device.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Device
        </a>
    </div>
</div>


<div class="space-y-8">
    <?php
    // Group devices by Network ID
    $groupedDevices = [];
    foreach ($devices as $device) {
        $netId = $device['network_id'] ?: 0;
        if (!isset($groupedDevices[$netId])) {
            $groupedDevices[$netId] = [
                'name' => $device['network_name'] ?: 'Unassigned',
                'vlan' => $device['vlan_tag'],
                'devices' => []
            ];
        }
        $groupedDevices[$netId]['devices'][] = $device;
    }

    // Sort groups (Unassigned last, others by VLAN)
    uasort($groupedDevices, function ($a, $b) {
        if ($a['vlan'] == $b['vlan'])
            return 0;
        if ($a['vlan'] === null)
            return 1;
        if ($b['vlan'] === null)
            return -1;
        return $a['vlan'] <=> $b['vlan'];
    });

    foreach ($groupedDevices as $netId => $group):
        ?>
        <div class="fade-in-up">
            <div class="flex items-center space-x-2 mb-4">
                <h2 class="text-xl font-bold text-slate-800">
                    <?php echo htmlspecialchars($group['name']); ?>
                </h2>
                <?php if ($group['vlan']): ?>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        VLAN <?php echo $group['vlan']; ?>
                    </span>
                <?php endif; ?>
                <span class="text-xs text-slate-400 font-medium ml-2">
                    (<?php echo count($group['devices']); ?> devices)
                </span>
            </div>

            <div class="saas-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-1/4">
                                    Device</th>
                                <th
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    IP Address</th>
                                <th
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Location</th>
                                <th
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($group['devices'] as $device):
                                // Icon and color based on device type
                                $iconMap = [
                                    'Printer' => ['icon' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z', 'color' => 'blue'],
                                    'POS' => ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'emerald'],
                                    'Scanner' => ['icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', 'color' => 'purple'],
                                    'IP Phone' => ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'color' => 'indigo'],
                                    'Camera' => ['icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'rose'],
                                    'Access Point' => ['icon' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0', 'color' => 'cyan'],
                                    'Computer/PC' => ['icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'sky'],
                                    'Other' => ['icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z', 'color' => 'slate']
                                ];

                                $deviceIcon = $iconMap[$device['device_type']] ?? $iconMap['Other'];
                                $color = $deviceIcon['color'];
                                $icon = $deviceIcon['icon'];

                                $statusColors = [
                                    'online' => 'emerald',
                                    'offline' => 'slate',
                                    'maintenance' => 'amber'
                                ];
                                $statusColor = $statusColors[$device['status']] ?? 'slate';
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors device-row"
                                    data-type="<?php echo $device['device_type']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="h-8 w-8 rounded-lg bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-600 flex items-center justify-center mr-3">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="<?php echo $icon; ?>"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($device['device_name']); ?></span>
                                                <?php if ($device['source'] === 'assignment'): ?>
                                                    <div class="flex items-center mt-0.5">
                                                        <span
                                                            class="px-1 py-0.5 bg-blue-50 text-blue-600 text-[9px] font-black uppercase rounded border border-blue-100 italic">VLAN
                                                            Assignment</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="text-[10px] font-bold text-<?php echo $color; ?>-600 uppercase tracking-widest bg-<?php echo $color; ?>-50 px-2 py-0.5 rounded">
                                            <?php echo $device['device_type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="font-mono text-xs font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($device['ip_address']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="text-xs font-medium text-slate-600"><?php echo htmlspecialchars($device['location']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-1.5 w-1.5 rounded-full bg-<?php echo $statusColor; ?>-500 mr-2"></div>
                                            <span
                                                class="text-[10px] font-bold text-<?php echo $statusColor; ?>-600 uppercase tracking-widest">
                                                <?php echo $device['status']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <?php
                                            $editUrl = ($device['source'] === 'assignment')
                                                ? "manage_ip.php?id={$device['id']}&network_id={$device['network_id']}"
                                                : "edit_static_device.php?id={$device['id']}";
                                            ?>
                                            <a href="<?php echo $editUrl; ?>"
                                                class="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <button
                                                onclick="confirmDelete(<?php echo $device['id']; ?>, '<?php echo addslashes($device['device_name']); ?>', '<?php echo $device['source']; ?>')"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($groupedDevices)): ?>
        <div class="saas-card p-12 text-center text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                </path>
            </svg>
            <p class="text-lg font-bold text-slate-600">No static devices found</p>
            <p class="text-sm mt-1">Add devices to start tracking them.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden fade-in-up">
        <form method="POST">
            <input type="hidden" name="device_id" id="delete_device_id">
            <input type="hidden" name="device_source" id="delete_device_source">
            <div class="p-6">
                <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Device</h3>
                <p class="text-slate-600">Are you sure you want to delete <strong id="delete_device_name"></strong>?</p>
            </div>
            <div class="px-6 pb-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')"
                    class="px-4 py-2 text-slate-400 hover:text-slate-600 font-medium">Cancel</button>
                <button type="submit" name="delete_device"
                    class="px-6 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg font-bold shadow-md shadow-rose-200 transition-all">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDelete(id, name, source) {
        document.getElementById('delete_device_id').value = id;
        document.getElementById('delete_device_name').textContent = name;
        document.getElementById('delete_device_source').value = source;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function filterDevices() {
        const filter = document.getElementById('deviceTypeFilter').value;
        const rows = document.querySelectorAll('.device-row');

        rows.forEach(row => {
            if (filter === '' || row.dataset.type === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>