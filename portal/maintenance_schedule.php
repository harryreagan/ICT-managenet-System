<?php
require_once __DIR__ . '/layout.php';

// Fetch upcoming maintenance tasks marked for portal
$stmt = $pdo->query("SELECT * FROM maintenance_tasks 
                    WHERE show_on_portal = 1 
                    AND (status != 'completed' OR (end_time IS NOT NULL AND end_time > NOW()))
                    ORDER BY COALESCE(start_time, next_due_date) ASC");
$schedules = $stmt->fetchAll();

renderPortalHeader("Maintenance Schedule");

function getImpactBadge($impact)
{
    return match ($impact) {
        'outage' => 'bg-red-500 text-white',
        'high' => 'bg-orange-500 text-white',
        'medium' => 'bg-amber-500 text-white',
        'low' => 'bg-blue-500 text-white',
        default => 'bg-slate-500 text-white'
    };
}

function getImpactLabel($impact)
{
    return match ($impact) {
        'outage' => 'Major Outage',
        'high' => 'Significant Impact',
        'medium' => 'Partial Outage',
        'low' => 'Minor Degradation',
        default => 'Routine Maintenance'
    };
}
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
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex-grow">
            <h1 class="text-3xl font-bold text-slate-800">Maintenance Schedule</h1>
            <p class="text-slate-500 mt-2">Scheduled system updates and maintenance windows.</p>
        </div>
    </div>

    <?php if (empty($schedules)): ?>
        <div class="bg-white p-20 rounded-xl border border-gray-100 text-center shadow-sm">
            <div
                class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-600">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800">All Systems Operational</h3>
            <p class="text-slate-500 mt-2">No maintenance is currently scheduled. All systems are running smoothly.</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($schedules as $task): ?>
                <?php
                $start = $task['start_time'] ? new DateTime($task['start_time']) : ($task['next_due_date'] ? new DateTime($task['next_due_date']) : null);
                $end = $task['end_time'] ? new DateTime($task['end_time']) : null;
                $isLive = $task['status'] === 'in_progress' || ($start && $start < new DateTime() && (!$end || $end > new DateTime()));
                ?>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden relative">
                    <?php if ($isLive): ?>
                        <div
                            class="absolute top-4 right-4 px-3 py-1 bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-lg z-10">
                            Active Now
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col md:flex-row">
                        <!-- Date/Time Sidebar -->
                        <div
                            class="w-full md:w-48 bg-gray-50 p-6 flex flex-col items-center justify-center text-center border-b md:border-b-0 md:border-r border-gray-100">
                            <?php if ($start): ?>
                                <span class="text-xs font-bold text-primary-600 uppercase tracking-wider mb-1">
                                    <?= $start->format('M') ?>
                                </span>
                                <span class="text-4xl font-bold text-slate-900 leading-none mb-1">
                                    <?= $start->format('d') ?>
                                </span>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                                    <?= $start->format('H:i') ?>
                                </span>
                                <?php if ($end): ?>
                                    <div class="mt-3 w-8 h-px bg-gray-300"></div>
                                    <span class="text-xs text-slate-500 mt-2">
                                        Until <?= $end->format('H:i') ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-slate-400 font-bold uppercase tracking-wider text-xs">TBD</span>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="flex-grow p-6">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <span
                                    class="px-3 py-1 <?= getImpactBadge($task['impact']) ?> text-xs font-bold uppercase tracking-wider rounded-full">
                                    <?= getImpactLabel($task['impact']) ?>
                                </span>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    #<?= str_pad($task['id'], 3, '0', STR_PAD_LEFT) ?>
                                </span>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-3 leading-tight">
                                <?= htmlspecialchars($task['description']) ?>
                            </h3>

                            <div class="text-slate-600 text-sm leading-relaxed mb-6">
                                <?= nl2br(htmlspecialchars($task['proposed_solution'])) ?>
                            </div>

                            <div class="flex items-center gap-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center text-xs font-bold text-primary-600">
                                        <?= strtoupper(substr($task['assigned_to'] ?: 'IT', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Assigned Team</p>
                                        <p class="text-sm font-bold text-slate-700">
                                            <?= htmlspecialchars($task['assigned_to'] ?: 'ICT Support') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="ml-auto text-right">
                                    <p class="text-xs text-slate-500 mb-1">Status</p>
                                    <span class="text-xs font-bold text-slate-900 uppercase bg-gray-100 px-3 py-1 rounded-lg">
                                        <?= str_replace('_', ' ', $task['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Information Card -->
    <div
        class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
        <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6">
            <div
                class="w-16 h-16 bg-white/10 backdrop-blur-xl rounded-xl flex items-center justify-center text-3xl shrink-0">
                💡
            </div>
            <div class="flex-grow text-center lg:text-left">
                <h2 class="text-lg font-bold mb-2 uppercase tracking-wide">Need Help?</h2>
                <p class="text-slate-300 text-sm leading-relaxed">
                    Maintenance is usually scheduled during off-peak hours (11 PM - 5 AM). If you experience any issues,
                    please report them immediately.
                </p>
            </div>
            <a href="submit_ticket.php"
                class="px-6 py-3 bg-white text-slate-900 text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-gray-100 transition-colors shadow-lg whitespace-nowrap">
                Report Issue
            </a>
        </div>
    </div>
</div>

<?php
renderPortalFooter();
?>