<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/encryption.php';

requireLogin();

$pageTitle = "Secure Credential Vault";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    
    // Ownership Check for Deletion
    $checkStmt = $pdo->prepare("SELECT user_id FROM credential_vault WHERE id = ?");
    $checkStmt->execute([$id]);
    $cred = $checkStmt->fetch();
    
    if ($cred && ($cred['user_id'] === null || $cred['user_id'] == $_SESSION['user_id'])) {
        $stmt = $pdo->prepare("DELETE FROM credential_vault WHERE id = ?");
        $stmt->execute([$id]);

        // Log deletion
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], 'DELETE_CREDENTIAL', "Deleted credential ID: $id"]);
    }

    redirect($_SERVER['REQUEST_URI']);
}

// Search
$search = $_GET['search'] ?? '';
$whereClause = "";
$params = [];

if ($search) {
    $whereClause = "AND (system_name LIKE ? OR username LIKE ? OR notes LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM credential_vault 
                     WHERE (user_id IS NULL OR user_id = ?) 
                     $whereClause 
                     ORDER BY system_name ASC");
$stmt->execute(array_merge([$_SESSION['user_id']], $params));
$credentials = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Credential Vault</h1>
        <p class="text-slate-500 mt-2">Securely manage system access. All passwords are encrypted.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                </path>
            </svg>
            Add Credential
        </a>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">
            <?php echo count($credentials); ?> Secured Items
        </div>

        <form action="" method="GET" id="credentialSearchForm" data-live-search
            class="flex items-center w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" placeholder="Search systems..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-full focus:ring-primary-500 focus:border-primary-500 transition-shadow">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">System
                        Name</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Username</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Password</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">URL /
                        Notes</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($credentials as $cred): ?>
                    <tr class="hover:bg-slate-50 transition-colors group" x-data="{ revealed: false, password: '' }">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-8 w-8 rounded bg-primary-50 flex items-center justify-center text-primary-600 mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="text-sm font-medium text-slate-900 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($cred['system_name']); ?>
                                </div>
                                <?php if ($cred['user_id']): ?>
                                    <span
                                        class="ml-2 px-1.5 py-0.5 bg-rose-50 text-rose-600 text-[10px] font-bold uppercase rounded border border-rose-100 flex items-center">
                                        <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 1a5 5 0 015 5v3h1a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2v-9a2 2 0 012-2h1V6a5 5 0 015-5zm0 13a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM12 3a3 3 0 00-3 3v3h6V6a3 3 0 00-3-3z" />
                                        </svg>
                                        Personal
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <div
                                class="flex items-center bg-slate-50 rounded-md px-2 py-1 max-w-max border border-slate-100">
                                <span class="font-mono text-xs"><?php echo htmlspecialchars($cred['username']); ?></span>
                                <button class="ml-2 text-slate-400 hover:text-primary-600 transition-colors"
                                    onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($cred['username']); ?>')"
                                    title="Copy Username">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <div class="flex items-center">
                                <span x-show="!revealed"
                                    class="font-mono text-slate-400 text-xs tracking-widest">•••••••••••••</span>
                                <span x-show="revealed" x-text="password"
                                    class="font-mono text-primary-700 bg-primary-50 px-2 py-0.5 rounded text-xs border border-primary-100 shadow-sm"></span>

                                <button @click="if(!revealed) { 
                                        fetch('reveal.php?id=<?php echo $cred['id']; ?>')
                                            .then(r => r.json())
                                            .then(data => { 
                                                if(data.password) {
                                                    password = data.password; 
                                                    revealed = true;
                                                    setTimeout(() => revealed = false, 10000); // Auto-hide after 10s
                                                }
                                            });
                                    } else {
                                        revealed = false;
                                    }"
                                    class="ml-2 text-slate-400 hover:text-primary-600 focus:outline-none transition-colors p-1 rounded-full hover:bg-slate-100"
                                    :title="revealed ? 'Hide' : 'Reveal'">
                                    <svg x-show="!revealed" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    <svg x-show="revealed" x-cloak class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21">
                                        </path>
                                    </svg>
                                </button>
                                <button x-show="revealed" style="display: none;"
                                    class="ml-1 text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-full hover:bg-slate-100"
                                    @click="navigator.clipboard.writeText(password)" title="Copy Password">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php if ($cred['url']): ?>
                                <a href="<?php echo htmlspecialchars($cred['url']); ?>" target="_blank"
                                    class="text-primary-600 hover:text-primary-700 hover:underline flex items-center mb-1 text-xs">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                        </path>
                                    </svg>
                                    Launch
                                </a>
                            <?php endif; ?>
                            <?php if ($cred['notes']): ?>
                                <div class="text-xs text-slate-400">
                                    <?php echo htmlspecialchars($cred['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="/ict/modules/credentials/edit.php?id=<?php echo $cred['id']; ?>"
                                    class="text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-md hover:bg-primary-50"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </a>
                                <form method="POST" action="" id="delete-cred-<?php echo $cred['id']; ?>"
                                    class="inline-block">
                                    <input type="hidden" name="delete_id" value="<?php echo $cred['id']; ?>">
                                    <button type="button"
                                        @click="$store.modal.trigger('delete-cred-<?php echo $cred['id']; ?>', 'Are you sure you want to delete this credential? This action is permanent and highly sensitive.', 'Delete Credential')"
                                        class="text-slate-400 hover:text-red-600 transition-colors p-1 rounded-md hover:bg-red-50"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($credentials) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-lg font-medium text-slate-700">Vault is empty</span>
                                <p class="text-slate-500 text-sm mt-1 mb-4">Securely store your system credentials here.</p>
                                <a href="create.php"
                                    class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    Add Credential
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>