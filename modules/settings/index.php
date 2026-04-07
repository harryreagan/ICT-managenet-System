<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'contact_back_office_ext' => $_POST['contact_back_office_ext'],
            'contact_duty_mobile' => $_POST['contact_duty_mobile'],
            'contact_duty_mobile_note' => $_POST['contact_duty_mobile_note']
        ];

        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $_SESSION['success'] = "Settings updated successfully.";
        redirect('/ict/modules/settings/index.php');
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch current values
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = "System Settings";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-end fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">System Settings</h1>
        <p class="text-slate-500 mt-1 text-sm">Manage global system configurations and contact information.</p>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in-up" style="animation-delay: 0.1s">

    <!-- Dashboard Contact Info -->
    <div class="saas-card p-6">
        <h2 class="text-xl font-bold text-slate-800 mb-4 border-b border-gray-100 pb-2">Dashboard Contact Info</h2>
        <form action="" method="POST" class="space-y-4">

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Back Office Extension</label>
                <input type="text" name="contact_back_office_ext"
                    value="<?= htmlspecialchars($settings['contact_back_office_ext'] ?? '') ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                <p class="text-xs text-slate-400 mt-1">Displayed on the user portal sidebar.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">IT Duty Mobile</label>
                <input type="text" name="contact_duty_mobile"
                    value="<?= htmlspecialchars($settings['contact_duty_mobile'] ?? '') ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Availability Note</label>
                <input type="text" name="contact_duty_mobile_note"
                    value="<?= htmlspecialchars($settings['contact_duty_mobile_note'] ?? '') ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                <p class="text-xs text-slate-400 mt-1">E.g., "Calls only when unavailable in office"</p>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-lg shadow-lg shadow-primary-500/20 transition-all hover:-translate-y-0.5">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>