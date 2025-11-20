<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Dashboard - Staff Portal'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <link rel="apple-touch-icon" href="<?php echo APP_LOGO_URL; ?>">
</head>
<body class="staff-dashboard">
    
    <?php 
    require_once 'app/core/Permission.php';
    require_once 'app/models/Staff.php';
    $staffModel = new Staff();
    
    // Force refresh shift status from database (no caching)
    $isOnShift = $staffModel->isOnShift($user['id']);
    
    // Log for debugging (remove in production)
    error_log('[DASHBOARD] User ID: ' . $user['id'] . ', Role: ' . ($user['role'] ?? 'unknown') . ', On Shift: ' . ($isOnShift ? 'yes' : 'no'));
    
    $canHandleCash = Permission::check('handle_cash');
    $canManageOrders = Permission::check('manage_orders');
    $canApprove = Permission::check('approve_actions');
    
    // Get stats for sidebar badges
    $statsResult = $staffModel->getDashboardStats();
    $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
    
    // Include sidebar
    include __DIR__ . '/_sidebar.php';
    ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <h1 id="page-title">Dashboard</h1>
                <p class="subtitle" id="page-subtitle">Overview of restaurant operations</p>
            </div>
            <div class="header-right">
                <div class="header-time">
                    <i class="fas fa-clock"></i>
                    <span id="currentTime"></span>
                </div>
                <button class="btn-refresh" onclick="handleRefreshClick()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </header>
        
        <div id="dashboard-home">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['tables']['occupied'] ?? 0; ?>/<?php echo $stats['tables']['total'] ?? 0; ?></div>
                    <div class="stat-label">Tables Occupied</div>
                </div>
            </div>
            
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            
            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['preparing_orders'] ?? 0; ?></div>
                    <div class="stat-label">In Kitchen</div>
                </div>
            </div>
            
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">RWF <?php echo number_format($stats['today_revenue'] ?? 0); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-sub"><?php echo $stats['today_orders'] ?? 0; ?> orders</div>
                </div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            
            <!-- Pending Orders -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-shopping-cart"></i> Active Orders</h2>
                    <span class="badge-count"><?php echo count($pending_orders); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_orders)): ?>
                        <div class="orders-list">
                            <?php foreach (array_slice($pending_orders, 0, 10) as $order): ?>
                                <div class="order-item order-status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <div class="order-header">
                                        <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                        <div class="order-status"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></div>
                                    </div>
                                    <div class="order-details">
                                        <span><i class="fas fa-chair"></i> Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                                        <span><i class="fas fa-shopping-bag"></i> <?php echo $order['item_count']; ?> items</span>
                                        <span><i class="fas fa-money-bill-wave"></i> RWF <?php echo number_format($order['total_amount']); ?></span>
                                    </div>
                                    <div class="order-actions">
                                        <button class="btn-small btn-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($order['status'] === 'pending' && Permission::check('manage_orders')): ?>
                                        <button class="btn-small btn-success" onclick="confirmOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>No active orders</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Waiter Calls -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-bell"></i> Waiter Calls</h2>
                    <span class="badge-count"><?php echo count($waiter_calls); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($waiter_calls)): ?>
                        <div class="calls-list">
                            <?php foreach (array_slice($waiter_calls, 0, 10) as $call): ?>
                                <div class="call-item call-priority-<?php echo htmlspecialchars($call['priority']); ?>">
                                    <div class="call-header">
                                        <div class="call-table">
                                            <i class="fas fa-chair"></i> Table <?php echo htmlspecialchars($call['table_number']); ?>
                                        </div>
                                        <div class="call-priority"><?php echo htmlspecialchars(ucfirst($call['priority'])); ?></div>
                                    </div>
                                    <div class="call-type">
                                        <i class="fas fa-hand-point-right"></i>
                                        <?php echo htmlspecialchars(ucfirst($call['request_type'])); ?>
                                    </div>
                                    <?php if (!empty($call['message'])): ?>
                                    <div class="call-message">
                                        <?php echo htmlspecialchars($call['message']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="call-time">
                                        <?php 
                                        $time = strtotime($call['created_at']);
                                        echo date('H:i', $time) . ' (' . floor((time() - $time) / 60) . ' min ago)';
                                        ?>
                                    </div>
                                    <?php if (Permission::check('manage_tables')): ?>
                                    <div class="call-actions">
                                        <button class="btn-small btn-primary" onclick="assignCall(<?php echo $call['id']; ?>)">
                                            <i class="fas fa-user-check"></i> Assign to Me
                                        </button>
                                        <button class="btn-small btn-success" onclick="completeCall(<?php echo $call['id']; ?>)">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>No pending calls</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        </div> <!-- /dashboard-home -->

        <div id="section-frame-wrapper" style="display:none; flex-direction:column; gap:10px;">
            <div id="section-frame-toolbar" style="display:flex; justify-content:space-between; align-items:center;">
                <button class="btn-refresh" onclick="showDashboardHome()">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </button>
                <div id="section-frame-title" style="font-weight:600; color:#2c3e50;"></div>
                <button class="btn-refresh" onclick="reloadSectionFrame()">
                    <i class="fas fa-sync"></i> Reload
                </button>
            </div>
            <iframe id="section-frame" title="Dashboard Section" style="width:100%; height:80vh; border:none; border-radius:12px; background:white;"></iframe>
        </div>
    </main>
    
    <script>
        const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const BASE_PATH = BASE_URL;
        const RESTAURANT_ID = <?php echo $_SESSION['staff_user']['restaurant_id'] ?? 'null'; ?>;
        const CURRENT_USER = <?php echo json_encode($user); ?>;
        let currentSection = 'dashboard';
        
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
        setInterval(updateTime, 1000); // Update every second for accurate counting
        
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
        
        // Clock in/out - Global function accessible from sidebar
        window.toggleShift = function(evt) {
            // Get current shift status from PHP variable
            let currentShiftStatus = <?php echo $isOnShift ? 'true' : 'false'; ?>;
            const action = currentShiftStatus ? 'clock_out' : 'clock_in';
            const actionName = currentShiftStatus ? 'clock out' : 'clock in';
            
            // Get button element
            let btn = null;
            if (evt && evt.target) {
                btn = evt.target;
            } else {
                btn = document.querySelector('.btn-shift');
            }
            
            // Disable button to prevent double-clicks
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                fetch(`${BASE_URL}/?req=staff&action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'OK') {
                        // Show success message briefly, then reload
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert(data.message || `Failed to ${actionName}`);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Clock in/out error:', error);
                    alert('Failed to ' + actionName + '. Please try again.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                });
            } else {
                // Fallback if button not found
                fetch(`${BASE_URL}/?req=staff&action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        window.location.reload();
                    } else {
                        alert(data.message || `Failed to ${actionName}`);
                    }
                })
                .catch(error => {
                    console.error('Clock in/out error:', error);
                    alert('Failed to ' + actionName);
                });
        }
        };
        
        // Update order status
        function updateOrderStatus(orderId, newStatus) {
            if (!confirm(`Change order status to ${newStatus}?`)) {
                return;
            }
            
            fetch(`${BASE_URL}/?req=api&action=staff_update_order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'OK') {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update order status');
            });
        }
        
        // Placeholder functions (implement API calls)
        function viewOrder(orderId) {
            alert('View order ' + orderId + ' - To be implemented');
        }
        
        function confirmOrder(orderId) {
            updateOrderStatus(orderId, 'confirmed');
        }
        
        function assignCall(callId) {
            if (!confirm('Assign this call to yourself?')) {
                return;
            }
            
            fetch(`${BASE_URL}/?req=api&action=staff_assign_call`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    call_id: callId
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'OK') {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to assign call');
            });
        }
        
        function completeCall(callId) {
            if (!confirm('Mark this call as completed?')) {
                return;
            }
            
            fetch(`${BASE_URL}/?req=api&action=staff_complete_call`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    call_id: callId
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'OK') {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to complete call');
            });
        }
    </script>
    
    <script>
        const SECTION_ROUTES = {
            dashboard: {
                title: 'Dashboard',
                subtitle: 'Overview of restaurant operations'
            },
            menu: {
                title: 'Menu Management',
                subtitle: 'Manage menu categories and items',
                url: BASE_URL + '/?req=staff&action=menu&fragment=true'
            },
            tables: {
                title: 'Tables Management',
                subtitle: 'Manage tables and QR codes',
                url: BASE_URL + '/?req=staff&action=tables&fragment=true'
            },
            staff_manage: {
                title: 'Staff Management',
                subtitle: 'Manage staff members',
                url: BASE_URL + '/?req=staff&action=staff_manage&fragment=true'
            },
            orders_manage: {
                title: 'Orders Management',
                subtitle: 'View and manage orders',
                url: BASE_URL + '/?req=staff&action=orders_manage&fragment=true'
            },
            settings: {
                title: 'Restaurant Settings',
                subtitle: 'Manage restaurant profile and settings',
                url: BASE_URL + '/?req=staff&action=settings&fragment=true'
            },
            reports: {
                title: 'Reports & Analytics',
                subtitle: 'View sales reports and analytics',
                url: BASE_URL + '/?req=staff&action=reports&fragment=true'
            }
        };
        
        const frameWrapper = document.getElementById('section-frame-wrapper');
        const frame = document.getElementById('section-frame');
        const frameTitle = document.getElementById('section-frame-title');
        const dashboardHome = document.getElementById('dashboard-home');
        
        frame.addEventListener('load', () => {
            frame.classList.remove('loading');
        });
        
        function setActiveNav(section) {
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => item.classList.remove('active'));
            const navItem = document.querySelector(`.sidebar-nav a[data-section="${section}"]`);
            if (navItem) {
                navItem.classList.add('active');
            }
        }
        
        // Global navigation function
        window.navigateTo = function(section, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Check if section exists in routes
            const route = SECTION_ROUTES[section];
            
            // If dashboard, show home
            if (section === 'dashboard' || !route) {
                showDashboardHome();
                if (!route && section !== 'dashboard') {
                    // Fallback to direct navigation
                    window.location.href = BASE_URL + '/?req=staff&action=' + section;
                }
                return false;
            }
            
            // Show loading
            if (frameWrapper) {
                frameWrapper.style.display = 'flex';
            }
            if (dashboardHome) {
                dashboardHome.style.display = 'none';
            }
            
            // Update navigation
            setActiveNav(section);
            
            // Update title and subtitle
            if (frameTitle) {
                frameTitle.textContent = route.title;
            }
            const pageTitleEl = document.getElementById('page-title');
            const pageSubtitleEl = document.getElementById('page-subtitle');
            if (pageTitleEl) pageTitleEl.textContent = route.title;
            if (pageSubtitleEl) pageSubtitleEl.textContent = route.subtitle;
            
            // Load section in iframe
            if (frame && frame.dataset.section !== section) {
                frame.classList.add('loading');
                frame.dataset.section = section;
                frame.src = route.url;
            }
            
            // Update URL
            window.history.replaceState({section: section}, route.title, `${BASE_URL}/?req=staff&action=${section}`);
            
            return false;
        };
        
        function showDashboardHome() {
            frameWrapper.style.display = 'none';
            frame.removeAttribute('data-section');
            dashboardHome.style.display = 'block';
            frame.src = 'about:blank';
            document.getElementById('page-title').textContent = 'Dashboard';
            document.getElementById('page-subtitle').textContent = 'Overview of restaurant operations';
            setActiveNav('dashboard');
            window.history.replaceState({}, 'Dashboard', `${BASE_URL}/?req=staff&action=dashboard`);
        }
        
        function reloadSectionFrame() {
            if (frame.dataset.section) {
                frame.classList.add('loading');
                frame.contentWindow.location.reload();
            }
        }
        
        function handleRefreshClick() {
            if (frameWrapper.style.display !== 'none' && frame.dataset.section) {
                reloadSectionFrame();
            } else {
                window.location.reload();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const action = params.get('action');
            if (action && action !== 'dashboard' && SECTION_ROUTES[action]) {
                navigateTo(action);
            } else {
                showDashboardHome();
            }
        });
    </script>
</body>
</html>
