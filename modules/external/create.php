<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $url = sanitize($_POST['url']);
    $notes = sanitize($_POST['notes']);
    $owner = sanitize($_POST['owner']);

    if (empty($name) || empty($url)) {
        $error = "Name and URL are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO external_systems (name, url, notes, owner) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $url, $notes, $owner]);
            redirect('/ict/modules/external/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Add External System</h1>
        <a href="index.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="name">System Name *</label>
                    <input type="text" name="name" id="name" placeholder="e.g. UniFi Controller" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- URL -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="url">URL *</label>
                    <input type="url" name="url" id="url" placeholder="https://..." required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Owner -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="owner">System Owner</label>
                    <input type="text" name="owner" id="owner"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Notes -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="2"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Save System
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>