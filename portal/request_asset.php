<?php
require_once __DIR__ . '/layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_name = sanitize($_POST['staff_name']);
    $department = sanitize($_POST['department']);
    $asset_type = sanitize($_POST['asset_type']);
    $event_name = sanitize($_POST['event_name']);
    $event_date = sanitize($_POST['event_date']);
    $details = sanitize($_POST['details']);
    $requester_username = $_SESSION['username'] ?? 'portal_user';

    if (empty($staff_name) || empty($department) || empty($asset_type) || empty($details)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO asset_requests (requester_username, staff_name, department, asset_type, event_name, event_date, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$requester_username, $staff_name, $department, $asset_type, $event_name, $event_date ?: null, $details]);

            // Notify ICT Team
            createNotification(
                $pdo,
                "New Asset Request",
                "$staff_name requested a $asset_type for $event_name.",
                "info",
                "/ict/modules/hardware/asset_requests.php"
            );

            $success = "Asset request submitted successfully! The ICT team will review your request.";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

renderPortalHeader("Request Event Asset");
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php"
            class="text-sm font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/40 p-6 md:p-8 border border-slate-100">
        <?php if ($success): ?>
            <div
                class="bg-emerald-50 text-emerald-700 p-4 rounded-xl border border-emerald-100 flex items-start gap-3 mb-6">
                <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="font-bold text-emerald-800">Success!</h3>
                    <p class="text-sm mt-1">
                        <?= $success ?>
                    </p>
                    <div class="mt-3">
                        <a href="index.php"
                            class="inline-block bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition">Return
                            to Portal</a>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 border border-red-100 flex gap-2 items-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-5 flex flex-col">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Staff Name *</label>
                            <input type="text" name="staff_name" required
                                value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''); ?>"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Department *</label>
                            <input type="text" name="department" required
                                value="<?php echo htmlspecialchars($_SESSION['department'] ?? ''); ?>"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Asset Required *</label>
                        <select name="asset_type" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm">
                            <option value="">-- Choose Asset --</option>
                            <option value="Mixer">Mixer (Audio)</option>
                            <option value="Microphone">Microphone</option>
                            <option value="Extension">Extension Cable / Power Strip</option>
                            <option value="Other">Other Equipment</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Event Name / Purpose</label>
                            <input type="text" name="event_name" placeholder="E.g. Conference Room A Setup"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Event Date</label>
                            <input type="date" name="event_date"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Additional Details / Quantity *</label>
                        <textarea name="details" required rows="3"
                            placeholder="Please specify quantity, location, or any special requirements..."
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white outline-none transition-all text-sm"></textarea>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-primary-600/30 transform hover:-translate-y-0.5 w-full md:w-auto flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Submit Request
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php renderPortalFooter(); ?>