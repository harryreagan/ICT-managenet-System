<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
$start_date = date('Y-m-d', strtotime("-$days days"));

try {
    // Issues by Priority
    $priorityStmt = $pdo->prepare("
        SELECT priority, COUNT(*) as count 
        FROM troubleshooting_logs 
        WHERE created_at >= ? 
        GROUP BY priority
    ");
    $priorityStmt->execute([$start_date]);
    $priorityData = $priorityStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $priority = [
        'low' => $priorityData['low'] ?? 0,
        'medium' => $priorityData['medium'] ?? 0,
        'high' => $priorityData['high'] ?? 0,
        'critical' => $priorityData['critical'] ?? 0
    ];

    // Issues by Status
    $statusStmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM troubleshooting_logs 
        WHERE created_at >= ? 
        GROUP BY status
    ");
    $statusStmt->execute([$start_date]);
    $statusData = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $status = [
        'open' => $statusData['open'] ?? 0,
        'in_progress' => $statusData['in_progress'] ?? 0,
        'resolved' => $statusData['resolved'] ?? 0,
        'closed' => $statusData['closed'] ?? 0
    ];

    // Issues Over Time (Trend)
    $trendStmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM troubleshooting_logs 
        WHERE created_at >= ? 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $trendStmt->execute([$start_date]);
    $trendData = $trendStmt->fetchAll();

    $trendLabels = [];
    $trendValues = [];
    foreach ($trendData as $row) {
        $trendLabels[] = date('M j', strtotime($row['date']));
        $trendValues[] = (int) $row['count'];
    }

    // Issues by Technician
    $technicianStmt = $pdo->prepare("
        SELECT 
            u.full_name as name, 
            COUNT(t.id) as count 
        FROM troubleshooting_logs t
        LEFT JOIN users u ON t.technician_id = u.id
        WHERE t.created_at >= ? AND u.full_name IS NOT NULL
        GROUP BY u.full_name 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $technicianStmt->execute([$start_date]);
    $technicianData = $technicianStmt->fetchAll();

    $technicianLabels = [];
    $technicianValues = [];
    foreach ($technicianData as $row) {
        $technicianLabels[] = $row['name'];
        $technicianValues[] = (int) $row['count'];
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'priority' => $priority,
        'status' => $status,
        'trend' => [
            'labels' => $trendLabels,
            'values' => $trendValues
        ],
        'technician' => [
            'labels' => $technicianLabels,
            'values' => $technicianValues
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>