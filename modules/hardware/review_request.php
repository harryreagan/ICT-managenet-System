<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('asset_requests.php');

$stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
$stmt->execute([$id]);
$req = $stmt->fetch();

if (!$req)
    redirect('asset_requests.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status']);
    $ict_notes = sanitize($_POST['ict_notes']);

    try {
        $stmt = $pdo->prepare("UPDATE asset_requests SET status=?, ict_notes=? WHERE id=?");
        $stmt->execute([$status, $ict_notes, $id]);
        $_SESSION['success'] = "Request updated successfully!";
        redirect('asset_requests.php');
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

$pageTitle = "Review Asset Request";
include '../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-800">Review Asset Request</h1>
        <a href="asset_requests.php" class="text-slate-500 hover:text-slate-700">Back to List</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6 border border-slate-100">
        <div class="p-6 md:p-8">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 mb-1">
                        <?php echo htmlspecialchars($req['asset_type']); ?>
                    </h2>
                    <p class="text-sm font-medium text-slate-500">Requested by <span class="text-primary-600">
                            <?php echo htmlspecialchars($req['staff_name']); ?>
                        </span> (
                        <?php echo htmlspecialchars($req['department']); ?>)
                    </p>
                </div>
                <div class="text-right">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-slate-100 text-slate-600">
                        <?php echo $req['status']; ?>
                    </span>
                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-wider">Submitted:
                        <?php echo date('M j, Y H:i', strtotime($req['created_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Event Details</h3>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <p class="font-bold text-slate-800 mb-1">
                            <?php echo htmlspecialchars($req['event_name'] ?: 'No Event Name'); ?>
                        </p>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <?php echo $req['event_date'] ? date('l, M j, Y', strtotime($req['event_date'])) : 'Not specified'; ?>
                        </p>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Request Context</h3>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 h-full">
                        <p class="text-sm text-slate-600 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($req['details'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Form -->
            <form method="POST" action="" class="bg-primary-50/50 rounded-2xl p-6 border border-primary-100/50">
                <h3 class="text-sm font-black text-primary-900 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ICT Action & Notes
                </h3>

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4 border border-red-100">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2"
                        for="status">Update Status</label>
                    <select name="status" id="status"
                        class="w-full md:w-1/2 px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm font-medium">
                        <option value="pending" <?php echo $req['status'] === 'pending' ? 'selected' : ''; ?>>🕒 Pending
                            Review</option>
                        <option value="approved" <?php echo $req['status'] === 'approved' ? 'selected' : ''; ?>>✅
                            Approved</option>
                        <option value="issued" <?php echo $req['status'] === 'issued' ? 'selected' : ''; ?>>📤 Issued to
                            Staff</option>
                        <option value="returned" <?php echo $req['status'] === 'returned' ? 'selected' : ''; ?>>📥
                            Returned & Verified</option>
                        <option value="rejected" <?php echo $req['status'] === 'rejected' ? 'selected' : ''; ?>>❌
                            Rejected</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2"
                        for="ict_notes">Internal Notes / Serial Number Issued</label>
                    <textarea name="ict_notes" id="ict_notes" rows="3"
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                        placeholder="e.g. Issued Microphone #04, remember to collect after event."><?php echo htmlspecialchars($req['ict_notes']); ?></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary-600/20 transition-all transform hover:-translate-y-0.5">
                        Save Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>