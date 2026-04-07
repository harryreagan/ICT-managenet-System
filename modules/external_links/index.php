<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
if (!hasRole(['admin', 'technician'])) {
    die("Access Denied: You do not have permission to view this page.");
}

$pageTitle = "External Systems";

// Handle Delete (Admins only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!isAdmin()) {
        die("Access Denied: Only administrators can delete systems.");
    }
    $stmt = $pdo->prepare("DELETE FROM external_links WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Fetch Links
// Fetch Links with Self-Healing
try {
    $stmt = $pdo->query("SELECT category, id, name, url, icon FROM external_links ORDER BY category, name");
    $links = $stmt->fetchAll(PDO::FETCH_GROUP);
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') { // Table not found
        $pdo->exec("CREATE TABLE IF NOT EXISTS external_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            url VARCHAR(255) NOT NULL,
            category ENUM('Network', 'Security', 'Vendor', 'Other') DEFAULT 'Other',
            icon VARCHAR(50) DEFAULT 'link',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("INSERT INTO external_links (name, url, category, icon) VALUES 
            ('Unifi Controller', 'https://unifi.ui.com', 'Network', 'wifi'),
            ('PBX Portal', 'http://172.16.1.10', 'Network', 'phone'),
            ('CCTV NVR', 'http://172.16.1.20', 'Security', 'video-camera')");

        header("Refresh:0"); // Reload page
        exit();
    }
    throw $e;
}

include '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">External Systems</h1>
        <p class="text-slate-500 text-sm">Manage links to external IT portals and tools</p>
    </div>
    <?php if (isAdmin()): ?>
        <a href="add.php"
            class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New System
        </a>
    <?php endif; ?>
</div>

<div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
    <?php foreach ($links as $category => $categoryLinks): ?>
        <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex items-center">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                <?php echo htmlspecialchars($category); ?>
            </h3>
            <span class="ml-2 px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[10px] font-bold">
                <?php echo count($categoryLinks); ?>
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            <?php foreach ($categoryLinks as $link): ?>
                <div class="group flex items-center justify-between p-4 hover:bg-slate-50 transition-all">
                    <div class="flex items-center flex-1 min-w-0 mr-4">
                        <div
                            class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center mr-4 group-hover:border-primary-200 group-hover:text-primary-600 shadow-sm transition-all">
                            <!-- Icon Logic -->
                            <?php
                            if (stripos($link['name'], 'wifi') !== false || $category == 'Network') {
                                echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>';
                            } elseif (stripos($link['name'], 'camera') !== false || $category == 'Security') {
                                echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>';
                            } else {
                                echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>';
                            }
                            ?>
                        </div>
                        <div class="overflow-hidden">
                            <h4
                                class="font-bold text-slate-800 text-sm group-hover:text-primary-600 transition-colors truncate">
                                <?php echo htmlspecialchars($link['name']); ?>
                            </h4>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"
                                class="text-xs text-slate-400 font-mono hover:underline hover:text-primary-500 truncate block">
                                <?php echo htmlspecialchars($link['url']); ?>
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"
                            class="p-2 text-slate-400 hover:text-primary-600 hover:bg-white rounded-lg transition-all"
                            title="Open Link">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="edit.php?id=<?php echo $link['id']; ?>"
                                class="p-2 text-slate-400 hover:text-amber-600 hover:bg-white rounded-lg transition-all"
                                title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete this link?');" class="inline-block">
                                <input type="hidden" name="delete_id" value="<?php echo $link['id']; ?>">
                                <button type="submit"
                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-white rounded-lg transition-all"
                                    title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../../includes/footer.php'; ?>