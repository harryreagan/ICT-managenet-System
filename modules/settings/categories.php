<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();
requireAdmin();

$pageTitle = "Category Management";

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $color = $_POST['color'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, type = ?, description = ?, color = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $type, $description, $color, $is_active, $id]);
            $_SESSION['success'] = "Category updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type, description, color, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $description, $color, $is_active]);
            $_SESSION['success'] = "Category created successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: categories.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = "Category deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Cannot delete category: it may be in use.";
    }
    header("Location: categories.php");
    exit;
}

// Fetch all categories
$type_filter = $_GET['type'] ?? '';
if ($type_filter) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? ORDER BY type, name");
    $stmt->execute([$type_filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY type, name");
}
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Category Management</h1>
        <p class="text-slate-500 mt-2">Manage categories for documentation, tickets, and assets.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Category Form -->
    <div class="lg:col-span-1">
        <div class="saas-card p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">
                <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
            </h2>

            <form method="POST" class="space-y-4">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Name</label>
                    <input type="text" name="name" required
                        value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Type</label>
                    <select name="type" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="documentation" <?php echo ($edit_category && $edit_category['type'] == 'documentation') ? 'selected' : ''; ?>>Documentation</option>
                        <option value="ticket" <?php echo ($edit_category && $edit_category['type'] == 'ticket') ? 'selected' : ''; ?>>Ticket</option>
                        <option value="inventory" <?php echo ($edit_category && $edit_category['type'] == 'inventory') ? 'selected' : ''; ?>>Inventory</option>
                        <option value="hardware" <?php echo ($edit_category && $edit_category['type'] == 'hardware') ? 'selected' : ''; ?>>Hardware</option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Color</label>
                    <input type="color" name="color"
                        value="<?php echo $edit_category ? $edit_category['color'] : '#6B7280'; ?>"
                        class="w-full h-10 border border-slate-200 rounded-lg cursor-pointer">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" <?php echo (!$edit_category || $edit_category['is_active']) ? 'checked' : ''; ?>
                    class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                    <label for="is_active" class="ml-2 text-sm text-slate-700">Active</label>
                </div>

                <div class="flex gap-2 pt-4">
                    <?php if ($edit_category): ?>
                        <a href="categories.php"
                            class="flex-1 px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium text-center hover:bg-slate-200 transition-colors">Cancel</a>
                    <?php endif; ?>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm font-medium transition-colors">
                        <?php echo $edit_category ? 'Update' : 'Create'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="lg:col-span-2">
        <div class="saas-card">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-800">Categories</h2>
                <div class="flex gap-2">
                    <a href="categories.php"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg <?php echo !$type_filter ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?> transition-colors">All</a>
                    <a href="?type=documentation"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg <?php echo $type_filter == 'documentation' ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?> transition-colors">Docs</a>
                    <a href="?type=ticket"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg <?php echo $type_filter == 'ticket' ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?> transition-colors">Tickets</a>
                    <a href="?type=inventory"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg <?php echo $type_filter == 'inventory' ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?> transition-colors">Inventory</a>
                    <a href="?type=hardware"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg <?php echo $type_filter == 'hardware' ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?> transition-colors">Hardware</a>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                <?php foreach ($categories as $cat): ?>
                    <div class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="w-4 h-4 rounded" style="background-color: <?php echo $cat['color']; ?>"></div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-800">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </h3>
                                    <span
                                        class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-bold rounded uppercase">
                                        <?php echo $cat['type']; ?>
                                    </span>
                                    <?php if (!$cat['is_active']): ?>
                                        <span
                                            class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-bold rounded">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cat['description']): ?>
                                    <p class="text-xs text-slate-500 mt-1">
                                        <?php echo htmlspecialchars($cat['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="?edit=<?php echo $cat['id']; ?>"
                                class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <a href="?delete=<?php echo $cat['id']; ?>"
                                class="p-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete"
                                onclick="return confirm('Are you sure you want to delete this category?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($categories)): ?>
                    <div class="p-12 text-center text-slate-500">
                        <p>No categories found. Create your first category above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>