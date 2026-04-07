<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
$doc = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM sop_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
}

$pageTitle = ($id ? "Edit Document" : "Add Policy/SOP");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $content = $_POST['content'] ?? '';
        $version = $_POST['version'] ?? '1.0';
        $author = $_POST['author'] ?? '';
        $visibility = $_POST['visibility'] ?? 'public';

        // Validation
        if (empty($title) || empty($category) || empty($content)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? '?id=' . $id : ''));
            exit;
        }

        // Auto-versioning logic
        if ($id && $doc) {
            // Only increment if title or content has changed
            if ($title !== $doc['title'] || $content !== $doc['content']) {
                if (is_numeric($version)) {
                    $version = number_format((float) $version + 0.1, 1);
                }
            }
        }

        $image_path = $doc['image_path'] ?? null;

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_file_name = uniqid('doc_', true) . '.' . $file_ext;
                $upload_dir = '../../uploads/documentation/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    // Delete old image if it exists
                    if ($image_path && file_exists('../../' . $image_path)) {
                        unlink('../../' . $image_path);
                    }
                    $image_path = 'uploads/documentation/' . $new_file_name;
                }
            }
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE sop_documents SET title = ?, category = ?, content = ?, version = ?, author = ?, image_path = ?, visibility = ? WHERE id = ?");
            $stmt->execute([$title, $category, $content, $version, $author, $image_path, $visibility, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO sop_documents (title, category, content, version, author, image_path, visibility) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $category, $content, $version, $author, $image_path, $visibility]);
        }
        $_SESSION['success'] = "Documentation updated successfully!";
        if ($id && isset($_GET['return']) && $_GET['return'] === 'department') {
            header("Location: /ict/modules/department/index.php?success=Responsibilities Updated");
        } else {
            header("Location: /ict/modules/policies");
        }
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving document: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? '?id=' . $id : ''));
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="max-w-4xl mx-auto fade-in-up">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">
            <?php echo $id ? "Edit Documentation" : "New Documentation"; ?>
        </h1>
        <a href="index.php" class="text-slate-400 hover:text-slate-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="saas-card p-8">
        <form id="sopForm" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Document
                        Title</label>
                    <input type="text" name="title" required placeholder="e.g. WiFi Password Rotation Policy"
                        value="<?php echo $doc ? htmlspecialchars($doc['title']) : ''; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm font-bold">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                    <input type="text" name="category" required placeholder="e.g. Security, Network, User Support"
                        value="<?php echo $doc ? htmlspecialchars($doc['category']) : ''; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm list-none"
                        list="category_list">
                    <datalist id="category_list">
                        <option value="Network">
                        <option value="Security">
                        <option value="Hardware">
                        <option value="Policy">
                        <option value="SOP">
                        <option value="Software Installation">
                        <option value="Software Documentation">
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Version</label>
                    <input type="text" name="version" required placeholder="e.g. 1.0"
                        value="<?php echo $doc ? htmlspecialchars($doc['version']) : '1.0'; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Author</label>
                    <input type="text" name="author" required placeholder="e.g. IT Manager"
                        value="<?php echo $doc ? htmlspecialchars($doc['author']) : $_SESSION['username']; ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Visibility</label>
                    <select name="visibility"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none text-sm bg-white">
                        <option value="public" <?php echo ($doc && $doc['visibility'] == 'public') ? 'selected' : ''; ?>>
                            Public (Show in Portal)</option>
                        <option value="private" <?php echo ($doc && $doc['visibility'] == 'private') ? 'selected' : ''; ?>>Private (IT Internal)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Reference
                    Image</label>
                <input type="file" name="image" accept="image/*"
                    class="w-full px-4 py-1.5 border border-slate-200 rounded-lg text-xs file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <?php if ($doc && $doc['image_path']): ?>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-tight">Current
                            Image:</span>
                        <img src="/ict/<?php echo $doc['image_path']; ?>"
                            class="h-8 w-8 object-cover rounded border border-slate-200">
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Document
                    Content</label>
                <div id="content-editor"
                    style="height: 400px; background: white; border: 1px solid #e2e8f0; border-radius: 0.5rem;"></div>
                <textarea name="content" id="content-hidden"
                    style="display:none;"><?php echo $doc ? htmlspecialchars($doc['content']) : ''; ?></textarea>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-50">
                <a href="index.php"
                    class="text-sm font-medium text-slate-400 hover:text-slate-600 transition-colors">Discard</a>
                <button type="submit"
                    class="px-8 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all hover:scale-105">
                    <?php echo $id ? "Save Changes" : "Publish Document"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Initialize Quill editor
    var quill = new Quill('#content-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Write your document content here...\n\nYou can paste formatted text from Word, Google Docs, or any website and the formatting will be preserved!'
    });

    // Load existing content if editing
    var existingContent = document.getElementById('content-hidden').value;
    if (existingContent) {
        quill.root.innerHTML = existingContent;
    }

    // Update hidden textarea before form submit
    var form = document.getElementById('sopForm');
    form.addEventListener('submit', function (e) {
        // Get content from Quill
        var content = quill.root.innerHTML;
        document.getElementById('content-hidden').value = content;

        // Check if content is empty
        var text = quill.getText().trim();
        if (text.length === 0) {
            alert('Please enter some content in the document editor.');
            e.preventDefault();
            return false;
        }

        // Allow form to submit
        return true;
    });
</script>

<?php include '../../includes/footer.php'; ?>