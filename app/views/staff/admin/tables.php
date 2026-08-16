<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Tables Management - Admin Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <style>
        body.staff-dashboard .admin-container {
            margin-left: 260px;
            padding: 30px;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        body.staff-dashboard.fragment-view .admin-container {
            margin-left: 0;
        }
        
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .btn-primary:hover {
            background: #2980b9;
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
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .table-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            position: relative;
        }
        .table-card.occupied {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        .table-card.available {
            border-color: #27ae60;
            background: #f0fff4;
        }
        .table-card.reserved {
            border-color: #f39c12;
            background: #fffbf0;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .table-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .table-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-occupied {
            background: #f8d7da;
            color: #721c24;
        }
        .status-reserved {
            background: #fff3cd;
            color: #856404;
        }
        .table-info {
            margin-bottom: 15px;
        }
        .table-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .table-info-item:last-child {
            border-bottom: none;
        }
        .table-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .table-actions .btn {
            flex: 1;
            min-width: 80px;
            padding: 8px 12px;
            font-size: 13px;
        }
        .qr-code-preview {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        .qr-code-preview img {
            max-width: 150px;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
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
            max-width: 500px;
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
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 1024px) {
            body.staff-dashboard .admin-container {
                margin-left: 70px;
                padding: 24px;
            }
        }
        
        @media (max-width: 768px) {
            body.staff-dashboard .admin-container {
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
            body.staff-dashboard .admin-container {
                padding: 8px;
            }
            
            .page-header {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 0.95rem;
            }
            
            .card {
                padding: 10px;
                margin-bottom: 12px;
            }
            
            table {
                font-size: 0.65rem;
            }
            
            th, td {
                padding: 6px 4px;
                font-size: 0.625rem;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.7rem;
            }
            
            .modal-content {
                padding: 10px;
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
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                <h1 style="margin: 0;">
                    <i class="fas fa-table"></i>
                    <?php echo isset($is_view_only) && $is_view_only ? 'Tables View' : 'Tables Management'; ?>
                </h1>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="btn-notification-header" onclick="parent.toggleNotificationPanel()" style="background: none; border: none; color: #666; font-size: 20px; cursor: pointer; position: relative; padding: 8px 12px;">
                        <i class="fas fa-bell"></i>
                        <span id="notification-badge-iframe" class="notification-badge" style="display: none; position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: bold; display: flex; align-items: center; justify-content: center;"></span>
                    </button>
                    <div style="text-align: right; margin-right: 15px;">
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                            <i class="fas fa-clock"></i> <span id="currentTime"></span>
                        </div>
                        <div style="color: #999; font-size: 12px;">
                            <span id="currentDate"></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!isset($is_view_only) || !$is_view_only): ?>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-info" onclick="regenerateAllQRCodes()">
                    <i class="fas fa-qrcode"></i> Regenerate All QR Codes
                </button>
                <button class="btn btn-primary" onclick="openTableModal()">
                    <i class="fas fa-plus"></i> Add Table
                </button>
            </div>
            <?php else: ?>
            <div style="color: #666; font-size: 14px;">
                <i class="fas fa-info-circle"></i> View-only mode - You can view table status and create orders
            </div>
            <?php endif; ?>
        </div>
        
        <div id="alert-container"></div>
        
        <div class="card">
            <div class="table-grid" id="tables-grid">
                <div class="loading">Loading tables...</div>
            </div>
        </div>
        
        <!-- Table Modal -->
        <div id="table-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="table-modal-title">Add Table</h3>
                    <button class="close" onclick="closeTableModal()">&times;</button>
                </div>
                <form id="table-form" onsubmit="saveTable(event)">
                    <input type="hidden" id="table-id" name="id">
                    <div class="form-group">
                        <label>Table Number *</label>
                        <input type="text" class="form-control" id="table-number" name="table_number" required>
                    </div>
                    <div class="form-group">
                        <label>Capacity (seats) *</label>
                        <input type="number" class="form-control" id="table-seats" name="seats" min="1" value="4" required>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select class="form-control" id="table-status" name="status" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" onclick="closeTableModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Version: <?php echo time(); ?>
        'use strict';
        
        const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const BASE_PATH = BASE_URL;
        const RESTAURANT_ID = '<?php echo isset($restaurant_id) ? htmlspecialchars($restaurant_id, ENT_QUOTES) : ''; ?>';
        const isViewOnly = <?php echo isset($is_view_only) && $is_view_only ? 'true' : 'false'; ?>;
        let tables = <?php echo json_encode($tables ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
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
        
        // Real-time updates using Server-Sent Events
        let eventSource = null;
        
        function setupRealTimeUpdates() {
            if (eventSource) {
                eventSource.close();
            }
            
            eventSource = new EventSource(`${BASE_PATH}/?req=events`);
            
            eventSource.addEventListener('table_updated', (e) => {
                const data = JSON.parse(e.data);
                if (data.restaurant_uuid === RESTAURANT_ID) {
                    loadTables();
                }
            });
            
            eventSource.addEventListener('table_added', (e) => {
                const data = JSON.parse(e.data);
                if (data.restaurant_uuid === RESTAURANT_ID) {
                    loadTables();
                }
            });
            
            eventSource.addEventListener('table_deleted', (e) => {
                const data = JSON.parse(e.data);
                if (data.restaurant_uuid === RESTAURANT_ID) {
                    loadTables();
                }
            });
            
            eventSource.addEventListener('table_status_changed', (e) => {
                const data = JSON.parse(e.data);
                if (data.restaurant_uuid === RESTAURANT_ID) {
                    loadTables();
                }
            });
            
            eventSource.onerror = (error) => {
                console.error('EventSource error:', error);
                eventSource.close();
                // Reconnect after 5 seconds
                setTimeout(setupRealTimeUpdates, 5000);
            };
        }
        
        setupRealTimeUpdates();
        
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Get restaurant slug for QR code paths
        let restaurantSlug = '<?php 
            try {
                require_once __DIR__ . "/../../../../app/models/Order.php";
                $orderModel = new Order();
                $stmt = $orderModel->db->prepare("SELECT slug FROM restaurants WHERE uuid = ?");
                $stmt->execute([$restaurant_id]);
                echo htmlspecialchars($stmt->fetchColumn() ?: '', ENT_QUOTES);
            } catch (Exception $e) {
                error_log("Error getting restaurant slug: " . $e->getMessage());
                echo '';
            }
        ?>';
        
        // Load tables from API
        async function loadTables() {
            const grid = document.getElementById('tables-grid');
            try {
                const response = await fetch(`${BASE_URL}/?req=api&action=staff_get_tables`, {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'OK') {
                    tables = data.data || [];
                    renderTables();
                } else {
                    console.error('Failed to load tables:', data.message);
                    if (grid) {
                        grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <p>${data.message || 'Failed to load tables'}</p>
                        </div>`;
                    }
                }
            } catch (error) {
                console.error('Error loading tables:', error);
                if (grid) {
                    grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #dc3545;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <p>Error: ${error.message}</p>
                    </div>`;
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Load fresh data from API instead of using PHP data
                loadTables();
            } catch (err) {
                console.error('Error initializing tables:', err);
                // Fallback to PHP data if API fails
                renderTables();
            }
        });
        
        function renderTables() {
            const grid = document.getElementById('tables-grid');
            
            if (!grid) {
                return;
            }
            
            try {
                if (!tables || tables.length === 0) {
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">No tables found.</div>';
                    return;
                }
                
                
                grid.innerHTML = tables.map(table => {
                    const statusClass = table.status || 'available';
                    const qrPath = getQRCodePath(table);
                    const qrImageUrl = qrPath ? (BASE_PATH + '/' + qrPath) : BASE_PATH + '/assets/images/no-qr.png';
                    
                    return `
                    <div class="table-card ${statusClass}">
                        <div class="table-header">
                            <div class="table-number">Table ${escapeHtml(table.table_number)}</div>
                            <span class="table-status status-${statusClass}">${statusClass}</span>
                        </div>
                        <div class="table-info">
                            <div class="table-info-item">
                                <span>Capacity:</span>
                                <strong>${table.seats || 4} seats</strong>
                            </div>
                            ${table.assigned_waiter_name ? `
                            <div class="table-info-item">
                                <span>Assigned Waiter:</span>
                                <strong style="color: #2196f3;">${escapeHtml(table.assigned_waiter_name)}</strong>
                            </div>
                            ` : ''}
                            ${!isViewOnly ? `
                            <div class="table-info-item">
                                <span>QR Code:</span>
                                <span style="font-size: 11px; color: #666;">${table.qr_code ? table.qr_code.substring(0, 12) + '...' : 'N/A'}</span>
                            </div>
                            ` : ''}
                        </div>
                        ${!isViewOnly && qrPath ? `
                        <div class="qr-code-preview">
                            <img src="${qrImageUrl}" alt="QR Code">
                            <div style="margin-top: 10px;">
                                <button class="btn btn-success" onclick="downloadQRCode('${table.uuid}', '${table.table_number}')" style="width: 100%; padding: 8px;">
                                    <i class="fas fa-download"></i> Download QR Code
                                </button>
                            </div>
                        </div>
                        ` : ''}
                        ${!isViewOnly ? `<div class="table-actions">
                            ${statusClass === 'available' ? `<button class="btn btn-warning" onclick="reserveTable('${table.uuid}', '${table.table_number}')" style="flex: 1;">
                                    <i class="fas fa-calendar-check"></i> Reserve
                                </button>` : ''}
                            ${statusClass === 'reserved' ? `<button class="btn btn-success" onclick="makeTableAvailable('${table.uuid}', '${table.table_number}')" style="flex: 1;">
                                    <i class="fas fa-check"></i> Make Available
                                </button>` : ''}
                            ${statusClass === 'occupied' ? `<button class="btn btn-success" onclick="releaseTable('${table.uuid}', '${table.table_number}')" style="flex: 1;">
                                    <i class="fas fa-door-open"></i> Release Table
                                </button>` : ''}
                            <button class="btn btn-warning" onclick="editTable('${table.uuid}')" style="flex: 1;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteTable('${table.uuid}')" style="flex: 1;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            ${qrPath ? `<button class="btn btn-info" onclick="regenerateQRCode('${table.uuid}')" style="width: 100%; margin-top: 8px;">
                                <i class="fas fa-sync"></i> Regenerate QR
                            </button>` : ''}
                        </div>` : ''}
                    </div>
                    `;
                }).join('');
                
            } catch (error) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #dc3545;">Error loading tables. Please refresh the page.</div>';
            }
        }
        
    function getQRCodePath(table) {
        // Try to construct the QR code path
        if (!restaurantSlug) return null;
        
        const directoryKey = restaurantSlug.toLowerCase().replace(/[^a-z0-9\-]/g, '-');
        const filename = directoryKey + '-table-' + table.table_number + '.png';
        const path = 'images/qrcodes/' + directoryKey + '/' + filename;
        
        // In a real scenario, you'd verify the file exists via API
        return path;
    }
    
    function openTableModal(id = null) {
        const modal = document.getElementById('table-modal');
        const form = document.getElementById('table-form');
        const title = document.getElementById('table-modal-title');
        
        if (id) {
            const table = tables.find(t => t.uuid == id);
            if (table) {
                document.getElementById('table-id').value = table.uuid;
                document.getElementById('table-number').value = table.table_number;
                document.getElementById('table-seats').value = table.seats || 4;
                document.getElementById('table-status').value = table.status || 'available';
                title.textContent = 'Edit Table';
            }
        } else {
            form.reset();
            document.getElementById('table-id').value = '';
            document.getElementById('table-seats').value = 4;
            document.getElementById('table-status').value = 'available';
            title.textContent = 'Add Table';
        }
        
        modal.classList.add('active');
    }
    
    function closeTableModal() {
        document.getElementById('table-modal').classList.remove('active');
        document.getElementById('table-form').reset();
    }
    
    function editTable(id) {
        openTableModal(id);
    }
    
    function saveTable(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const tableId = document.getElementById('table-id').value;
            
            // Change form field names to match API expectations
            if (tableId) {
                formData.append('table_id', tableId);
                formData.delete('id');
            }
            formData.set('capacity', formData.get('seats'));
            formData.set('table_number', formData.get('table_number'));
            formData.set('status', formData.get('status'));
            
            const action = tableId ? 'staff_update_table' : 'staff_add_table';
            
            fetch(`${BASE_PATH}/?req=api&action=${action}`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Table saved successfully!', 'success');
                    closeTableModal();
                    loadTables(); // Reload tables immediately
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error saving table', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function deleteTable(id) {
            if (!confirm('Are you sure you want to delete this table? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('table_id', id);
            
            fetch(`${BASE_PATH}/?req=api&action=staff_delete_table`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    loadTables(); // Reload tables immediately
                    showAlert('Table deleted successfully!', 'success');
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error deleting table', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function regenerateQRCode(tableId) {
            if (!confirm('Regenerate QR code for this table?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('table_id', tableId);
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=regenerate_qrcodes`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('QR code regenerated successfully!', 'success');
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error regenerating QR code', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function regenerateAllQRCodes() {
            if (!confirm('Regenerate QR codes for all tables? This may take a moment.')) {
                return;
            }
            
            showAlert('Regenerating QR codes...', 'info');
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=regenerate_qrcodes`, {
                method: 'POST',
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('All QR codes regenerated successfully!', 'success');
                    if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
                } else {
                    showAlert(data.message || 'Error regenerating QR codes', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function downloadQRCode(tableId, tableNumber) {
            const qrPath = getQRCodePath(tables.find(t => t.uuid == tableId));
            if (!qrPath) {
                showAlert('QR code not found. Please regenerate it first.', 'error');
                return;
            }
            
            const qrImageUrl = BASE_PATH + '/' + qrPath;
            const link = document.createElement('a');
            link.href = qrImageUrl;
            link.download = `table-${tableNumber}-qr-code.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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
        
        // Waiter function to create order for customers without smartphones
        function createOrderForTable(tableId, tableNumber) {
            // Redirect to orders page with pre-selected table
            window.location.href = `${BASE_PATH}/?req=staff&action=orders&table_id=${tableId}&create=1`;
        }
        
        // Manager function to reserve a table
        function reserveTable(tableId, tableNumber) {
            if (!confirm(`Reserve Table ${tableNumber}?`)) {
                return;
            }
            
            fetch(`${BASE_PATH}/?req=api&action=staff_reserve_table`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `table_id=${tableId}`,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    loadTables(); // Reload tables immediately
                    showAlert(`Table ${tableNumber} reserved successfully!`, 'success');
                    setTimeout(() => { if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); }, 1000);
                } else {
                    showAlert(data.message || 'Error reserving table', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        // Manager function to make a reserved table available
        function makeTableAvailable(tableId, tableNumber) {
            if (!confirm(`Make Table ${tableNumber} available?`)) {
                return;
            }
            
            fetch(`${BASE_PATH}/?req=api&action=staff_update_table_status`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `table_id=${tableId}&status=available`,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (loadTables(); // Reload tables immediately
                    data.status === 'OK') {
                    showAlert(`Table ${tableNumber} is now available!`, 'success');
                    setTimeout(() => { if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); }, 1000);
                } else {
                    showAlert(data.message || 'Error updating table status', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        // Admin/Manager/Cashier function to release an occupied table
        function releaseTable(tableId, tableNumber) {
            if (!confirm(`Release Table ${tableNumber}? This will clear any pending orders and make it available.`)) {
                return;
            }
            
            fetch(`${BASE_PATH}/?req=api&action=staff_reset_table`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `table_id=${tableId}`,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (loadTables(); // Reload tables immediately
                    data.status === 'OK') {
                    showAlert(`Table ${tableNumber} has been released!`, 'success');
                    setTimeout(() => { if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); }, 1000);
                } else {
                    showAlert(data.message || 'Error releasing table', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
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

