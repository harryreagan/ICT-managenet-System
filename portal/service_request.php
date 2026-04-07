<?php
require_once __DIR__ . '/layout.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = sanitize($_POST['request_type']);
    $title = sanitize($_POST['title']); // We'll auto-generate or use this
    $details = sanitize($_POST['details']);
    $urgency = sanitize($_POST['urgency']);

    if (empty($request_type) || empty($details)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        // Prefix title to distinguish from incidents
        $final_title = "[REQUEST] " . $request_type . ": " . $title;
        $full_description = "REQUESTER: " . $_SESSION['username'] . "\nTYPE: Service Request\n\n" . $details;

        // We use 'Low' priority as default for requests unless specified, but let's map urgency
        // status = 'open'
        $stmt = $pdo->prepare("INSERT INTO troubleshooting_logs (title, requester_username, symptoms, priority, system_affected, status, incident_date) VALUES (?, ?, ?, ?, 'Service Request', 'open', NOW())");

        if ($stmt->execute([$final_title, $_SESSION['username'], $full_description, $urgency])) {
            $success_msg = "Service request submitted successfully! We will review it shortly.";

            // Send Email to ICT Team
            $email_body = "A new service request has been submitted via the Portal.\n\n";
            $email_body .= "Type: $request_type\n";
            $email_body .= "Title: $title\n";
            $email_body .= "Urgency: $urgency\n";
            $email_body .= "Requester: " . $_SESSION['username'] . "\n";
            $email_body .= "Details: $details";
            sendICTEmail("New Service Request: $final_title", $email_body);
        } else {
            $error_msg = "Failed to submit request. Please try again.";
        }
    }
}

renderPortalHeader("Request Service");
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php" class="text-sm text-slate-500 hover:text-primary-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m-7 7h18">
                </path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-indigo-100 overflow-hidden">
        <div class="bg-indigo-50 px-6 py-4 border-b border-indigo-100">
            <h1 class="text-xl font-bold text-indigo-900">Request New Service / Access</h1>
            <p class="text-sm text-indigo-700/80 mt-1">Need something new? Let us know below.</p>
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

                <form method="POST" action="" class="space-y-5">

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">What do you need?</label>
                        <select name="request_type"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Access">New Account / Access Rights</option>
                            <option value="Hardware">New Hardware (Mouse, Keyboard, etc.)</option>
                            <option value="Software">Software Installation</option>
                            <option value="WiFi">Guest Wi-Fi Access</option>
                            <option value="Other">Other Request</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Short Title</label>
                        <input type="text" name="title" placeholder="e.g. Need access to HR Folder"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Details / Justification</label>
                        <textarea name="details" rows="4" placeholder="Please explain what you need and why..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Urgency</label>
                        <select name="urgency"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="low">Standard (Processing time: 1-2 days)</option>
                            <option value="medium">Important</option>
                            <option value="high">Urgent (Requires immediate approval)</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5">
                            Submit Ticket
                        </button>
                    </div>

                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>