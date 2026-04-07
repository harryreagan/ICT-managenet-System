<?php
function renderQuickActions()
{
    ?>
    <div class="mb-6 flex flex-col md:flex-row justify-between items-end fade-in-up">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">System Overview</h1>
            <p class="text-slate-500 mt-1 text-sm">Welcome back,
                <?php echo htmlspecialchars($_SESSION['username']); ?>. Monitor your ICT infrastructure in real-time.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3 items-center">
            <!-- Quick Action Buttons -->
            <a href="modules/knowledgebase/create.php"
                class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold rounded-lg shadow-lg shadow-primary-500/20 transition-all hover:-translate-y-0.5">
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
                ADD TICKET
            </a>
            <a href="modules/policies"
                class="flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-bold rounded-lg border border-slate-200 shadow-sm transition-all hover:-translate-y-0.5">
                <svg class="w-3.5 h-3.5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
                DOCUMENTATION
            </a>

            <div class="w-px h-6 bg-slate-200 mx-1"></div>
            <div
                class="flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-bold uppercase tracking-wider">
                <span class="relative flex h-2 w-2 mr-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                System Live
            </div>
            <div
                class="flex items-center px-3 py-1 rounded-full bg-white text-slate-500 border border-slate-200 text-[10px] font-bold uppercase tracking-wider shadow-sm">
                <svg class="w-3.5 h-3.5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo date('D, d M Y'); ?>
            </div>
        </div>
    </div>
    <?php
}
?>