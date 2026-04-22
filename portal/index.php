<?php
require_once __DIR__ . '/layout.php';

// 1. Fetch recent tickets submitted by THIS user
$stmt = $pdo->prepare("SELECT id, title, status, created_at, priority FROM troubleshooting_logs WHERE requester_username = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['username']]);
$my_tickets = $stmt->fetchAll();

// 3. Fetch global announcements/stats
renderPortalHeader("My Dashboard");

// Fetch Notifications for Announcements
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3");
$announcements = $stmt->fetchAll();

// Fetch Recent SOPs for Knowledge Base Section
$stmt = $pdo->query("SELECT * FROM sop_documents ORDER BY last_updated DESC LIMIT 4");
$recent_sops = $stmt->fetchAll();

// Fetch Upcoming Maintenance for Dashboard Widget using only columns this schema actually has.
try {
    $maintenanceColumns = $pdo->query("SHOW COLUMNS FROM maintenance_tasks")->fetchAll(PDO::FETCH_COLUMN);
    $hasShowOnPortal = in_array('show_on_portal', $maintenanceColumns, true);
    $hasStatus = in_array('status', $maintenanceColumns, true);
    $hasEndTime = in_array('end_time', $maintenanceColumns, true);
    $hasStartTime = in_array('start_time', $maintenanceColumns, true);
    $hasNextDueDate = in_array('next_due_date', $maintenanceColumns, true);

    $whereClauses = [];
    if ($hasShowOnPortal) {
        $whereClauses[] = "show_on_portal = 1";
    }
    if ($hasStatus && $hasEndTime) {
        $whereClauses[] = "(status != 'completed' OR (end_time IS NOT NULL AND end_time > NOW()))";
    } elseif ($hasStatus) {
        $whereClauses[] = "status != 'completed'";
    }

    if ($hasStartTime && $hasNextDueDate) {
        $orderBy = "COALESCE(start_time, next_due_date) ASC";
    } elseif ($hasStartTime) {
        $orderBy = "start_time ASC";
    } elseif ($hasNextDueDate) {
        $orderBy = "next_due_date ASC";
    } else {
        $orderBy = "id DESC";
    }

    $maintenanceSql = "SELECT * FROM maintenance_tasks";
    if (!empty($whereClauses)) {
        $maintenanceSql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    $maintenanceSql .= " ORDER BY {$orderBy} LIMIT 2";

    $stmt = $pdo->query($maintenanceSql);
    $upcoming_maintenance = $stmt->fetchAll();
} catch (PDOException $e) {
    $upcoming_maintenance = [];
}
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">

    <!-- Hero / Quick Actions -->
    <div class="md:col-span-2 space-y-6">

        <!-- System Status Banner -->
        <?php
        try {
            $tableExists = $pdo->query("SHOW TABLES LIKE 'system_status'")->rowCount() > 0;
            if ($tableExists) {
                $statusStmt = $pdo->query("SELECT * FROM system_status WHERE id = 1");
                $sysStatus = $statusStmt->fetch();
            } else {
                $sysStatus = ['status' => 'operational', 'message' => 'Network and core services are running normally.'];
            }
        } catch (PDOException $e) {
            $sysStatus = ['status' => 'operational', 'message' => 'Network and core services are running normally.'];
        }

        $statusTheme = [
            'operational' => [
                'bg' => 'emerald-50',
                'border' => 'emerald-100',
                'icon_bg' => 'emerald-100',
                'icon_text' => 'emerald-600',
                'title_text' => 'emerald-800',
                'body_text' => 'emerald-600',
                'label' => 'All Systems Operational',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
            ],
            'partial_outage' => [
                'bg' => 'amber-50',
                'border' => 'amber-100',
                'icon_bg' => 'amber-100',
                'icon_text' => 'amber-600',
                'title_text' => 'amber-800',
                'body_text' => 'amber-600',
                'label' => 'Service Advisory',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
            ],
            'major_outage' => [
                'bg' => 'red-50',
                'border' => 'red-100',
                'icon_bg' => 'red-100',
                'icon_text' => 'red-600',
                'title_text' => 'red-800',
                'body_text' => 'red-600',
                'label' => 'Service Outage',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
            ]
        ];
        $currentTheme = $statusTheme[$sysStatus['status'] ?? 'operational'];
        ?>
        <div
            class="bg-<?= $currentTheme['bg'] ?> border border-<?= $currentTheme['border'] ?> rounded-xl p-4 flex items-start gap-4 shadow-sm">
            <div class="bg-<?= $currentTheme['icon_bg'] ?> text-<?= $currentTheme['icon_text'] ?> p-2.5 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $currentTheme['icon'] ?>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-<?= $currentTheme['title_text'] ?> text-base uppercase tracking-tight">
                    <?= $sysStatus['status'] == 'operational' ? $currentTheme['label'] : htmlspecialchars($sysStatus['label'] ?? $currentTheme['label']) ?>
                </h3>
                <p class="text-sm text-<?= $currentTheme['body_text'] ?> mt-0.5 opacity-90">
                    <?= htmlspecialchars($sysStatus['message']) ?>
                </p>
            </div>
        </div>


        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">How can we help you today?</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <a href="submit_ticket.php?category=WiFi"
                    class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">WiFi Issue</span>
                </a>
                <a href="submit_ticket.php?category=Printer"
                    class="flex flex-col items-center justify-center p-4 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Printer</span>
                </a>
                <a href="submit_ticket.php?category=Software"
                    class="flex flex-col items-center justify-center p-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Computer/App</span>
                </a>

                <!-- TV Issue -->
                <a href="submit_ticket.php?category=TV"
                    class="flex flex-col items-center justify-center p-4 bg-red-50 hover:bg-red-100 text-red-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">TV/Remote</span>
                </a>

                <!-- Phone Issue -->
                <a href="submit_ticket.php?category=Phone"
                    class="flex flex-col items-center justify-center p-4 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Phone</span>
                </a>

                <!-- Workstation POS -->
                <a href="submit_ticket.php?category=POS"
                    class="flex flex-col items-center justify-center p-4 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm text-center">Workstation POS<br><span
                            class="text-xs font-normal opacity-75">Point of Sale</span></span>
                </a>

                <!-- Key Card Issue -->
                <a href="submit_ticket.php?category=KeyCard"
                    class="flex flex-col items-center justify-center p-4 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Key Card/Lock</span>
                </a>

                <!-- Power Issue -->
                <a href="submit_ticket.php?category=Power"
                    class="flex flex-col items-center justify-center p-4 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Power/Electric</span>
                </a>

                <!-- KOT Issue -->
                <a href="submit_ticket.php?category=KOT"
                    class="flex flex-col items-center justify-center p-4 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">KOT Printer</span>
                </a>

                <!-- Oracle MC -->
                <a href="submit_ticket.php?category=Oracle"
                    class="flex flex-col items-center justify-center p-4 bg-red-50 hover:bg-red-100 text-red-900 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Oracle MC</span>
                </a>

                <!-- Public Address -->
                <a href="submit_ticket.php?category=PublicAddress"
                    class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 text-blue-800 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Music / PA / Mic</span>
                </a>

                <!-- Projector -->
                <a href="submit_ticket.php?category=Projector"
                    class="flex flex-col items-center justify-center p-4 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Projector</span>
                </a>


                <!-- CCTV / Surveillance -->
                <a href="submit_ticket.php?category=CCTV"
                    class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 00-2 2z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm text-center">CCTV /<br>Surveillance</span>
                </a>

                <!-- Name Tag -->
                <a href="submit_ticket.php?category=NameTag"
                    class="flex flex-col items-center justify-center p-4 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Name Tag</span>
                </a>

                <!-- Staff ID Inquiry -->
                <a href="submit_ticket.php?category=StaffID"
                    class="flex flex-col items-center justify-center p-4 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14M9 10h.01M9 13h.01M9 16h.01">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm text-center">Staff ID<br>Inquiry</span>
                </a>

                <!-- Gym Equipment -->
                <a href="submit_ticket.php?category=Gym"
                    class="flex flex-col items-center justify-center p-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 7.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm9 0a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM3.75 12h16.5m-15 0a1.5 1.5 0 100 3h15a1.5 1.5 0 100-3H5.25zm1.5 3v3.75a.75.75 0 001.5 0V15h9v3.75a.75.75 0 001.5 0V15">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Gym Equipment</span>
                </a>

                <!-- Asset Request -->
                <a href="request_asset.php"
                    class="flex flex-col items-center justify-center p-4 bg-lime-50 hover:bg-lime-100 text-lime-700 rounded-xl transition-colors group">
                    <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm text-center">Event Asset<br><span
                            class="text-[10px] font-normal opacity-80 uppercase tracking-wider">Audio/Power</span></span>
                </a>

                <a href="submit_ticket.php?category=Other"
                    class="col-span-1 sm:col-span-1 flex flex-col items-center justify-center p-4 bg-gray-50 hover:bg-gray-100 text-slate-600 rounded-xl transition-colors border border-dashed border-gray-300">
                    <svg class="w-8 h-8 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    <span class="font-semibold text-sm">Other Issue</span>
                </a>
            </div>
        </div>



        <!-- Featured Knowledge Base -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-slate-800">Self-Help & Documentation</h2>
                <a href="knowledge_base.php" class="text-sm font-bold text-primary-600 hover:text-primary-700">View All
                    Guides &rarr;</a>
            </div>

            <?php if (count($recent_sops) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($recent_sops as $sop): ?>
                        <a href="knowledge_base.php"
                            class="block bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-primary-100 transition-all group">
                            <div class="flex items-start justify-between mb-2">
                                <span
                                    class="bg-primary-50 text-primary-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                                    <?= htmlspecialchars($sop['category'] ?: 'General') ?>
                                </span>
                                <span class="text-slate-400 text-xs">v<?= htmlspecialchars($sop['version']) ?></span>
                            </div>
                            <h3
                                class="font-bold text-slate-800 group-hover:text-primary-700 transition-colors mb-2 line-clamp-1">
                                <?= htmlspecialchars($sop['title']) ?>
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2">
                                <?= strip_tags($sop['content']) ?>
                            </p>
                            <div class="mt-3 text-[10px] text-slate-400 font-medium">
                                Updated <?= time_elapsed_string($sop['last_updated']) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-6 text-center">
                    <p class="text-slate-400 text-sm">No documentation available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar / Recent Tickets & Contacts -->
    <div class="space-y-6">

        <!-- Request Service Button -->
        <a href="service_request.php"
            class="block bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-600/30 p-4 transition-transform transform hover:-translate-y-0.5 group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-lg">Request Service</h3>
                    <p class="text-indigo-200 text-xs mt-1">New access, hardware, or WiFi?</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg group-hover:bg-white/30 transition-colors">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
            </div>
        </a>

        <!-- My Incident History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                My Incident History</h3>
            <div class="space-y-4">
                <?php if (count($my_tickets) > 0): ?>
                    <?php foreach ($my_tickets as $ticket): ?>
                        <div class="block p-3 rounded-lg border border-slate-50 transition-all bg-white shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <span
                                    class="bg-slate-100 text-slate-500 text-[10px] font-mono px-1.5 py-0.5 rounded">#<?= $ticket['id'] ?></span>
                                <span class="text-[10px] font-bold uppercase 
                                    <?php
                                    if ($ticket['status'] == 'open')
                                        echo 'text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full';
                                    elseif ($ticket['status'] == 'resolved')
                                        echo 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full';
                                    else
                                        echo 'text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full';
                                    ?>">
                                    <?= htmlspecialchars($ticket['status']) ?>
                                </span>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-700 leading-tight">
                                <?= htmlspecialchars($ticket['title']) ?>
                            </h4>
                            <div class="flex items-center justify-between mt-3">
                                <p class="text-[10px] text-slate-400">
                                    <?= time_elapsed_string($ticket['created_at']) ?>
                                </p>
                                <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                                    <form action="close_ticket.php" method="POST" class="inline">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                        <button type="submit"
                                            class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded transition-colors flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Confirm Solved
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-400 text-xs italic text-center py-4">You haven't submitted any tickets yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Maintenance Widget -->
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
                        $startTime = $maint['start_time'] ?? null;
                        $nextDueDate = $maint['next_due_date'] ?? null;
                        $m_start = $startTime ? new DateTime($startTime) : ($nextDueDate ? new DateTime($nextDueDate) : null);
                        $impact = $maint['impact'] ?? 'none';
                        $impactColor = match ($impact) {
                            'outage' => 'red',
                            'high' => 'orange',
                            'medium' => 'amber',
                            default => 'blue'
                        };
                        ?>
                        <div class="p-3 rounded-lg border border-<?= $impactColor ?>-50 bg-<?= $impactColor ?>-50/30">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[9px] font-black uppercase tracking-widest text-<?= $impactColor ?>-600">
                                    <?= str_replace('_', ' ', $impact) ?>
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

        <!-- IT Contact Directory (Sticky) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h3
                class="font-bold text-slate-800 text-sm uppercase tracking-wider mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                    </path>
                </svg>
                Contact Support
                <div class="ml-auto flex items-center gap-2">
                    <a href="contacts.php" title="View IT Directory" class="text-primary-500 hover:text-primary-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="/ict/modules/settings/index.php" title="Edit Contact Info"
                            class="text-primary-500 hover:text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                </path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </h3>

            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="bg-indigo-50 p-2 rounded-lg text-indigo-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase">IT Duty Mobile</p>
                        <p class="text-sm font-bold text-slate-800">
                            <?= htmlspecialchars(get_setting($pdo, 'contact_duty_mobile', '0743 606 108')) ?>
                        </p>
                        <p class="text-[10px] text-slate-400 italic">
                            <?= htmlspecialchars(get_setting($pdo, 'contact_duty_mobile_note', 'Calls only when unavailable in office')) ?>
                        </p>
                    </div>
                </div>

                <hr class="border-slate-50">

                <!-- Quick Feedback -->
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Rate your experience</h4>
                    <div class="flex justify-between gap-2" id="feedback-buttons">
                        <button onclick="submitFeedback('bad')"
                            class="flex-1 py-2 bg-slate-50 hover:bg-red-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                            title="Bad">😡</button>
                        <button onclick="submitFeedback('okay')"
                            class="flex-1 py-2 bg-slate-50 hover:bg-yellow-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                            title="Okay">😐</button>
                        <button onclick="submitFeedback('great')"
                            class="flex-1 py-2 bg-slate-50 hover:bg-emerald-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                            title="Great">😃</button>
                    </div>
                    <div id="feedback-message"
                        class="hidden text-center py-2 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg">
                        Thanks for your feedback!
                    </div>

                    <script>
                        function submitFeedback(rating) {
                            const buttons = document.getElementById('feedback-buttons');
                            const message = document.getElementById('feedback-message');

                            // Visual immediate feedback
                            buttons.style.opacity = '0.5';
                            buttons.style.pointerEvents = 'none';

                            fetch('record_feedback.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ rating: rating })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        buttons.classList.add('hidden');
                                        message.classList.remove('hidden');
                                    } else {
                                        // Reset on error
                                        buttons.style.opacity = '1';
                                        buttons.style.pointerEvents = 'auto';
                                        alert('Error saving feedback. Please try again.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    buttons.style.opacity = '1';
                                    buttons.style.pointerEvents = 'auto';
                                });
                        }
                    </script>
                </div>
            </div>
        </div>

        <?php
        renderPortalFooter();
        ?>
