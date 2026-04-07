<?php
require_once 'config/database.php';
$queries = [
    "Incidents" => "SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('open', 'in_progress')",
    "Critical" => "SELECT COUNT(*) FROM troubleshooting_logs WHERE status != 'closed' AND priority = 'critical'",
    "Solved" => "SELECT COUNT(*) FROM troubleshooting_logs WHERE status IN ('resolved', 'closed')",
    "Renewals" => "SELECT COUNT(*) FROM renewals WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'",
    "Hardware Issues" => "SELECT COUNT(*) FROM hardware_assets WHERE condition_status != 'working'",
    "Maintenance" => "SELECT COUNT(*) FROM maintenance_tasks WHERE status = 'pending'",
    "Audit Log" => "SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5",
    "Procurement" => "SELECT COUNT(*) FROM procurement_requests WHERE status IN ('requested', 'approved', 'ordered')",
    "WiFi" => "SELECT * FROM networks WHERE is_wifi_hotspot = TRUE AND wifi_password IS NOT NULL AND wifi_password != '' ORDER BY hotspot_location ASC",
    "Floor Issues" => "SELECT COUNT(*) FROM floors WHERE status != 'operational'",
    "Spend" => "SELECT SUM(estimated_cost) FROM procurement_requests WHERE MONTH(date_requested) = MONTH(CURRENT_DATE()) AND YEAR(date_requested) = YEAR(CURRENT_DATE()) AND status != 'cancelled'",
    "Avg Resolution" => "SELECT AVG(DATEDIFF(NOW(), incident_date)) as avg_days FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    "Low Stock" => "SELECT COUNT(*) FROM inventory_items WHERE stock_level < 10",
    "Ticket Trend" => "SELECT DATE_FORMAT(incident_date, '%b') as month, COUNT(*) as count FROM troubleshooting_logs WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(incident_date) ORDER BY incident_date ASC",
    "Top Vendor" => "SELECT v.name, COUNT(t.id) as ticket_count, AVG(DATEDIFF(NOW(), t.incident_date)) as avg_resolution FROM vendors v LEFT JOIN troubleshooting_logs t ON v.id = t.vendor_id WHERE t.status IN ('resolved', 'closed') GROUP BY v.id ORDER BY avg_resolution ASC LIMIT 1",
    "Leave Metrics" => "SELECT COUNT(DISTINCT user_id) FROM ict_leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date"
];

foreach ($queries as $name => $sql) {
    $start = microtime(true);
    try {
        $pdo->query($sql);
        $time = round(microtime(true) - $start, 4);
        echo "$name: OK ({$time}s)\n";
    } catch (Exception $e) {
        echo "$name: ERROR - " . $e->getMessage() . "\n";
    }
}
echo "All queries tested.\n";
