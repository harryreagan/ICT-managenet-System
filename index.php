<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SESSION['role'] === 'staff') {
    header("Location: /ict/portal/index.php");
    exit();
}

$hasQuickNotesTable = false;
try {
    $hasQuickNotesTable = $pdo->query("SHOW TABLES LIKE 'quick_notes'")->rowCount() > 0;
} catch (PDOException $e) {
    $hasQuickNotesTable = false;
}

// --- POST HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_note' && $hasQuickNotesTable) {
            $stmt = $pdo->prepare("INSERT INTO quick_notes (user_id, content) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $_POST['note_content']]);
            exit();
        }
        if ($_POST['action'] === 'toggle_note' && $hasQuickNotesTable) {
            $stmt = $pdo->prepare("UPDATE quick_notes SET is_done = NOT is_done WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['note_id'], $_SESSION['user_id']]);
            exit();
        }
        if ($_POST['action'] === 'delete_note' && $hasQuickNotesTable) {
            $stmt = $pdo->prepare("DELETE FROM quick_notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['note_id'], $_SESSION['user_id']]);
            exit();
        }
    }
    if (isset($_POST['update_guest_wifi'])) {
        $stmt = $pdo->prepare("UPDATE networks SET hotspot_ssid = ?, wifi_password = ?, password_last_changed = NOW() WHERE id = ?");
        $stmt->execute([$_POST['wifi_ssid'], $_POST['wifi_password'], $_POST['network_id']]);
        header("Location: index.php");
        exit();
    }
}


// Alarms are now checked globally in header.php

// --- ANALYTICS QUERIES ---

// 1-2. Ticket Stats (Consolidated)
$ticketStats = $pdo->query("SELECT 
    SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status != 'closed' AND priority = 'critical' THEN 1 ELSE 0 END) as critical,
    SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as solved
    FROM troubleshooting_logs")->fetch();

$openIncidents = $ticketStats['open'] ?: 0;
$criticalIncidents = $ticketStats['critical'] ?: 0;
$solvedCount = $ticketStats['solved'] ?: 0;

// Fetch Problematic Assets for Alerts
$alertStmt = $pdo->query("SELECT name, condition_notes, id FROM hardware_assets WHERE condition_notes IS NOT NULL AND condition_notes != '' LIMIT 5");
$problemAssets = $alertStmt->fetchAll();
$problemCount = count($problemAssets);

