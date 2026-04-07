<?php
require_once __DIR__ . '/layout.php';

// Fetch IT personnel (admins and technicians)
$stmt = $pdo->query("SELECT full_name, username, role, department, extension, duty_number FROM users WHERE role IN ('admin', 'technician') AND status = 'active' ORDER BY full_name ASC");
$contacts = $stmt->fetchAll();

renderPortalHeader("IT Directory");
?>

<div class="space-y-8">
    <!-- Back Navigation -->
    <a href="index.php"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Back to Dashboard
    </a>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-800">IT Directory</h1>
        <p class="text-slate-500 mt-2">Connect with our specialized IT support team.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($contacts as $contact): ?>
            <div
                class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col">
                <div class="flex items-center gap-4 mb-4">
                    <div
                        class="w-12 h-12 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center font-bold text-lg">
                        <?= strtoupper(substr($contact['full_name'] ?: $contact['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">
                            <?= htmlspecialchars($contact['full_name'] ?: $contact['username']) ?>
                        </h3>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-primary-500">
                            <?= htmlspecialchars($contact['role']) ?>
                        </span>
                    </div>
                </div>

                <div class="space-y-3 flex-grow">
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-xs text-slate-400 font-bold uppercase">Back Office Ext</span>
                        <span class="text-sm font-mono font-bold text-slate-700">
                            <?= htmlspecialchars($contact['extension'] ?: 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">Duty / Mobile</span>
                        <span class="text-sm font-bold text-slate-700">
                            <?= htmlspecialchars($contact['duty_number'] ?: 'N/A') ?>
                        </span>
                    </div>
                </div>

                <?php if ($contact['department']): ?>
                    <div class="mt-4 pt-4 border-t border-slate-50">
                        <span class="text-[10px] text-slate-400 font-medium">Specialization:
                            <?= htmlspecialchars($contact['department']) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Hotlines Section -->
    <div class="bg-slate-900 rounded-2xl p-8 text-white relative overflow-hidden shadow-xl mt-12">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary-500/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
            <div>
                <h2 class="text-2xl font-bold mb-2">System Hotlines</h2>
                <p class="text-slate-400">For urgent issues requiring immediate attention.</p>
            </div>
            <div class="flex flex-wrap gap-8">
                <div>
                    <span class="block text-[10px] font-bold text-primary-400 uppercase tracking-widest mb-1">Primary
                        Support</span>
                    <span class="text-2xl font-black text-white">Ext #
                        <?= htmlspecialchars(get_setting($pdo, 'contact_back_office_ext', '104')) ?>
                    </span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-primary-400 uppercase tracking-widest mb-1">Duty
                        Manager</span>
                    <span class="text-2xl font-black text-white">
                        <?= htmlspecialchars(get_setting($pdo, 'contact_duty_mobile', '0743 606 108')) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>