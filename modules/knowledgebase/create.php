<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$error = '';

// Pre-populate if asset_id is provided from QR/Inventory
$prefill_system = '';
$prefill_title = '';
$asset_id = $_GET['asset_id'] ?? null;

if ($asset_id) {
    try {
        $stmt = $pdo->prepare("SELECT name, category, serial_number FROM hardware_assets WHERE id = ?");
        $stmt->execute([$asset_id]);
        $asset = $stmt->fetch();
        if ($asset) {
            $prefill_system = $asset['category'] . ": " . $asset['name'];
            $prefill_title = "Issue with " . $asset['name'] . " (" . $asset['serial_number'] . ")";
        }
    } catch (PDOException $e) {
        $error = "Warning: Could not fetch asset details.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $system_affected = sanitize($_POST['system_affected']);
    $priority = sanitize($_POST['priority']);
    $status = 'open'; // Default status
    $visibility = sanitize($_POST['visibility'] ?? 'public');
    $symptoms = $_POST['symptoms']; // Don't sanitize rich text from Quill here if we want to keep HTML
    $technician_name = sanitize($_POST['technician_name']);
    $assigned_to = sanitize($_POST['assigned_to']);
    $incident_date = date('Y-m-d'); // Today

    if (empty($title) || empty($system_affected)) {
        $error = "Title and System Affected are required.";
    } else {
        try {
            $image_path = null;
            if (isset($_FILES['solution_image']) && $_FILES['solution_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/kb/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_extension = pathinfo($_FILES['solution_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('kb_') . '.' . $file_extension;
                $image_path = 'uploads/kb/' . $file_name;
                move_uploaded_file($_FILES['solution_image']['tmp_name'], $upload_dir . $file_name);
            }

            $stmt = $pdo->prepare("INSERT INTO troubleshooting_logs (title, system_affected, priority, status, visibility, symptoms, technician_name, assigned_to, incident_date, solution_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $system_affected, $priority, $status, $visibility, $symptoms, $technician_name, $assigned_to, $incident_date, $image_path]);
            $issue_id = $pdo->lastInsertId();

            // Create Notification for IT Team
            $notif_msg = "New incident logged by " . $_SESSION['username'] . ": " . $title;
            createNotification($pdo, $notif_msg, 'info', "/ict/modules/knowledgebase/view.php?id=" . $issue_id, 'admin');

            // Send Email to ICT Team
            $email_body = "A new ticket has been created manually in the Admin module.\n\n";
            $email_body .= "Title: $title\n";
            $email_body .= "Priority: $priority\n";
            $email_body .= "Created By: " . $_SESSION['username'] . "\n\n";
            $email_body .= "View Ticket: " . BASE_URL . "/modules/knowledgebase/view.php?id=$issue_id";
            sendICTEmail("Admin Ticket Created: $title", $email_body);
            $notif_msg = "New incident logged: " . $title;
            createNotification($pdo, $notif_msg, 'info', "/ict/modules/knowledgebase/view.php?id=" . $issue_id, 'admin');

            // Log Action
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], 'CREATE_TICKET', "Created Ticket: $title ($visibility)"]);

            redirect('/ict/modules/knowledgebase/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<div class="space-y-8 animate-blur-in">
    <!-- Breadcrumbs/Back -->
    <a href="index.php"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Back to List
    </a>

    <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">New Incident Report</h1>
            <p class="text-slate-500 mt-2">Submit a new incident to track system issues and document resolutions.</p>
        </div>
    </header>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-8 md:p-12">
            <?php if ($error): ?>
                <div
                    class="bg-red-50 text-red-700 px-6 py-4 rounded-xl flex items-center space-x-3 mb-8 border border-red-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-bold tracking-tight"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="ticketForm" class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                    <!-- Title -->
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                            for="title">
                            Issue Title
                        </label>
                        <input type="text" name="title" id="title" placeholder="Brief summary of the issue..." required
                            value="<?php echo htmlspecialchars($prefill_title); ?>"
                            class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none text-sm font-medium text-slate-900 transition-all placeholder:text-slate-400">
                    </div>

                    <!-- System Affected -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                            for="system_affected">
                            System Affected
                        </label>
                        <input type="text" name="system_affected" id="system_affected"
                            placeholder="e.g., POS, WiFi, Phone System" required
                            value="<?php echo htmlspecialchars($prefill_system); ?>"
                            class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none text-sm font-medium text-slate-900 transition-all placeholder:text-slate-400">
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                            for="priority">
                            Priority Level
                        </label>
                        <select name="priority" id="priority"
                            class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none text-sm font-bold text-slate-900 transition-all appearance-none cursor-pointer">
                            <option value="low">Low - Minor Request</option>
                            <option value="medium" selected>Medium - Normal Issue</option>
                            <option value="high">High - Serious Problem</option>
                            <option value="critical">Critical - System Offline</option>
                        </select>
                    </div>

                    <!-- Visibility -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                            for="visibility">
                            Visibility
                        </label>
                        <select name="visibility" id="visibility"
                            class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none text-sm font-bold text-slate-900 transition-all appearance-none cursor-pointer">
                            <option value="public" selected>Public (Everyone)</option>
                            <option value="internal">Internal (IT Only)</option>
                        </select>
                    </div>

                    <!-- Assigned To -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                            for="assigned_to">
                            Assign To
                        </label>
                        <select name="assigned_to" id="assigned_to"
                            class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none text-sm font-bold text-slate-900 transition-all appearance-none cursor-pointer">
                            <option value="">Unassigned</option>
                            <option value="tech_support">Tech Support</option>
                            <option value="manager">IT Manager</option>
                            <option value="admin" selected>Administrator</option>
                        </select>
                    </div>

                    <!-- Symptoms (Rich Text) -->
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                            Symptoms & Description
                        </label>
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <div id="editor-container" style="height: 350px; border: none;" class="bg-white"></div>
                        </div>
                        <input type="hidden" name="symptoms" id="symptoms_input">
                    </div>

                    <!-- Solution Image -->
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                            Attachment (Optional)
                        </label>
                        <div class="relative group">
                            <input id="solution_image" name="solution_image" type="file" class="sr-only"
                                accept="image/*">
                            <label for="solution_image"
                                class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-slate-100 hover:border-primary-400 transition-all cursor-pointer">
                                <div
                                    class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 mb-3 group-hover:text-primary-500 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-600">Click to upload screenshot</span>
                                <span class="text-[10px] text-slate-400 mt-1 uppercase tracking-tight">PNG, JPG up to
                                    5MB</span>
                            </label>
                        </div>

                        <div id="image_preview" class="mt-6 hidden">
                            <div
                                class="max-w-md p-2 bg-white rounded-xl border border-slate-200 shadow-lg relative inline-block group">
                                <img src="" id="preview_img" class="rounded-lg max-h-64">
                                <button type="button"
                                    onclick="document.getElementById('image_preview').classList.add('hidden'); document.getElementById('solution_image').value='';"
                                    class="absolute -top-2 -right-2 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reporter Info (Readonly) -->
                    <div class="col-span-2">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <div
                                class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold">
                                <?php echo substr($_SESSION['username'] ?? '?', 0, 1); ?>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reporting
                                    Agent</span>
                                <span
                                    class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Anonymous'); ?></span>
                            </div>
                            <input type="hidden" name="technician_name"
                                value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold py-3.5 px-10 rounded-xl focus:outline-none focus:ring-4 focus:ring-primary-500/20 transition-all shadow-md active:scale-95">
                        Save Incident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Describe the problem, error messages, and what was witnessed...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'blockquote', 'code-block'],
                ['clean']
            ],
            clipboard: {
                matchVisual: false
            }
        }
    });

    // Populate hidden input before submit
    document.getElementById('ticketForm').onsubmit = function () {
        document.getElementById('symptoms_input').value = quill.root.innerHTML;
        return true;
    };

    // Image Preview
    document.getElementById('solution_image').onchange = function (evt) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('preview_img').src = e.target.result;
                document.getElementById('image_preview').classList.remove('hidden');
            };
            reader.readAsDataURL(this.files[0]);
        }
    };
</script>

<?php include '../../includes/footer.php'; ?>