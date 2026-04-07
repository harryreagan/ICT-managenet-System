<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "ICT Department Hub";
include '../../includes/header.php';

// Fetch the Responsibilities document if it exists
$stmt = $pdo->prepare("SELECT id, content FROM sop_documents WHERE title = ?");
$stmt->execute(["ICT Department – Duties & Responsibilities"]);
$doc = $stmt->fetch();
$responsibilities = $doc['content'] ?? null;
$docId = $doc['id'] ?? null;

$successMsg = $_GET['success'] ?? null;
?>

<div class="flex flex-col gap-8 fade-in-up">
    <?php if ($successMsg): ?>
        <div
            class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-3 text-emerald-700 animate-bounce">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-sm font-bold"><?php echo htmlspecialchars($successMsg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-slate-900 rounded-3xl p-8 text-white shadow-2xl">
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="max-w-2xl">
                <span
                    class="px-3 py-1 bg-primary-500/20 text-primary-300 text-[10px] font-bold uppercase tracking-widest rounded-full border border-primary-500/30 mb-4 inline-block">Department
                    Profile</span>
                <h1 class="text-4xl font-black mb-4 tracking-tight">ICT Operations & Strategy</h1>
                <p class="text-slate-400 text-lg leading-relaxed">
                    Powering the hotel's digital guest experience and operational excellence through robust
                    infrastructure,
                    proactive monitoring, and strategic system administration.
                </p>
            </div>
            <div class="flex gap-4">
                <div
                    class="bg-white/5 backdrop-blur-md border border-white/10 p-6 rounded-2xl text-center min-w-[140px]">
                    <div class="text-3xl font-black text-primary-400 mb-1" id="uptimeCounter">99.9%</div>
                    <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Core Uptime</div>
                </div>
                <div
                    class="bg-white/5 backdrop-blur-md border border-white/10 p-6 rounded-2xl text-center min-w-[140px]">
                    <div class="text-3xl font-black text-emerald-400 mb-1" id="resolutionRate">--%</div>
                    <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">SLA Compliance</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Ticket Health -->
        <div class="saas-card group hover:border-primary-500/30 transition-all p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-primary-50 text-primary-600 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Helpdesk</span>
            </div>
            <h3 class="text-sm font-bold text-slate-800 mb-1">Service Velocity</h3>
            <p class="text-xs text-slate-500 mb-4">Tickets resolved in last 30 days</p>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-black text-slate-900" id="totalTickets">0</span>
                <span class="text-xs font-bold text-emerald-600 pb-1" id="resolvedRateLabel">+0%</span>
            </div>
        </div>

        <!-- Infrastructure Health -->
        <div class="saas-card group hover:border-blue-500/30 transition-all p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Infrastructure</span>
            </div>
            <h3 class="text-sm font-bold text-slate-800 mb-1">Asset Integrity</h3>
            <p class="text-xs text-slate-500 mb-4">Hardware in good condition</p>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-black text-slate-900" id="assetHealth">0/0</span>
            </div>
        </div>

        <!-- Power Status -->
        <div class="saas-card group hover:border-amber-500/30 transition-all p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Energy</span>
            </div>
            <h3 class="text-sm font-bold text-slate-800 mb-1">Solar & Power</h3>
            <p class="text-xs text-slate-500 mb-4" id="powerStatus">Status: Optimal</p>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-black text-slate-900" id="powerLoad">0.0</span>
                <span class="text-[10px] font-bold text-slate-400 pb-1.5 uppercase tracking-widest">kW Avg Load</span>
            </div>
        </div>
    </div>

    <!-- Duties & Responsibilities Integrated -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="saas-card overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-bold text-slate-800">Duties & Responsibilities</h2>
                        <?php if ($docId): ?>
                            <a href="/ict/modules/policies/manage.php?id=<?php echo $docId; ?>&return=department"
                                class="p-2 bg-white border border-slate-200 text-slate-400 hover:text-primary-600 rounded-lg transition-all shadow-sm hover:shadow-md group"
                                title="Edit Responsibilities">
                                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    <a href="/ict/modules/policies"
                        class="text-xs font-bold text-primary-600 hover:text-primary-700">Documentation Center
                        &rarr;</a>
                </div>
                <div
                    class="p-8 prose prose-slate max-w-none prose-headings:text-slate-800 prose-h2:text-xl prose-h2:mt-8 prose-h2:mb-4 prose-h3:text-sm prose-h3:font-bold prose-h3:uppercase prose-h3:tracking-widest prose-h3:text-slate-400 prose-li:text-slate-600 prose-hr:border-slate-100">
                    <?php if ($responsibilities): ?>
                        <?php echo $responsibilities; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <p class="text-slate-500 mb-6">Official documentation not yet published.</p>
                            <a href="<?php echo BASE_URL; ?>/add_ict_responsibilities.php"
                                class="saas-button-primary inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Publish Official Documentation
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar / Strategy Card -->
        <div class="space-y-6">
            <div class="saas-card p-6 bg-gradient-to-br from-primary-500 to-indigo-600 text-white">
                <h3 class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-60">Staff Mission</h3>
                <p class="text-xl font-bold leading-tight mb-6">
                    "Reliability first. We ensure every guest and staff member has seamless digital access 24/7."
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/10 p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-60">Tickets</div>
                        <div class="text-lg font-bold">Priority Driven</div>
                    </div>
                    <div class="bg-white/10 p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-60">Monitoring</div>
                        <div class="text-lg font-bold">Proactive</div>
                    </div>
                </div>
            </div>

            <div class="saas-card p-6 border-l-4 border-l-amber-500">
                <h3 class="text-sm font-bold text-slate-800 mb-2">Emergency Protocols</h3>
                <p class="text-xs text-slate-500 mb-4">Critical system failure response steps for new staff.</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] font-bold">
                            01</div>
                        <span class="text-xs font-bold text-slate-700">Check Power Redundancy</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] font-bold">
                            02</div>
                        <span class="text-xs font-bold text-slate-700">Verify Server VM Status</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] font-bold">
                            03</div>
                        <span class="text-xs font-bold text-slate-700">Log Activity Immediately</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function updateDepartmentMetrics() {
        try {
            const response = await fetch('data.php');
            const data = await response.json();

            if (data.metrics) {
                // Update Tickets
                document.getElementById('totalTickets').textContent = data.metrics.tickets.total;
                document.getElementById('resolvedRateLabel').textContent = data.metrics.tickets.resolved_rate + '%';
                document.getElementById('resolutionRate').textContent = data.metrics.tickets.resolved_rate + '%';

                // Update Assets
                document.getElementById('assetHealth').textContent = data.metrics.infrastructure.healthy_assets + '/' + data.metrics.infrastructure.total_assets;

                // Update Power
                document.getElementById('powerStatus').textContent = 'Status: ' + data.metrics.power.status;
                document.getElementById('powerLoad').textContent = data.metrics.power.avg_load;

                const statusEl = document.getElementById('powerStatus');
                if (data.metrics.power.status !== 'Optimal') {
                    statusEl.className = 'text-xs text-red-500 font-bold mb-4';
                }
            }
        } catch (err) {
            console.error('Failed to fetch department metrics:', err);
        }
    }

    updateDepartmentMetrics();
    // Refresh every 5 minutes
    setInterval(updateDepartmentMetrics, 300000);
</script>

<?php include '../../includes/footer.php'; ?>