<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/encryption.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/credentials/index.php');

$stmt = $pdo->prepare("SELECT * FROM credential_vault WHERE id = ?");
$stmt->execute([$id]);
$cred = $stmt->fetch();

if (!$cred)
    redirect('/ict/modules/credentials/index.php');

// Ownership Check: If personal, only the owner can edit
if ($cred['user_id'] !== null && $cred['user_id'] != $_SESSION['user_id']) {
    die("Access Denied: You do not have permission to edit this personal secret.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = sanitize($_POST['system_name']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $url = sanitize($_POST['url']);
    $notes = sanitize($_POST['notes']);
    $responsible_staff = sanitize($_POST['responsible_staff']);
    $is_personal = isset($_POST['is_personal']);

    if (empty($system_name)) {
        $error = "System Name is required.";
    } else {
        try {
            $userId = $is_personal ? $_SESSION['user_id'] : null;

            // Only update password if a new one is provided.
            if (!empty($password)) {
                $encrypted_password = encryptData($password);
                $stmt = $pdo->prepare("UPDATE credential_vault SET system_name=?, username=?, encrypted_password=?, url=?, notes=?, responsible_staff=?, user_id=? WHERE id=?");
                $stmt->execute([$system_name, $username, $encrypted_password, $url, $notes, $responsible_staff, $userId, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE credential_vault SET system_name=?, username=?, url=?, notes=?, responsible_staff=?, user_id=? WHERE id=?");
                $stmt->execute([$system_name, $username, $url, $notes, $responsible_staff, $userId, $id]);
            }

            // Log update
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], 'UPDATE_CREDENTIAL', "Updated credential ID: $id"]);

            $_SESSION['success'] = "Credential updated successfully!";
            redirect('/ict/modules/credentials/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Edit Credential</h1>
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
                <!-- System Name -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="system_name">System Name *</label>
                    <input type="text" name="system_name" id="system_name"
                        value="<?php echo htmlspecialchars($cred['system_name']); ?>" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username"
                        value="<?php echo htmlspecialchars($cred['username']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="password">New Password (Empty to
                        keep current)</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" id="password" placeholder="••••••••"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-sm leading-5 text-slate-500">
                            <span x-text="show ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>

                <!-- URL -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="url">Login URL</label>
                    <input type="url" name="url" id="url" value="<?php echo htmlspecialchars($cred['url']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Responsible Staff -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="responsible_staff">Responsible
                        Staff</label>
                    <input type="text" name="responsible_staff" id="responsible_staff"
                        value="<?php echo htmlspecialchars($cred['responsible_staff']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Personal Toggle -->
                <div class="col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center justify-between mt-4">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Personal Secret</p>
                        <p class="text-xs text-slate-500 mt-0.5">Only you will be able to see or manage this entry.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_personal" class="sr-only peer" <?php echo $cred['user_id'] ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500"></div>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Update Credential
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>