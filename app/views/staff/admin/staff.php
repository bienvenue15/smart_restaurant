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
    
    // Include sidebar when not embedded in dashboard iframe
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-users"></i>
                Staff Management
            </h1>
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
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-table-body">
                        <tr class="loading"><td colspan="9">Loading staff members...</td></tr>
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
                            <option value="admin">Admin</option>
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
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        const CURRENT_USER_ID = <?php echo $user['id']; ?>;
        let staff = <?php echo json_encode($staff); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            renderStaff();
        });
        
        function renderStaff() {
            const tbody = document.getElementById('staff-table-body');
            
            if (staff.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="no-data">No staff members found. Add your first staff member!</td></tr>';
                return;
            }
            
            tbody.innerHTML = staff.map(member => {
                const roleClass = 'badge-' + member.role;
                const statusClass = member.is_active == 1 ? 'badge-active' : 'badge-inactive';
                const lastLogin = member.last_login ? new Date(member.last_login).toLocaleString() : 'Never';
                const isCurrentUser = member.id == CURRENT_USER_ID;
                
                return `
                    <tr>
                        <td>${member.id}</td>
                        <td><strong>${escapeHtml(member.username)}</strong></td>
                        <td>${escapeHtml(member.full_name)}</td>
                        <td>${escapeHtml(member.email || '-')}</td>
                        <td>${escapeHtml(member.phone || '-')}</td>
                        <td><span class="badge ${roleClass}">${member.role}</span></td>
                        <td><span class="badge ${statusClass}">${member.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
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
                const member = staff.find(s => s.id == id);
                if (member) {
                    document.getElementById('staff-id').value = member.id;
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
                    location.reload();
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
                    location.reload();
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
    </script>
</body>
</html>

