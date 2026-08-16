<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Staff Management - Admin Dashboard'; ?></title>
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
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .card-header h2 {
            margin: 0;
            color: #2c3e50;
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
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
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
            text-transform: capitalize;
        }
        .badge-shift-on {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .badge-shift-off {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .shift-time {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        .badge-admin {
            background: #d4edda;
            color: #155724;
        }
        .badge-manager {
            background: #cfe2ff;
            color: #084298;
        }
        .badge-waiter {
            background: #fff3cd;
            color: #856404;
        }
        .badge-kitchen {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-cashier {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
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
            max-width: 600px;
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
        .modal-header h3 {
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
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
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
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
                gap: 12px;
            }
            
            .card {
                padding: 18px;
                margin-bottom: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .card-header .btn {
                width: 100%;
                justify-content: center;
            }
            
            .table-responsive {
                margin: 0 -18px;
                padding: 0 18px;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.8125rem;
            }
            
            .btn {
                padding: 9px 16px;
                font-size: 0.8125rem;
            }
            
            .modal-content {
                width: 95%;
                padding: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .admin-container {
                padding: 12px;
            }
            
            .page-header {
                padding: 16px 14px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            .card {
                padding: 14px;
            }
            
            table {
                min-width: 500px;
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.75rem;
            }
            
            .modal-content {
                width: 96%;
                padding: 18px;
                margin: 5px;
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
    
    // Include sidebar when not embedded in dashboard iframe
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h1 style="margin: 0;">
                    <i class="fas fa-users"></i>
                    Staff Management
                </h1>
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
        
        <div id="alert-container"></div>
        
        <div class="card">
            <div class="card-header">
                <h2>Staff Members</h2>
                <button class="btn btn-primary" onclick="openStaffModal()">
                    <i class="fas fa-plus"></i> Add Staff Member
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Shift Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-table-body">
                        <tr class="loading"><td colspan="10">Loading staff members...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Staff Modal -->
        <div id="staff-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="staff-modal-title">Add Staff Member</h3>
                    <button class="close" onclick="closeStaffModal()">&times;</button>
                </div>
                <form id="staff-form" onsubmit="saveStaff(event)">
                    <input type="hidden" id="staff-id" name="id">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" class="form-control" id="staff-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" class="form-control" id="staff-full-name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" id="staff-email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="staff-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select class="form-control" id="staff-role" name="role" required>
                            <option value="waiter">Waiter</option>
                            <option value="kitchen">Kitchen Staff</option>
                            <option value="cashier">Cashier</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Restaurant Owner (Admin)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="password-label">Password *</label>
                        <input type="password" class="form-control" id="staff-password" name="password">
                        <small style="color: #666;">Leave blank to keep current password when editing</small>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="staff-active" name="is_active" checked>
                            Active
                        </label>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" onclick="closeStaffModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        const BASE_PATH = '<?php echo BASE_URL; ?>';
        
        // Audio notification system
        <?php include __DIR__ . '/../../includes/audio_notification.js'; ?>
        
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
        
        const CURRENT_USER_ID = '<?php echo htmlspecialchars($user['uuid'], ENT_QUOTES); ?>';
        const DEBUG_RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        const DEBUG_USER_RESTAURANT_ID = '<?php echo $_SESSION['staff_user']['restaurant_uuid'] ?? 'null'; ?>';
        let staff = <?php echo json_encode($staff, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // Debug output
        document.addEventListener('DOMContentLoaded', function() {
            // Check staff data
            if (!staff) {
                alert('Staff data is null or undefined');
                return;
            }
            if (!Array.isArray(staff)) {
                alert('Staff is not an array: ' + typeof staff);
                return;
            }
            if (staff.length === 0) {
                alert('Staff array is empty.\nRestaurant ID used: ' + DEBUG_RESTAURANT_ID + 
                      '\nUser Restaurant ID: ' + DEBUG_USER_RESTAURANT_ID +
                      '\nCheck PHP error log for query details.');
            }
            
            renderStaff();
        });
        
        // Auto-refresh staff data every 30 seconds
        setInterval(() => {
            if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
        }, 30000);
        
        function renderStaff() {
            const tbody = document.getElementById('staff-table-body');
            
            if (staff.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="no-data">No staff members found. Add your first staff member!</td></tr>';
                return;
            }
            
            tbody.innerHTML = staff.map(member => {
                const roleClass = 'badge-' + member.role;
                const statusClass = member.is_active == 1 ? 'badge-active' : 'badge-inactive';
                const lastLogin = member.last_login ? new Date(member.last_login).toLocaleString() : 'Never';
                const isCurrentUser = member.uuid == CURRENT_USER_ID;
                
                // Shift status
                const isOnShift = member.is_on_shift == 1;
                const shiftBadgeClass = isOnShift ? 'badge-shift-on' : 'badge-shift-off';
                const shiftIcon = isOnShift ? 'fa-circle-check' : 'fa-circle-xmark';
                const shiftText = isOnShift ? 'On Shift' : 'Off Shift';
                let shiftTime = '';
                if (isOnShift && member.clock_in_time) {
                    const clockIn = new Date(member.clock_in_time);
                    shiftTime = `<div class="shift-time">Since ${clockIn.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</div>`;
                }
                
                return `
                    <tr>
                        <td>${member.id}</td>
                        <td><strong>${escapeHtml(member.username)}</strong></td>
                        <td>${escapeHtml(member.full_name)}</td>
                        <td>${escapeHtml(member.email || '-')}</td>
                        <td>${escapeHtml(member.phone || '-')}</td>
                        <td><span class="badge ${roleClass}">${member.role}</span></td>
                        <td><span class="badge ${statusClass}">${member.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <span class="badge ${shiftBadgeClass}">
                                <i class="fas ${shiftIcon}"></i> ${shiftText}
                            </span>
                            ${shiftTime}
                        </td>
                        <td>${lastLogin}</td>
                        <td>
                            <button class="btn btn-warning" onclick="editStaff(${member.id})" style="padding: 5px 10px; margin-right: 5px;">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!isCurrentUser ? `
                            <button class="btn btn-danger" onclick="deleteStaff(${member.id})" style="padding: 5px 10px;">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : '<span style="color: #999; font-size: 12px;">You</span>'}
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function openStaffModal(id = null) {
            const modal = document.getElementById('staff-modal');
            const form = document.getElementById('staff-form');
            const title = document.getElementById('staff-modal-title');
            const passwordLabel = document.getElementById('password-label');
            const passwordInput = document.getElementById('staff-password');
            
            if (id) {
                const member = staff.find(s => s.uuid == id);
                if (member) {
                    document.getElementById('staff-id').value = member.uuid;
                    document.getElementById('staff-username').value = member.username;
                    document.getElementById('staff-full-name').value = member.full_name;
                    document.getElementById('staff-email').value = member.email || '';
                    document.getElementById('staff-phone').value = member.phone || '';
                    document.getElementById('staff-role').value = member.role;
                    document.getElementById('staff-active').checked = member.is_active == 1;
                    passwordInput.value = '';
                    passwordInput.required = false;
                    passwordLabel.innerHTML = 'Password <small style="color: #666;">(leave blank to keep current)</small>';
                    title.textContent = 'Edit Staff Member';
                }
            } else {
                form.reset();
                document.getElementById('staff-id').value = '';
                passwordInput.required = true;
                passwordLabel.innerHTML = 'Password *';
                title.textContent = 'Add Staff Member';
            }
            
            modal.classList.add('active');
        }
        
        function closeStaffModal() {
            document.getElementById('staff-modal').classList.remove('active');
            document.getElementById('staff-form').reset();
        }
        
        function editStaff(id) {
            openStaffModal(id);
        }
        
        function saveStaff(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const action = document.getElementById('staff-id').value ? 'update_staff' : 'create_staff';
            
            // Password not required for updates
            if (action === 'update_staff' && !formData.get('password')) {
                // Remove password from form data if empty
                formData.delete('password');
            }
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=${action}`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Staff member saved successfully!', 'success');
                    closeStaffModal();
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error saving staff member', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function deleteStaff(id) {
            if (id == CURRENT_USER_ID) {
                showAlert('Cannot delete your own account!', 'error');
                return;
            }
            
            if (!confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=delete_staff`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Staff member deleted successfully!', 'success');
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error deleting staff member', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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


