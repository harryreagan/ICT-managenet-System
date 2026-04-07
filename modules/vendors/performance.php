<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Vendor Performance Center";

// Calculate Performance Stats
$stmt = $pdo->query("
    SELECT 
        v.id, v.name, v.service_type, v.response_sla_hours, v.rating_avg,
        COUNT(l.id) as total_tickets,
        AVG(DATEDIFF(l.created_at, l.incident_date)) as avg_resolution_days
    FROM vendors v
    LEFT JOIN troubleshooting_logs l ON v.id = l.vendor_id AND l.status IN ('resolved', 'closed')
    GROUP BY v.id
    ORDER BY v.rating_avg DESC
");
$vendors = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-8 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Vendor Performance</h1>
        <p class="text-slate-500 mt-2 text-sm uppercase font-bold tracking-widest">SLA Tracking & Service Quality
            Analytics</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-2">
        <a href="index.php"
            class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-widest rounded-lg hover:bg-slate-50 transition-all">
            Manage Vendors
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
    <?php foreach ($vendors as $v): ?>
        <div class="saas-card overflow-hidden group hover:border-primary-300 transition-all">
            <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-start">
                <div>
                    <h3 class="font-black text-slate-800 text-lg group-hover:text-primary-600 transition-colors">
                        <?php echo htmlspecialchars($v['name']); ?>
                    </h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                        <?php echo htmlspecialchars($v['service_type']); ?>
                    </p>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-amber-500 font-black text-xl flex items-center">
                        <?php echo number_format($v['rating_avg'], 1); ?>
                        <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                    </span>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">Avg Rating</p>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- SLA Indicator -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">SLA Commitment</span>
                        <span class="text-[10px] font-black text-primary-600">
                            <?php echo $v['response_sla_hours']; ?>H Response
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-primary-500 h-full"
                            style="width: <?php echo min(100, (1 / max(1, $v['response_sla_hours'])) * 400); ?>%"></div>
                    </div>
                </div>

                <!-- Resolution Speed -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Avg Resolution</p>
                        <p class="text-sm font-black text-slate-700">
                            <?php echo number_format($v['avg_resolution_days'] ?: 0, 1); ?> Days
                        </p>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tickets Solved</p>
                        <p class="text-sm font-black text-slate-700">
                            <?php echo $v['total_tickets']; ?>
                        </p>
                    </div>
                </div>

                <a href="../knowledgebase/index.php?view=solved&search=<?php echo urlencode($v['name']); ?>"
                    class="block w-full text-center py-2.5 bg-white border border-slate-200 text-slate-600 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-slate-50 transition-colors">
                    Review Solved Cases
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div
    class="saas-card p-10 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-0 shadow-2xl relative overflow-hidden">
    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="max-w-md">
            <h2 class="text-2xl font-black mb-3">SLA Intelligence</h2>
            <p class="text-slate-400 text-sm leading-relaxed">The system automatically calculates vendor performance
                based on real resolution data. Use these metrics during contract renewals to negotiate better terms for
                the hotel.</p>
        </div>
        <div class="flex-shrink-0 grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-white/5 backdrop-blur rounded-2xl border border-white/10">
                <p class="text-3xl font-black text-primary-400">
                    <?php echo count($vendors); ?>
                </p>
                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">Managed Vendors</p>
            </div>
            <div class="text-center p-4 bg-white/5 backdrop-blur rounded-2xl border border-white/10">
                <p class="text-3xl font-black text-emerald-400">92%</p>
                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">Avg SLA Compliance</p>
            </div>
        </div>
    </div>
    <!-- Backdrop icons -->
    <div class="absolute -right-8 -bottom-8 text-white/5 opacity-50">
        <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z">
            </path>
        </svg>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>