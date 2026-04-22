<?php
/**
 * Fetches all necessary data for the Admin Dashboard
 * 
 * @param PDO $pdo Database connection
 * @return array Associative array of dashboard data
 */
function getAdminDashboardData($pdo)
{
    $data = [];
    $hasQuickNotesTable = false;

    try {
        $hasQuickNotesTable = $pdo->query("SHOW TABLES LIKE 'quick_notes'")->rowCount() > 0;
    } catch (PDOException $e) {
        $hasQuickNotesTable = false;
    }

    // 1. Open Incidents Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('open', 'in_progress')");
    $data['openIncidents'] = $stmt->fetchColumn();

    // 2. Critical Incidents
    $stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status != 'closed' AND priority = 'critical'");
    $data['criticalIncidents'] = $stmt->fetchColumn();

    // 2b. Solved Tickets Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed')");
    $data['solvedCount'] = $stmt->fetchColumn();

    // Fetch Problematic Assets for Alerts
    $alertStmt = $pdo->query("SELECT name, condition_notes, id FROM hardware_assets WHERE condition_notes IS NOT NULL AND condition_notes != '' LIMIT 5");
    $data['problemAssets'] = $alertStmt->fetchAll();
    $data['problemCount'] = count($data['problemAssets']);

    // 3. Renewals Due (Next 30 Days)
    $stmt = $pdo->query("SELECT COUNT(*) FROM renewals WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'");
    $data['renewalsDue'] = $stmt->fetchColumn();

    // 3b. Subscription Analytics
    $stmt = $pdo->query("SELECT COUNT(*) FROM renewals WHERE status = 'active'");
    $data['totalSubscriptions'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM renewals WHERE status = 'active' AND payment_status = 'unpaid'");
    $data['unpaidSubscriptions'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(CASE 
        WHEN billing_cycle = 'monthly' THEN amount_paid * 12 
        ELSE amount_paid 
    END) FROM renewals WHERE status = 'active'");
    $data['annualSubscriptionSpend'] = $stmt->fetchColumn() ?: 0;

    // 4. Hardware Needing Service
    $stmt = $pdo->query("SELECT COUNT(*) FROM hardware_assets WHERE condition_status != 'working'");
    $data['hardwareIssues'] = $stmt->fetchColumn();

    // 5. Maintenance Pending
    $stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_tasks WHERE status = 'pending'");
    $data['maintenancePending'] = $stmt->fetchColumn();

    // 5b. Total Assets (for Health calculation)
    $stmt = $pdo->query("SELECT COUNT(*) FROM hardware_assets");
    $data['totalAssets'] = $stmt->fetchColumn();

    // 6. Recent Activities (Audit Log)
    $stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
    $data['recentActivities'] = $stmt->fetchAll();

    // 7. Upcoming Maintenance (Next 7 Days)
    $stmt = $pdo->query("SELECT * FROM maintenance_tasks WHERE (next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND status != 'completed' ORDER BY next_due_date ASC LIMIT 5");
    $data['upcomingMaintenance'] = $stmt->fetchAll();

    // 8. At Risk Backups
    $stmt = $pdo->query("SELECT COUNT(*) FROM backup_logs WHERE status != 'safe'");
    $data['atRiskBackups'] = $stmt->fetchColumn();

    // 9. Pending Procurement
    $stmt = $pdo->query("SELECT COUNT(*) FROM procurement_requests WHERE status IN ('requested', 'approved', 'ordered')");
    $data['pendingProcurementCount'] = $stmt->fetchColumn();

    // 10. WiFi Hotspots (not VLANs)
    $stmt = $pdo->query("SELECT * FROM networks WHERE is_wifi_hotspot = TRUE AND wifi_password IS NOT NULL AND wifi_password != '' ORDER BY hotspot_location ASC");
    $data['wifiNetworks'] = $stmt->fetchAll();

    // 13. Critical Floor Issues
    $stmt = $pdo->query("SELECT COUNT(*) FROM floors WHERE status != 'operational'");
    $data['floorIssues'] = $stmt->fetchColumn();

    // 16. Network Health (Real Data from Monitoring)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM static_devices WHERE status = 'online'
            UNION ALL
            SELECT id FROM ip_assignments WHERE status = 'static'
        ) as unified_online
    ");
    $data['onlineDevices'] = $stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM static_devices
            UNION ALL
            SELECT id FROM ip_assignments WHERE status = 'static' AND ip_address NOT IN (SELECT ip_address FROM static_devices)
        ) as unified_total
    ");
    $data['totalDevices'] = $stmt->fetchColumn();

    if ($data['totalDevices'] > 0) {
        $data['networkHealthPercent'] = round(($data['onlineDevices'] / $data['totalDevices']) * 100);
    } else {
        $data['networkHealthPercent'] = 100; // Assume perfect if nothing to monitor
    }

    // 20. User Management Stats
    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'active'");
    $data['activeUsersCount'] = $stmt->fetchColumn();

    // External Systems (Fail-safe)
    $data['externalLinks'] = [];
    try {
        $stmt = $pdo->query("SELECT * FROM external_links WHERE is_active = 1 LIMIT 6");
        $data['externalLinks'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Table might not exist in web env yet; suppress error to keep dashboard alive
        error_log("External Links table missing: " . $e->getMessage());
    }

    // 22. ICT Staff Metrics
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE department = 'IT' AND status = 'active'");
    $data['ictStaffCount'] = $stmt->fetchColumn();

    // 23. Staff Currently on Leave
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM ict_leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
    $data['staffOnLeave'] = $stmt->fetchColumn();

    // 17. Average Ticket Resolution Time (in days)
    $stmt = $pdo->query("SELECT AVG(DATEDIFF(NOW(), incident_date)) as avg_days FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $data['avgResolutionTime'] = round($stmt->fetchColumn() ?: 0, 1);

    // 18. Low Stock Inventory Items (below 10 units)
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE stock_level < 10");
    $data['lowStockCount'] = $stmt->fetchColumn();

    // 19. Monthly Ticket Trend (Last 6 Months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(incident_date, '%b') as month, COUNT(*) as count FROM troubleshooting_logs WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(incident_date) ORDER BY incident_date ASC");
    $data['monthlyTickets'] = $stmt->fetchAll();

    // 20. Top Performing Vendor (by SLA compliance)
    $stmt = $pdo->query("SELECT v.name, COUNT(t.id) as ticket_count, AVG(DATEDIFF(NOW(), t.incident_date)) as avg_resolution FROM vendors v LEFT JOIN troubleshooting_logs t ON v.id = t.vendor_id WHERE t.status IN ('resolved', 'closed') GROUP BY v.id ORDER BY avg_resolution ASC LIMIT 1");
    $data['topVendor'] = $stmt->fetch();

    // 21. Asset Health Breakdown (for chart)
    $stmt = $pdo->query("SELECT condition_status, COUNT(*) as count FROM hardware_assets GROUP BY condition_status");
    $data['assetHealthBreakdown'] = $stmt->fetchAll();

    // 21b. Ticket Priority Breakdown (for chart)
    $stmt = $pdo->query("SELECT priority, COUNT(*) as count FROM troubleshooting_logs GROUP BY priority");
    $data['priorityBreakdown'] = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT ip_address FROM static_devices
            UNION
            SELECT ip_address FROM ip_assignments WHERE status = 'static'
        ) as unified_ips
    ");
    $data['staticDevicesCount'] = $stmt->fetchColumn();

    // 30. Guest WiFi Details
    $stmt = $pdo->query("SELECT * FROM networks WHERE name LIKE '%Guest%' LIMIT 1");
    $guestWifi = $stmt->fetch();
    if (!$guestWifi) {
        $guestWifi = [
            'id' => 0,
            'hotspot_ssid' => 'Not Configured',
            'wifi_password' => 'Agg',
            'password_last_changed' => null
        ];
    }
    $data['guestWifi'] = $guestWifi;

    // 24. Pending Leave Requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM ict_leave_requests WHERE status = 'pending'");
    $data['pendingLeaveRequests'] = $stmt->fetchColumn();

    // 25. Upcoming Approved Leaves (Next 7 Days)
    $stmt = $pdo->query("SELECT l.*, u.full_name FROM ict_leave_requests l JOIN users u ON l.user_id = u.id WHERE l.status = 'approved' AND l.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY l.start_date ASC LIMIT 5");
    $data['upcomingLeaves'] = $stmt->fetchAll();

    // 27. Top Device Types
    $stmt = $pdo->query("
        SELECT type_name, COUNT(*) as count FROM (
            SELECT device_type as type_name FROM static_devices
            UNION ALL
            SELECT 'VLAN Assignment' as type_name FROM ip_assignments WHERE status = 'static' AND ip_address NOT IN (SELECT ip_address FROM static_devices)
        ) as combined_types
        GROUP BY type_name ORDER BY count DESC LIMIT 3
    ");
    $data['topDeviceTypes'] = $stmt->fetchAll();

    // 28. Knowledge Base Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed')");
    $data['totalKbArticles'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $data['newKbArticles'] = $stmt->fetchColumn();

    // 31. Quick Notes for Current User
    $data['userNotes'] = [];
    if ($hasQuickNotesTable) {
        $stmt = $pdo->prepare("SELECT * FROM quick_notes WHERE user_id = ? ORDER BY is_done ASC, created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $data['userNotes'] = $stmt->fetchAll();
    }

    // VLANs for Quick Access
    $vlanStmt = $pdo->query("SELECT * FROM networks WHERE vlan_tag IS NOT NULL ORDER BY vlan_tag ASC");
    $data['vlans'] = $vlanStmt->fetchAll();

    return $data;
}

?>