// 3. Renewal Analytics (Consolidated)
$renewalStats = $pdo->query("SELECT 
    SUM(CASE WHEN renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active' THEN 1 ELSE 0 END) as due_30,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' AND payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid
    FROM renewals WHERE status = 'active'")->fetch();

$renewalsDue = $renewalStats['due_30'] ?: 0;
$totalSubscriptions = $renewalStats['total'] ?: 0;
$unpaidSubscriptions = $renewalStats['unpaid'] ?: 0;

$stmt = $pdo->query("SELECT SUM(CASE 
    WHEN billing_cycle = 'monthly' THEN amount_paid * 12 
    ELSE amount_paid 
END) FROM renewals WHERE status = 'active'");
$annualSubscriptionSpend = $stmt->fetchColumn() ?: 0;

// 4-5. Asset & Maintenance Basics (Consolidated)
$systemStats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM hardware_assets WHERE condition_status != 'working') as hardware_issues,
    (SELECT COUNT(*) FROM maintenance_tasks WHERE status = 'pending') as maintenance_pending,
    (SELECT COUNT(*) FROM hardware_assets) as total_assets
")->fetch();

$hardwareIssues = $systemStats['hardware_issues'] ?: 0;
$maintenancePending = $systemStats['maintenance_pending'] ?: 0;
$totalAssets = $systemStats['total_assets'] ?: 0;

// 6. Recent Activities (Audit Log)
$stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$recentActivities = $stmt->fetchAll();

// 7. Upcoming Maintenance (Next 7 Days)
$stmt = $pdo->query("SELECT * FROM maintenance_tasks WHERE (next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND status != 'completed' ORDER BY next_due_date ASC LIMIT 5");
$upcomingMaintenance = $stmt->fetchAll();

// --- EXPANSION QUERIES ---

// 8. At Risk Backups
$stmt = $pdo->query("SELECT COUNT(*) FROM backup_logs WHERE status != 'safe'");
$atRiskBackups = $stmt->fetchColumn();

// 9. Pending Procurement
$stmt = $pdo->query("SELECT COUNT(*) FROM procurement_requests WHERE status IN ('requested', 'approved', 'ordered')");
$pendingProcurementCount = $stmt->fetchColumn();

// 10. WiFi Hotspots (not VLANs)
$stmt = $pdo->query("SELECT * FROM networks WHERE is_wifi_hotspot = TRUE AND wifi_password IS NOT NULL AND wifi_password != '' ORDER BY hotspot_location ASC");
$wifiNetworks = $stmt->fetchAll();

// --- INFRASTRUCTURE QUERIES ---

// 11. Solar Power Status
$stmt = $pdo->query("SELECT * FROM power_systems WHERE system_type = 'Solar Array' LIMIT 1");
$solarStatus = $stmt->fetch();

// 12. UPS Status
$stmt = $pdo->query("SELECT * FROM power_systems WHERE system_type = 'UPS Cluster' LIMIT 1");
$upsStatus = $stmt->fetch();

// 13. Critical Floor Issues
$stmt = $pdo->query("SELECT COUNT(*) FROM floors WHERE status != 'operational'");
$floorIssues = $stmt->fetchColumn();

// 14. Asset Distribution (Top 5)
$stmt = $pdo->query("SELECT name as category, COUNT(*) as count FROM hardware_assets GROUP BY name ORDER BY count DESC LIMIT 5");
$assetDistribution = $stmt->fetchAll();

// 15. Monthly Procurement Spend (KES)
$stmt = $pdo->query("SELECT SUM(estimated_cost) FROM procurement_requests WHERE MONTH(date_requested) = MONTH(CURRENT_DATE()) AND YEAR(date_requested) = YEAR(CURRENT_DATE()) AND status != 'cancelled'");
$monthlySpend = $stmt->fetchColumn() ?: 0;

// --- NEW ANALYTICS QUERIES ---

// 16. Network Health (Real Data from Monitoring) - Optimized
$healthStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM static_devices WHERE status = 'online') + 
        (SELECT COUNT(*) FROM ip_assignments WHERE status = 'static') as online,
        (SELECT COUNT(*) FROM static_devices) + 
        (SELECT COUNT(*) FROM ip_assignments WHERE status = 'static' AND ip_address NOT IN (SELECT ip_address FROM static_devices)) as total
")->fetch();

$onlineDevices = $healthStats['online'] ?: 0;
$totalDevices = $healthStats['total'] ?: 0;

if ($totalDevices > 0) {
    $networkHealthPercent = round(($onlineDevices / $totalDevices) * 100);
} else {
    $networkHealthPercent = 100; // Assume perfect if nothing to monitor
}

// 17. Average Ticket Resolution Time (in days)
$stmt = $pdo->query("SELECT AVG(DATEDIFF(NOW(), incident_date)) as avg_days FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$avgResolutionTime = round($stmt->fetchColumn() ?: 0, 1);

// 18. Low Stock Inventory Items (below 10 units)
$stmt = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE stock_level < 10");
$lowStockCount = $stmt->fetchColumn();

// 19. Monthly Ticket Trend (Last 6 Months)
$stmt = $pdo->query("SELECT DATE_FORMAT(incident_date, '%b') as month, COUNT(*) as count FROM troubleshooting_logs WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(incident_date) ORDER BY incident_date ASC");
$monthlyTickets = $stmt->fetchAll();

// 20. Top Performing Vendor (by SLA compliance)
// User Management Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$activeUsersCount = $stmt->fetchColumn();

// External Systems (Fail-safe)
$externalLinks = [];
try {
    $stmt = $pdo->query("SELECT * FROM external_links WHERE is_active = 1 LIMIT 6");
    $externalLinks = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist in web env yet; suppress error to keep dashboard alive
    error_log("External Links table missing: " . $e->getMessage());
}

$stmt = $pdo->query("SELECT v.name, COUNT(t.id) as ticket_count, AVG(DATEDIFF(NOW(), t.incident_date)) as avg_resolution FROM vendors v LEFT JOIN troubleshooting_logs t ON v.id = t.vendor_id WHERE t.status IN ('resolved', 'closed') GROUP BY v.id ORDER BY avg_resolution ASC LIMIT 1");
$topVendor = $stmt->fetch();

// 21. Asset Health Breakdown (for chart)
$stmt = $pdo->query("SELECT condition_status, COUNT(*) as count FROM hardware_assets GROUP BY condition_status");
$assetHealthBreakdown = $stmt->fetchAll();

// 21b. Ticket Priority Breakdown (for chart)
$stmt = $pdo->query("SELECT priority, COUNT(*) as count FROM troubleshooting_logs GROUP BY priority");
$priorityBreakdown = $stmt->fetchAll();

$staffOnDuty = 0;
try {
    $staffStats = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM users WHERE (department LIKE '%IT%' OR department LIKE '%ICT%') AND status = 'active') as ict_count,
        (SELECT COUNT(DISTINCT user_id) FROM ict_leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date) as on_leave,
        (SELECT COUNT(*) FROM ict_leave_requests WHERE status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM users WHERE is_on_duty = 1 AND status = 'active') as duty_count
    ")->fetch();

    $ictStaffCount = $staffStats['ict_count'] ?: 0;
    $staffOnLeave = $staffStats['on_leave'] ?: 0;
    $pendingLeaveRequests = $staffStats['pending_requests'] ?: 0;
    $staffOnDuty = $staffStats['duty_count'] ?: 0;
} catch (PDOException $e) {
    // Column might be missing if migration hasn't run
    $staffStats = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM users WHERE (department LIKE '%IT%' OR department LIKE '%ICT%') AND status = 'active') as ict_count,
        (SELECT COUNT(DISTINCT user_id) FROM ict_leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date) as on_leave,
        (SELECT COUNT(*) FROM ict_leave_requests WHERE status = 'pending') as pending_requests
    ")->fetch();

    $ictStaffCount = $staffStats['ict_count'] ?: 0;
    $staffOnLeave = $staffStats['on_leave'] ?: 0;
    $pendingLeaveRequests = $staffStats['pending_requests'] ?: 0;
}

