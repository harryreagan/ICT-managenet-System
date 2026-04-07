<?php
require_once __DIR__ . '/layout.php';

$success_msg = "";
$error_msg = "";
$category = $_GET['category'] ?? 'General';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $priority = sanitize($_POST['priority']);
    $location = sanitize($_POST['location']); // New field for context
    $system_affected = sanitize($_POST['category']); // Map category to system_affected
    $staff_name = sanitize($_POST['staff_name'] ?? '');
    $department = sanitize($_POST['department'] ?? '');

    if (empty($title) || empty($description) || empty($staff_name) || empty($department)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        // Insert into troubleshooting_logs
        // Note: Using 'technician_name' strictly for Technician assignment, so we'll put the Requester Name in the description or a new field if available.
        // For now, we prepend "REQUESTER: [Name]" to the description to track who asked.
        $full_description = "REQUESTER: $staff_name ($department)\nLOCATION: $location\n\n" . $description;

        $stmt = $pdo->prepare("INSERT INTO troubleshooting_logs (title, requester_username, staff_name, department, symptoms, priority, system_affected, status, incident_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())");

        if ($stmt->execute([$title, $_SESSION['username'], $staff_name, $department, $full_description, $priority, $system_affected])) {
            $issue_id = $pdo->lastInsertId();

            // Create Notification for IT Admins
            $notif_msg = "New ticket from " . $_SESSION['username'] . ": " . $title . " (" . $priority . ")";
            createNotification($pdo, $notif_msg, 'info', "/ict/modules/knowledgebase/view.php?id=" . $issue_id, 'admin');

            // Send Email to ICT Team
            $email_body = "A new ticket has been submitted via the Portal.\n\n";
            $email_body .= "Title: $title\n";
            $email_body .= "Priority: $priority\n";
            $email_body .= "Requester: " . $_SESSION['username'] . "\n";
            $email_body .= "Details: $description\n\n";
            $email_body .= "View Ticket: " . BASE_URL . "/modules/knowledgebase/view.php?id=$issue_id";
            sendICTEmail("New Ticket: $title", $email_body);

            // Handle File Uploads
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = '../uploads/kb/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $max_file_size = 10 * 1024 * 1024; // 10MB

                $file_count = count($_FILES['attachments']['name']);

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['attachments']['name'][$i];
                        $file_tmp = $_FILES['attachments']['tmp_name'][$i];
                        $file_size = $_FILES['attachments']['size'][$i];
                        $file_type = $_FILES['attachments']['type'][$i];

                        if (!in_array($file_type, $allowed_types) || $file_size > $max_file_size) {
                            continue; // Skip invalid files
                        }

                        $unique_name = uniqid('kb_' . $issue_id . '_') . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
                        $file_path = 'uploads/kb/' . $unique_name;

                        if (move_uploaded_file($file_tmp, $upload_dir . $unique_name)) {
                            // Determine user ID (we only have username in session for portal, need to fetch ID or use 0/default)
                            // Assuming portal users are in `users` table? 
                            // If `requester_username` is used, maybe they are not in `users` table as IDs?
                            // Let's check if we have a user_id in session.
                            $uploader_id = $_SESSION['user_id'] ?? 0;
                            // If 0, it might violate FK if `uploaded_by` refers to `users`. 
                            // Let's check `users` table. If portal users are not real users, we might need a dummy user or allow NULL.
                            // The schema says `uploaded_by` REFERENCES `users(id)`.
                            // I'll grab the first admin or a system user if none exists.
                            // Or better, fetch the user ID based on username if it exists.

                            $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $uStmt->execute([$_SESSION['username']]);
                            $u = $uStmt->fetch();
                            $uploader_id = $u ? $u['id'] : 1; // Fallback to admin (ID 1) if not found, to satisfy FK.

                            $attStmt = $pdo->prepare("INSERT INTO issue_attachments (issue_id, file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                            $attStmt->execute([$issue_id, $file_name, $file_path, $file_type, $file_size, $uploader_id]);

                            // Log activity
                            $actStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'attachment_added', ?)");
                            $actStmt->execute([$issue_id, $uploader_id, "User uploaded: $file_name"]);
                        }
                    }
                }
            }

            $success_msg = "Ticket submitted successfully! IT team has been notified.";
        } else {
            $error_msg = "Failed to submit ticket. Please try again.";
        }
    }
}

