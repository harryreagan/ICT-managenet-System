<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

// Fetch floor details
$stmt = $pdo->prepare("SELECT * FROM floors WHERE id = ?");
$stmt->execute([$id]);
$floor = $stmt->fetch();

if (!$floor)
    redirect('index.php');

// Handle deleting a link between asset and floor (or deleting asset if preferred)
if (isset($_POST['action']) && $_POST['action'] === 'remove_asset') {
    $asset_id = $_POST['asset_id'];
    $stmt = $pdo->prepare("UPDATE hardware_assets SET floor_id = NULL WHERE id = ?");
    $stmt->execute([$asset_id]);
    $_SESSION['success'] = "Asset removed from floor.";
    header("Location: view.php?id=$id");
    exit;
}

// Fetch Infrastructure: Cabinets (Data Links)
$stmt = $pdo->prepare("SELECT * FROM data_links WHERE floor_id = ? ORDER BY cabinet_name ASC");
$stmt->execute([$id]);
$cabinets = $stmt->fetchAll();

// Fetch Infrastructure: Hardware Assets (Linked via floor_id)
$stmt = $pdo->prepare("SELECT * FROM hardware_assets WHERE floor_id = ? ORDER BY category, name ASC");
$stmt->execute([$id]);
$assets = $stmt->fetchAll();

// Summary counts
$ap_count = 0;
$switch_count = 0;
$other_count = 0;
foreach ($assets as $a) {
    if ($a['category'] === 'Access Point')
        $ap_count++;
    elseif ($a['category'] === 'Switch')
        $switch_count++;
    else
        $other_count++;
}

$pageTitle = "Floor Dashboard: " . $floor['label'];
include '../../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <a href="index.php"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Floors
    </a>

    <div class="flex space-x-3">
        <a href="/ict/modules/hardware/create.php?floor_id=<?php echo $id; ?>"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all flex items-center">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Infrastructure
        </a>
    </div>
</div>

<!-- Header Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="saas-card p-5 border-l-4 border-l-primary-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Network Cabinets</p>
        <p class="text-2xl font-black text-slate-800"><?php echo count($cabinets); ?></p>
    </div>
    <div class="saas-card p-5 border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Access Points</p>
        <p class="text-2xl font-black text-slate-800"><?php echo $ap_count; ?></p>
    </div>
    <div class="saas-card p-5 border-l-4 border-l-amber-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Switches</p>
        <p class="text-2xl font-black text-slate-800"><?php echo $switch_count; ?></p>
    </div>
    <div class="saas-card p-5 border-l-4 border-l-slate-400">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Other Assets</p>
        <p class="text-2xl font-black text-slate-800"><?php echo $other_count; ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

    <!-- Left: Cabinets -->
    <div class="space-y-6">
        <h3 class="text-sm font-bold text-slate-800 flex items-center">
            <svg class="w-4 h-4 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Data Cabinets & Racks
        </h3>

        <?php if (empty($cabinets)): ?>
            <div class="saas-card p-8 border-dashed border-2 flex flex-col items-center justify-center text-slate-400">
                <p class="text-xs font-medium">No cabinets registered on this floor.</p>
                <a href="add_cabinet.php?floor_id=<?php echo $id; ?>"
                    class="text-[10px] font-bold text-primary-500 mt-2 uppercase">Create First Cabinet</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($cabinets as $cab): ?>
                    <div class="saas-card p-5 hover:border-primary-300 transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-primary-500 group-hover:bg-primary-50">
                                    <span class="text-lg font-black"><?php echo $cab['u_space']; ?>U</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800">
                                        <a href="cabinet_view.php?id=<?php echo $cab['id']; ?>"
                                            class="hover:text-primary-600 transition-colors">
                                            <?php echo htmlspecialchars($cab['cabinet_name']); ?>
                                        </a>
                                    </h4>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                        <?php echo $cab['switch_count']; ?> Active Switches • <?php echo $cab['link_type']; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <a href="cabinet_view.php?id=<?php echo $cab['id']; ?>"
                                    class="p-1.5 bg-primary-50 text-primary-600 rounded-lg hover:bg-primary-100 transition-colors mb-2"
                                    title="View Digital ID / QR">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </a>
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                    <?php echo $cab['status'] === 'online' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'; ?>">
                                    <?php echo $cab['status'] ?: 'Unknown'; ?>
                                </span>
                                <p class="text-[9px] text-slate-400 mt-1">Last Inspection:
                                    <?php echo date('M d, Y', strtotime($cab['last_inspection'] ?: 'now')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: Other Infrastructure Assets -->
    <div class="space-y-6">
        <h3 class="text-sm font-bold text-slate-800 flex items-center">
            <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
            On-Floor Technology Assets
        </h3>

        <?php if (empty($assets)): ?>
            <div class="saas-card p-8 border-dashed border-2 flex flex-col items-center justify-center text-slate-400">
                <p class="text-xs font-medium">No specialized hardware on this floor.</p>
                <a href="/ict/modules/hardware/create.php?floor_id=<?php echo $id; ?>"
                    class="text-[10px] font-bold text-emerald-500 mt-2 uppercase">Add First Asset</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($assets as $asset): ?>
                    <div class="saas-card p-5 hover:border-emerald-300 transition-all group">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center">
                                    <?php if ($asset['category'] === 'Access Point'): ?>
                                        <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 11c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 2c0-3.31-2.69-6-6-6s-6 2.69-6 6c0 1.66.67 3.16 1.76 4.24l1.42-1.42C8.41 14.98 8 14.04 8 13c0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.04-.41 1.98-1.18 2.82l1.42 1.42C17.33 16.16 18 14.66 18 13zm4 0c0-5.52-4.48-10-10-10S2 7.48 2 13c0 2.76 1.12 5.26 2.93 7.07l1.42-1.42C4.85 17.15 4 15.17 4 13c0-4.41 3.59-8 8-8s8 3.59 8 8c0 2.17-.85 4.15-2.35 5.65l1.42 1.42C20.88 18.26 22 15.76 22 13z" />
                                        </svg>
                                    <?php elseif ($asset['category'] === 'CCTV Camera'): ?>
                                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($asset['name']); ?>
                                    </h4>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                        <?php echo $asset['category']; ?> • <?php echo $asset['serial_number']; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                        <?php
                                        $s = $asset['condition_status'];
                                        if ($s === 'working')
                                            echo 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                                        elseif ($s === 'needs_service')
                                            echo 'bg-amber-50 text-amber-600 border border-amber-100';
                                        else
                                            echo 'bg-red-50 text-red-600 border border-red-100';
                                        ?>">
                                        <?php echo ucfirst($asset['condition_status']); ?>
                                    </span>
                                </div>
                                <form method="POST" onsubmit="return confirm('Remove asset from this floor?')">
                                    <input type="hidden" name="action" value="remove_asset">
                                    <input type="hidden" name="asset_id" value="<?php echo $asset['id']; ?>">
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>