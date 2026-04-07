<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $extension = $_POST['extension'];
    $duty_number = $_POST['duty_number'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, department, role, status, extension, duty_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $full_name, $email, $department, $role, $status, $extension, $duty_number]);

        $_SESSION['success'] = "User created successfully!";
        redirect('/ict/modules/users/index.php');
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Add User";
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
        <h2 class="text-xl font-bold text-slate-800 mb-6">Create New User</h2>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="create.php" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="jdoe">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="John Doe">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="email">Email Address</label>
                    <input type="email" name="email" id="email"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="jdoe@example.com">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="department">Department</label>
                    <select name="department" id="department" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="">-- Select Department --</option>
                        <option value="Front Office">Front Office</option>
                        <option value="IT Department">IT Department</option>
                        <option value="F&B Department">F&B Department</option>
                        <option value="Housekeeping Department">Housekeeping Department</option>
                        <option value="Management Department">Management Department</option>
                        <option value="Security Department">Security Department</option>
                        <option value="Kitchen Department">Kitchen Department</option>
                        <option value="Finance Department">Finance Department</option>
                        <option value="Controls">Controls</option>
                        <option value="Internal Audits">Internal Audits</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="role">Role</label>
                    <select name="role" id="role" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="staff">Staff (Default)</option>
                        <option value="technician">Technician</option>
                        <option value="admin">Administrator</option>
                        <option value="viewer">Viewer (Read-only)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="status">Status</label>
                    <select name="status" id="status" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="extension">Extension</label>
                    <input type="text" name="extension" id="extension"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="e.g. 104">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="duty_number">Duty Number /
                        Mobile</label>
                    <input type="text" name="duty_number" id="duty_number"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none"
                        placeholder="e.g. 0743...">
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="index.php"
                    class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>