<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Analytics Hub";
include '../../includes/header.php';
?>

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500" id="reportTabs" role="tablist">
        <li class="mr-2" role="presentation">
            <button
                class="inline-block p-4 border-b-2 rounded-t-lg active hover:text-gray-600 hover:border-gray-300 border-primary-600 text-primary-600"
                id="helpdesk-tab" data-tabs-target="#helpdesk" type="button" role="tab" aria-controls="helpdesk"
                aria-selected="true">
                Helpdesk Operations
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button
                class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                id="assets-tab" data-tabs-target="#assets" type="button" role="tab" aria-controls="assets"
                aria-selected="false">
                Assets & Infrastructure
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button
                class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                id="finance-tab" data-tabs-target="#finance" type="button" role="tab" aria-controls="finance"
                aria-selected="false">
                Financials
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button
                class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                id="subscriptions-tab" data-tabs-target="#subscriptions" type="button" role="tab"
                aria-controls="subscriptions" aria-selected="false">
                Subscriptions & Renewals
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button
                class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                id="facility-tab" data-tabs-target="#facility" type="button" role="tab" aria-controls="facility"
                aria-selected="false">
                Facility & Power
            </button>
        </li>
    </ul>
</div>

<!-- Filters -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date Range</label>
        <div class="flex items-center gap-2">
            <input type="date" id="startDate"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5"
                value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            <span class="text-gray-400">to</span>
            <input type="date" id="endDate"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5"
                value="<?php echo date('Y-m-d'); ?>">
        </div>
    </div>
    <div class="flex items-center gap-4">
        <button onclick="fetchReportData()"
            class="bg-primary-600 text-white hover:bg-primary-700 font-bold py-2.5 px-6 rounded-lg text-sm transition-colors shadow-lg shadow-primary-500/30">
            Update Reports
        </button>
        <div class="relative group">
            <button
                class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2.5 px-4 rounded-lg text-sm transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </button>
            <div
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50">
                <a href="export_csv.php?type=helpdesk"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Export
                    Tickets</a>
                <a href="export_csv.php?type=assets"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Export
                    Assets</a>
                <a href="export_csv.php?type=subscriptions"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Export
                    Subscriptions</a>
                <a href="export_csv.php?type=inventory"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Export
                    Inventory</a>
                <a href="export_csv.php?type=spending"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Export
                    Spending</a>
            </div>
        </div>
    </div>
</div>

