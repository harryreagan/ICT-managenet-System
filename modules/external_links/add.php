<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireAdmin();

$pageTitle = "Add External System";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $category = $_POST['category'];

    if (empty($name) || empty($url)) {
        $error = "Name and URL are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO external_links (name, url, category) VALUES (?, ?, ?)");
        $stmt->execute([$name, $url, $category]);
        header("Location: index.php");
        exit();
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php" class="text-slate-500 hover:text-slate-700 flex items-center text-sm font-bold">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to External Systems
        </a>
    </div>

    <div class="saas-card p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Add New System</h1>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-bold">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">System Name</label>
                <input type="text" name="name" required placeholder="e.g. Unifi Controller"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">URL</label>
                <input type="url" name="url" required placeholder="https://..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Category</label>
                <select name="category"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                    <option value="Network">Network Infrastructure</option>
                    <option value="Security">Security & Surveillance</option>
                    <option value="Vendor">Vendor Portal</option>
                    <option value="Other">Other Tools</option>
                </select>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-primary-500/30 transition-all">
                    Save System
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>