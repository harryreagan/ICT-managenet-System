<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$stmt = $pdo->prepare("
    SELECT c.*, f.label as floor_label 
    FROM data_links c
    LEFT JOIN floors f ON c.floor_id = f.id 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$cab = $stmt->fetch();

if (!$cab)
    redirect('index.php');

$pageTitle = "Cabinet: " . $cab['cabinet_name'];
include '../../includes/header.php';
?>

<div class="max-w-xl mx-auto pb-20 px-4">
    <!-- Back Navigation -->
    <a href="view.php?id=<?php echo $cab['floor_id']; ?>"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center mb-6">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Floor Map
    </a>

    <!-- Cabinet Identity Card -->
    <div class="saas-card p-6 bg-slate-900 text-white rounded-2xl relative overflow-hidden mb-6 border-0 shadow-2xl">
        <div class="relative z-10">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div class="flex items-center space-x-2 mb-1">
                        <span
                            class="px-2 py-0.5 bg-primary-500 text-white text-[9px] font-black uppercase tracking-tighter rounded">IT
                            Infrastructure</span>
                        <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                            <?php echo htmlspecialchars($cab['floor_label']); ?>
                        </span>
                    </div>
                    <h1 class="text-3xl font-black text-white">
                        <?php echo htmlspecialchars($cab['cabinet_name']); ?>
                    </h1>
                </div>
                <div class="p-3 bg-white/10 backdrop-blur rounded-xl border border-white/10 text-primary-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/5 p-4 rounded-xl border border-white/5 text-center">
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Capacity</p>
                    <p class="text-2xl font-black">
                        <?php echo $cab['u_space']; ?><span class="text-xs font-medium text-slate-400 ml-1">U</span>
                    </p>
                </div>
                <div class="bg-white/5 p-4 rounded-xl border border-white/5 text-center">
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status</p>
                    <span class="inline-flex items-center text-[10px] font-extrabold uppercase py-0.5 px-2 rounded <?php
                    echo $cab['status'] === 'online' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400';
                    ?>">
                        <?php echo $cab['status'] ?: 'Offline'; ?>
                    </span>
                </div>
            </div>
        </div>
        <!-- Decorative watermarks -->
        <div class="absolute -right-6 -bottom-6 text-white/5 select-none pointer-events-none">
            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm0-8h-2V7h2v2z">
                </path>
            </svg>
        </div>
    </div>

    <!-- Stats & Logs -->
    <div class="grid grid-cols-1 gap-6 mb-8">
        <div class="saas-card p-5">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Connectivity Details</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Link Type</span>
                    <span class="font-bold text-slate-800">
                        <?php echo htmlspecialchars($cab['link_type'] ?: 'Fibre Backbone'); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-medium">Active Switches</span>
                    <span class="font-bold text-slate-800">
                        <?php echo $cab['switch_count']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="saas-card p-5">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Site Notes</h3>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-xs text-slate-600 italic">
                <?php echo $cab['notes'] ? nl2br(htmlspecialchars($cab['notes'])) : 'No specific onsite notes for this cabinet.'; ?>
            </div>
        </div>
    </div>

    <!-- Quick Toolset -->
    <div class="flex flex-col gap-3">
        <a href="../knowledgebase/create.php?system=Cabinet: <?php echo urlencode($cab['cabinet_name']); ?>"
            class="w-full py-4 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest text-center shadow-lg shadow-primary-500/30">
            Report Connectivity Issue
        </a>
        <div class="grid grid-cols-2 gap-3">
            <button onclick="window.print()"
                class="py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-bold uppercase tracking-widest text-slate-600 hover:bg-slate-50">
                Print Rack Label
            </button>
            <a href="view.php?id=<?php echo $cab['floor_id']; ?>"
                class="py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-bold uppercase tracking-widest text-slate-600 hover:bg-slate-50 text-center">
                Floor Plan
            </a>
        </div>
    </div>

    <!-- Unique QR Identity -->
    <div class="mt-12 p-8 saas-card flex flex-col items-center justify-center border-dashed border-2">
        <div id="qrcode"></div>
        <p class="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">Digital Asset Identity</p>
        <p class="text-[8px] text-slate-300 font-mono mt-1">
            <?php echo strtoupper(md5($cab['cabinet_name'])); ?>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: window.location.href,
        width: 140,
        height: 140,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>

<?php include '../../includes/footer.php'; ?>