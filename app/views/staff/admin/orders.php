<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Orders Management - Admin Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <style>
        .admin-container {
            margin-left: 260px;
            padding: 30px;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
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
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .badge-confirmed {
            background: #cfe2ff;
            color: #084298;
        }
        .badge-preparing {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-ready {
            background: #d4edda;
            color: #155724;
        }
        .badge-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .order-details {
            cursor: pointer;
            color: #3498db;
        }
        .order-details:hover {
            text-decoration: underline;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .order-items {
            margin-top: 20px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<?php $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true'; ?>
<body class="staff-dashboard<?php echo $isFragment ? ' fragment-view' : ''; ?>">
    <?php 
    // Load sidebar dependencies
    require_once __DIR__ . '/../../../src/model.php';
    require_once __DIR__ . '/../../../app/core/Permission.php';
    require_once __DIR__ . '/../../../app/models/Staff.php';
    
    $staffModel = new Staff();
    $isOnShift = $staffModel->isOnShift($user['id']);
    $canHandleCash = Permission::check('handle_cash');
    $canApprove = Permission::check('approve_actions');
    
    // Get stats for sidebar badges
    $statsResult = $staffModel->getDashboardStats();
    $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
    
    // Include sidebar
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-list-alt"></i>
                Orders Management
            </h1>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select class="form-control" id="status-filter" onchange="applyFilters()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" class="form-control" id="date-filter" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="applyFilters()">
            </div>
            <div class="filter-group">
                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Filter
                </button>
                <button type="button" class="btn" onclick="clearFilters()" style="margin-left: 10px;">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="no-data">No orders found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>Table <?php echo htmlspecialchars($order['table_number']); ?></td>
                            <td><?php echo number_format($order['total'] ?? 0, 0); ?> RWF</td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)" style="padding: 5px 10px;">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Order Details Modal -->
        <div id="order-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Order Details #<span id="order-id-display"></span></h3>
                    <button class="close" onclick="closeOrderModal()">&times;</button>
                </div>
                <div id="order-details-content">
                    <div class="loading">Loading order details...</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        
        function applyFilters() {
            const status = document.getElementById('status-filter').value;
            const date = document.getElementById('date-filter').value;
            
            const params = new URLSearchParams();
            params.append('req', 'staff');
            params.append('action', 'orders_manage');
            if (status !== 'all') {
                params.append('status', status);
            }
            if (date) {
                params.append('date', date);
            }
            
            window.location.href = BASE_PATH + '/?' + params.toString();
        }
        
        function clearFilters() {
            document.getElementById('status-filter').value = 'all';
            document.getElementById('date-filter').value = '';
            applyFilters();
        }
        
        function viewOrderDetails(orderId) {
            const modal = document.getElementById('order-modal');
            const content = document.getElementById('order-details-content');
            
            modal.classList.add('active');
            content.innerHTML = '<div class="loading">Loading order details...</div>';
            
            // Fetch order details
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=get_order_details&order_id=${orderId}`, {
                method: 'GET',
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    renderOrderDetails(data.data);
                } else {
                    content.innerHTML = '<div class="alert alert-error">' + (data.message || 'Error loading order details') + '</div>';
                }
            })
            .catch(err => {
                content.innerHTML = '<div class="alert alert-error">Error: ' + err.message + '</div>';
            });
        }
        
        function renderOrderDetails(order) {
            const content = document.getElementById('order-details-content');
            document.getElementById('order-id-display').textContent = order.id;
            
            const itemsHtml = order.items ? order.items.map(item => `
                <div class="order-item">
                    <div>
                        <strong>${escapeHtml(item.name || 'Item')}</strong>
                        <div style="color: #666; font-size: 13px;">x${item.quantity} @ ${formatCurrency(item.price)}</div>
                    </div>
                    <div><strong>${formatCurrency(item.quantity * item.price)}</strong></div>
                </div>
            `).join('') : '<p>No items found</p>';
            
            content.innerHTML = `
                <div>
                    <div style="margin-bottom: 20px;">
                        <strong>Table:</strong> ${order.table_number || 'N/A'}<br>
                        <strong>Status:</strong> <span class="badge badge-${order.status || 'pending'}">${order.status || 'Pending'}</span><br>
                        <strong>Created:</strong> ${new Date(order.created_at).toLocaleString()}<br>
                        <strong>Total:</strong> <strong style="font-size: 18px;">${formatCurrency(order.total || 0)}</strong>
                    </div>
                    <div class="order-items">
                        <h4>Order Items</h4>
                        ${itemsHtml}
                    </div>
                </div>
            `;
        }
        
        function closeOrderModal() {
            document.getElementById('order-modal').classList.remove('active');
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-RW', {
                style: 'currency',
                currency: 'RWF',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

