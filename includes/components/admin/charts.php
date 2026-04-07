<?php
function renderCharts()
{
    ?>
    <!-- Charts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-left">
        <div class="saas-card p-5 text-left">
            <div class="flex justify-between items-center mb-6 text-left">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest text-left">Ticket Trends</h3>
                <span class="text-[9px] font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded text-left">6
                    Months</span>
            </div>
            <div class="h-48 text-left">
                <canvas id="ticketTrendChart"></canvas>
            </div>
        </div>
        <div class="saas-card p-5 text-left">
            <div class="flex justify-between items-center mb-6 text-left">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest text-left">Asset Status</h3>
                <span
                    class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-left">Health</span>
            </div>
            <div class="h-48 text-left">
                <canvas id="assetHealthChart"></canvas>
            </div>
        </div>
        <div class="saas-card p-5 text-left">
            <div class="flex justify-between items-center mb-6 text-left">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest text-left">Issue Priorities
                </h3>
                <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded text-left">Severity</span>
            </div>
            <div class="h-48 text-left">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>
    </div>
    <?php
}
?>