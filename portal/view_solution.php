<?php
require_once __DIR__ . '/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    header("Location: knowledge_base.php");
    exit;
}

// Fetch Solution Details
$stmt = $pdo->prepare("SELECT * FROM troubleshooting_logs WHERE id = ? AND status IN ('resolved', 'closed')");
$stmt->execute([$id]);
$sol = $stmt->fetch();

if (!$sol) {
    header("Location: knowledge_base.php");
    exit;
}

renderPortalHeader("Solution: " . htmlspecialchars($sol['title']));
?>

<div class="space-y-8">
    <!-- Back Navigation -->
    <a href="verified_solutions.php"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Back to Solutions
    </a>

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-6">
            <span
                class="bg-emerald-50 text-emerald-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-emerald-100">
                Verified Solution
            </span>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                #<?= str_pad($sol['id'], 4, '0', STR_PAD_LEFT) ?>
            </span>
            <span class="text-xs text-slate-400">•</span>
            <span class="text-xs text-slate-500"><?= htmlspecialchars($sol['system_affected']) ?></span>
        </div>

        <h1 class="text-3xl font-bold text-slate-800 mb-4 leading-tight">
            <?= htmlspecialchars($sol['title']) ?>
        </h1>

        <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
            <div
                class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center text-sm font-bold text-primary-600">
                <?= strtoupper(substr($sol['technician_name'] ?: 'T', 0, 1)) ?>
            </div>
            <div>
                <p class="text-xs text-slate-500">Resolved by</p>
                <p class="text-sm font-bold text-slate-700">
                    <?= htmlspecialchars($sol['technician_name'] ?: 'ICT Team') ?>
                </p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-slate-500">Date</p>
                <p class="text-sm font-bold text-slate-700">
                    <?= date('M j, Y', strtotime($sol['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Problem Description -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-800">Problem Description</h2>
        </div>
        <div class="prose prose-slate max-w-none text-slate-600">
            <?= $sol['symptoms'] ?>
        </div>
    </div>

    <!-- Solution Image -->
    <?php if ($sol['solution_image']): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Visual Reference</h2>
            </div>
            <div class="rounded-lg overflow-hidden border border-gray-200">
                <img src="/ict/<?= $sol['solution_image'] ?>" alt="Solution Reference" class="w-full">
            </div>
        </div>
    <?php endif; ?>

    <!-- Root Cause -->
    <?php if ($sol['root_cause']): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Root Cause</h2>
            </div>
            <div class="prose prose-slate max-w-none text-slate-600">
                <?= $sol['root_cause'] ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Steps Taken -->
    <?php if ($sol['steps_taken']): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Steps Taken</h2>
            </div>
            <div class="prose prose-slate max-w-none text-slate-600">
                <?= $sol['steps_taken'] ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resolution -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 border-l-4 border-l-emerald-500">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-800">Resolution</h2>
        </div>
        <div class="prose prose-slate max-w-none text-slate-600">
            <?= $sol['resolution'] ?>
        </div>
    </div>

    <!-- Help Card -->
    <div
        class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
        <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6">
            <div
                class="w-16 h-16 bg-white/10 backdrop-blur-xl rounded-xl flex items-center justify-center text-3xl shrink-0">
                💬
            </div>
            <div class="flex-grow text-center lg:text-left">
                <h2 class="text-lg font-bold mb-2 uppercase tracking-wide">Still Having Issues?</h2>
                <p class="text-slate-300 text-sm leading-relaxed">
                    If this solution didn't work for you, please submit a new ticket and our team will help you.
                </p>
            </div>
            <a href="submit_ticket.php"
                class="px-6 py-3 bg-white text-slate-900 text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-gray-100 transition-colors shadow-lg whitespace-nowrap">
                Submit Ticket
            </a>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>