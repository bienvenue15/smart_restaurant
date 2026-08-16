<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Activity Log - Admin Dashboard'; ?></title>
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
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .tabs {
            background: white;
            border-radius: 10px 10px 0 0;
            padding: 0;
            display: flex;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 0;
        }
        .tab {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab:hover {
            background: #f8f9fa;
        }
        .tab.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .card {
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .action-badge {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 3px;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 1024px) {
            .admin-container {
                margin-left: 70px;
                padding: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-header {
                padding: 20px 18px;
                margin-bottom: 20px;
            }
            
            .page-header h1 {
                font-size: 1.375rem;
            }
            
            .filters {
                flex-direction: column;
                gap: 12px;
            }
            
            .table-responsive {
                margin: 0 -16px;
                padding: 0 16px;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.8125rem;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
        }
        
        @media (max-width: 480px) {
            .admin-container {
                padding: 12px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            table {
                min-width: 600px;
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<?php $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true'; ?>
<body class="staff-dashboard<?php echo $isFragment ? ' fragment-view' : ''; ?>">
    
    <!-- Mobile Menu Toggle -->
    <?php if (!$isFragment): ?>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>
    <?php endif; ?>
    
    <?php 
    // Load sidebar dependencies
    require_once __DIR__ . '/../../../../app/core/Permission.php';
    require_once __DIR__ . '/../../../../app/models/Staff.php';
    
    $staffModel = new Staff();
    $isOnShift = $staffModel->isOnShift($user['uuid']);
    $canHandleCash = Permission::check('handle_cash');
    $canApprove = Permission::check('approve_actions');
    
    // Get stats for sidebar badges
    $restaurantIdForStats = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
    $statsResult = $staffModel->getDashboardStats($restaurantIdForStats);
    $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
    
    // Include sidebar when not in fragment mode
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                <div>
                    <h1>
                        <i class="fas fa-history"></i>
                        Activity Log & Shift Tracking
                    </h1>
                    <p style="color: #666; margin: 5px 0 0 0;">Monitor all system activities and staff shifts</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="btn-notification-header" onclick="parent.toggleNotificationPanel()" style="background: none; border: none; color: #666; font-size: 20px; cursor: pointer; position: relative; padding: 8px 12px;">
                        <i class="fas fa-bell"></i>
                        <span id="notification-badge-iframe" class="notification-badge" style="display: none; position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: bold; display: flex; align-items: center; justify-content: center;"></span>
                    </button>
                    <div style="text-align: right;">
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                            <i class="fas fa-clock"></i> <span id="currentTime"></span>
                        </div>
                        <div style="color: #999; font-size: 12px;">
                            <span id="currentDate"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label>Staff Member</label>
                <select class="form-control" id="filter-user" onchange="applyFilters()">
                    <option value="">All Staff</option>
                    <?php foreach ($staff_list as $staff): ?>
                    <option value="<?php echo htmlspecialchars($staff['uuid'], ENT_QUOTES); ?>" <?php echo $filter_user == $staff['uuid'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($staff['full_name']); ?> (<?php echo htmlspecialchars($staff['role']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Action Type</label>
                <input type="text" class="form-control" id="filter-action" placeholder="e.g., create_order, accept_payment" 
                       value="<?php echo htmlspecialchars($filter_action ?? ''); ?>" onchange="applyFilters()">
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" class="form-control" id="filter-date" 
                       value="<?php echo htmlspecialchars($filter_date ?? ''); ?>" onchange="applyFilters()">
            </div>
            <div class="filter-group" style="align-self: flex-end;">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Filter
                </button>
                <button class="btn" onclick="clearFilters()" style="background: #6c757d; color: white; margin-left: 10px;">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('activities')">
                <i class="fas fa-list"></i> Activity History
            </button>
            <button class="tab" onclick="switchTab('shifts')">
                <i class="fas fa-clock"></i> Shift Tracking
            </button>
        </div>

        <!-- Activity History Tab -->
        <div id="tab-activities" class="tab-content active">
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Staff</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Table/Entity</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No activities found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong><br>
                                    <small style="color: #666;">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $activity['role'] === 'admin' ? 'danger' : 
                                            ($activity['role'] === 'manager' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($activity['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="action-badge"><?php echo htmlspecialchars($activity['action']); ?></span>
                                </td>
                                <td>
                                    <?php if ($activity['table_name']): ?>
                                    <?php echo htmlspecialchars($activity['table_name']); ?> #<?php echo $activity['record_id']; ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($activity['old_value'] || $activity['new_value']): ?>
                                    <?php if ($activity['old_value']): ?>
                                    <small><strong>From:</strong> <?php echo htmlspecialchars(substr($activity['old_value'], 0, 50)); ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($activity['new_value']): ?>
                                    <small><strong>To:</strong> <?php echo htmlspecialchars(substr($activity['new_value'], 0, 50)); ?></small>
                                    <?php endif; ?>
                                    <?php elseif ($activity['description']): ?>
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shift Tracking Tab -->
        <div id="tab-shifts" class="tab-content">
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Role</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No shift records found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($shift['full_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $shift['role'] === 'admin' ? 'danger' : 
                                            ($shift['role'] === 'manager' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($shift['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($shift['clock_in'])); ?></td>
                                <td>
                                    <?php if ($shift['clock_out']): ?>
                                    <?php echo date('M d, Y H:i', strtotime($shift['clock_out'])); ?>
                                    <?php else: ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($shift['clock_out']) {
                                        // Completed shift - calculate exact duration
                                        $start = new DateTime($shift['clock_in']);
                                        $end = new DateTime($shift['clock_out']);
                                        $diff = $start->diff($end);
                                        echo $diff->h . 'h ' . $diff->i . 'm ' . $diff->s . 's';
                                    } else {
                                        // Active shift - calculate time so far
                                        $start = new DateTime($shift['clock_in']);
                                        $now = new DateTime();
                                        $diff = $start->diff($now);
                                        echo $diff->h . 'h ' . $diff->i . 'm ' . $diff->s . 's';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($shift['clock_out']): ?>
                                    <span class="badge badge-info">Completed</span>
                                    <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 8px;"></i> On Shift</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        // Real-time clock
        function updateTime() {
            const now = new Date();
            const timeEl = document.getElementById('currentTime');
            const dateEl = document.getElementById('currentDate');
            if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true});
            if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Audio notification system
        <?php include __DIR__ . '/../../includes/audio_notification.js'; ?>
        
        // Auto-refresh every 10 seconds
        setInterval(() => {
            
            location.reload();
        }, 10000);
        
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }
        
        function applyFilters() {
            const user = document.getElementById('filter-user').value;
            const action = document.getElementById('filter-action').value;
            const date = document.getElementById('filter-date').value;
            
            const params = new URLSearchParams();
            params.append('req', 'staff');
            params.append('action', 'activity_log');
            
            if (user) params.append('user', user);
            if (action) params.append('action_type', action);
            if (date) params.append('date', date);
            
            window.location.href = BASE_URL + '/?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = BASE_URL + '/?req=staff&action=activity_log';
        }
        
        // Mobile menu toggle functions
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    </script>
</body>
</html>

