<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/knowledgebase/index.php');

$stmt = $pdo->prepare("SELECT * FROM troubleshooting_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM issue_attachments WHERE issue_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();

if (!$log)
    redirect('/ict/modules/knowledgebase/index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $priority = sanitize($_POST['priority']);
    $status = sanitize($_POST['status']);
    $visibility = sanitize($_POST['visibility']);
    $assigned_to = sanitize($_POST['assigned_to']);

    $system_affected = sanitize($_POST['system_affected']);
    $symptoms = $_POST['symptoms'];
    $root_cause = $_POST['root_cause'];
    $steps_taken = $_POST['steps_taken'];
    $resolution = $_POST['resolution'];

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        try {
            $image_path = $log['solution_image'];
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

            $stmt = $pdo->prepare("UPDATE troubleshooting_logs SET title=?, system_affected=?, priority=?, status=?, visibility=?, assigned_to=?, symptoms=?, root_cause=?, steps_taken=?, resolution=?, solution_image=? WHERE id=?");
            $stmt->execute([$title, $system_affected, $priority, $status, $visibility, $assigned_to, $symptoms, $root_cause, $steps_taken, $resolution, $image_path, $id]);

            // Log Status Change
            if ($log['status'] !== $status) {
                // Legacy Audit Log
                $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'UPDATE_STATUS', "Ticket #$id status changed to $status"]);

                // Timeline Activity
                $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, 'status_changed', ?, ?, ?)");
                $description = "Status changed from " . ucfirst(str_replace('_', ' ', $log['status'])) . " to " . ucfirst(str_replace('_', ' ', $status));
                $activityStmt->execute([$id, $_SESSION['user_id'], $description, $log['status'], $status]);

                // Notify Requester on Resolution
                if ($status === 'resolved' && !empty($log['requester_username'])) {
                    // Find user ID for the requester
                    $userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $userStmt->execute([$log['requester_username']]);
                    $targetUser = $userStmt->fetch();

                    if ($targetUser) {
                        $notifMsg = "Your ticket #" . $id . " has been resolved: " . $title;
                        createNotification($pdo, $notifMsg, 'success', "/ict/portal/index.php", 'all', $targetUser['id']);
                    }
                }
            }

            // Log Priority Change
            if ($log['priority'] !== $priority) {
                $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, 'updated', ?, ?, ?)");
                $description = "Priority changed from " . ucfirst($log['priority']) . " to " . ucfirst($priority);
                $activityStmt->execute([$id, $_SESSION['user_id'], $description, $log['priority'], $priority]);
            }

            // Log Assignment Change
            if ($log['assigned_to'] !== $assigned_to) {
                // If assigned_to is not empty, it's an assignment. If changing to empty, it's unassigning.
                $type = $assigned_to ? 'assigned' : 'updated';
                $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)");

                $oldAssign = $log['assigned_to'] ? ucfirst(str_replace('_', ' ', $log['assigned_to'])) : 'Unassigned';
                $newAssign = $assigned_to ? ucfirst(str_replace('_', ' ', $assigned_to)) : 'Unassigned';
                $description = "Assigned to " . $newAssign . " (was " . $oldAssign . ")";

                $activityStmt->execute([$id, $_SESSION['user_id'], $type, $description, $log['assigned_to'], $assigned_to]);
            }

            $_SESSION['success'] = "Ticket updated successfully!";
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

