<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$item_key = $_GET['item'] ?? 'solar';

// Define item details
$items = [
    'solar' => ['name' => 'Solar & Battery Backups', 'icon' => 'sun'],
    'charging' => ['name' => 'Car Charging Station', 'icon' => 'charging'],
    'gym' => ['name' => 'Gym Checkup', 'icon' => 'gym'],
    'playground' => ['name' => 'Kids Playground (WiFi/POS)', 'icon' => 'wifi'],
    'ac' => ['name' => 'Server Room AC', 'icon' => 'ac']
];

if (!isset($items[$item_key])) {
    header("Location: index.php");
    exit();
}

$active_item = $items[$item_key];
$pageTitle = "Facility Checkup: " . $active_item['name'];

// Fetch latest checkup for this item
try {
    $stmt = $pdo->prepare("SELECT * FROM facility_checks WHERE item_key = ?");
    $stmt->execute([$item_key]);
    $last_check = $stmt->fetch();
} catch (PDOException $e) {
    $last_check = null;
}

// Fetch assets for context
$hardwareQuery = "";
$staticQuery = "";
switch ($item_key) {
    case 'solar':
        $hardwareQuery = "SELECT name, condition_status, 'Hardware' as type FROM hardware_assets WHERE category = 'Inverter' OR name LIKE '%Solar%' OR name LIKE '%Battery%'";
        break;
    case 'charging':
        $hardwareQuery = "SELECT name, condition_status, 'Hardware' as type FROM hardware_assets WHERE category = 'EV Charger' OR name LIKE '%Charger%'";
        break;
    case 'gym':
        $hardwareQuery = "SELECT h.name, h.condition_status, h.category as type FROM hardware_assets h JOIN floors f ON h.floor_id = f.id WHERE f.label LIKE '%Gym%' OR h.location LIKE '%Gym%'";
        break;
    case 'playground':
        $hardwareQuery = "SELECT name, condition_status, category as type FROM hardware_assets WHERE location LIKE '%Playground%' OR floor_id IN (SELECT id FROM floors WHERE floor_number = 99)";
        $staticQuery = "SELECT device_name as name, 'Network' as type FROM static_devices WHERE location LIKE '%Playground%'";
        break;
    case 'ac':
        $hardwareQuery = "SELECT name, condition_status, 'Hardware' as type FROM hardware_assets WHERE category = 'AC Unit' OR name LIKE '%AC%' OR name LIKE '%Cooling%'";
        break;
}

$relatedAssets = $pdo->query($hardwareQuery)->fetchAll();
$relatedDevices = (!empty($staticQuery)) ? $pdo->query($staticQuery)->fetchAll() : [];
$assets = array_merge($relatedAssets, $relatedDevices);

include '../../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="index.php"
                class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center mb-2">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Hub
            </a>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Daily Facility Checkup</h1>
            <p class="text-slate-500 text-xs mt-1">Recording status for <span
                    class="font-bold text-slate-700"><?php echo $active_item['name']; ?></span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Form Column -->
        <div class="lg:col-span-2">
            <div class="saas-card p-8 bg-white shadow-xl shadow-slate-200/50">
                <form action="api/save_checkup.php" method="POST" class="space-y-8">
                    <input type="hidden" name="item_key" value="<?php echo $item_key; ?>">
                    <input type="hidden" name="item_name" value="<?php echo $active_item['name']; ?>">

                    <!-- Status Selection -->
                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Operational
                            Status</label>
                        <div class="grid grid-cols-3 gap-4">
                            <label
                                class="relative flex flex-col items-center p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:bg-white hover:border-emerald-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/30 group">
                                <input type="radio" name="status" value="operational" class="peer hidden" <?php echo (!$last_check || $last_check['status'] == 'operational') ? 'checked' : ''; ?>>
                                <div
                                    class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span
                                    class="text-xs font-black text-slate-700 uppercase tracking-tighter">Healthy</span>
                            </label>

                            <label
                                class="relative flex flex-col items-center p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:bg-white hover:border-amber-200 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/30 group">
                                <input type="radio" name="status" value="warning" class="peer hidden" <?php echo ($last_check && $last_check['status'] == 'warning') ? 'checked' : ''; ?>>
                                <div
                                    class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                        </path>
                                    </svg>
                                </div>
                                <span
                                    class="text-xs font-black text-slate-700 uppercase tracking-tighter">Warning</span>
                            </label>

                            <label
                                class="relative flex flex-col items-center p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:bg-white hover:border-rose-200 has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/30 group">
                                <input type="radio" name="status" value="faulty" class="peer hidden" <?php echo ($last_check && $last_check['status'] == 'faulty') ? 'checked' : ''; ?>>
                                <div
                                    class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-black text-slate-700 uppercase tracking-tighter">Faulty</span>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Checkup
                            Observations</label>
                        <textarea name="notes" rows="4"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all placeholder:text-slate-400"
                            placeholder="Describe any issues, unusual noises, or maintenance performed..."><?php echo $last_check ? htmlspecialchars($last_check['notes']) : ''; ?></textarea>
                        <p class="text-[10px] text-slate-400 mt-2 italic flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Warnings and Faults will automatically generate a Handover alert for the next shift.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full bg-slate-800 hover:bg-slate-900 text-white py-4 rounded-xl text-sm font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-slate-800/20 active:scale-[0.98]">
                            Submit Checkup Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Asset Sidebar -->
        <div class="space-y-6">
            <div class="saas-card p-6 bg-slate-900 text-white border-0 shadow-xl">
                <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    Area Assets
                </h3>
                <div class="space-y-3">
                    <?php if (empty($assets)): ?>
                        <p class="text-[10px] text-slate-500 italic">No specific ICT assets linked to this area.</p>
                    <?php else: ?>
                        <?php foreach ($assets as $asset):
                            $a_color = (isset($asset['condition_status']) && $asset['condition_status'] != 'working') ? 'rose' : 'emerald';
                            ?>
                            <div
                                class="flex items-center justify-between p-3 bg-white/5 rounded-xl border border-white/10 group">
                                <div class="overflow-hidden">
                                    <p class="text-[11px] font-black text-slate-200 truncate">
                                        <?php echo htmlspecialchars($asset['name']); ?></p>
                                    <p class="text-[9px] text-slate-500 uppercase tracking-tighter">
                                        <?php echo htmlspecialchars($asset['type'] ?? 'Device'); ?></p>
                                </div>
                                <span
                                    class="w-2 h-2 rounded-full bg-<?php echo $a_color; ?>-500 shadow-[0_0_8px_<?php echo $a_color; ?>]"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="assets.php?item=<?php echo $item_key; ?>"
                    class="block w-full mt-6 text-center py-2.5 bg-white/10 hover:bg-white hover:text-slate-900 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                    Inventory Details &rarr;
                </a>
            </div>

            <div class="saas-card p-6 border-dashed border-2 border-slate-200 bg-slate-50/50">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="history.php?item=<?php echo $item_key; ?>"
                        class="flex items-center p-2 text-[10px] font-bold text-slate-600 hover:text-primary-600 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        View History Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>