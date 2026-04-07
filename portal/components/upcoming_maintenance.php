<?php
/**
 * Renders the Recent Maintenance Widget
 * used in Portal Dashboard
 */
function renderMaintenanceWidget($upcoming_maintenance)
{
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-6">
        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2">
            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Maintenance
            </h3>
            <a href="maintenance_schedule.php"
                class="text-[10px] font-bold text-primary-500 hover:text-primary-600 uppercase tracking-widest">Full
                Schedule &rarr;</a>
        </div>

        <div class="space-y-3">
            <?php if (empty($upcoming_maintenance)): ?>
                <div class="py-4 text-center">
                    <div
                        class="w-8 h-8 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">All systems clear</p>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_maintenance as $maint): ?>
                    <?php
                    $m_start = $maint['start_time'] ? new DateTime($maint['start_time']) : ($maint['next_due_date'] ? new DateTime($maint['next_due_date']) : null);
                    $impactColor = match ($maint['impact']) {
                        'outage' => 'red',
                        'high' => 'orange',
                        'medium' => 'amber',
                        default => 'blue'
                    };
                    ?>
                    <div class="p-3 rounded-lg border border-<?= $impactColor ?>-50 bg-<?= $impactColor ?>-50/30">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] font-black uppercase tracking-widest text-<?= $impactColor ?>-600">
                                <?= str_replace('_', ' ', $maint['impact']) ?>
                            </span>
                            <span class="text-[9px] font-bold text-slate-400">
                                <?= $m_start ? $m_start->format('M d, H:i') : 'TBD' ?>
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 leading-tight line-clamp-1">
                            <?= htmlspecialchars($maint['description']) ?>
                        </h4>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>