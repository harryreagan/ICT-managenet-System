<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();
if (!isAdmin() && $_SESSION['role'] !== 'technician')
    redirect('index.php');

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item)
    redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $stock_level = $_POST['stock_level'];
    $reorder_threshold = $_POST['reorder_threshold'];
    $unit_price = $_POST['unit_price'];

    try {
        $stmt = $pdo->prepare("UPDATE inventory_items SET name = ?, category = ?, stock_level = ?, reorder_threshold = ?, unit_price = ?, last_restocked = CASE WHEN stock_level > ? THEN NOW() ELSE last_restocked END WHERE id = ?");
        $stmt->execute([$name, $category, $stock_level, $reorder_threshold, $unit_price, $item['stock_level'], $id]);

        $_SESSION['success'] = "Inventory item updated!";
        redirect('/ict/modules/inventory/index.php');
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Edit Item";
include '../../includes/header.php';
?>

<div class="mb-6">
    <a href="index.php"
        class="text-primary-600 hover:text-primary-700 font-bold text-xs uppercase tracking-widest flex items-center">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Inventory
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="saas-card p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6 text-center">Edit / Restock Item</h2>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="name">Item Name /
                        Description</label>
                    <input type="text" name="name" id="name" required
                        value="<?php echo htmlspecialchars($item['name']); ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="category">Category</label>
                    <select name="category" id="category" required
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none bg-white">
                        <option value="Consumables" <?php echo $item['category'] === 'Consumables' ? 'selected' : ''; ?>
                            >Consumables</option>
                        <option value="Networking" <?php echo $item['category'] === 'Networking' ? 'selected' : ''; ?>
                            >Networking</option>
                        <option value="Computer Parts" <?php echo $item['category'] === 'Computer Parts' ? 'selected' : ''; ?>>Computer Parts</option>
                        <option value="Peripherals" <?php echo $item['category'] === 'Peripherals' ? 'selected' : ''; ?>
                            >Peripherals</option>
                        <option value="Other" <?php echo $item['category'] === 'Other' ? 'selected' : ''; ?>>Other
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="unit_price">Unit Price (KES)</label>
                    <input type="number" step="0.01" name="unit_price" id="unit_price" required
                        value="<?php echo $item['unit_price']; ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="stock_level">Update Stock
                        Level</label>
                    <input type="number" name="stock_level" id="stock_level" required
                        value="<?php echo $item['stock_level']; ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="reorder_threshold">Reorder
                        Threshold</label>
                    <input type="number" name="reorder_threshold" id="reorder_threshold" required
                        value="<?php echo $item['reorder_threshold']; ?>"
                        class="shadow appearance-none border border-slate-200 rounded-lg w-full py-2.5 px-3 text-slate-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all outline-none">
                </div>
            </div>

            <div class="flex items-center justify-center space-x-4 pt-4">
                <a href="index.php"
                    class="text-slate-500 hover:text-slate-700 font-bold text-xs uppercase tracking-widest">Cancel</a>
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white px-10 py-3 rounded-lg text-sm font-bold uppercase tracking-wider shadow-lg shadow-primary-500/30 transition-all">
                    Update Item
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>