<!-- Tab Content -->
<div id="tabContent">

    <!-- HELPDESK TAB -->
    <div class="hidden p-4 rounded-lg bg-gray-50" id="helpdesk" role="tabpanel" aria-labelledby="helpdesk-tab">

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Tickets</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="totalTickets">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Resolved</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-2" id="resolvedTickets">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Open / Pending</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-2" id="openTickets">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Critical Issues</p>
                <h3 class="text-2xl font-bold text-red-600 mt-2" id="criticalTickets">--</h3>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 lg:col-span-2">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Ticket Volume Trend</h4>
                <div class="h-64">
                    <canvas id="volumeChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Status Distribution</h4>
                <div class="h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Issues by Category</h4>
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Technician Performance</h4>
                <div class="h-64">
                    <canvas id="techChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Tickets by Department</h4>
                <div class="h-64">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ASSETS TAB -->
    <div class="hidden p-4 rounded-lg bg-gray-50" id="assets" role="tabpanel" aria-labelledby="assets-tab">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Asset Condition</h4>
                <div class="h-64">
                    <canvas id="conditionChart"></canvas>
                </div>
            </div>
            <div
                class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center items-center text-center">
                <p class="text-sm font-bold text-gray-400 uppercase">Total Inventory Value</p>
                <h3 class="text-4xl font-bold text-primary-600 mt-4" id="inventoryValue">--</h3>
                <p class="text-xs text-gray-400 mt-2">Based on current stock levels</p>
            </div>
        </div>
    </div>

    <!-- FINANCE TAB -->
    <div class="hidden p-4 rounded-lg bg-gray-50" id="finance" role="tabpanel" aria-labelledby="finance-tab">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Procurement Spending</h4>
                <div class="h-64">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- SUBSCRIPTIONS TAB -->
    <div class="hidden p-4 rounded-lg bg-gray-50" id="subscriptions" role="tabpanel"
        aria-labelledby="subscriptions-tab">
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Active Subscriptions</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="kpi-total-subs">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Expiring (30d)</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-2" id="kpi-expiring-subs">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Annualized Billing</p>
                <h3 class="text-2xl font-bold text-primary-600 mt-2" id="kpi-annual-subs">--</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase">Unpaid Contracts</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-2" id="kpi-unpaid-subs">--</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 lg:col-span-2">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Renewal Timeline (12 Months)</h4>
                <div class="h-64">
                    <canvas id="renewalTimelineChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Billing Distribution</h4>
                <div class="h-64">
                    <canvas id="billingDistChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Top Vendors</h4>
                <div class="h-64">
                    <canvas id="vendorDistChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Payment Status</h4>
                <div class="h-64">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
            </div>
        </div>
        <!-- FACILITY & POWER TAB -->
        <div class="hidden p-4 rounded-lg bg-gray-50" id="facility" role="tabpanel" aria-labelledby="facility-tab">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Operational Items</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-2" id="infra-operational">--</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Facility Alerts</p>
                    <h3 class="text-2xl font-bold text-amber-600 mt-2" id="infra-warnings">--</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Critical Faults</p>
                    <h3 class="text-2xl font-bold text-rose-600 mt-2" id="infra-faults">--</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Load</p>
                    <h3 class="text-2xl font-bold text-primary-600 mt-2" id="infra-total-load">--</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 lg:col-span-2">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Checkup Consistency (Daily Trend)</h4>
                    <div class="h-64">
                        <canvas id="facilityTrendChart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Status Breakdown</h4>
                    <div class="h-64">
                        <canvas id="facilityStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Power System Utilization (kW)</h4>
                    <div class="h-64">
                        <canvas id="powerLoadChart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Battery Health (%)</h4>
                    <div class="h-64">
                        <canvas id="batteryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Tab Switching Logic
        const tabs = document.querySelectorAll('[role="tab"]');
        const tabPanels = document.querySelectorAll('[role="tabpanel"]');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all
                tabs.forEach(t => {
                    t.setAttribute('aria-selected', 'false');
                    t.classList.remove('border-primary-600', 'text-primary-600');
                    t.classList.add('border-transparent', 'hover:border-gray-300');
                });
                tabPanels.forEach(p => p.classList.add('hidden'));

                // Activate current
                tab.setAttribute('aria-selected', 'true');
                tab.classList.remove('border-transparent', 'hover:border-gray-300');
                tab.classList.add('border-primary-600', 'text-primary-600');
                document.querySelector(tab.dataset.tabsTarget).classList.remove('hidden');
            });
        });

        // Chart Instances
        let volumeChart, statusChart, categoryChart, techChart, conditionChart, spendingChart, deptChart;
        let renewalChart, billingChart, vendorChart, paymentChart;
        let facilityTrendChart, facilityStatusChart, powerLoadChart, batteryChart;

        // Fetch Data
        async function fetchReportData() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            try {
                const response = await fetch(`data.php?start_date=${startDate}&end_date=${endDate}`);
                const text = await response.text();

                try {
                    const data = JSON.parse(text);
                    if (data.error) throw new Error(data.error);

                    updateHelpdeskCharts(data.helpdesk);
                    updateAssetCharts(data.assets);
                    updateFinanceCharts(data.financials);
                    updateSubscriptionCharts(data.subscriptions);
                    updateFacilityCharts(data.facility_power);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Raw Response:', text);
                    alert('Failed to load report data. See console for details.\nRaw: ' + text.substring(0, 100));
                }

            } catch (error) {
                console.error('Fetch error:', error);
                alert('Network error loading reports.');
            }
        }

        function updateHelpdeskCharts(data) {
            // KPI Updates (Simple aggregation for now)
            let total = 0, resolved = 0, open = 0, critical = 0;

            data.status_dist.forEach(item => {
                total += parseInt(item.count);
                if (item.status === 'resolved' || item.status === 'closed') resolved += parseInt(item.count);
                else open += parseInt(item.count);
            });

            data.priority_dist.forEach(item => {
                if (item.priority === 'critical') critical += parseInt(item.count); // Assuming priority dist filters by date too
            });

            document.getElementById('totalTickets').textContent = total;
            document.getElementById('resolvedTickets').textContent = resolved;
            document.getElementById('openTickets').textContent = open;
            document.getElementById('criticalTickets').textContent = critical; // Note: Priority dist is independent of status, so this is critical raised in period

            // Volume Chart
            const dates = data.daily_volume.map(d => d.date);
            const counts = data.daily_volume.map(d => d.count);

            if (volumeChart) volumeChart.destroy();
            volumeChart = new Chart(document.getElementById('volumeChart'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Tickets Created',
                        data: counts,
                        borderColor: '#4F46E5',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(79, 70, 229, 0.1)'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Status Chart (Doughnut)
            const statuses = data.status_dist.map(d => d.status.replace('_', ' ').toUpperCase());
            const statusCounts = data.status_dist.map(d => d.count);

            if (statusChart) statusChart.destroy();
            statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: statuses,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: ['#3B82F6', '#F59E0B', '#10B981', '#6B7280', '#EF4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Category Chart (Bar)
            const cats = data.category_dist.map(d => d.category);
            const catCounts = data.category_dist.map(d => d.count);

            if (categoryChart) categoryChart.destroy();
            categoryChart = new Chart(document.getElementById('categoryChart'), {
                type: 'bar',
                data: {
                    labels: cats,
                    datasets: [{
                        label: 'Tickets by Category',
                        data: catCounts,
                        backgroundColor: '#8B5CF6'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Tech Chart (Bar Horizontal)
            const techs = data.tech_performance.map(d => d.assigned_to || 'Unassigned');
            const techCounts = data.tech_performance.map(d => d.count);

            if (techChart) techChart.destroy();
            techChart = new Chart(document.getElementById('techChart'), {
                type: 'bar',
                indexAxis: 'y',
                data: {
                    labels: techs,
                    datasets: [{
                        label: 'Resolved Tickets',
                        data: techCounts,
                        backgroundColor: '#10B981'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Department Chart (Bar Horizontal)
            const depts = data.department_dist.map(d => d.department || 'Unknown');
            const deptCounts = data.department_dist.map(d => d.count);

            if (deptChart) deptChart.destroy();
            deptChart = new Chart(document.getElementById('deptChart'), {
                type: 'bar',
                indexAxis: 'y',
                data: {
                    labels: depts,
                    datasets: [{
                        label: 'Tickets by Department',
                        data: deptCounts,
                        backgroundColor: '#F59E0B'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        }

        function updateAssetCharts(data) {
            // Condition Chart
            const labels = data.condition_dist.map(d => d.condition_status);
            const counts = data.condition_dist.map(d => d.count);

            if (conditionChart) conditionChart.destroy();
            conditionChart = new Chart(document.getElementById('conditionChart'), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Value
            document.getElementById('inventoryValue').textContent = 'KES ' + parseFloat(data.inventory_value || 0).toLocaleString();
        }

        function updateFinanceCharts(data) {
            const labels = data.spending_dist.map(d => d.status);
            const totals = data.spending_dist.map(d => d.total);

            if (spendingChart) spendingChart.destroy();
            spendingChart = new Chart(document.getElementById('spendingChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Spend by Status',
                        data: totals,
                        backgroundColor: '#3B82F6'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        function updateSubscriptionCharts(data) {
            // KPI Updates
            let total = 0, monthly = 0, unpaid = 0;
            data.status_dist.forEach(s => total += parseInt(s.count));
            data.billing_dist.forEach(b => { if (b.billing_cycle === 'monthly') monthly = b.count; });
            data.payment_status_dist.forEach(p => { if (p.payment_status === 'unpaid') unpaid = p.count; });

            document.getElementById('kpi-total-subs').textContent = total;
            document.getElementById('kpi-unpaid-subs').textContent = unpaid;
            // Expiring 30d is harder from just status dist, but we have renewal_timeline
            let expiring30 = data.renewal_timeline.length > 0 ? data.renewal_timeline[0].count : 0;
            document.getElementById('kpi-expiring-subs').textContent = expiring30;
            document.getElementById('kpi-annual-subs').textContent = (monthly > 0 ? 'Hybrid' : 'Annual');

            // Renewal Timeline Chart
            if (renewalChart) renewalChart.destroy();
            renewalChart = new Chart(document.getElementById('renewalTimelineChart'), {
                type: 'bar',
                data: {
                    labels: data.renewal_timeline.map(d => d.month_label),
                    datasets: [{
                        label: 'Renewals',
                        data: data.renewal_timeline.map(d => d.count),
                        backgroundColor: '#4F46E5',
                        borderRadius: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Billing Dist Chart
            if (billingChart) billingChart.destroy();
            billingChart = new Chart(document.getElementById('billingDistChart'), {
                type: 'doughnut',
                data: {
                    labels: data.billing_dist.map(d => d.billing_cycle.toUpperCase()),
                    datasets: [{
                        data: data.billing_dist.map(d => d.count),
                        backgroundColor: ['#6366F1', '#EC4899']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Vendor Dist Chart
            if (vendorChart) vendorChart.destroy();
            vendorChart = new Chart(document.getElementById('vendorDistChart'), {
                type: 'bar',
                indexAxis: 'y',
                data: {
                    labels: data.vendor_dist.map(d => d.vendor_name),
                    datasets: [{
                        label: 'Subscriptions',
                        data: data.vendor_dist.map(d => d.count),
                        backgroundColor: '#10B981'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Payment Status Chart
            if (paymentChart) paymentChart.destroy();
            paymentChart = new Chart(document.getElementById('paymentStatusChart'), {
                type: 'pie',
                data: {
                    labels: data.payment_status_dist.map(d => d.payment_status.toUpperCase()),
                    datasets: [{
                        data: data.payment_status_dist.map(d => d.count),
                        backgroundColor: ['#F59E0B', '#10B981']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        function updateFacilityCharts(data) {
            if (!data) return;

            // KPI Updates
            let operational = 0, warnings = 0, faults = 0;
            data.status_dist.forEach(s => {
                if (s.status === 'operational') operational = s.count;
                if (s.status === 'warning') warnings = s.count;
                if (s.status === 'faulty') faults = s.count;
            });

            document.getElementById('infra-operational').textContent = operational;
            document.getElementById('infra-warnings').textContent = warnings;
            document.getElementById('infra-faults').textContent = faults;

            let totalLoad = 0;
            data.power_metrics.forEach(p => totalLoad += parseFloat(p.current_load_kw || 0));
            document.getElementById('infra-total-load').textContent = totalLoad.toFixed(1) + ' kW';

            // Facility Trend Chart (Line)
            if (facilityTrendChart) facilityTrendChart.destroy();
            facilityTrendChart = new Chart(document.getElementById('facilityTrendChart'), {
                type: 'line',
                data: {
                    labels: data.check_trend.map(d => d.date),
                    datasets: [{
                        label: 'Facility Checks Performed',
                        data: data.check_trend.map(d => d.count),
                        borderColor: '#b59454',
                        backgroundColor: 'rgba(181, 148, 84, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });

            // Facility Status Chart (Doughnut)
            if (facilityStatusChart) facilityStatusChart.destroy();
            facilityStatusChart = new Chart(document.getElementById('facilityStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Operational', 'Warning', 'Faulty'],
                    datasets: [{
                        data: [operational, warnings, faults],
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Power Load Chart (Bar)
            if (powerLoadChart) powerLoadChart.destroy();
            powerLoadChart = new Chart(document.getElementById('powerLoadChart'), {
                type: 'bar',
                data: {
                    labels: data.power_metrics.map(p => p.name),
                    datasets: [{
                        label: 'Current Load (kW)',
                        data: data.power_metrics.map(p => p.current_load_kw),
                        backgroundColor: '#3B82F6'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Battery Health Chart (Polar Area)
            const batterySystems = data.power_metrics.filter(p => p.battery_percentage !== null);
            if (batteryChart) batteryChart.destroy();
            batteryChart = new Chart(document.getElementById('batteryChart'), {
                type: 'polarArea',
                data: {
                    labels: batterySystems.map(p => p.name),
                    datasets: [{
                        data: batterySystems.map(p => p.battery_percentage),
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.5)',
                            'rgba(59, 130, 246, 0.5)',
                            'rgba(245, 158, 11, 0.5)',
                            'rgba(239, 68, 68, 0.5)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: { beginAtZero: true, max: 100 }
                    }
                }
            });
        }

        // Initial Load
        // Select first tab by default
        document.getElementById('helpdesk-tab').click();
        fetchReportData();

    </script>

    <?php include '../../includes/footer.php'; ?>