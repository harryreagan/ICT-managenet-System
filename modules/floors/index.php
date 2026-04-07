<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Handle Floor Name Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_floor') {
    requireAdmin();
    $id = $_POST['floor_id'];
    $label = sanitize($_POST['label']);
    $floor_number = (int) $_POST['floor_number'];

    $stmt = $pdo->prepare("UPDATE floors SET label = ?, floor_number = ? WHERE id = ?");
    $stmt->execute([$label, $floor_number, $id]);
    $_SESSION['success'] = "Floor updated successfully!";
    redirect('index.php');
}

$pageTitle = "Floor Mapping & Infrastructure";

// Fetch floors with their respective data links
$stmt = $pdo->query("SELECT f.*, 
                    (SELECT COUNT(*) FROM data_links WHERE floor_id = f.id) as kabinet_count,
                    (SELECT status FROM data_links WHERE floor_id = f.id ORDER BY status DESC LIMIT 1) as link_status
                    FROM floors f ORDER BY floor_number DESC");
$floors = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-8 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Vertical Infrastructure</h1>
        <p class="text-slate-500 mt-2">Monitoring 9 floors of data links, CCTV cabinets, and IP phone connectivity.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <button
            class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm font-bold shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all">
            Refresh Status
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 fade-in-up" style="animation-delay: 0.1s">

    <!-- Floor Stack Visualizer -->
    <div class="lg:col-span-8 space-y-3">
        <?php foreach ($floors as $floor): ?>
            <div class="saas-card group hover:border-primary-300 transition-all cursor-pointer relative overflow-hidden">
                <div class="flex items-center"
                    x-data="{ editing: false, label: '<?php echo addslashes($floor['label']); ?>', floor_number: '<?php echo $floor['floor_number']; ?>' }">
                    <!-- Floor Level Indicator -->
                    <a href="view.php?id=<?php echo $floor['id']; ?>"
                        class="w-20 h-20 bg-slate-50 flex flex-col items-center justify-center border-r border-slate-100 group-hover:bg-primary-50 transition-colors">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Floor</span>
                        <span class="text-3xl font-black text-slate-700 group-hover:text-primary-600">
                            <?php echo $floor['floor_number'] == 0 ? 'B' : $floor['floor_number']; ?>
                        </span>
                    </a>

                    <!-- Content -->
                    <div class="flex-1 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-800 flex items-center">
                                    <?php echo htmlspecialchars($floor['label']); ?>
                                    <?php if ($floor['link_status'] == 'offline'): ?>
                                        <span
                                            class="ml-3 px-2 py-0.5 bg-red-100 text-red-600 text-[10px] font-bold rounded uppercase animate-pulse">Offline</span>
                                    <?php endif; ?>
                                </h4>
                                <button @click="editing = true"
                                    class="text-slate-400 hover:text-primary-500 p-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Editing Inline (Optional) or Modal -->
                            <div x-show="editing" x-cloak
                                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                                <div @click.away="editing = false"
                                    class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-slate-100">
                                    <h3 class="text-lg font-bold text-slate-800 mb-4">Edit Floor Label</h3>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_floor">
                                        <input type="hidden" name="floor_id" value="<?php echo $floor['id']; ?>">
                                        <div class="grid grid-cols-2 gap-4 mb-6">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Floor
                                                    No.</label>
                                                <input type="number" name="floor_number" x-model="floor_number"
                                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Label</label>
                                                <input type="text" name="label" x-model="label"
                                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold">
                                            </div>
                                        </div>
                                        <div class="flex justify-end space-x-3">
                                            <button type="button" @click="editing = false"
                                                class="px-4 py-2 text-slate-400 text-xs font-bold uppercase">Cancel</button>
                                            <button type="submit"
                                                class="bg-primary-500 text-white px-6 py-2 rounded-lg text-xs font-bold uppercase shadow-lg shadow-primary-500/20">Save
                                                Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 mt-2 text-xs text-slate-500">
                                <span class="flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                        </path>
                                    </svg>
                                    <?php echo $floor['kabinet_count']; ?> Cabinets
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1 <?php echo $floor['status'] == 'operational' ? 'text-emerald-500' : 'text-amber-500'; ?>"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <?php echo ucfirst($floor['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Data Link Visual -->
                        <div class="flex space-x-1.5 ml-4">
                            <div class="w-1.5 h-8 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.4)]"
                                title="Phone System Link"></div>
                            <div class="w-1.5 h-8 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.4)]"
                                title="CCTV System Link"></div>
                            <div class="w-1.5 h-8 <?php echo $floor['floor_number'] == 4 ? 'bg-amber-400 animate-pulse' : 'bg-emerald-500'; ?> rounded-full shadow-[0_0_8px_rgba(16,185,129,0.4)]"
                                title="Data/WiFi Link"></div>
                        </div>
                    </div>
                </div>
                <a href="view.php?id=<?php echo $floor['id']; ?>" class="absolute inset-0"></a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Sidebar Info -->
    <div class="lg:col-span-4 space-y-6">
        <div class="saas-card p-6 bg-gradient-to-br from-slate-900 to-primary-900 text-white">
            <h3 class="text-xs font-bold text-primary-300 uppercase tracking-widest mb-4">Infrastructure Legend</h3>
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-3 h-3 mt-1 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div>
                    <div>
                        <p class="text-xs font-bold">Operational</p>
                        <p class="text-[10px] text-slate-400">All data links and switches are active.</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div
                        class="w-3 h-3 mt-1 bg-amber-400 rounded-full animate-pulse shadow-[0_0_8px_rgba(251,191,36,0.6)]">
                    </div>
                    <div>
                        <p class="text-xs font-bold">Degraded</p>
                        <p class="text-[10px] text-slate-400">Minor issues or packet loss detected.</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-3 h-3 mt-1 bg-red-500 rounded-full"></div>
                    <div>
                        <p class="text-xs font-bold">Critical Failure</p>
                        <p class="text-[10px] text-slate-400">Switch or data link down. Immediate attention required.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="saas-card p-6">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Key Support Zone</h3>
            <div class="space-y-3">
                <div class="p-3 bg-slate-50 rounded-lg flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-700">Gym Technology</span>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Stable</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-700">Playground Security</span>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Stable</span>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>