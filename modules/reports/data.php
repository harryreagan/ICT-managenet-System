<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
error_reporting(0); // Suppress warnings preventing JSON corruption
ini_set('display_errors', 0);

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    $response = [
        'helpdesk' => [],
        'assets' => [],
        'financials' => []
    ];

    // --- HELPDESK METRICS ---

    // Status Distribution
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM troubleshooting_logs WHERE date(created_at) BETWEEN ? AND ? GROUP BY status");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['status_dist'] = $stmt->fetchAll();

    // Priority Distribution
    $stmt = $pdo->prepare("SELECT priority, COUNT(*) as count FROM troubleshooting_logs WHERE date(created_at) BETWEEN ? AND ? GROUP BY priority");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['priority_dist'] = $stmt->fetchAll();

    // Category Distribution
    $stmt = $pdo->prepare("SELECT system_affected as category, COUNT(*) as count FROM troubleshooting_logs WHERE date(created_at) BETWEEN ? AND ? GROUP BY system_affected");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['category_dist'] = $stmt->fetchAll();

    // Daily Volume (Line Chart)
    $stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM troubleshooting_logs WHERE date(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date ASC");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['daily_volume'] = $stmt->fetchAll();

    // Tech Performance (Resolved Tickets)
    $stmt = $pdo->prepare("SELECT assigned_to, COUNT(*) as count FROM troubleshooting_logs WHERE status IN ('resolved', 'closed') AND date(created_at) BETWEEN ? AND ? GROUP BY assigned_to");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['tech_performance'] = $stmt->fetchAll();

    // Department Distribution (New)
    $stmt = $pdo->prepare("
        SELECT u.department, COUNT(l.id) as count 
        FROM troubleshooting_logs l
        JOIN users u ON l.requester_username = u.username
        WHERE date(l.created_at) BETWEEN ? AND ?
        GROUP BY u.department
    ");
    $stmt->execute([$startDate, $endDate]);
    $response['helpdesk']['department_dist'] = $stmt->fetchAll();

    // Resolution Time (Avg in Hours) - Simplistic calculation
    // Assuming we don't have a structured 'resolution_time' column yet, skipping or using a rough estimate if 'incident_date' and 'updated_at' exist. 
    // For now, let's skip complex duration math in SQL and just return raw counts.


    // --- ASSET METRICS ---

    // Condition Status
    $stmt = $pdo->query("SELECT condition_status, COUNT(*) as count FROM hardware_assets GROUP BY condition_status");
    $response['assets']['condition_dist'] = $stmt->fetchAll();

    // Total Inventory Value
    $stmt = $pdo->query("SELECT SUM(stock_level * unit_price) as total_value FROM inventory_items");
    $response['assets']['inventory_value'] = $stmt->fetchColumn();


    // --- FINANCIAL METRICS ---

    // Procurement Spending
    $stmt = $pdo->query("SELECT status, SUM(estimated_cost) as total FROM procurement_requests GROUP BY status");
    $response['financials']['spending_dist'] = $stmt->fetchAll();

    // --- SUBSCRIPTION ANALYTICS ---
    $response['subscriptions'] = [];

    // 1. Status Distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM renewals GROUP BY status");
    $response['subscriptions']['status_dist'] = $stmt->fetchAll();

    // 2. Billing Cycle Distribution
    $stmt = $pdo->query("SELECT billing_cycle, COUNT(*) as count FROM renewals WHERE status = 'active' GROUP BY billing_cycle");
    $response['subscriptions']['billing_dist'] = $stmt->fetchAll();

    // 3. Vendor Distribution
    $stmt = $pdo->query("SELECT v.name as vendor_name, COUNT(r.id) as count 
                         FROM renewals r 
                         JOIN vendors v ON r.vendor_id = v.id 
                         WHERE r.status = 'active' 
                         GROUP BY r.vendor_id 
                         ORDER BY count DESC 
                         LIMIT 5");
    $response['subscriptions']['vendor_dist'] = $stmt->fetchAll();

    // 4. Renewal Timeline (Next 12 Months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(renewal_date, '%Y-%m') as month_key, DATE_FORMAT(renewal_date, '%b %Y') as month_label, COUNT(*) as count 
                         FROM renewals 
                         WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 12 MONTH) 
                         AND status = 'active'
                         GROUP BY month_key 
                         ORDER BY month_key ASC");
    $response['subscriptions']['renewal_timeline'] = $stmt->fetchAll();

    // 5. Unpaid vs Paid (Active only)
    $stmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM renewals WHERE status = 'active' GROUP BY payment_status");
    $response['subscriptions']['payment_status_dist'] = $stmt->fetchAll();


    // --- FACILITY & POWER METRICS ---
    $response['facility_power'] = [];

    // 1. Facility Status Distribution (Overall)
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM facility_checks GROUP BY status");
    $response['facility_power']['status_dist'] = $stmt->fetchAll();

    // 2. Checkup History Trend (Last 30 Days)
    $stmt = $pdo->prepare("SELECT DATE(checked_at) as date, COUNT(*) as count 
                           FROM facility_check_logs 
                           WHERE checked_at BETWEEN ? AND ? 
                           GROUP BY DATE(checked_at) 
                           ORDER BY date ASC");
    $stmt->execute([$startDate, $endDate]);
    $response['facility_power']['check_trend'] = $stmt->fetchAll();

    // 3. Status Breakdown by Item Category
    $stmt = $pdo->query("SELECT item_name, status, last_check_at FROM facility_checks");
    $response['facility_power']['item_statuses'] = $stmt->fetchAll();

    // 4. Power System Load Metrics
    $stmt = $pdo->query("SELECT name, current_load_kw, battery_percentage, status FROM power_systems");
    $response['facility_power']['power_metrics'] = $stmt->fetchAll();

    // 5. Critical Alerts Count (Facility Items)
    $stmt = $pdo->query("SELECT COUNT(*) FROM facility_checks WHERE status = 'faulty'");
    $response['facility_power']['critical_count'] = (int) $stmt->fetchColumn();

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>