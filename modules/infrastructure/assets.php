<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$area_key = $_GET['area'] ?? 'solar';

$areas = [
    'solar' => ['name' => 'Solar & Battery Backups', 'icon' => 'sun'],
    'charging' => ['name' => 'Car Charging Station', 'icon' => 'charging'],
    'gym' => ['name' => 'Gym Checkup', 'icon' => 'gym'],
    'playground' => ['name' => 'Kids Playground (WiFi/POS)', 'icon' => 'wifi'],
    'ac' => ['name' => 'Server Room AC', 'icon' => 'ac']
];

if (!isset($areas[$area_key])) {
    header("Location: index.php");
    exit();
}

$active_area = $areas[$area_key];
$pageTitle = "Manage Assets: " . $active_area['name'];

// Define Filter Queries
$hardwareQuery = "";
$staticQuery = "";
$params = [];

switch ($area_key) {
    case 'solar':
        $hardwareQuery = "SELECT * FROM hardware_assets WHERE category = 'Inverter' OR name LIKE '%Solar%' OR name LIKE '%Battery%'";
        break;
    case 'charging':
        $hardwareQuery = "SELECT * FROM hardware_assets WHERE category = 'EV Charger' OR name LIKE '%Charger%'";
        break;
    case 'gym':
        $hardwareQuery = "SELECT h.* FROM hardware_assets h JOIN floors f ON h.floor_id = f.id WHERE f.label LIKE '%Gym%' OR h.location LIKE '%Gym%'";
        break;
    case 'playground':
        $hardwareQuery = "SELECT * FROM hardware_assets WHERE location LIKE '%Playground%' OR floor_id IN (SELECT id FROM floors WHERE floor_number = 99)";
        $staticQuery = "SELECT *, device_name as name FROM static_devices WHERE location LIKE '%Playground%'";
        break;
    case 'ac':
        $hardwareQuery = "SELECT * FROM hardware_assets WHERE category = 'AC Unit' OR name LIKE '%AC%' OR name LIKE '%Cooling%'";
        break;
}

$assets = $pdo->query($hardwareQuery)->fetchAll();
$devices = (!empty($staticQuery)) ? $pdo->query($staticQuery)->fetchAll() : [];

include '../../includes/header.php';
?>

<div class="mb-8 flex items-center justify-between">
    <a href="index.php"
        class="text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        BACK TO HUB
    </a>
    <div class="flex items-center space-x-3">
        <a href="../hardware/create.php?prefill_area=<?php echo $area_key; ?>"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-all shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Asset
        </a>
    </div>
</div>

<div class="flex items-center space-x-4 mb-8">
    <div class="p-3 bg-primary-100 text-primary-600 rounded-2xl">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
            </path>
        </svg>
    </div>
    <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">
            <?php echo $active_area['name']; ?> Inventory
        </h1>
        <p class="text-slate-500 text-sm">Managing
            <?php echo count($assets) + count($devices); ?> items in this category.
        </p>
    </div>
</div>

<div class="saas-card overflow-hidden">
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50/80">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Asset /
                    Device</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ref /
                    Serial</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Location
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                </th>
                <th class="relative px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
            <?php foreach ($assets as $asset): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-900">
                                <?php echo htmlspecialchars($asset['name']); ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-mono">
                        <?php echo htmlspecialchars($asset['serial_number']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                        <?php echo htmlspecialchars($asset['location']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?php
                        echo $asset['condition_status'] == 'working' ? 'bg-emerald-50 text-emerald-600' : ($asset['condition_status'] == 'needs_service' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600');
                        ?>">
                            <?php echo str_replace('_', ' ', $asset['condition_status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="../hardware/edit.php?id=<?php echo $asset['id']; ?>"
                            class="text-primary-600 hover:text-primary-900">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php foreach ($devices as $device): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-blue-500 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0">
                                    </path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-900">
                                <?php echo htmlspecialchars($device['name']); ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-mono">
                        <?php echo htmlspecialchars($device['ip_address']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                        <?php echo htmlspecialchars($device['location']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 text-blue-600">
                            Managed Device
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="../networks/edit_static.php?id=<?php echo $device['id']; ?>"
                            class="text-primary-600 hover:text-primary-900">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($assets) && empty($devices)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No assets mapped to this facility
                        area yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>