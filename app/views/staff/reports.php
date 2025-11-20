<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Staff Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        .reports-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            color: #007bff;
        }
        
        .tab-btn.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        
        .report-panel {
            display: none;
        }
        
        .report-panel.active {
            display: block;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .date-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-selector input,
        .date-selector select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 14px;
            color: #28a745;
        }
        
        .stat-change.negative {
            color: #dc3545;
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h3 {
            margin-top: 0;
            color: #333;
        }
        
        .table-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .loading i {
            font-size: 48px;
            color: #007bff;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .chart-placeholder {
            height: 300px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php
    require_once 'app/core/Permission.php';
    Permission::require('view_reports', false);
    ?>
    
    <div class="reports-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <div style="color: #666; font-weight: 600;">
                <i class="fas fa-clock"></i> <span id="currentTime"></span>
            </div>
        </div>
        
        <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
        
        <!-- Report Tabs -->
        <div class="report-tabs">
            <button class="tab-btn active" onclick="switchTab('daily')">
                <i class="fas fa-calendar-day"></i> Daily
            </button>
            <button class="tab-btn" onclick="switchTab('weekly')">
                <i class="fas fa-calendar-week"></i> Weekly
            </button>
            <button class="tab-btn" onclick="switchTab('monthly')">
                <i class="fas fa-calendar-alt"></i> Monthly
            </button>
            <button class="tab-btn" onclick="switchTab('yearly')">
                <i class="fas fa-calendar"></i> Yearly
            </button>
        </div>
        
        <!-- Daily Report -->
        <div id="daily-panel" class="report-panel active">
            <div class="report-header">
                <div class="date-selector">
                    <label><strong>Select Date:</strong></label>
                    <input type="date" id="dailyDate" value="<?php echo date('Y-m-d'); ?>">
                    <button class="btn btn-primary" onclick="loadDailyReport()">
                        <i class="fas fa-sync"></i> Load Report
                    </button>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportReport('daily', 'pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-success" onclick="exportReport('daily', 'excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="btn btn-success" onclick="exportReport('daily', 'csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                </div>
            </div>
            
            <div id="dailyStats" class="stats-grid">
                <div class="loading"><i class="fas fa-spinner"></i><p>Loading...</p></div>
            </div>
            
            <div id="dailyCharts" class="chart-container" style="display:none;">
                <h3>Hourly Sales</h3>
                <canvas id="dailyChart"></canvas>
            </div>
            
            <div id="dailyTable" class="table-container" style="display:none;">
                <h3>Order Details</h3>
                <table class="data-table" id="dailyOrdersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Time</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        
        <!-- Weekly Report -->
        <div id="weekly-panel" class="report-panel">
            <div class="report-header">
                <div class="date-selector">
                    <label><strong>Select Week:</strong></label>
                    <input type="week" id="weeklyDate" value="<?php echo date('Y') . '-W' . date('W'); ?>">
                    <button class="btn btn-primary" onclick="loadWeeklyReport()">
                        <i class="fas fa-sync"></i> Load Report
                    </button>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportReport('weekly', 'pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-success" onclick="exportReport('weekly', 'excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            
            <div id="weeklyStats" class="stats-grid">
                <div class="loading"><i class="fas fa-spinner"></i><p>Loading...</p></div>
            </div>
            
            <div id="weeklyCharts" class="chart-container" style="display:none;">
                <h3>Daily Sales Comparison</h3>
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
        
        <!-- Monthly Report -->
        <div id="monthly-panel" class="report-panel">
            <div class="report-header">
                <div class="date-selector">
                    <label><strong>Select Month:</strong></label>
                    <input type="month" id="monthlyDate" value="<?php echo date('Y-m'); ?>">
                    <button class="btn btn-primary" onclick="loadMonthlyReport()">
                        <i class="fas fa-sync"></i> Load Report
                    </button>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportReport('monthly', 'pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-success" onclick="exportReport('monthly', 'excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            
            <div id="monthlyStats" class="stats-grid">
                <div class="loading"><i class="fas fa-spinner"></i><p>Loading...</p></div>
            </div>
            
            <div id="monthlyCharts" class="chart-container" style="display:none;">
                <h3>Weekly Performance</h3>
                <canvas id="monthlyChart"></canvas>
            </div>
            
            <div id="monthlyTable" class="table-container" style="display:none;">
                <h3>Top Menu Items</h3>
                <table class="data-table" id="topItemsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        
        <!-- Yearly Report -->
        <div id="yearly-panel" class="report-panel">
            <div class="report-header">
                <div class="date-selector">
                    <label><strong>Select Year:</strong></label>
                    <select id="yearlyDate">
                        <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="btn btn-primary" onclick="loadYearlyReport()">
                        <i class="fas fa-sync"></i> Load Report
                    </button>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportReport('yearly', 'pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-success" onclick="exportReport('yearly', 'excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            
            <div id="yearlyStats" class="stats-grid">
                <div class="loading"><i class="fas fa-spinner"></i><p>Loading...</p></div>
            </div>
            
            <div id="yearlyCharts" class="chart-container" style="display:none;">
                <h3>Monthly Revenue Trend</h3>
                <canvas id="yearlyChart"></canvas>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let currentTab = 'daily';
        let charts = {};
        
        // Switch between tabs
        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update panels
            document.querySelectorAll('.report-panel').forEach(panel => panel.classList.remove('active'));
            document.getElementById(tab + '-panel').classList.add('active');
            
            // Load data if not loaded
            const statsDiv = document.getElementById(tab + 'Stats');
            if (statsDiv.innerHTML.includes('Loading')) {
                switch(tab) {
                    case 'daily': loadDailyReport(); break;
                    case 'weekly': loadWeeklyReport(); break;
                    case 'monthly': loadMonthlyReport(); break;
                    case 'yearly': loadYearlyReport(); break;
                }
            }
        }
        
        // Load reports on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDailyReport();
        });
        
        // Load Daily Report
        function loadDailyReport() {
            const date = document.getElementById('dailyDate').value;
            
            fetch(`${BASE_URL}/?req=api&action=staff_get_report&type=daily&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        displayDailyStats(data.data);
                    } else {
                        showError('dailyStats', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('dailyStats', 'Failed to load report');
                });
        }
        
        function displayDailyStats(data) {
            const statsHtml = `
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">$${parseFloat(data.total_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change ${data.revenue_change >= 0 ? '' : 'negative'}">
                        <i class="fas fa-arrow-${data.revenue_change >= 0 ? 'up' : 'down'}"></i>
                        ${Math.abs(data.revenue_change || 0).toFixed(1)}% vs yesterday
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value">${data.total_orders || 0}</div>
                    <div class="stat-change ${data.orders_change >= 0 ? '' : 'negative'}">
                        <i class="fas fa-arrow-${data.orders_change >= 0 ? 'up' : 'down'}"></i>
                        ${Math.abs(data.orders_change || 0).toFixed(1)}% vs yesterday
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Average Order</h3>
                    <div class="stat-value">$${parseFloat(data.avg_order || 0).toFixed(2)}</div>
                    <div class="stat-change">
                        ${data.total_orders > 0 ? 'Per order' : 'No orders yet'}
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Customers Served</h3>
                    <div class="stat-value">${data.total_customers || 0}</div>
                    <div class="stat-change">
                        ${data.unique_tables || 0} unique tables
                    </div>
                </div>
            `;
            
            document.getElementById('dailyStats').innerHTML = statsHtml;
            
            // Show tables
            if (data.orders && data.orders.length > 0) {
                displayDailyOrders(data.orders);
                document.getElementById('dailyTable').style.display = 'block';
            }
            
            // Show charts
            if (data.hourly_sales) {
                displayDailyChart(data.hourly_sales);
                document.getElementById('dailyCharts').style.display = 'block';
            }
        }
        
        function displayDailyOrders(orders) {
            const tbody = document.getElementById('dailyOrdersTable').querySelector('tbody');
            tbody.innerHTML = orders.map(order => `
                <tr>
                    <td>#${order.id}</td>
                    <td>${order.table_number}</td>
                    <td>${new Date(order.created_at).toLocaleTimeString()}</td>
                    <td>${order.item_count}</td>
                    <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>${order.payment_status || 'pending'}</td>
                    <td><span class="badge badge-${order.status}">${order.status}</span></td>
                </tr>
            `).join('');
        }
        
        function displayDailyChart(hourlySales) {
            const ctx = document.getElementById('dailyChart').getContext('2d');
            
            if (charts.daily) {
                charts.daily.destroy();
            }
            
            charts.daily = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hourlySales.map(h => h.hour + ':00'),
                    datasets: [{
                        label: 'Sales',
                        data: hourlySales.map(h => h.revenue),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Load Weekly Report
        function loadWeeklyReport() {
            const week = document.getElementById('weeklyDate').value;
            
            fetch(`${BASE_URL}/?req=api&action=staff_get_report&type=weekly&week=${week}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        displayWeeklyStats(data.data);
                    } else {
                        showError('weeklyStats', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('weeklyStats', 'Failed to load report');
                });
        }
        
        function displayWeeklyStats(data) {
            const statsHtml = `
                <div class="stat-card">
                    <h3>Weekly Revenue</h3>
                    <div class="stat-value">$${parseFloat(data.total_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change">
                        ${data.days_count || 7} days
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value">${data.total_orders || 0}</div>
                    <div class="stat-change">
                        ${parseFloat(data.total_orders / 7 || 0).toFixed(1)} avg/day
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Best Day</h3>
                    <div class="stat-value">${data.best_day || 'N/A'}</div>
                    <div class="stat-change">
                        $${parseFloat(data.best_day_revenue || 0).toFixed(2)}
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Average Daily</h3>
                    <div class="stat-value">$${parseFloat(data.avg_daily_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change">
                        Per day average
                    </div>
                </div>
            `;
            
            document.getElementById('weeklyStats').innerHTML = statsHtml;
            
            if (data.daily_sales) {
                displayWeeklyChart(data.daily_sales);
                document.getElementById('weeklyCharts').style.display = 'block';
            }
        }
        
        function displayWeeklyChart(dailySales) {
            const ctx = document.getElementById('weeklyChart').getContext('2d');
            
            if (charts.weekly) {
                charts.weekly.destroy();
            }
            
            charts.weekly = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailySales.map(d => d.day_name),
                    datasets: [{
                        label: 'Revenue',
                        data: dailySales.map(d => d.revenue),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Load Monthly Report
        function loadMonthlyReport() {
            const month = document.getElementById('monthlyDate').value;
            
            fetch(`${BASE_URL}/?req=api&action=staff_get_report&type=monthly&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        displayMonthlyStats(data.data);
                    } else {
                        showError('monthlyStats', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('monthlyStats', 'Failed to load report');
                });
        }
        
        function displayMonthlyStats(data) {
            const statsHtml = `
                <div class="stat-card">
                    <h3>Monthly Revenue</h3>
                    <div class="stat-value">$${parseFloat(data.total_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change ${data.growth >= 0 ? '' : 'negative'}">
                        <i class="fas fa-arrow-${data.growth >= 0 ? 'up' : 'down'}"></i>
                        ${Math.abs(data.growth || 0).toFixed(1)}% vs last month
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value">${data.total_orders || 0}</div>
                    <div class="stat-change">
                        ${data.days_in_month || 30} days
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Avg Daily Revenue</h3>
                    <div class="stat-value">$${parseFloat(data.avg_daily_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change">
                        Per day
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Top Item</h3>
                    <div class="stat-value" style="font-size: 20px;">${data.top_item || 'N/A'}</div>
                    <div class="stat-change">
                        ${data.top_item_qty || 0} sold
                    </div>
                </div>
            `;
            
            document.getElementById('monthlyStats').innerHTML = statsHtml;
            
            if (data.weekly_sales) {
                displayMonthlyChart(data.weekly_sales);
                document.getElementById('monthlyCharts').style.display = 'block';
            }
            
            if (data.top_items) {
                displayTopItems(data.top_items);
                document.getElementById('monthlyTable').style.display = 'block';
            }
        }
        
        function displayMonthlyChart(weeklySales) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            if (charts.monthly) {
                charts.monthly.destroy();
            }
            
            charts.monthly = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: weeklySales.map(w => 'Week ' + w.week),
                    datasets: [{
                        label: 'Revenue',
                        data: weeklySales.map(w => w.revenue),
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function displayTopItems(items) {
            const tbody = document.getElementById('topItemsTable').querySelector('tbody');
            tbody.innerHTML = items.map((item, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.name}</td>
                    <td>${item.category}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.revenue).toFixed(2)}</td>
                </tr>
            `).join('');
        }
        
        // Load Yearly Report
        function loadYearlyReport() {
            const year = document.getElementById('yearlyDate').value;
            
            fetch(`${BASE_URL}/?req=api&action=staff_get_report&type=yearly&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        displayYearlyStats(data.data);
                    } else {
                        showError('yearlyStats', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('yearlyStats', 'Failed to load report');
                });
        }
        
        function displayYearlyStats(data) {
            const statsHtml = `
                <div class="stat-card">
                    <h3>Annual Revenue</h3>
                    <div class="stat-value">$${parseFloat(data.total_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change ${data.yoy_growth >= 0 ? '' : 'negative'}">
                        <i class="fas fa-arrow-${data.yoy_growth >= 0 ? 'up' : 'down'}"></i>
                        ${Math.abs(data.yoy_growth || 0).toFixed(1)}% YoY
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value">${data.total_orders || 0}</div>
                    <div class="stat-change">
                        ${data.year}
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Best Month</h3>
                    <div class="stat-value">${data.best_month || 'N/A'}</div>
                    <div class="stat-change">
                        $${parseFloat(data.best_month_revenue || 0).toFixed(2)}
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Monthly Average</h3>
                    <div class="stat-value">$${parseFloat(data.avg_monthly_revenue || 0).toFixed(2)}</div>
                    <div class="stat-change">
                        Per month
                    </div>
                </div>
            `;
            
            document.getElementById('yearlyStats').innerHTML = statsHtml;
            
            if (data.monthly_sales) {
                displayYearlyChart(data.monthly_sales);
                document.getElementById('yearlyCharts').style.display = 'block';
            }
        }
        
        function displayYearlyChart(monthlySales) {
            const ctx = document.getElementById('yearlyChart').getContext('2d');
            
            if (charts.yearly) {
                charts.yearly.destroy();
            }
            
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            charts.yearly = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Revenue',
                        data: monthlySales,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Export Report
        function exportReport(type, format) {
            let date = '';
            switch(type) {
                case 'daily':
                    date = document.getElementById('dailyDate').value;
                    break;
                case 'weekly':
                    date = document.getElementById('weeklyDate').value;
                    break;
                case 'monthly':
                    date = document.getElementById('monthlyDate').value;
                    break;
                case 'yearly':
                    date = document.getElementById('yearlyDate').value;
                    break;
            }
            
            window.location.href = `${BASE_URL}/?req=api&action=staff_export_report&type=${type}&format=${format}&date=${date}`;
        }
        
        // Show error
        function showError(containerId, message) {
            document.getElementById(containerId).innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
        
        // Update current time with seconds - accurate live clock
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeStr;
        }
        updateTime();
        setInterval(updateTime, 1000); // Update every second
    </script>
    
    <footer style="text-align: center; padding: 20px; color: #666; margin-top: 40px;">
        © 2025 Inovasiyo Ltd. All rights reserved.
    </footer>
</body>
</html>
