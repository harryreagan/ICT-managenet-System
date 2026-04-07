<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Get date range (default: last 30 days)
$days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
$start_date = date('Y-m-d', strtotime("-$days days"));
$end_date = date('Y-m-d');

include '../../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Analytics & Reports</h1>
            <p class="text-slate-500 mt-2">Insights and metrics for incident management</p>
        </div>
        <div class="flex gap-3">
            <select id="dateRange" onchange="window.location.href='?days='+this.value"
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white text-slate-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 cursor-pointer">
                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Last 90 Days</option>
                <option value="365" <?php echo $days == 365 ? 'selected' : ''; ?>>Last Year</option>
            </select>
            <a href="index.php"
                class="inline-flex items-center px-6 py-2.5 bg-white text-slate-600 font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all shadow-sm text-sm">
                Back to Issues
            </a>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        // Total Issues
        $totalStmt = $pdo->prepare("SELECT COUNT(*) as count FROM troubleshooting_logs WHERE created_at >= ?");
        $totalStmt->execute([$start_date]);
        $totalIssues = $totalStmt->fetch()['count'];

        // Open Issues
        $openStmt = $pdo->prepare("SELECT COUNT(*) as count FROM troubleshooting_logs WHERE status IN ('open', 'in_progress') AND created_at >= ?");
        $openStmt->execute([$start_date]);
        $openIssues = $openStmt->fetch()['count'];

        // Resolved Issues
        $resolvedStmt = $pdo->prepare("SELECT COUNT(*) as count FROM troubleshooting_logs WHERE status = 'resolved' AND created_at >= ?");
        $resolvedStmt->execute([$start_date]);
        $resolvedIssues = $resolvedStmt->fetch()['count'];

        // Average Resolution Time (in hours)
        $avgTimeStmt = $pdo->prepare("SELECT AVG(total_time_spent) as avg_time FROM troubleshooting_logs WHERE status = 'resolved' AND created_at >= ?");
        $avgTimeStmt->execute([$start_date]);
        $avgTime = $avgTimeStmt->fetch()['avg_time'] ?? 0;
        ?>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Issues</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">
                        <?php echo $totalIssues; ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Open Issues</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">
                        <?php echo $openIssues; ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Resolved</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-2">
                        <?php echo $resolvedIssues; ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Avg. Resolution Time</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">
                        <?php echo number_format($avgTime, 1); ?>h
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Issues by Priority -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Issues by Priority</h3>
            <canvas id="priorityChart" height="250"></canvas>
        </div>

        <!-- Issues by Status -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Issues by Status</h3>
            <canvas id="statusChart" height="250"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Issues Over Time -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Issues Over Time</h3>
            <canvas id="trendChart" height="250"></canvas>
        </div>

        <!-- Issues by Technician -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Issues by Technician</h3>
            <canvas id="technicianChart" height="250"></canvas>
        </div>
    </div>

    <!-- Top Issues Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Recent Critical Issues</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Issue
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $criticalStmt = $pdo->prepare("SELECT * FROM troubleshooting_logs WHERE priority IN ('high', 'critical') AND created_at >= ? ORDER BY created_at DESC LIMIT 10");
                    $criticalStmt->execute([$start_date]);
                    $criticalIssues = $criticalStmt->fetchAll();

                    foreach ($criticalIssues as $issue):
                        $priorityColors = [
                            'low' => 'bg-gray-100 text-gray-700',
                            'medium' => 'bg-blue-100 text-blue-700',
                            'high' => 'bg-orange-100 text-orange-700',
                            'critical' => 'bg-red-100 text-red-700'
                        ];
                        $statusColors = [
                            'open' => 'bg-yellow-100 text-yellow-700',
                            'in_progress' => 'bg-blue-100 text-blue-700',
                            'resolved' => 'bg-emerald-100 text-emerald-700',
                            'closed' => 'bg-gray-100 text-gray-700'
                        ];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="view.php?id=<?php echo $issue['id']; ?>"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                    <?php echo htmlspecialchars($issue['title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full <?php echo $priorityColors[$issue['priority']]; ?>">
                                    <?php echo ucfirst($issue['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full <?php echo $statusColors[$issue['status']]; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <?php echo $issue['assigned_to'] ? ucfirst(str_replace('_', ' ', $issue['assigned_to'])) : 'Unassigned'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                <?php echo date('M j, Y', strtotime($issue['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Fetch analytics data
    fetch('api/analytics_data.php?days=<?php echo $days; ?>')
        .then(response => response.json())
        .then(data => {
            // Priority Chart (Doughnut)
            new Chart(document.getElementById('priorityChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Critical'],
                    datasets: [{
                        data: [data.priority.low, data.priority.medium, data.priority.high, data.priority.critical],
                        backgroundColor: ['#94a3b8', '#3b82f6', '#f97316', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Status Chart (Bar)
            new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                    datasets: [{
                        label: 'Issues',
                        data: [data.status.open, data.status.in_progress, data.status.resolved, data.status.closed],
                        backgroundColor: ['#fbbf24', '#3b82f6', '#10b981', '#6b7280'],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Trend Chart (Line)
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [{
                        label: 'Issues Created',
                        data: data.trend.values,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Technician Chart (Horizontal Bar)
            new Chart(document.getElementById('technicianChart'), {
                type: 'bar',
                data: {
                    labels: data.technician.labels,
                    datasets: [{
                        label: 'Issues Handled',
                        data: data.technician.values,
                        backgroundColor: '#8b5cf6',
                        borderRadius: 8
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
</script>

<?php include '../../includes/footer.php'; ?>