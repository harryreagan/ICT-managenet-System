<?php
function renderDashboardScripts($data)
{
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Ticket Trend Chart ---
            const ticketTrendCtx = document.getElementById('ticketTrendChart').getContext('2d');
            const ticketData = <?php echo json_encode($data['monthlyTickets']); ?>;
            new Chart(ticketTrendCtx, {
                type: 'line',
                data: {
                    labels: ticketData.map(item => item.month),
                    datasets: [{
                        label: 'Incidents',
                        data: ticketData.map(item => item.count),
                        borderColor: '#b59454', // Primary 500
                        backgroundColor: 'rgba(181, 148, 84, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { display: false } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // --- Asset Health Chart ---
            const assetHealthCtx = document.getElementById('assetHealthChart').getContext('2d');
            const assetHealthData = <?php echo json_encode($data['assetHealthBreakdown']); ?>;
            const healthLabels = assetHealthData.map(item => item.condition_status);
            const healthCounts = assetHealthData.map(item => item.count);

            // Map colors to status
            // working -> emerald, damaged -> rose, maintenance -> amber, retired -> slate
            const healthColors = healthLabels.map(status => {
                if (status === 'working') return '#10b981'; // emerald-500
                if (status === 'damaged') return '#f43f5e'; // rose-500
                if (status === 'maintenance') return '#f59e0b'; // amber-500
                return '#94a3b8'; // slate-400
            });

            new Chart(assetHealthCtx, {
                type: 'doughnut',
                data: {
                    labels: healthLabels,
                    datasets: [{
                        data: healthCounts,
                        backgroundColor: healthColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } }
                    }
                }
            });

            // --- Priority Chart ---
            const priorityCtx = document.getElementById('priorityChart').getContext('2d');
            const priorityData = <?php echo json_encode($data['priorityBreakdown']); ?>;

            // Expected priorities: low, medium, high, critical
            const priorityMap = { 'low': 0, 'medium': 0, 'high': 0, 'critical': 0 };
            priorityData.forEach(item => {
                const key = item.priority.toLowerCase();
                if (priorityMap.hasOwnProperty(key)) priorityMap[key] = item.count;
            });

            new Chart(priorityCtx, {
                type: 'bar',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Critical'],
                    datasets: [{
                        label: 'Tickets',
                        data: [priorityMap.low, priorityMap.medium, priorityMap.high, priorityMap.critical],
                        backgroundColor: [
                            '#94a3b8', // slate-400
                            '#60a5fa', // blue-400
                            '#f59e0b', // amber-400
                            '#f43f5e'  // rose-500
                        ],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>
    <?php
}
?>