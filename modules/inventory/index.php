<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "IT Inventory";

// Fetch items
$items = $pdo->query("SELECT * FROM inventory_items ORDER BY name ASC")->fetchAll();

include '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">IT Inventory</h1>
        <p class="text-slate-500 text-sm">Track consumables and spare assets</p>
    </div>
    <?php if (isAdmin() || $_SESSION['role'] === 'technician'): ?>
        <a href="create.php"
            class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Item
        </a>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="saas-card p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Items</p>
        <h3 class="text-2xl font-bold text-slate-800">
            <?php echo count($items); ?>
        </h3>
    </div>
    <div class="saas-card p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Low Stock Alerts</p>
        <h3 class="text-2xl font-bold text-amber-600">
            <?php
            echo count(array_filter($items, function ($i) {
                return $i['status'] === 'low_stock'; }));
            ?>
        </h3>
    </div>
    <div class="saas-card p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Out of Stock</p>
        <h3 class="text-2xl font-bold text-red-600">
            <?php
            echo count(array_filter($items, function ($i) {
                return $i['status'] === 'out_of_stock'; }));
            ?>
        </h3>
    </div>
</div>

<div class="saas-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item Name</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Category</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">In
                        Stock</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Unit
                        Price</th>
                    <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-700">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?php echo htmlspecialchars($item['category'] ?: 'Uncategorized'); ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-700">
                                <?php echo $item['stock_level']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $statusClasses = [
                                'in_stock' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'low_stock' => 'bg-amber-50 text-amber-600 border-amber-100',
                                'out_of_stock' => 'bg-red-50 text-red-600 border-red-100'
                            ];
                            $statusLabels = [
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock'
                            ];
                            ?>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border <?php echo $statusClasses[$item['status']]; ?>">
                                <?php echo $statusLabels[$item['status']]; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-500 font-bold">
                            KES
                            <?php echo number_format($item['unit_price'], 2); ?>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div
                                class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if (isAdmin() || $_SESSION['role'] === 'technician'): ?>
                                    <a href="edit.php?id=<?php echo $item['id']; ?>"
                                        class="p-1.5 text-slate-400 hover:text-primary-600 transition-colors rounded-md hover:bg-primary-50"
                                        title="Edit/Restock">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg>
                                    </a>
                                    <form id="delete-form-<?php echo $item['id']; ?>" action="delete.php" method="POST"
                                        class="inline">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="button"
                                            @click="$store.modal.trigger('delete-form-<?php echo $item['id']; ?>', 'Remove this item from inventory?')"
                                            class="p-1.5 text-slate-400 hover:text-red-600 transition-colors rounded-md hover:bg-red-50"
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
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic text-sm">
                            No inventory items found. Add your first consumable or spare asset.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>