renderPortalHeader("Submit Ticket");
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php" class="text-sm text-slate-500 hover:text-primary-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-primary-100 overflow-hidden">
        <div class="bg-primary-50 px-6 py-4 border-b border-primary-100">
            <h1 class="text-xl font-bold text-primary-900">New Support Ticket</h1>
            <p class="text-sm text-primary-700/80 mt-1">Please describe your issue below.</p>
        </div>

        <div class="p-6">
            <?php if ($success_msg): ?>
                <div class="bg-emerald-50 text-emerald-700 p-4 rounded-lg mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <?= $success_msg ?>
                </div>
                <div class="text-center">
                    <a href="index.php"
                        class="inline-block bg-primary-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-primary-700 transition">Return
                        Home</a>
                </div>
            <?php else: ?>

                <?php if ($error_msg): ?>
                    <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <!-- AI Suggestion Container -->
                <div id="aiSuggestionContainer" class="hidden mb-6 animate-vibrant-pop">
                    <div class="glass-luxury rounded-xl border-l-4 border-l-primary-500 overflow-hidden">
                        <div
                            class="px-4 py-2 bg-primary-50/50 flex items-center justify-between border-b border-primary-100">
                            <div class="flex items-center gap-2">
                                <span class="flex h-2 w-2 rounded-full bg-primary-500 animate-pulse"></span>
                                <span class="text-[10px] font-black uppercase tracking-widest text-primary-700">AI Instant
                                    Recommendations</span>
                            </div>
                            <button type="button" onclick="this.closest('#aiSuggestionContainer').classList.add('hidden')"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="suggestionList" class="divide-y divide-primary-100 max-h-[300px] overflow-y-auto">
                            <!-- Suggestions injected here -->
                        </div>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-5" enctype="multipart/form-data" id="ticketForm">

                    <!-- File Upload Section -->
                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Attachments / Screenshots <span
                                class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 hover:bg-gray-100 transition-colors text-center cursor-pointer relative">
                            <input type="file" name="attachments[]" multiple
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                onchange="updateFileList(this)">

                            <div class="space-y-1 pointer-events-none">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium text-primary-600">Click to upload</span> or drag and drop
                                </div>
                                <p class="text-xs text-slate-500">PNG, JPG, PDF up to 10MB</p>
                            </div>
                        </div>
                        <div id="fileList" class="mt-2 text-xs text-slate-600 space-y-1"></div>
                        <script>
                            function updateFileList(input) {
                                const list = document.getElementById('fileList');
                                list.innerHTML = '';
                                if (input.files.length > 0) {
                                    for (let i = 0; i < input.files.length; i++) {
                                        list.innerHTML += '<div class="flex items-center gap-1"><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>' + input.files[i].name + '</div>';
                                    }
                                }
                            }
                        </script>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Your Name *</label>
                            <input type="text" name="staff_name" required
                                value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''); ?>"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Department *</label>
                            <input type="text" name="department" required
                                value="<?php echo htmlspecialchars($_SESSION['department'] ?? ''); ?>"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Category</label>
                            <select name="category"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="WiFi" <?= $category == 'WiFi' ? 'selected' : '' ?>>WiFi / Internet</option>
                                <option value="Printer" <?= $category == 'Printer' ? 'selected' : '' ?>>Printer / Scanner
                                </option>
                                <option value="Hardware" <?= $category == 'Hardware' ? 'selected' : '' ?>>Computer Hardware
                                </option>
                                <option value="Software" <?= $category == 'Software' ? 'selected' : '' ?>>Software / App
                                </option>
                                <option value="Email" <?= $category == 'Email' ? 'selected' : '' ?>>Email / Account</option>
                                <option value="TV" <?= $category == 'TV' ? 'selected' : '' ?>>TV / Remote</option>
                                <option value="Phone" <?= $category == 'Phone' ? 'selected' : '' ?>>Telephone</option>
                                <option value="POS" <?= $category == 'POS' ? 'selected' : '' ?>>Workstation POS (Point of Sale)
                                </option>
                                <option value="KeyCard" <?= $category == 'KeyCard' ? 'selected' : '' ?>>Key Card / Door Lock
                                </option>
                                <option value="Power" <?= $category == 'Power' ? 'selected' : '' ?>>Power / Electricity
                                </option>
                                <option value="KOT" <?= $category == 'KOT' ? 'selected' : '' ?>>KOT Printer / System</option>
                                <option value="Oracle" <?= $category == 'Oracle' ? 'selected' : '' ?>>Oracle Material Control
                                </option>
                                <option value="PublicAddress" <?= $category == 'PublicAddress' ? 'selected' : '' ?>>Public
                                    Address / Music / Mic
                                </option>
                                <option value="Projector" <?= $category == 'Projector' ? 'selected' : '' ?>>Projector / Screen
                                </option>
                                <option value="CCTV" <?= $category == 'CCTV' ? 'selected' : '' ?>>CCTV / Surveillance</option>
                                <option value="NameTag" <?= $category == 'NameTag' ? 'selected' : '' ?>>Name Tag Issue</option>
                                <option value="StaffID" <?= $category == 'StaffID' ? 'selected' : '' ?>>Staff ID Inquiry
                                </option>
                                <option value="Gym" <?= $category == 'Gym' ? 'selected' : '' ?>>Gym Equipment</option>
                                <option value="Other" <?= $category == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Urgency</label>
                            <select name="priority"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="low">Low - Can wait a bit</option>
                                <option value="medium" selected>Medium - Affects work</option>
                                <option value="high">High - Cannot work</option>
                                <option value="critical">Critical - System Down</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Issue Title</label>
                        <input type="text" name="title" placeholder="e.g. Printer in Reception is jammed"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Location / Room Number</label>
                        <input type="text" name="location" placeholder="e.g. Front Desk, Room 305, Kitchen Office"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Description</label>
                        <textarea name="description" rows="4" placeholder="Please describe what happened..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit"
                            class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg shadow-primary-600/20 transition-all transform hover:-translate-y-0.5">
                            Submit Ticket
                        </button>
                    </div>

                </form>

                <script>
                    let suggestionTimer;
                    const titleInput = document.querySelector('input[name="title"]');
                    const container = document.getElementById('aiSuggestionContainer');
                    const list = document.getElementById('suggestionList');

                    titleInput.addEventListener('input', function () {
                        clearTimeout(suggestionTimer);
                        const query = this.value.trim();

                        if (query.length < 3) {
                            container.classList.add('hidden');
                            return;
                        }

                        suggestionTimer = setTimeout(() => {
                            fetch('api/suggest_solutions.php?q=' + encodeURIComponent(query))
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success && data.suggestions.length > 0) {
                                        renderSuggestions(data.suggestions);
                                        container.classList.remove('hidden');
                                    } else {
                                        container.classList.add('hidden');
                                    }
                                });
                        }, 500);
                    });

                    function renderSuggestions(suggestions) {
                        list.innerHTML = suggestions.map(s => `
                            <div class="p-4 hover:bg-primary-50/30 transition-colors group">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-sm font-bold text-slate-800 group-hover:text-primary-700 transition-colors">${s.title}</h4>
                                    <span class="text-[10px] bg-white border border-slate-200 px-2 py-0.5 rounded-full text-slate-500">${s.system_affected}</span>
                                </div>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-3">${s.resolution_preview}</p>
                                <div class="flex gap-2">
                                    <button type="button" onclick="showSolution(${s.id})" class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1">
                                        View Full Steps
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }

                    function showSolution(id) {
                        // For now we open in a new tab, but could be a modal in V2
                        window.open('view_solution.php?id=' + id, '_blank');
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>