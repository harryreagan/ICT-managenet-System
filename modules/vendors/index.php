<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Vendor Directory";

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $vendorId = $_POST['delete_id'];

    try {
        $pdo->beginTransaction();

        // 1. Delete associated renewals (Cascade)
        $stmt = $pdo->prepare("DELETE FROM renewals WHERE vendor_id = ?");
        $stmt->execute([$vendorId]);

        // 2. Unlink from Ticets (Set NULL)
        // Check if table column exists first to be safe, or just try update
        // Assuming table is `troubleshooting_logs`
        $stmt = $pdo->prepare("UPDATE troubleshooting_logs SET vendor_id = NULL WHERE vendor_id = ?");
        $stmt->execute([$vendorId]);

        // 3. Unlink from Procurement (Set NULL)
        $stmt = $pdo->prepare("UPDATE procurement_requests SET vendor_id = NULL WHERE vendor_id = ?");
        $stmt->execute([$vendorId]);

        // 4. Delete Vendor
        $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
        $stmt->execute([$vendorId]);

        $pdo->commit();
        redirect($_SERVER['REQUEST_URI']);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting vendor: " . $e->getMessage();
    }
}

// Search
$search = $_GET['search'] ?? '';
$whereClause = "";
$params = [];

if ($search) {
    $whereClause = "WHERE name LIKE ? OR service_type LIKE ? OR contact_person LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM vendors $whereClause ORDER BY name ASC");
$stmt->execute($params);
$vendors = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Vendor Directory</h1>
        <p class="text-slate-500 mt-2">Manage external partners and service providers.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Vendor
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm mb-6 fade-in-up">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm"><?php echo $error; ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">
            <?php echo count($vendors); ?> Vendors
        </div>

        <form action="" method="GET" id="vendorSearchForm" data-live-search class="flex items-center w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" name="search" placeholder="Search vendors..."
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
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Service Type</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Contact Person</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Contacts</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <?php foreach ($vendors as $vendor): ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 mr-3 text-xs font-bold">
                                    <?php echo strtoupper(substr($vendor['name'], 0, 2)); ?>
                                </div>
                                <div
                                    class="text-sm font-medium text-slate-900 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($vendor['name']); ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                <?php echo htmlspecialchars($vendor['service_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            <?php if ($vendor['contact_person']): ?>
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-slate-400 mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($vendor['contact_person'] ?? '-'); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <div class="flex flex-col space-y-1">
                                <?php if ($vendor['phone']): ?>
                                    <div class="flex items-center text-xs text-slate-500">
                                        <svg class="w-3 h-3 mr-1.5 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                        <?php echo htmlspecialchars($vendor['phone']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($vendor['email']): ?>
                                    <div class="flex items-center text-xs text-slate-500">
                                        <svg class="w-3 h-3 mr-1.5 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <a href="mailto:<?php echo htmlspecialchars($vendor['email']); ?>"
                                            class="hover:text-primary-600 hover:underline"><?php echo htmlspecialchars($vendor['email']); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="/ict/modules/vendors/edit.php?id=<?php echo $vendor['id']; ?>"
                                    class="text-slate-400 hover:text-primary-600 transition-colors p-1 rounded-md hover:bg-primary-50"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </a>
                                <form method="POST" action="" id="delete-vendor-<?php echo $vendor['id']; ?>"
                                    class="inline-block">
                                    <input type="hidden" name="delete_id" value="<?php echo $vendor['id']; ?>">
                                    <button type="button"
                                        @click="$store.modal.trigger('delete-vendor-<?php echo $vendor['id']; ?>', 'Are you sure you want to delete this vendor? They may have associated records.', 'Delete Vendor')"
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
                <?php if (count($vendors) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-lg font-medium text-slate-700">No vendors found</span>
                                <p class="text-slate-500 text-sm mt-1 mb-4">Add your first vendor to start tracking
                                    services.</p>
                                <a href="create.php"
                                    class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    Add Vendor
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