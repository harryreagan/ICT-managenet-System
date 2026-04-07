<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/ict/modules/vendors/index.php');

$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$id]);
$vendor = $stmt->fetch();

if (!$vendor)
    redirect('/ict/modules/vendors/index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $service_type = sanitize($_POST['service_type']);
    $contact_person = sanitize($_POST['contact_person']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $sla_notes = sanitize($_POST['sla_notes']);
    $last_service_date = !empty($_POST['last_service_date']) ? $_POST['last_service_date'] : null;

    if (empty($name)) {
        $error = "Vendor Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, contact_person=?, phone=?, email=?, sla_notes=?, last_service_date=? WHERE id=?");
            $stmt->execute([$name, $service_type, $contact_person, $phone, $email, $sla_notes, $last_service_date, $id]);
            $_SESSION['success'] = "Vendor information updated successfully!";
            redirect('/ict/modules/vendors/index.php');
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Edit Vendor</h1>
        <a href="index.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="name">Vendor Name *</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($vendor['name']); ?>"
                        required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Service Type -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="service_type">Service Type</label>
                    <input type="text" name="service_type" id="service_type"
                        value="<?php echo htmlspecialchars($vendor['service_type']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Contact Person -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="contact_person">Contact
                        Person</label>
                    <input type="text" name="contact_person" id="contact_person"
                        value="<?php echo htmlspecialchars($vendor['contact_person']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="phone">Phone</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($vendor['phone']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email"
                        value="<?php echo htmlspecialchars($vendor['email']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- Last Service Date -->
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="last_service_date">Last Service
                        Date</label>
                    <input type="date" name="last_service_date" id="last_service_date"
                        value="<?php echo htmlspecialchars($vendor['last_service_date']); ?>"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm">
                </div>

                <!-- SLA Notes -->
                <div class="col-span-2">
                    <label class="block text-slate-700 text-sm font-bold mb-2" for="sla_notes">SLA Notes / Terms</label>
                    <textarea name="sla_notes" id="sla_notes" rows="3"
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-primary-500 outline-none text-sm"><?php echo htmlspecialchars($vendor['sla_notes']); ?></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg shadow-primary-500/20">
                    Update Vendor
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>