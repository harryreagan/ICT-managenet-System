<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $response = [
        'uptime' => '99.9%',
        'metrics' => [
            'tickets' => [
                'total' => 0,
                'resolved_rate' => 0
            ],
            'infrastructure' => [
                'healthy_assets' => 0,
                'total_assets' => 0
            ],
            'power' => [
                'status' => 'Optimal',
                'avg_load' => 0
            ]
        ]
    ];

    // 1. Ticket Metrics (Last 30 Days)
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved
        FROM troubleshooting_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $ticketStats = $stmt->fetch();
    $response['metrics']['tickets']['total'] = (int) $ticketStats['total'];
    if ($ticketStats['total'] > 0) {
        $response['metrics']['tickets']['resolved_rate'] = round(($ticketStats['resolved'] / $ticketStats['total']) * 100, 1);
    }

    // 2. Asset Metrics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN condition_status = 'good' THEN 1 ELSE 0 END) as healthy
        FROM hardware_assets");
    $assetStats = $stmt->fetch();
    $response['metrics']['infrastructure']['total_assets'] = (int) $assetStats['total'];
    $response['metrics']['infrastructure']['healthy_assets'] = (int) $assetStats['healthy'];

    // 3. Power Metrics
    $stmt = $pdo->query("SELECT 
        AVG(current_load_kw) as avg_load,
        SUM(CASE WHEN status != 'operational' THEN 1 ELSE 0 END) as alerts
        FROM power_systems");
    $powerStats = $stmt->fetch();
    $response['metrics']['power']['avg_load'] = round((float) $powerStats['avg_load'], 1);
    if ($powerStats['alerts'] > 0) {
        $response['metrics']['power']['status'] = 'Active Alerts';
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
