<?php
// Sidebar include for staff dashboard and admin views
// Requires: $user, $isOnShift, $canHandleCash, $canApprove, $stats (optional)

if (!isset($stats)) {
    $stats = [];
}
$isAdminRole = isset($user['role']) && $user['role'] === 'admin';
?>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
            <span>Smart Restaurant</span>
        </div>
        <div class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard - All roles -->
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="nav-item <?php echo ($page ?? '') === 'staff_dashboard' ? 'active' : ''; ?>" 
           data-section="dashboard" onclick="return navigateTo('dashboard', event);">
            <i class="fas fa-dashboard"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- Orders - All roles except kitchen (kitchen sees orders differently) -->
        <?php if (Permission::check('view_orders') || $user['role'] === 'kitchen'): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=orders_manage" class="nav-item"
           data-section="orders_manage" onclick="return navigateTo('orders_manage', event);">
            <i class="fas fa-shopping-cart"></i>
            <span><?php echo $user['role'] === 'kitchen' ? 'Kitchen Orders' : 'Orders'; ?></span>
            <?php if (!empty($stats['pending_orders'])): ?>
            <span class="badge"><?php echo $stats['pending_orders']; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        
        <!-- Waiter Calls - Admin, Manager, Waiter only -->
        <?php if (in_array($user['role'], ['admin', 'manager', 'waiter']) && Permission::check('view_tables')): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=orders_manage" class="nav-item"
           data-section="orders_manage" onclick="return navigateTo('orders_manage', event);">
            <i class="fas fa-bell"></i>
            <span>Waiter Calls</span>
            <?php if (!empty($stats['pending_calls'])): ?>
            <span class="badge"><?php echo $stats['pending_calls']; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        
        <!-- Tables - Admin, Manager, Waiter, Cashier only (not kitchen) -->
        <?php if (in_array($user['role'], ['admin', 'manager', 'waiter', 'cashier']) && (Permission::check('manage_tables') || Permission::check('view_tables'))): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=tables" class="nav-item"
           data-section="tables" onclick="return navigateTo('tables', event);">
            <i class="fas fa-chair"></i>
            <span>Tables</span>
        </a>
        <?php endif; ?>
        
        <!-- Menu - All roles can view -->
        <?php if (Permission::check('view_menu')): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=menu" class="nav-item"
           data-section="menu" onclick="return navigateTo('menu', event);">
            <i class="fas fa-book"></i>
            <span>Menu</span>
        </a>
        <?php endif; ?>
        
        <!-- Cash Register - Admin, Manager, Cashier only -->
        <?php if (in_array($user['role'], ['admin', 'manager', 'cashier']) && ($canHandleCash ?? Permission::check('handle_cash'))): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=cash_management" class="nav-item">
            <i class="fas fa-cash-register"></i>
            <span>Cash Register</span>
        </a>
        <?php endif; ?>
        
        <!-- Approvals - Admin, Manager only -->
        <?php if (in_array($user['role'], ['admin', 'manager']) && ($canApprove ?? Permission::check('approve_actions'))): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=pending_approvals" class="nav-item">
            <i class="fas fa-check-circle"></i>
            <span>Approvals</span>
        </a>
        <?php endif; ?>
        
        <!-- Reports - Admin, Manager only -->
        <?php if (in_array($user['role'], ['admin', 'manager']) && Permission::check('view_reports')): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=reports" class="nav-item"
           data-section="reports" onclick="return navigateTo('reports', event);">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <?php endif; ?>
        
        <?php if ($isAdminRole): ?>
        <div class="nav-divider" style="margin: 15px 0; border-top: 1px solid rgba(255,255,255,0.2);"></div>
        <div class="nav-section-header" style="padding: 10px 20px; color: rgba(255,255,255,0.7); font-size: 11px; text-transform: uppercase; font-weight: 600;">
            Admin Panel
        </div>
        
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=menu" class="nav-item <?php echo ($page ?? '') === 'menu_manage' ? 'active' : ''; ?>" 
           data-section="menu" onclick="return navigateTo('menu', event);">
            <i class="fas fa-utensils"></i>
            <span>Menu Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=tables" class="nav-item <?php echo ($page ?? '') === 'tables_manage' ? 'active' : ''; ?>" 
           data-section="tables" onclick="return navigateTo('tables', event);">
            <i class="fas fa-table"></i>
            <span>Tables Management</span>
        </a>
        
        <?php 
        // Staff Management - Admin Only (not manager)
        if (isset($user['role']) && $user['role'] === 'admin'): ?>
        <!-- Staff Management - Admin Only -->
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=staff_manage" class="nav-item <?php echo ($page ?? '') === 'staff_manage' ? 'active' : ''; ?>" 
           data-section="staff_manage" onclick="return navigateTo('staff_manage', event);">
            <i class="fas fa-users"></i>
            <span>Staff Management</span>
        </a>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=orders_manage" class="nav-item <?php echo ($page ?? '') === 'orders_manage' ? 'active' : ''; ?>" 
           data-section="orders_manage" onclick="return navigateTo('orders_manage', event);">
            <i class="fas fa-list-alt"></i>
            <span>Orders Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=reports" class="nav-item <?php echo ($page ?? '') === 'reports' ? 'active' : ''; ?>" 
           data-section="reports" onclick="return navigateTo('reports', event);">
            <i class="fas fa-chart-bar"></i>
            <span>Reports & Analytics</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=settings" class="nav-item <?php echo ($page ?? '') === 'restaurant_settings' ? 'active' : ''; ?>" 
           data-section="settings" onclick="return navigateTo('settings', event);">
            <i class="fas fa-cog"></i>
            <span>Restaurant Settings</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <!-- Shift Status -->
        <?php if (isset($isOnShift)): ?>
        <div class="shift-status" style="padding: 15px; margin-bottom: 10px; background: <?php echo $isOnShift ? '#d4edda' : '#f8d7da'; ?>; border-radius: 8px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-weight: 600; color: <?php echo $isOnShift ? '#155724' : '#721c24'; ?>;">
                    <i class="fas fa-<?php echo $isOnShift ? 'check-circle' : 'clock'; ?>"></i>
                    <?php echo $isOnShift ? 'On Shift' : 'Off Shift'; ?>
                </span>
            </div>
            <button onclick="if(typeof window.toggleShift==='function'){window.toggleShift(event);}else{toggleShift(event);}" class="btn-shift" style="width: 100%; padding: 8px; background: <?php echo $isOnShift ? '#dc3545' : '#28a745'; ?>; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-<?php echo $isOnShift ? 'clock' : 'play-circle'; ?>"></i>
                <?php echo $isOnShift ? 'Clock Out' : 'Clock In'; ?>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=logout" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

