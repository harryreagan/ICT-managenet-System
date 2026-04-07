<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireAdmin();

$type = $_GET['type'] ?? 'spending';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

if ($type === 'helpdesk') {
    fputcsv($output, ['ID', 'Title', 'Category', 'Priority', 'Status', 'Assigned To', 'Created Date']);
    $stmt = $pdo->query("SELECT id, title, system_affected, priority, status, assigned_to, created_at FROM troubleshooting_logs ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'assets') {
    fputcsv($output, ['Asset Name', 'Serial Number', 'Location', 'Department', 'Condition', 'Warranty Expiry']);
    $stmt = $pdo->query("SELECT name, serial_number, location, department, condition_status, warranty_expiry FROM hardware_assets");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'inventory') {
    fputcsv($output, ['Item Name', 'Category', 'Stock Level', 'Reorder Threshold', 'Unit Price', 'Status']);
    $stmt = $pdo->query("SELECT name, category, stock_level, reorder_threshold, unit_price, status FROM inventory_items");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'subscriptions') {
    fputcsv($output, ['ID', 'Service Name', 'Vendor', 'Renewal Date', 'Amount', 'Billing Cycle', 'Status', 'Payment Status']);
    $stmt = $pdo->query("SELECT r.id, r.service_name, v.name as vendor_name, r.renewal_date, r.amount_paid, r.billing_cycle, r.status, r.payment_status 
                         FROM renewals r 
                         JOIN vendors v ON r.vendor_id = v.id 
                         ORDER BY r.renewal_date ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} else {
    // Default: Spending
    fputcsv($output, ['Item Name', 'Requester', 'Estimated Cost', 'Status', 'Date Requested']);
    $stmt = $pdo->query("SELECT item_name, requester, estimated_cost, status, date_requested FROM procurement_requests");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
