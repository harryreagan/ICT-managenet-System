<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$networkId = $_GET['id'] ?? 0;

// Fetch network details
$stmt = $pdo->prepare("SELECT * FROM networks WHERE id = ?");
$stmt->execute([$networkId]);
$network = $stmt->fetch();

if (!$network) {
    redirect('/ict/modules/networks');
}

$pageTitle = htmlspecialchars($network['name']) . " Directory";

// Handle Assignment Deletion
if (isset($_POST['delete_assignment'])) {
    $stmt = $pdo->prepare("DELETE FROM ip_assignments WHERE id = ? AND network_id = ?");
    $stmt->execute([$_POST['assignment_id'], $networkId]);
    $_SESSION['success'] = "IP assignment updated successfully!";
    header("Location: /ict/modules/networks/view.php?id=$networkId");
    exit;
}

// Fetch IP assignments
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM ip_assignments WHERE network_id = ? ";
$params = [$networkId];

if ($search) {
    $sql .= " AND (ip_address LIKE ? OR device_name LIKE ? OR mac_address LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$sql .= " ORDER BY INET_ATON(ip_address) ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Fetch static devices for this network
$stmt = $pdo->prepare("SELECT * FROM static_devices WHERE network_id = ? ORDER BY device_type, location");
$stmt->execute([$networkId]);
$staticDevices = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="mb-6 fade-in-up">
    <a href="index.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium flex items-center mb-4">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Networks
    </a>
    <div class="flex flex-col md:flex-row justify-between items-end">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <h1 class="text-3xl font-bold text-slate-800">
                    <?php echo htmlspecialchars($network['name']); ?>
                </h1>
                <span
                    class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-bold rounded uppercase tracking-widest border border-slate-200">VLAN
                    <?php echo $network['vlan_tag']; ?>
                </span>
            </div>
            <p class="text-slate-500 text-sm">Managing IP range
                <?php echo htmlspecialchars($network['subnet']); ?> (Gateway:
                <?php echo htmlspecialchars($network['gateway']); ?>)
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="manage_ip.php?network_id=<?php echo $networkId; ?>"
                class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Assign IP
            </a>
            <a href="edit_network.php?id=<?php echo $networkId; ?>"
                class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-all ml-2">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                    </path>
                </svg>
                Edit
            </a>
        </div>
    </div>
</div>

<!-- Static Devices Section -->
<?php if (count($staticDevices) > 0): ?>
    <div class="mb-6 fade-in-up" style="animation-delay: 0.05s">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-slate-800">Static Devices on this Network</h2>
            <a href="add_static_device.php?network_id=<?php echo $networkId; ?>"
                class="text-xs font-bold text-primary-600 hover:text-primary-800 uppercase tracking-wider">
                + Add Device
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($staticDevices as $device):
                $iconMap = [
                    'Printer' => ['icon' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z', 'color' => 'blue'],
                    'POS' => ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'emerald'],
                    'Scanner' => ['icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', 'color' => 'purple'],
                    'IP Phone' => ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'color' => 'indigo'],
                    'Camera' => ['icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'rose'],
                    'Access Point' => ['icon' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0', 'color' => 'cyan'],
                    'Other' => ['icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z', 'color' => 'slate']
                ];
                $deviceIcon = $iconMap[$device['device_type']] ?? $iconMap['Other'];
                $color = $deviceIcon['color'];
                $icon = $deviceIcon['icon'];
                ?>
                <div class="saas-card p-4 hover:border-<?php echo $color; ?>-300 transition-all">
                    <div class="flex items-start space-x-3">
                        <div
                            class="h-10 w-10 rounded-lg bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $icon; ?>">
                                </path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 text-sm truncate">
                                <?php echo htmlspecialchars($device['device_name']); ?>
                            </h3>
                            <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($device['location']); ?></p>
                            <p class="text-xs font-mono font-bold text-primary-600 mt-1">
                                <?php echo htmlspecialchars($device['ip_address']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">
            <?php echo count($assignments); ?> Assignments Found
        </div>

        <form action="" method="GET" class="flex items-center w-full sm:w-auto">
            <input type="hidden" name="id" value="<?php echo $networkId; ?>">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" placeholder="Search IP, Device, MAC..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-full focus:ring-primary-500 focus:border-primary-500 transition-shadow outline-none">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto text-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50">
                    <th
                        class="px-6 py-4 font-bold text-slate-500 uppercase tracking-widest text-[10px] border-b border-slate-100">
                        IP Address</th>
                    <th
                        class="px-6 py-4 font-bold text-slate-500 uppercase tracking-widest text-[10px] border-b border-slate-100">
                        Device Name</th>
                    <th
                        class="px-6 py-4 font-bold text-slate-500 uppercase tracking-widest text-[10px] border-b border-slate-100">
                        MAC Address</th>
                    <th
                        class="px-6 py-4 font-bold text-slate-500 uppercase tracking-widest text-[10px] border-b border-slate-100">
                        Status</th>
                    <th
                        class="px-6 py-4 font-bold text-slate-500 uppercase tracking-widest text-[10px] border-b border-slate-100 text-right">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($assignments as $ip): ?>
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-mono text-primary-600 font-bold">
                            <?php echo htmlspecialchars($ip['ip_address']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">
                                <?php echo htmlspecialchars($ip['device_name']); ?>
                            </div>
                            <?php if ($ip['notes']): ?>
                                <div class="text-[10px] text-slate-400 line-clamp-1 italic">
                                    <?php echo htmlspecialchars($ip['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-500">
                            <?php echo htmlspecialchars($ip['mac_address'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?php echo $ip['status'] === 'static' ? 'bg-emerald-50 text-emerald-600' : 'bg-primary-50 text-primary-600'; ?> shadow-sm">
                                <?php echo htmlspecialchars($ip['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="manage_ip.php?id=<?php echo $ip['id']; ?>&network_id=<?php echo $networkId; ?>"
                                    class="p-1.5 text-slate-400 hover:text-primary-600 transition-colors rounded-md hover:bg-primary-50"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                                <form method="POST" id="release-ip-<?php echo $ip['id']; ?>" class="inline-block">
                                    <input type="hidden" name="assignment_id" value="<?php echo $ip['id']; ?>">
                                    <button type="button" name="delete_assignment"
                                        @click="$store.modal.trigger('release-ip-<?php echo $ip['id']; ?>', 'Release this IP assignment?', 'Release IP')"
                                        class="p-1.5 text-slate-400 hover:text-red-600 transition-colors rounded-md hover:bg-red-50"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($assignments) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <div class="h-10 w-10 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <span>No IP assignments found in this network.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>