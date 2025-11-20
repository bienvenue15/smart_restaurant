<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Reports & Analytics - Admin Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <style>
        .admin-container{
            margin-left:260px;
            padding:30px;
            background:#f5f7fa;
            min-height:100vh;
        }
        .page-header{
            background:white;
            padding:25px;
            border-radius:10px;
            margin-bottom:25px;
            box-shadow:0 2px 4px rgba(0,0,0,0.1);
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .filters{
            background:white;
            padding:20px;
            border-radius:10px;
            margin-bottom:25px;
            box-shadow:0 2px 4px rgba(0,0,0,0.1);
            display:flex;
            flex-wrap:wrap;
            gap:15px;
        }
        .filter-group{
            flex:1;
            min-width:200px;
        }
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:20px;
            margin-bottom:25px;
        }
        .stat-card{
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-label{color:#666;font-size:14px;margin-bottom:10px;}
        .stat-value{font-size:30px;font-weight:700;color:#2c3e50;}
        .card{
            background:white;
            border-radius:10px;
            padding:25px;
            margin-bottom:25px;
            box-shadow:0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header h2{margin:0 0 15px 0;color:#2c3e50;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #e0e0e0;}
        th{background:#f8f9fa;font-weight:600;color:#2c3e50;}
        .empty-row{text-align:center;padding:40px;color:#888;}
        .btn{
            padding:10px 20px;
            border:none;
            border-radius:5px;
            background:#3498db;
            color:white;
            cursor:pointer;
            font-weight:500;
            display:inline-flex;
            align-items:center;
            gap:8px;
        }
    </style>
</head>
<?php $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true'; ?>
<body class="staff-dashboard<?php echo $isFragment ? ' fragment-view' : ''; ?>">
    <?php
    require_once __DIR__ . '/../../../src/model.php';
    require_once __DIR__ . '/../../../app/core/Permission.php';
    require_once __DIR__ . '/../../../app/models/Staff.php';
    $staffModel = new Staff();
    $isOnShift = $staffModel->isOnShift($user['id']);
    $canHandleCash = Permission::check('handle_cash');
    $canApprove = Permission::check('approve_actions');
    $statsResult = $staffModel->getDashboardStats();
    $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>

    <div class="admin-container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
                <p style="color:#666;">Monitor revenue, orders and popular items</p>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" class="form-control" id="start-date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" class="form-control" id="end-date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="filter-group" style="align-self:flex-end;">
                <button class="btn" onclick="applyDateFilter()">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo number_format($total_stats['total_orders'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">RWF <?php echo number_format($total_stats['total_revenue'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Average Order Value</div>
                <div class="stat-value">RWF <?php echo number_format($total_stats['avg_order_value'] ?? 0, 0); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Daily Revenue</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daily_revenue)): ?>
                            <tr>
                                <td colspan="3" class="empty-row">No data for selected range</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daily_revenue as $row): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo number_format($row['order_count'] ?? 0); ?></td>
                                    <td>RWF <?php echo number_format($row['total_revenue'] ?? 0, 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Top Selling Items</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_items)): ?>
                            <tr>
                                <td colspan="3" class="empty-row">No items found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo number_format($item['total_quantity'] ?? 0); ?></td>
                                    <td>RWF <?php echo number_format($item['total_revenue'] ?? 0, 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        function applyDateFilter() {
            const start = document.getElementById('start-date').value;
            const end = document.getElementById('end-date').value;
            const params = new URLSearchParams({
                req: 'staff',
                action: 'reports',
                start_date: start,
                end_date: end
            });
            window.location.href = BASE_PATH + '/?' + params.toString();
        }
    </script>
</body>
</html>

