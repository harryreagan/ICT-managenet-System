<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$floor_id = $_GET['floor_id'] ?? null;
if (!$floor_id)
    redirect('index.php');

// Fetch floor details
$stmt = $pdo->prepare("SELECT * FROM floors WHERE id = ?");
$stmt->execute([$floor_id]);
$floor = $stmt->fetch();

if (!$floor)
    redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cabinet_name = sanitize($_POST['cabinet_name']);
    $u_space = (int) $_POST['u_space'];
    $switch_count = (int) $_POST['switch_count'];
    $link_type = sanitize($_POST['link_type']);
    $status = sanitize($_POST['status']);
    $last_inspection = $_POST['last_inspection'] ?: date('Y-m-d');

    if (empty($cabinet_name)) {
        $error = "Cabinet Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO data_links (floor_id, cabinet_name, u_space, switch_count, link_type, status, last_inspection) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$floor_id, $cabinet_name, $u_space, $switch_count, $link_type, $status, $last_inspection]);
            $_SESSION['success'] = "Resource Cabinet added to " . $floor['label'];
            redirect("view.php?id=$floor_id");
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Add Cabinet: " . $floor['label'];
include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">New Data Cabinet</h1>
            <p class="text-slate-500 text-xs mt-1 uppercase font-bold tracking-widest">
                <?php echo htmlspecialchars($floor['label']); ?>
            </p>
        </div>
        <a href="view.php?id=<?php echo $floor_id; ?>"
            class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
    </div>

    <div class="saas-card p-8 bg-white shadow-xl">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cabinet Name -->
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Cabinet
                        Label (e.g. IDF-1, Server Rack A)</label>
                    <input type="text" name="cabinet_name" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm transition-all">
                </div>

                <!-- U-Space -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">U-Space
                        Height</label>
                    <select name="u_space"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm">
                        <option value="6">6U (SOHO)</option>
                        <option value="9">9U (Wall Mount)</option>
                        <option value="12">12U (Intermediate)</option>
                        <option value="22">22U (Half Rack)</option>
                        <option value="42" selected>42U (Full Rack)</option>
                        <option value="47">47U (Extra Tall)</option>
                    </select>
                </div>

                <!-- Switch Count -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Active
                        Switches</label>
                    <input type="number" name="switch_count" value="0" min="0" max="48"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm">
                </div>

                <!-- Link Type -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Uplink
                        Type</label>
                    <select name="link_type"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm">
                        <option value="Fiber Optic">Fiber Optic (10G+)</option>
                        <option value="Cat6 Ethernet">Cat6 Ethernet (1G)</option>
                        <option value="Wireless Bridge">Wireless Bridge (Backhaul)</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Initial
                        Status</label>
                    <select name="status"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm">
                        <option value="online">Operational / Online</option>
                        <option value="offline">Inactive / Offline</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>

                <!-- Last Inspection -->
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Last
                        Physical Inspection</label>
                    <input type="date" name="last_inspection" value="<?php echo date('Y-m-d'); ?>"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold shadow-sm">
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-3 px-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-xl shadow-primary-500/30 text-xs uppercase tracking-widest">
                    Add Cabinet
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>