// Fetch Latest Handover Note
$latestHandover = null;
try {
    $stmt = $pdo->query("SELECT h.*, u.username, u.full_name FROM handover_notes h JOIN users u ON h.user_id = u.id WHERE h.status = 'active' ORDER BY h.created_at DESC LIMIT 1");
    $latestHandover = $stmt->fetch();
} catch (PDOException $e) {
    // Table might be missing
}

// 25. Upcoming Approved Leaves (Next 7 Days)
$stmt = $pdo->query("SELECT l.*, u.full_name FROM ict_leave_requests l JOIN users u ON l.user_id = u.id WHERE l.status = 'approved' AND l.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY l.start_date ASC LIMIT 5");
$upcomingLeaves = $stmt->fetchAll();

// 26. Static Devices Count (Unified)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT ip_address FROM static_devices
        UNION
        SELECT ip_address FROM ip_assignments WHERE status = 'static'
    ) as unified_ips
");
$staticDevicesCount = $stmt->fetchColumn();

// 27. Static Devices by Type (Unified)
$stmt = $pdo->query("
    SELECT type_name, COUNT(*) as count FROM (
        SELECT device_type as type_name FROM static_devices
        UNION ALL
        SELECT 'VLAN Assignment' as type_name FROM ip_assignments WHERE status = 'static' AND ip_address NOT IN (SELECT ip_address FROM static_devices)
    ) as combined_types
    GROUP BY type_name ORDER BY count DESC LIMIT 3
");
$topDeviceTypes = $stmt->fetchAll();

// 27.1 Recent Static Devices (Unified)
$stmt = $pdo->query("
    (SELECT device_name, ip_address, device_type, 'device' as source, created_at FROM static_devices)
    UNION 
    (SELECT device_name, ip_address, 'VLAN Assignment' as device_type, 'assignment' as source, created_at FROM networks n JOIN ip_assignments ia ON n.id = ia.network_id WHERE ia.status = 'static' AND ia.ip_address NOT IN (SELECT ip_address FROM static_devices))
    ORDER BY created_at DESC LIMIT 3
");
$recentStaticDevices = $stmt->fetchAll();

// 28. Knowledge Base Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed')");
$totalKbArticles = $stmt->fetchColumn();

// Articles added this month
$stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$newKbArticles = $stmt->fetchColumn();

// 29. External Systems
$stmt = $pdo->query("SELECT * FROM external_systems ORDER BY name ASC");
$externalSystems = $stmt->fetchAll();

// 30. Guest WiFi Details
$stmt = $pdo->query("SELECT * FROM networks WHERE name LIKE '%Guest%' LIMIT 1");
$guestWifi = $stmt->fetch();
if (!$guestWifi) {
    // Fallback if not found
    $guestWifi = [
        'id' => 0,
        'hotspot_ssid' => 'Not Configured',
        'wifi_password' => 'Agg',
        'password_last_changed' => null
    ];
}

// 31. Quick Notes for Current User
$userNotes = [];
if ($hasQuickNotesTable) {
    $stmt = $pdo->prepare("SELECT * FROM quick_notes WHERE user_id = ? ORDER BY is_done ASC, created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $userNotes = $stmt->fetchAll();
}

// 32. Facility Health Summary
$facilityStats = ['total' => 0, 'faulty' => 0, 'warning' => 0];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM facility_checks GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        $facilityStats[$row['status']] = $row['count'];
        $facilityStats['total'] += $row['count'];
    }

    $stmt = $pdo->query("SELECT * FROM facility_checks ORDER BY last_check_at DESC LIMIT 5");
    $recentChecks = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentChecks = [];
}

include 'includes/header.php';
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
        <a href="modules/hardware/index.php"
            class="flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold rounded-lg shadow-lg shadow-amber-500/20 transition-all hover:-translate-y-0.5">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
            </svg>
            ISSUE ASSET
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

<!-- Top Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-8 fade-in-up" style="animation-delay: 0.1s">

    <!-- 1. Incidents Card -->
    <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
        <div class="flex justify-between items-start text-left">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Active Incidents
                </p>
                <h3 class="text-xl font-bold text-slate-800 text-left">
                    <?php echo $openIncidents; ?>
                </h3>
            </div>
            <div
                class="p-2 <?php echo $criticalIncidents > 0 ? 'bg-red-50 text-red-600' : 'bg-primary-50 text-primary-600'; ?> rounded-lg">
                <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
            </div>
        </div>
        <div class="mt-2 flex items-center">
            <?php if ($criticalIncidents > 0): ?>
                <span class="text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded uppercase tracking-tight">
                    <?php echo $criticalIncidents; ?> Critical
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
                        <?php echo $solvedCount; ?>
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
                    <?php echo $renewalsDue; ?>
                </h3>
            </div>
            <div
                class="p-2 <?php echo $renewalsDue > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-300'; ?> rounded-lg">
                <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-2 flex justify-between items-center text-left">
            <span
                class="text-[9px] font-bold <?php echo $renewalsDue > 0 ? 'text-amber-600 bg-amber-50' : 'text-slate-400 bg-slate-50'; ?> px-1.5 py-0.5 rounded uppercase tracking-tight">Soon</span>
            <span class="text-[9px] font-bold text-slate-400"><?php echo $unpaidSubscriptions; ?> Unpaid</span>
        </div>
        <a href="modules/renewals" class="absolute inset-0 z-0 text-left"></a>
    </div>

    <!-- 4. Staffing Card -->
    <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
        <div class="flex justify-between items-start text-left">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">ICT Staff</p>
                <h3 class="text-xl font-bold text-slate-800 text-left">
                    <?php echo $ictStaffCount; ?>
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
                class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-tight"><?php echo $staffOnDuty; ?>
                On Duty</span>
            <span class="text-[9px] font-bold text-slate-400"><?php echo $staffOnLeave; ?> Leave</span>
        </div>
        <a href="modules/handover/index.php" class="absolute inset-0 z-0 text-left"></a>
    </div>

    <!-- 5. Assets Card -->
    <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
        <div class="flex justify-between items-start text-left">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Total Assets</p>
                <h3 class="text-xl font-bold text-slate-800 text-left">
                    <?php echo $totalAssets; ?>
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
            <span
                class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded uppercase tracking-tight"><?php echo $hardwareIssues; ?>
                alerts</span>
        </div>
        <a href="modules/hardware" class="absolute inset-0 z-0 text-left"></a>
    </div>

    <!-- 6. Static IPs Card -->
    <div class="saas-card p-3 relative overflow-hidden group hover:border-primary-300">
        <div class="flex justify-between items-start text-left">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Static IPs</p>
                <h3 class="text-xl font-bold text-slate-800 text-left">
                    <?php echo $staticDevicesCount; ?>
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
                    <?php echo $networkHealthPercent; ?>%
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
                <div class="bg-emerald-500 h-1 rounded-full" style="width: <?php echo $networkHealthPercent; ?>%"></div>
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
                    <?php echo $activeUsersCount; ?>
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

<!-- Main Split Container -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in-up" style="animation-delay: 0.2s">

    <!-- Left Column (2/3) -->
    <div class="lg:col-span-2 space-y-8 text-left">

        <!-- Facility Health Overview -->
        <div class="saas-card p-5 border-l-4 border-primary-500 bg-white shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-primary-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        Facility Health Hub
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Status of core ICT service areas (Solar, Charging, Gym, etc.)
                    </p>
                </div>
                <a href="modules/infrastructure"
                    class="text-[10px] font-black text-primary-600 hover:text-primary-700 uppercase tracking-widest transition-colors">View
                    Hub &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <?php
                $items_map = [
                    'solar' => ['name' => 'Solar', 'icon' => 'sun'],
                    'charging' => ['name' => 'Charging', 'icon' => 'lightning-bolt'],
                    'gym' => ['name' => 'Gym', 'icon' => 'globe-alt'],
                    'playground' => ['name' => 'Playground', 'icon' => 'wifi'],
                    'ac' => ['name' => 'Server AC', 'icon' => 'cog']
                ];

                $indexedChecks = [];
                foreach ($recentChecks as $rc) {
                    $indexedChecks[$rc['item_key']] = $rc;
                }

                foreach ($items_map as $key => $info):
                    $check = $indexedChecks[$key] ?? null;
                    $status = $check['status'] ?? 'unknown';
                    $color = $status == 'operational' ? 'emerald' : ($status == 'warning' ? 'amber' : ($status == 'faulty' ? 'rose' : 'slate'));
                    ?>
                    <a href="<?php echo $key == 'solar' ? 'modules/infrastructure' : 'modules/infrastructure/checkup.php?item=' . $key; ?>"
                        class="p-3 bg-<?php echo $color; ?>-50/50 rounded-xl border border-<?php echo $color; ?>-100 flex flex-col items-center text-center transition-all hover:shadow-md hover:-translate-y-1 group">
                        <span class="text-<?php echo $color; ?>-600 mb-1 group-hover:scale-110 transition-transform">
                            <?php if ($key == 'solar'): ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                                    </path>
                                </svg>
                            <?php elseif ($key == 'charging'): ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            <?php endif; ?>
                        </span>
                        <span class="text-[9px] font-black text-slate-700 uppercase tracking-tighter">
                            <?php echo $info['name']; ?>
                        </span>
                        <span
                            class="text-[8px] font-bold text-<?php echo $color; ?>-600 uppercase mt-1 px-1.5 py-0.5 bg-white rounded-full group-hover:bg-<?php echo $color; ?>-600 group-hover:text-white transition-colors">
                            <?php echo $status; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($facilityStats['faulty'] > 0 || $facilityStats['warning'] > 0): ?>
                <div class="mt-4 p-3 bg-rose-50 border border-rose-100 rounded-lg flex items-center">
                    <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mr-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-rose-800 uppercase tracking-tight">Active Infrastructure Alerts
                        </h4>
                        <p class="text-[10px] text-rose-600"><?php echo $facilityStats['faulty']; ?> Systems Faulty,
                            <?php echo $facilityStats['warning']; ?> Warnings detected.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Latest Handover Note -->
        <?php if ($latestHandover): ?>
            <div class="saas-card p-5 border-amber-500/40 bg-white shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Latest Handover
                    </h3>
                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">
                        <?php echo htmlspecialchars($latestHandover['note_category']); ?> •
                        <?php echo time_elapsed_string($latestHandover['latest_created_at'] ?? $latestHandover['created_at']); ?>
                    </span>
                </div>
                <div class="flex items-start space-x-4">
                    <div
                        class="w-11 h-11 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-sm flex-shrink-0 border border-amber-100 shadow-sm">
                        <?php echo strtoupper(substr($latestHandover['username'], 0, 2)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-bold text-slate-700 mb-1">
                            <?php echo htmlspecialchars($latestHandover['full_name']); ?> says:
                        </p>
                        <p class="text-sm text-slate-600 leading-relaxed line-clamp-2 italic">
                            "<?php echo htmlspecialchars($latestHandover['content']); ?>"
                        </p>
                        <div class="mt-4 flex space-x-6">
                            <a href="modules/handover/index.php"
                                class="text-[10px] font-black text-amber-600 hover:text-amber-700 uppercase tracking-[0.15em] transition-colors">View
                                Full Feed</a>
                            <a href="modules/handover/create.php"
                                class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase tracking-[0.15em] transition-colors">New
                                Handover</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Network Quick Access -->
        <div class="saas-card p-4 text-left">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center text-left">
                <svg class="w-4 h-4 mr-2 text-primary-500 text-left" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Network Quick Access
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                <?php
                // Fetch All Networks that are VLANS (have a vlan_tag)
                $vlanStmt = $pdo->query("SELECT * FROM networks WHERE vlan_tag IS NOT NULL ORDER BY vlan_tag ASC");
                $vlans = $vlanStmt->fetchAll();

                foreach ($vlans as $vlan):
                    // Determine icon based on name
                    $icon_class = "bg-blue-100 text-blue-600";
                    $svg_path = 'd="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"'; // Default LAN icon
                
                    if (stripos($vlan['name'], 'VoIP') !== false) {
                        $icon_class = "bg-indigo-100 text-indigo-600";
                        $svg_path = 'd="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"';
                    } elseif (stripos($vlan['name'], 'CCTV') !== false || stripos($vlan['name'], 'Camera') !== false) {
                        $icon_class = "bg-rose-100 text-rose-600";
                        $svg_path = 'd="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"';
                    } elseif (stripos($vlan['name'], 'Guest') !== false || stripos($vlan['name'], 'WiFi') !== false) {
                        $icon_class = "bg-amber-100 text-amber-600";
                        $svg_path = 'd="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0"';
                    }
                    ?>
                    <a href="modules/networks/view.php?id=<?php echo $vlan['id']; ?>"
                        class="flex items-center p-3 bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 rounded-lg transition-all group shadow-sm hover:shadow-md text-left">
                        <div
                            class="w-10 h-10 rounded-lg <?php echo $icon_class; ?> flex items-center justify-center mr-3 text-left">
                            <svg class="w-5 h-5 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" <?php echo $svg_path; ?>></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-700 text-sm group-hover:text-primary-600 text-left">
                                <?php echo htmlspecialchars($vlan['name']); ?>
                            </h4>
                            <p class="text-[10px] text-slate-400 font-mono text-left">
                                <?php echo htmlspecialchars($vlan['gateway'] ?: $vlan['subnet']); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- External Systems Quick Access -->
        <?php if (!empty($externalLinks)): ?>
            <div class="saas-card p-4 text-left">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center text-left">
                        <svg class="w-4 h-4 mr-2 text-primary-500 text-left" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                            </path>
                        </svg>
                        External Systems
                    </h3>
                    <a href="modules/external_links/index.php"
                        class="text-[10px] font-bold text-primary-600 hover:text-primary-700">Manage</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                    <?php foreach ($externalLinks as $link): ?>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"
                            class="flex items-center p-3 bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 rounded-lg transition-all group shadow-sm hover:shadow-md text-left">
                            <div
                                class="w-10 h-10 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center mr-3 text-left group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors">
                                <!-- Simple Icon Logic based on name/category -->
                                <?php if (stripos($link['name'], 'wifi') !== false || $link['category'] == 'Network'): ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0">
                                        </path>
                                    </svg>
                                <?php elseif (stripos($link['name'], 'camera') !== false || $link['category'] == 'Security'): ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-bold text-slate-700 text-sm group-hover:text-primary-600 text-left truncate"
                                    title="<?php echo htmlspecialchars($link['name']); ?>">
                                    <?php echo htmlspecialchars($link['name']); ?>
                                </h4>
                                <p class="text-[10px] text-slate-400 font-mono text-left truncate">
                                    <?php echo htmlspecialchars(parse_url($link['url'], PHP_URL_HOST)); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Guest WiFi Widget -->
        <div class="saas-card p-5 text-left bg-gradient-to-br from-primary-50 to-white border-primary-100">
            <div class="flex justify-between items-start mb-4 text-left">
                <div class="flex items-center text-left">
                    <div
                        class="w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center mr-3 text-left">
                        <svg class="w-6 h-6 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 text-left">Guest WiFi</h3>
                        <p class="text-[10px] text-slate-500 text-left">
                            <?php
                            if ($guestWifi['password_last_changed']) {
                                echo 'Updated ' . time_elapsed_string($guestWifi['password_last_changed']);
                            } else {
                                echo 'Last updated: Never';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <button onclick="document.getElementById('guestWifiModal').classList.remove('hidden')"
                    class="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors text-left">
                    <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-2 text-left">
                <div class="bg-white/60 p-3 rounded-lg border border-primary-100 text-left">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Network
                        Name (SSID)</p>
                    <p class="font-bold text-slate-800 text-lg text-left">
                        <?php echo htmlspecialchars($guestWifi['hotspot_ssid'] ?: 'Not Set'); ?>
                    </p>
                </div>
                <div class="bg-white/60 p-3 rounded-lg border border-primary-100 text-left">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Password
                    </p>
                    <div class="flex items-center justify-between text-left">
                        <p class="font-mono font-bold text-primary-600 text-lg text-left">
                            <?php echo htmlspecialchars($guestWifi['wifi_password'] ?: '---'); ?>
                        </p>
                        <button onclick="copyToClipboard('<?php echo addslashes($guestWifi['wifi_password']); ?>')"
                            class="text-slate-400 hover:text-primary-600 text-left">
                            <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
                    <span
                        class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded text-left">Severity</span>
                </div>
                <div class="h-48 text-left">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column (1/3) -->
    <div class="space-y-8 text-left">

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


        <!-- Subscription Economics Widget -->
        <div class="saas-card overflow-hidden">
            <div class="p-5 bg-gradient-to-br from-slate-900 to-slate-800 text-white">
                <div class="flex justify-between items-center mb-0">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Subscription
                            Metrics</p>
                        <h3 class="text-lg font-bold text-primary-400">Active Monitoring</h3>
                    </div>
                    <div class="p-2 bg-slate-700/50 rounded-lg">
                        <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-13c-1.11 0-2.08.402-2.599 1M12 8V7m0 1v8m0 0v1m0-1c1.11 0 2.08-.402 2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-4 mt-4 border-t border-slate-700">
                    <div>
                        <p class="text-[9px] font-bold text-slate-500 uppercase">Active Plans</p>
                        <p class="text-lg font-bold"><?php echo $totalSubscriptions; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-bold text-slate-500 uppercase">Pending Due</p>
                        <p class="text-lg font-bold text-amber-400"><?php echo $unpaidSubscriptions; ?></p>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white">
                <a href="modules/renewals" class="flex items-center justify-between group">
                    <span
                        class="text-[11px] font-bold text-slate-600 uppercase group-hover:text-primary-600 transition-colors">Manage
                        Subscriptions</span>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-primary-500 transition-transform group-hover:translate-x-1"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Recent Activity (Audit Logs) -->
        <div class="saas-card p-5 text-left">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Recent System Activity</h3>
                <a href="<?php echo BASE_URL; ?>/modules/audit/index.php"
                    class="text-[10px] font-bold text-primary-600 hover:text-primary-700 uppercase tracking-widest transition-colors">Full
                    Trail &rarr;</a>
            </div>
            <div class="space-y-4">
                <?php foreach ($recentActivities as $activity):
                    $userInitial = strtoupper(substr($activity['username'] ?? 'S', 0, 1));
                    $time = time_elapsed_string($activity['created_at']);
                    ?>
                    <div class="flex items-start gap-3 group">
                        <div
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold flex-shrink-0 group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors">
                            <?php echo $userInitial; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700 truncate">
                                <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                <span
                                    class="font-normal text-slate-400 ml-1 italic"><?php echo htmlspecialchars($activity['action']); ?></span>
                            </p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">
                                <?php echo htmlspecialchars($activity['details']); ?>
                            </p>
                            <p class="text-[9px] text-slate-300 mt-1 uppercase tracking-tighter">
                                <?php echo $time; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recentActivities)): ?>
                    <p class="text-center text-slate-400 text-xs italic py-4">No recent activity found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Notes / To-Do List -->
        <div class="saas-card p-5" x-data="{ 
            notes: <?php echo htmlspecialchars(json_encode($userNotes)); ?>, 
            newNote: '',
            addNote() {
                if (this.newNote.trim() === '') return;
                let formData = new FormData();
                formData.append('action', 'add_note');
                formData.append('note_content', this.newNote);
                fetch('index.php', { method: 'POST', body: formData }).then(() => {
                    location.reload();
                });
            },
            toggleNote(id) {
                let formData = new FormData();
                formData.append('action', 'toggle_note');
                formData.append('note_id', id);
                fetch('index.php', { method: 'POST', body: formData });
            },
            deleteNote(id) {
                if (!confirm('Delete this note?')) return;
                let formData = new FormData();
                formData.append('action', 'delete_note');
                formData.append('note_id', id);
                fetch('index.php', { method: 'POST', body: formData }).then(() => {
                    location.reload();
                });
            }
        }">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">IT Workflow / To-Do</h3>
                <span class="text-[10px] font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full"
                    x-text="notes.filter(n => !n.is_done).length + ' Pending'"></span>
            </div>

            <!-- Add Note Input -->
            <div class="mb-6 relative">
                <input type="text" x-model="newNote" @keydown.enter="addNote()" placeholder="Quick add a task..."
                    class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all focus:bg-white">
                <button @click="addNote()"
                    class="absolute right-2 top-1.5 p-1.5 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Notes List -->
            <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                <template x-for="note in notes" :key="note.id">
                    <div
                        class="flex items-center group bg-slate-50/50 p-3 rounded-xl border border-transparent hover:border-slate-100 hover:bg-white transition-all">
                        <label class="flex items-center flex-grow cursor-pointer">
                            <input type="checkbox" :checked="note.is_done == 1"
                                @change="toggleNote(note.id); note.is_done = !note.is_done"
                                class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500 transition-all">
                            <span class="ml-3 text-sm transition-all"
                                :class="note.is_done == 1 ? 'line-through text-slate-400' : 'text-slate-700 font-medium'"
                                x-text="note.content"></span>
                        </label>
                        <button @click="deleteNote(note.id)"
                            class="opacity-0 group-hover:opacity-100 p-1 text-slate-300 hover:text-red-500 transition-all ml-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                </template>

                <div x-show="notes.length === 0" class="py-12 text-center text-slate-400 text-xs italic">
                    <p>No tasks yet. Stay productive!</p>
                </div>
            </div>
        </div>

        <!-- Inventory Summary -->
        <div class="saas-card p-5 text-left">
            <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6 text-left">Asset Distribution
            </h3>
            <div class="space-y-4 text-left">
                <?php foreach ($assetDistribution as $item):
                    $percent = ($totalAssets > 0) ? ($item['count'] / $totalAssets * 100) : 0;
                    ?>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="font-extrabold text-slate-700 uppercase tracking-tight">
                                <?php echo htmlspecialchars($item['category']); ?>
                            </span>
                            <span class="font-bold text-slate-400">
                                <?php echo $item['count']; ?> Units
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 h-1 rounded-full overflow-hidden">
                            <div class="bg-primary-500 h-1 rounded-full" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Charts Initialization
        const ticketCtx = document.getElementById('ticketTrendChart').getContext('2d');
        const ticketData = <?php echo json_encode($monthlyTickets); ?>;

        new Chart(ticketCtx, {
            type: 'line',
            data: {
                labels: ticketData.map(item => item.month),
                datasets: [{
                    label: 'Tickets',
                    data: ticketData.map(item => item.count),
                    borderColor: '#b59454',
                    backgroundColor: 'rgba(181, 148, 84, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                }
            }
        });

        const assetCtx = document.getElementById('assetHealthChart').getContext('2d');
        const assetData = <?php echo json_encode($assetHealthBreakdown); ?>;

        new Chart(assetCtx, {
            type: 'doughnut',
            data: {
                labels: assetData.map(item => item.condition_status),
                datasets: [{
                    data: assetData.map(item => item.count),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } }
                }
            }
        });

        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        const priorityData = <?php echo json_encode($priorityBreakdown); ?>;

        new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: priorityData.map(item => item.priority.toUpperCase()),
                datasets: [{
                    data: priorityData.map(item => item.count),
                    backgroundColor: priorityData.map(item => {
                        const p = item.priority.toLowerCase();
                        if (p === 'critical') return '#e11d48';
                        if (p === 'high') return '#f59e0b';
                        if (p === 'medium') return '#3b82f6';
                        return '#94a3b8';
                    }),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } }
                }
            }
        });
    });

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function () {
            // Optional: Show toast
        }, function (err) {
            console.error('Async: Could not copy text: ', err);
        });
    }
</script>

<!-- Guest WiFi Modal -->
<div id="guestWifiModal"
    class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden fade-in-up">
        <form method="POST" class="p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Update Guest WiFi</h3>
            <input type="hidden" name="network_id" value="<?php echo htmlspecialchars($guestWifi['id']); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Network
                        Name
                        (SSID)</label>
                    <input type="text" name="wifi_ssid"
                        value="<?php echo htmlspecialchars($guestWifi['hotspot_ssid']); ?>" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">New
                        Password</label>
                    <input type="text" name="wifi_password"
                        value="<?php echo htmlspecialchars($guestWifi['wifi_password']); ?>" required
                        class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 outline-none font-mono">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('guestWifiModal').classList.add('hidden')"
                    class="px-4 py-2 text-slate-400 hover:text-slate-600 font-medium">Cancel</button>
                <button type="submit" name="update_guest_wifi"
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-bold shadow-md shadow-primary-200 transition-all">Update
                    Details</button>
            </div>
        </form>
    </div>
</div>
<?php
include 'includes/footer.php';
?>
