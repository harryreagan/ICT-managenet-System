<?php
function renderKeyMetrics($data)
{
    ?>
    <!-- Top Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-8 fade-in-up" style="animation-delay: 0.1s">

        <!-- 1. Incidents Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Active Incidents
                    </p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['openIncidents']; ?>
                    </h3>
                </div>
                <div
                    class="p-2 <?php echo $data['criticalIncidents'] > 0 ? 'bg-red-50 text-red-600' : 'bg-primary-50 text-primary-600'; ?> rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center">
                <?php if ($data['criticalIncidents'] > 0): ?>
                    <span class="text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded uppercase tracking-tight">
                        <?php echo $data['criticalIncidents']; ?> Critical
                    </span>
                <?php else: ?>
                    <span
                        class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-tight">Normal
                        Ops</span>
                <?php endif; ?>
            </div>
            <a href="modules/knowledgebase" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- 2. Solved Tickets Card -->
        <?php if (isAdmin()): ?>
            <div class="saas-card p-3 relative overflow-hidden group hover:border-emerald-300">
                <div class="flex justify-between items-start text-left">
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Solved (30d)</p>
                        <h3 class="text-xl font-bold text-slate-800 text-left">
                            <?php echo $data['solvedCount']; ?>
                        </h3>
                    </div>
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                        <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center">
                    <span
                        class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-tight">Efficient</span>
                </div>
                <a href="modules/knowledgebase/index.php" class="absolute inset-0 z-0 text-left"></a>
            </div>
        <?php endif; ?>

        <!-- 3. Renewals Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-amber-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Renewals</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['renewalsDue']; ?>
                    </h3>
                </div>
                <div
                    class="p-2 <?php echo $data['renewalsDue'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-300'; ?> rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between items-center text-left">
                <span
                    class="text-[9px] font-bold <?php echo $data['renewalsDue'] > 0 ? 'text-amber-600 bg-amber-50' : 'text-slate-400 bg-slate-50'; ?> px-1.5 py-0.5 rounded uppercase tracking-tight">Soon</span>
                <span class="text-[9px] font-bold text-slate-400">
                    <?php echo $data['unpaidSubscriptions']; ?> Unpaid
                </span>
            </div>
            <a href="modules/renewals" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- 4. Staffing Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">ICT Staff</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['ictStaffCount']; ?>
                    </h3>
                </div>
                <div class="p-2 bg-primary-50 text-primary-600 rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between items-baseline text-left">
                <span
                    class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-tight">
                    <?php echo ($data['ictStaffCount'] - $data['staffOnLeave']); ?>
                    Active
                </span>
                <span class="text-[9px] font-bold text-slate-400">
                    <?php echo $data['staffOnLeave']; ?> Leave
                </span>
            </div>
            <a href="modules/users" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- 5. Assets Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Total Assets</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['totalAssets']; ?>
                    </h3>
                </div>
                <div class="p-2 bg-primary-50 text-primary-600 rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-start text-left">
                <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded uppercase tracking-tight">
                    <?php echo $data['hardwareIssues']; ?>
                    alerts
                </span>
            </div>
            <a href="modules/hardware" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- 6. Static IPs Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Static IPs</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['staticDevicesCount']; ?>
                    </h3>
                </div>
                <div class="p-2 bg-primary-50 text-primary-600 rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-start text-left">
                <span
                    class="text-[9px] font-bold text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded uppercase tracking-tight">Network
                    Assets</span>
            </div>
            <a href="modules/networks/static_devices.php" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- 7. Network Card -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-emerald-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Network</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['networkHealthPercent']; ?>%
                    </h3>
                </div>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <div class="w-full bg-slate-100 rounded-full h-1">
                    <div class="bg-emerald-500 h-1 rounded-full"
                        style="width: <?php echo $data['networkHealthPercent']; ?>%">
                    </div>
                </div>
            </div>
            <a href="modules/networks/monitoring.php" class="absolute inset-0 z-0 text-left"></a>
        </div>

        <!-- User Management Quick Access -->
        <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
            <div class="flex justify-between items-start text-left">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Users</p>
                    <h3 class="text-xl font-bold text-slate-800 text-left">
                        <?php echo $data['activeUsersCount']; ?>
                    </h3>
                </div>
                <div class="p-2 bg-primary-50 text-primary-600 rounded-lg">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <p class="text-[10px] text-slate-500">Active Accounts</p>
            </div>
            <a href="modules/users/index.php" class="absolute inset-0 z-0 text-left"></a>
        </div>
    </div>
    <?php
}
?>