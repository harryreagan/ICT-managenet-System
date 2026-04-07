<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Allow staff to view via QR but require login (hotel security)
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$stmt = $pdo->prepare("
    SELECT a.*, f.label as floor_label 
    FROM hardware_assets a 
    LEFT JOIN floors f ON a.floor_id = f.id 
    WHERE a.id = ?
");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset)
    redirect('index.php');

$pageTitle = "Asset: " . $asset['name'];
include '../../includes/header.php';
?>

<div class="max-w-xl mx-auto pb-20">
    <!-- Back Navigation -->
    <a href="index.php"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center mb-6">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Inventory
    </a>

    <!-- Header Card -->
    <div
        class="saas-card p-6 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-0 shadow-2xl relative overflow-hidden mb-6">
        <div class="relative z-10">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] font-bold text-primary-400 uppercase tracking-widest mb-1">
                        <?php echo htmlspecialchars($asset['category']); ?>
                    </p>
                    <h1 class="text-2xl font-black">
                        <?php echo htmlspecialchars($asset['name']); ?>
                    </h1>
                    <p class="text-slate-400 text-xs mt-1">Serial: <span class="text-white font-mono">
                            <?php echo htmlspecialchars($asset['serial_number']); ?>
                        </span></p>
                </div>
                <div
                    class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur flex items-center justify-center text-primary-400 border border-white/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="mt-8 flex items-center space-x-6">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Location</p>
                    <p class="text-sm font-bold">
                        <?php echo htmlspecialchars($asset['floor_label'] ?: 'Floating / Unassigned'); ?>
                    </p>
                </div>
                <div class="h-8 w-px bg-white/10"></div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Condition</p>
                    <span class="inline-flex items-center text-[10px] font-bold uppercase py-0.5 px-2 rounded <?php
                    $s = $asset['condition_status'];
                    echo $s === 'working' ? 'bg-emerald-500/20 text-emerald-400' : ($s === 'needs_service' ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400');
                    ?> border border-current">
                        <?php echo ucfirst($asset['condition_status']); ?>
                    </span>
                </div>
            </div>
        </div>
        <!-- Decorative background icon -->
        <div class="absolute -right-4 -bottom-4 opacity-10">
            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm0-8h-2V7h2v2z">
                </path>
            </svg>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="saas-card p-5">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Properties</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Department</span>
                    <span class="font-bold text-slate-800">
                        <?php echo htmlspecialchars($asset['department']); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Specific Location</span>
                    <span class="font-bold text-slate-800">
                        <?php echo htmlspecialchars($asset['location']); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Warranty Expiry</span>
                    <span class="font-bold text-slate-800">
                        <?php echo $asset['warranty_expiry'] ? formatDate($asset['warranty_expiry']) : 'N/A'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="saas-card p-5">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Maintenance Overview</h3>
            <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 min-h-[80px]">
                <p class="text-xs text-slate-600 italic">
                    <?php echo $asset['maintenance_log'] ? nl2br(htmlspecialchars($asset['maintenance_log'])) : 'No recent maintenance logs recorded for this asset.'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-col gap-3">
        <a href="../knowledgebase/create.php?asset_id=<?php echo $asset['id']; ?>"
            class="w-full py-4 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-xl shadow-primary-500/20 text-center transition-all flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Report Incident / Fault
        </a>

        <div class="grid grid-cols-2 gap-3">
            <a href="edit.php?id=<?php echo $asset['id']; ?>"
                class="py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-bold uppercase tracking-widest text-center hover:bg-slate-50">
                Update Status
            </a>
            <button onclick="window.print()"
                class="py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-bold uppercase tracking-widest text-center hover:bg-slate-50">
                Print QR Label
            </button>
        </div>
    </div>

    <!-- QR Code Section (Hidden in mobile, visible for printing/desktop) -->
    <div class="mt-12 saas-card p-8 flex flex-col items-center justify-center border-dashed border-2">
        <div id="qrcode"></div>
        <p class="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">Asset Digital Identity</p>
        <p class="text-[9px] text-slate-300 font-mono mt-1">
            <?php echo htmlspecialchars($asset['serial_number']); ?>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: window.location.href,
        width: 150,
        height: 150,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>

<?php include '../../includes/footer.php'; ?>