<div class="space-y-8">
    <div class="max-w-6xl mx-auto">
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <span
                    class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-1 rounded-full border border-slate-200 mb-4 inline-block">Issue
                    #<?php echo $id; ?></span>
                <h1 class="text-3xl font-bold text-slate-800">
                    Edit Issue
                </h1>
                <p class="text-slate-500 mt-2">Update the incident details and resolution information.</p>
            </div>
            <a href="index.php"
                class="inline-flex items-center justify-center px-6 py-2.5 bg-white text-slate-600 font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all shadow-sm text-sm">
                Back to List
            </a>
        </header>

        <form method="POST" action="" enctype="multipart/form-data" id="editTicketForm">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 md:p-10">
                        <?php if ($error): ?>
                            <div
                                class="bg-red-50 text-red-700 px-6 py-4 rounded-lg flex items-center gap-3 mb-6 border border-red-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="text-sm font-bold"><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-6">
                            <div class="group">
                                <label class="block text-sm font-bold text-slate-700 mb-2" for="title">Issue
                                    Title</label>
                                <input type="text" name="title" id="title"
                                    value="<?php echo htmlspecialchars($log['title']); ?>" required
                                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none text-sm text-slate-900 transition-all">
                            </div>

                            <div class="group">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Problem Description</label>
                                <div
                                    class="bg-white border border-gray-200 rounded-lg overflow-hidden focus-within:border-primary-500 transition-all">
                                    <div id="symptoms-editor" style="height: 300px; border: none;"></div>
                                </div>
                                <input type="hidden" name="symptoms" id="symptoms_input">
                            </div>

                            <div class="pt-8 border-t border-gray-200">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-lg font-bold text-slate-800">Resolution Details</h2>
                                    <span
                                        class="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full border border-emerald-100">Solution
                                        Info</span>
                                </div>

                                <div class="space-y-6">
                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">What Caused
                                            It</label>
                                        <div
                                            class="bg-white border border-gray-200 rounded-lg overflow-hidden focus-within:border-primary-500 transition-all">
                                            <div id="root_cause-editor" style="height: 200px; border: none;"></div>
                                        </div>
                                        <input type="hidden" name="root_cause" id="root_cause_input">
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Steps Taken</label>
                                        <div
                                            class="bg-white border border-gray-200 rounded-lg overflow-hidden focus-within:border-primary-500 transition-all">
                                            <div id="steps_taken-editor" style="height: 250px; border: none;"></div>
                                        </div>
                                        <input type="hidden" name="steps_taken" id="steps_taken_input">
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-emerald-700 mb-2">How It Was
                                            Fixed</label>
                                        <div
                                            class="bg-emerald-50 border border-emerald-200 rounded-lg overflow-hidden focus-within:border-emerald-500 transition-all">
                                            <div id="resolution-editor" style="height: 200px; border: none;"></div>
                                        </div>
                                        <input type="hidden" name="resolution" id="resolution_input">
                                    </div>

                                    <div class="pt-8 mt-8 border-t border-gray-200">
                                        <label class="block text-sm font-bold text-slate-700 mb-4">Attachments</label>

                                        <!-- Attachment List -->
                                        <div id="attachmentList" class="space-y-2 mb-4">
                                            <?php foreach ($attachments as $att):
                                                $icon = match ($att['file_type']) {
                                                    'application/pdf' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>',
                                                    'image/jpeg', 'image/png', 'image/gif' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>',
                                                    default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'
                                                };
                                                ?>
                                                <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg group"
                                                    id="att-<?php echo $att['id']; ?>">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-500">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <?php echo $icon; ?>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <a href="../../<?php echo $att['file_path']; ?>" target="_blank"
                                                                class="text-sm font-bold text-slate-700 hover:text-primary-600 hover:underline">
                                                                <?php echo htmlspecialchars($att['file_name']); ?>
                                                            </a>
                                                            <p class="text-xs text-slate-400">
                                                                <?php echo round($att['file_size'] / 1024, 1); ?> KB •
                                                                <?php echo date('M j, Y', strtotime($att['uploaded_at'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        onclick="deleteAttachment(<?php echo $att['id']; ?>)"
                                                        class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Upload Area -->
                                        <div id="dropzone"
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-primary-500 transition-all bg-gray-50 cursor-pointer relative">

                                            <!-- Loading Overlay -->
                                            <div id="uploadLoader"
                                                class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                                                <svg class="animate-spin h-8 w-8 text-primary-600"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>

                                            <div class="space-y-1 text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                                                    fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                    <path
                                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <div class="flex text-sm text-gray-600 justify-center">
                                                    <label for="file_attachments"
                                                        class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                                        <span>Upload files</span>
                                                        <input id="file_attachments" name="files[]" type="file"
                                                            class="sr-only" accept="image/*,.pdf,.doc,.docx" multiple
                                                            onchange="uploadFiles(this)">
                                                    </label>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Meta -->
                        <div class="space-y-6">
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 md:p-8">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-6">
                                    Issue Details
                                </h3>

                                <div class="space-y-6">
                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2"
                                            for="status">Status</label>
                                        <select name="status" id="status"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg outline-none text-sm font-medium text-slate-700 transition-all appearance-none cursor-pointer focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                            <option value="open" <?php echo $log['status'] === 'open' ? 'selected' : ''; ?>>
                                                Open</option>
                                            <option value="in_progress" <?php echo $log['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $log['status'] === 'resolved' ? 'selected' : ''; ?>>
                                                Resolved</option>
                                            <option value="closed" <?php echo $log['status'] === 'closed' ? 'selected' : ''; ?>>
                                                Closed</option>
                                        </select>
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2"
                                            for="visibility">Visibility</label>
                                        <select name="visibility" id="visibility"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg outline-none text-sm font-medium text-slate-700 transition-all appearance-none cursor-pointer focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                            <option value="public" <?php echo ($log['visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>
                                                Public (All Users)</option>
                                            <!-- Fallback for older records: treat empty as public or force update -->
                                            <option value="internal" <?php echo ($log['visibility'] ?? '') === 'internal' ? 'selected' : ''; ?>>
                                                Internal (IT Only)</option>
                                        </select>
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2"
                                            for="priority">Priority</label>
                                        <select name="priority" id="priority"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg outline-none text-sm font-medium text-slate-700 transition-all appearance-none cursor-pointer focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                            <option value="low" <?php echo $log['priority'] === 'low' ? 'selected' : ''; ?>>
                                                Low</option>
                                            <option value="medium" <?php echo $log['priority'] === 'medium' ? 'selected' : ''; ?>>
                                                Medium</option>
                                            <option value="high" <?php echo $log['priority'] === 'high' ? 'selected' : ''; ?>>
                                                High</option>
                                            <option value="critical" <?php echo $log['priority'] === 'critical' ? 'selected' : ''; ?>>
                                                Critical</option>
                                        </select>
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2"
                                            for="assigned_to">Assigned To</label>
                                        <select name="assigned_to" id="assigned_to"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg outline-none text-sm font-medium text-slate-700 transition-all appearance-none cursor-pointer focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                            <option value="">-- Not Assigned --</option>
                                            <option value="tech_support" <?php echo $log['assigned_to'] === 'tech_support' ? 'selected' : ''; ?>>Tech Support</option>
                                            <option value="manager" <?php echo $log['assigned_to'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                            <option value="admin" <?php echo $log['assigned_to'] === 'admin' ? 'selected' : ''; ?>>
                                                Administrator</option>
                                        </select>
                                    </div>

                                    <div class="group">
                                        <label class="block text-sm font-bold text-slate-700 mb-2"
                                            for="system_affected">System Affected</label>
                                        <input type="text" name="system_affected" id="system_affected"
                                            value="<?php echo htmlspecialchars($log['system_affected']); ?>"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg outline-none text-sm font-medium text-slate-700 transition-all focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                    </div>

                                    <button type="submit"
                                        class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500/20 transition-all shadow-sm">
                                        Save Changes
                                    </button>

                                    <div class="mt-6 pt-6 border-t border-slate-100 space-y-3">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-slate-400">Created</span>
                                            <span
                                                class="font-bold text-slate-600"><?php echo date('M j, Y', strtotime($log['created_at'])); ?></span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-slate-400">Technician</span>
                                            <span
                                                class="font-bold text-primary-600"><?php echo htmlspecialchars($log['technician_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
        </form>
    </div>
</div>
</div>

<script>
    // Initialize multiple Quill editors
    function initQuill(selector, placeholder, content) {
        var quill = new Quill(selector, {
            theme: 'snow',
            placeholder: placeholder,
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
        if (content) {
            quill.root.innerHTML = content;
        }
        return quill;
    }

    var symptomsQuill = initQuill('#symptoms-editor', 'Describe the issue...', <?php echo json_encode($log['symptoms']); ?>);
    var rootCauseQuill = initQuill('#root_cause-editor', 'What caused this?', <?php echo json_encode($log['root_cause']); ?>);
    var stepsTakenQuill = initQuill('#steps_taken-editor', 'What did you do?', <?php echo json_encode($log['steps_taken']); ?>);
    var resolutionQuill = initQuill('#resolution-editor', 'How was it fixed?', <?php echo json_encode($log['resolution']); ?>);

    // Sync content before submit
    document.getElementById('editTicketForm').onsubmit = function () {
        document.getElementById('symptoms_input').value = symptomsQuill.root.innerHTML;
        document.getElementById('root_cause_input').value = rootCauseQuill.root.innerHTML;
        document.getElementById('steps_taken_input').value = stepsTakenQuill.root.innerHTML;
        document.getElementById('resolution_input').value = resolutionQuill.root.innerHTML;
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

    // Attachment Upload
    function uploadFiles(input) {
        if (!input.files || input.files.length === 0) return;

        const formData = new FormData();
        formData.append('issue_id', <?php echo $id; ?>);

        for (let i = 0; i < input.files.length; i++) {
            formData.append('files[]', input.files[i]);
        }

        document.getElementById('uploadLoader').classList.remove('hidden');

        fetch('upload_attachment.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                document.getElementById('uploadLoader').classList.add('hidden');
                if (data.success) {
                    // Reload page to show new files (simplest way to catch all UI updates)
                    window.location.reload();
                } else {
                    alert('Upload failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById('uploadLoader').classList.add('hidden');
                console.error('Error:', error);
                alert('Upload failed');
            });
    }

    // Delete Attachment
    function deleteAttachment(id) {
        if (!confirm('Are you sure you want to delete this attachment?')) return;

        const formData = new FormData();
        formData.append('attachment_id', id);

        fetch('delete_attachment.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('att-' + id).remove();
                } else {
                    alert('Delete failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Delete failed');
            });
    }
</script>

<?php include '../../includes/footer.php'; ?>