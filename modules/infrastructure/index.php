<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Facility & Operations Hub";

// Fetch power systems status
$stmt = $pdo->query("SELECT * FROM power_systems ORDER BY system_type ASC");
$systems = $stmt->fetchAll();

// Fetch latest facility checks
try {
    $stmt = $pdo->query("SELECT * FROM facility_checks");
    $facilityChecks = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $facilityChecks = [];
}

// Fetch asset counts per facility item
// This is a simplified mapping based on location and category
$assetCounts = [
    'solar' => $pdo->query("SELECT COUNT(*) FROM hardware_assets WHERE category = 'Inverter' OR name LIKE '%Solar%' OR name LIKE '%Battery%'")->fetchColumn(),
    'charging' => $pdo->query("SELECT COUNT(*) FROM hardware_assets WHERE category = 'EV Charger' OR name LIKE '%Charger%'")->fetchColumn(),
    'gym' => $pdo->query("SELECT COUNT(*) FROM hardware_assets h JOIN floors f ON h.floor_id = f.id WHERE f.label LIKE '%Gym%' OR h.location LIKE '%Gym%'")->fetchColumn(),
    'playground' => $pdo->query("SELECT COUNT(*) FROM (
        SELECT id FROM hardware_assets WHERE location LIKE '%Playground%' OR floor_id IN (SELECT id FROM floors WHERE floor_number = 99)
        UNION
        SELECT id FROM static_devices WHERE location LIKE '%Playground%'
    ) as p")->fetchColumn(),
    'ac' => $pdo->query("SELECT COUNT(*) FROM hardware_assets WHERE category = 'AC Unit' OR name LIKE '%AC%' OR name LIKE '%Cooling%'")->fetchColumn(),
];

// Default items if not in DB
$items = [
    'solar' => ['name' => 'Solar & Battery Backups', 'icon' => 'sun'],
    'charging' => ['name' => 'Car Charging Station', 'icon' => 'charging'],
    'gym' => ['name' => 'Gym Checkup', 'icon' => 'gym'],
    'playground' => ['name' => 'Kids Playground (WiFi/POS)', 'icon' => 'wifi'],
    'ac' => ['name' => 'Server Room AC', 'icon' => 'ac']
];

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-8 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Facility & Operations Hub</h1>
        <p class="text-slate-500 mt-2">Monitoring solar, backups, facility health, and specialized ICT assets.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <div
            class="flex items-center space-x-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-bold uppercase tracking-widest border border-emerald-100">
            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            Live Monitoring Active
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in-up mt-8" style="animation-delay: 0.1s">
    <?php foreach ($items as $key => $item):
        $check = $facilityChecks[$key] ?? ['status' => 'operational', 'notes' => 'No recent checkup recorded'];
        $status_color = $check['status'] == 'operational' ? 'emerald' : ($check['status'] == 'warning' ? 'amber' : 'rose');
        $lastCheckTime = isset($check['last_check_at']) ? time_elapsed_string($check['last_check_at']) : 'Never';
        ?>
        <div
            class="saas-card group hover:shadow-xl transition-all duration-300 border-t-4 border-t-<?php echo $status_color; ?>-500 overflow-hidden bg-white">
            <div class="p-5">
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="p-3 bg-<?php echo $status_color; ?>-50 rounded-2xl text-<?php echo $status_color; ?>-600 group-hover:scale-110 transition-transform duration-500">
                        <?php if ($key == 'solar'): ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        <?php elseif ($key == 'charging'): ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        <?php elseif ($key == 'gym'): ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        <?php elseif ($key == 'playground'): ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0">
                                </path>
                            </svg>
                        <?php else: ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                </path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col items-end">
                        <span
                            class="px-3 py-1 bg-<?php echo $status_color; ?>-50 text-<?php echo $status_color; ?>-600 rounded-full text-[10px] font-black uppercase tracking-tighter border border-<?php echo $status_color; ?>-100 shadow-sm mb-1">
                            <?php echo $check['status']; ?>
                        </span>
                        <span class="text-[10px] font-bold text-slate-300">Checked <?php echo $lastCheckTime; ?></span>
                    </div>
                </div>

                <h3 class="text-xl font-black text-slate-800 tracking-tight leading-none mb-1">
                    <?php echo $item['name']; ?>
                </h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">Operations & Health</p>

                <div class="grid grid-cols-2 gap-3 mb-6">
                    <a href="assets.php?item=<?php echo $key; ?>"
                        class="bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 p-3 rounded-xl transition-all group/sub">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Inventory</p>
                        <p class="text-lg font-black text-slate-700 group-hover/sub:text-primary-600">
                            <?php echo $assetCounts[$key]; ?> <span
                                class="text-[10px] font-normal text-slate-400">Assets</span>
                        </p>
                    </a>
                    <a href="history.php?item=<?php echo $key; ?>"
                        class="bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 p-3 rounded-xl transition-all group/sub">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Logs</p>
                        <div class="flex items-center text-primary-600 group-hover/sub:translate-x-1 transition-transform">
                            <span class="text-[10px] font-black uppercase">History</span>
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>

            <div class="bg-slate-50 border-t border-slate-100 p-1 flex">
                <a href="checkup.php?item=<?php echo $key; ?>"
                    class="flex-1 text-center py-3 text-[11px] font-black text-slate-500 hover:bg-white hover:text-primary-600 transition-all uppercase tracking-widest flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Record Checkup
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-12">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
        <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        Live Power Systems
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in-up">
        <?php foreach ($systems as $sys):
            $status_color = $sys['status'] == 'operational' ? 'emerald' : ($sys['status'] == 'warning' ? 'amber' : 'rose');
            ?>
            <div class="saas-card p-6 overflow-hidden relative group hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 bg-white block">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] leading-none mb-1">
                            <?php echo htmlspecialchars($sys['system_type']); ?>
                        </h3>
                        <h2 class="text-xl font-black text-slate-800 tracking-tight">
                            <?php echo htmlspecialchars($sys['name']); ?>
                        </h2>
                        <p class="text-[10px] text-slate-500 font-bold mt-1 uppercase tracking-tight">
                            <?php echo htmlspecialchars($sys['location']); ?>
                        </p>
                    </div>
                    <div class="p-3 bg-<?php echo $status_color; ?>-50 text-<?php echo $status_color; ?>-600 rounded-2xl group-hover:scale-110 group-hover:bg-<?php echo $status_color; ?>-100 transition-all duration-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>

                <?php if (isset($sys['battery_percentage']) && $sys['battery_percentage'] !== null): ?>
                    <div class="mt-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Charge Level</span>
                            <span class="text-3xl font-black text-slate-800 tracking-tighter">
                                <?php echo $sys['battery_percentage']; ?><span class="text-sm font-bold text-slate-400">%</span>
                            </span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-50">
                            <div class="h-full <?php
                            echo $sys['battery_percentage'] > 50 ? 'bg-emerald-500' : ($sys['battery_percentage'] > 20 ? 'bg-amber-500' : 'bg-rose-500');
                            ?> shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all duration-1000 group-hover:brightness-110"
                                style="width: <?php echo $sys['battery_percentage']; ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-8 pt-6 border-t border-slate-50 flex justify-between items-center">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        Current Load: <span class="text-slate-800 font-black"><?php echo $sys['current_load_kw']; ?></span>
                        kW
                    </span>
                    <button onclick="openPowerModal(<?php echo htmlspecialchars(json_encode($sys)); ?>)"
                        class="text-[10px] font-black text-primary-600 uppercase tracking-[0.2em] group-hover:translate-x-1 transition-transform pointer-events-auto">Details
                        &rarr;</button>
                </div>
            </div>
        <?php endforeach; ?>
