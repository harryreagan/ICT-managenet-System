<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Edit Network";

$id = $_GET['id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE networks 
        SET name = ?, vlan_tag = ?, subnet = ?, gateway = ?, notes = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['vlan_tag'],
        $_POST['subnet'],
        $_POST['gateway'],
        $_POST['notes'],
        $id
    ]);

    $_SESSION['success'] = "Network updated successfully!";
    redirect('/ict/modules/networks/index.php');
}

// Fetch network data
$stmt = $pdo->prepare("SELECT * FROM networks WHERE id = ?");
$stmt->execute([$id]);
$network = $stmt->fetch();

if (!$network) {
    $_SESSION['error'] = "Network not found!";
    redirect('/ict/modules/networks/index.php');
}

include '../../includes/header.php';
?>

<div class="max-w-3xl mx-auto fade-in-up">
    <div class="flex items-center space-x-3 mb-6">
        <a href="index.php" class="text-slate-400 hover:text-primary-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h1 class="text-3xl font-bold text-slate-800">Edit Network</h1>
    </div>

    <div class="saas-card p-6">
        <form method="POST" class="space-y-6">
            <!-- Name -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Network Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($network['name']); ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <!-- VLAN Tag -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    VLAN Tag <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="vlan_tag" required
                    value="<?php echo htmlspecialchars($network['vlan_tag']); ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
            </div>

            <!-- Subnet -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Subnet (CIDR) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="subnet" required value="<?php echo htmlspecialchars($network['subnet']); ?>"
                    placeholder="e.g. 192.168.1.0/24"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Gateway IP <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="gateway" required value="<?php echo htmlspecialchars($network['gateway']); ?>"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">
                    Notes
                </label>
                <textarea name="notes" rows="3"
                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none resize-none"><?php echo htmlspecialchars($network['notes'] ?? ''); ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="index.php" class="px-6 py-2 text-slate-400 hover:text-slate-600 font-medium transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-md shadow-primary-200 transition-all transform hover:-translate-y-0.5">
                    Update Network
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>