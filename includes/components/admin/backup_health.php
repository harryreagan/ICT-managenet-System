<?php
function renderBackupHealth($atRiskBackups)
{
    ?>
    <!-- Backup Health Reminder -->
    <div class="space-y-4">
        <a href="modules/backups/index.php" class="block transition-transform hover:-translate-y-1">
            <div
                class="saas-card p-5 bg-gradient-to-br <?php echo $atRiskBackups > 0 ? 'from-amber-50 to-white border-amber-200' : 'from-emerald-50 to-white border-emerald-100'; ?>">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center">
                        <div
                            class="p-2 <?php echo $atRiskBackups > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'; ?> rounded-lg mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Backup Health</h3>
                            <p class="text-[10px] text-slate-500">Last verified: Daily</p>
                        </div>
                    </div>
                    <?php if ($atRiskBackups > 0): ?>
                        <span class="flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[11px] font-bold text-slate-600 uppercase">System Integrity</span>
                        <span
                            class="text-[11px] font-bold <?php echo $atRiskBackups > 0 ? 'text-amber-600' : 'text-emerald-600'; ?>">
                            <?php echo $atRiskBackups > 0 ? $atRiskBackups . ' Assets at Risk' : 'All Data Safe'; ?>
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $atRiskBackups > 0 ? 'bg-amber-500' : 'bg-emerald-500'; ?>"
                            style="width: <?php echo max(5, 100 - ($atRiskBackups * 10)); ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-3 leading-relaxed italic">
                        <?php echo $atRiskBackups > 0 ? 'Action required! Some systems have not been backed up successfully within the last 24 hours.' : 'Excellent work! All critical system backups are current and verified.'; ?>
                    </p>
                </div>
            </div>
        </a>
    </div>
    <?php
}
?>