</div>
</div>

<!-- Power Edit Modal -->
<div id="powerModal"
    class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div
        class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 id="modalType" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">
                        SYSTEM TYPE</h3>
                    <h2 id="modalName" class="text-2xl font-black text-slate-800 tracking-tight">System Name</h2>
                </div>
                <button onclick="closePowerModal()"
                    class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <form action="api/update_power.php" method="POST" class="space-y-6">
                <input type="hidden" name="id" id="modalId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current
                            Load (KW)</label>
                        <input type="number" step="0.01" name="current_load_kw" id="modalLoad"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all">
                    </div>
                    <div id="chargeGroup">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Charge
                            (%)</label>
                        <input type="number" name="battery_percentage" id="modalCharge" min="0" max="100"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Operational
                        Status</label>
                    <select name="status" id="modalStatus"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all appearance-none uppercase font-bold tracking-tighter">
                        <option value="operational">Operational</option>
                        <option value="warning">Warning</option>
                        <option value="faulty">Faulty</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Quick
                        Notes</label>
                    <textarea name="notes" id="modalNotes" rows="3"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none transition-all"
                        placeholder="Any weird readings or maintenance notes..."></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-slate-800 hover:bg-slate-900 text-white py-4 rounded-xl text-sm font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-slate-800/20">
                        Update System Metrics
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openPowerModal(sys) {
        document.getElementById('modalId').value = sys.id;
        document.getElementById('modalType').innerText = sys.system_type;
        document.getElementById('modalName').innerText = sys.name;
        document.getElementById('modalLoad').value = sys.current_load_kw;
        document.getElementById('modalCharge').value = sys.battery_percentage || '';
        document.getElementById('modalStatus').value = sys.status;
        document.getElementById('modalNotes').value = sys.notes || '';

        // Hide charge level input if not applicable
        const chargeGroup = document.getElementById('chargeGroup');
        if (sys.system_type === 'Main Utility' || sys.system_type === 'HVAC/AC') {
            chargeGroup.classList.add('hidden');
        } else {
            chargeGroup.classList.remove('hidden');
        }

        document.getElementById('powerModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePowerModal() {
        document.getElementById('powerModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close on backdrop click
    document.getElementById('powerModal').addEventListener('click', function (e) {
        if (e.target === this) closePowerModal();
    });
</script>

<div class="mt-12 saas-card p-6 fade-in-up" style="animation-delay: 0.2s">
    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Historical Peak Load (24h)</h3>
    <div class="h-48 flex items-end justify-between space-x-2">
        <?php for ($i = 0; $i < 24; $i++):
            $h = rand(30, 95); ?>
            <div class="flex-1 bg-slate-100 rounded-t-lg group relative hover:bg-primary-500 transition-all duration-500"
                style="height: <?php echo $h; ?>%">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-500/0 to-primary-500/20 rounded-t-lg opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div
                    class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] font-black px-2 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 whitespace-nowrap z-10 shadow-xl">
                    <?php echo $h * 5; ?> kW
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <div class="flex justify-between mt-4 text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
        <span>00:00</span>
        <span>06:00</span>
        <span>12:00</span>
        <span>18:00</span>
        <span>23:59</span>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>