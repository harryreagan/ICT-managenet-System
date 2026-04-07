<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/users/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user)
    redirect('/ict/modules/users/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $extension = $_POST['extension'];
    $duty_number = $_POST['duty_number'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, full_name = ?, email = ?, department = ?, role = ?, status = ?, extension = ?, duty_number = ? WHERE id = ?");
            $stmt->execute([$username, $password_hash, $full_name, $email, $department, $role, $status, $extension, $duty_number, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, role = ?, status = ?, extension = ?, duty_number = ? WHERE id = ?");
            $stmt->execute([$username, $full_name, $email, $department, $role, $status, $extension, $duty_number, $id]);
        }

        $_SESSION['success'] = "User updated successfully!";
        redirect('/ict/modules/users/index.php');
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Edit User";
include '../../includes/header.php';
?>

<div class="mb-6">
    <a href="index.php"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Users
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="saas-card p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Edit User:
            <?php echo htmlspecialchars($user['username']); ?>
        </h2>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="space-y-6"
            onsubmit="return confirm('Protect important users. Are you sure you want to update this staff member?')">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username" required
                        value="<?php echo htmlspecialchars($user['username']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="jdoe">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="password">Password (Leave blank to
                        keep current)</label>
                    <input type="password" name="password" id="password"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name"
                        value="<?php echo htmlspecialchars($user['full_name']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="John Doe">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="jdoe@example.com">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="department">Department</label>
                    <select name="department" id="department" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="">-- Select Department --</option>
                        <?php
                        $departments = [
                            "Front Office",
                            "IT Department",
                            "F&B Department",
                            "Housekeeping Department",
                            "Management Department",
                            "Security Department",
                            "Kitchen Department",
                            "Finance Department",
                            "Controls",
                            "Internal Audits"
                        ];
                        foreach ($departments as $dept) {
                            $selected = ($user['department'] === $dept) ? 'selected' : '';
                            echo "<option value=\"$dept\" $selected>$dept</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="role">Role</label>
                    <select name="role" id="role" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="technician" <?php echo $user['role'] === 'technician' ? 'selected' : ''; ?>>
                            Technician</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator
                        </option>
                        <option value="viewer" <?php echo $user['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer
                            (Read-only)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="status">Status</label>
                    <select name="status" id="status" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="extension">Extension</label>
                    <input type="text" name="extension" id="extension"
                        value="<?php echo htmlspecialchars($user['extension']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="e.g. 104">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="duty_number">Duty Number /
                        Mobile</label>
                    <input type="text" name="duty_number" id="duty_number"
                        value="<?php echo htmlspecialchars($user['duty_number']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="e.g. 0743...">
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="index.php"
                    class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>