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
                <i class="fas fa-table"></i>
                Tables Management
            </h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-info" onclick="regenerateAllQRCodes()">
                    <i class="fas fa-qrcode"></i> Regenerate All QR Codes
                </button>
                <button class="btn btn-primary" onclick="openTableModal()">
                    <i class="fas fa-plus"></i> Add Table
                </button>
            </div>
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
                        <input type="number" class="form-control" id="table-capacity" name="capacity" min="1" value="4" required>
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
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        let tables = <?php echo json_encode($tables); ?>;
        
        // Get restaurant slug for QR code paths
        let restaurantSlug = '<?php 
            try {
                require_once __DIR__ . "/../../../src/model.php";
                require_once __DIR__ . "/../../../src/config.php";
                $model = new Model();
                $stmt = $model->db->prepare("SELECT slug FROM restaurants WHERE id = ?");
                $stmt->execute([$restaurant_id]);
                echo htmlspecialchars($stmt->fetchColumn() ?: '', ENT_QUOTES);
            } catch (Exception $e) {
                echo '';
            }
        ?>';
        
        document.addEventListener('DOMContentLoaded', function() {
            renderTables();
        });
        
        function renderTables() {
            const grid = document.getElementById('tables-grid');
            
            if (tables.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">No tables found. Add your first table!</div>';
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
                                <strong>${table.capacity || 4} seats</strong>
                            </div>
                            <div class="table-info-item">
                                <span>QR Code:</span>
                                <span style="font-size: 11px; color: #666;">${table.qr_code ? table.qr_code.substring(0, 12) + '...' : 'N/A'}</span>
                            </div>
                        </div>
                        ${qrPath ? `
                        <div class="qr-code-preview">
                            <img src="${qrImageUrl}" alt="QR Code">
                            <div style="margin-top: 10px;">
                                <button class="btn btn-success" onclick="downloadQRCode(${table.id}, '${table.table_number}')" style="width: 100%; padding: 8px;">
                                    <i class="fas fa-download"></i> Download QR Code
                                </button>
                            </div>
                        </div>
                        ` : ''}
                        <div class="table-actions">
                            <button class="btn btn-warning" onclick="editTable(${table.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteTable(${table.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            ${qrPath ? `
                            <button class="btn btn-info" onclick="regenerateQRCode(${table.id})">
                                <i class="fas fa-sync"></i> Regenerate QR
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
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
                const table = tables.find(t => t.id == id);
                if (table) {
                    document.getElementById('table-id').value = table.id;
                    document.getElementById('table-number').value = table.table_number;
                    document.getElementById('table-capacity').value = table.capacity || 4;
                    document.getElementById('table-status').value = table.status || 'available';
                    title.textContent = 'Edit Table';
                }
            } else {
                form.reset();
                document.getElementById('table-id').value = '';
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
            const action = document.getElementById('table-id').value ? 'update_table' : 'create_table';
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=${action}`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Table saved successfully!', 'success');
                    closeTableModal();
                    location.reload();
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
            formData.append('id', id);
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=delete_table`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Table deleted successfully!', 'success');
                    location.reload();
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
                    location.reload();
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
                    location.reload();
                } else {
                    showAlert(data.message || 'Error regenerating QR codes', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function downloadQRCode(tableId, tableNumber) {
            const qrPath = getQRCodePath(tables.find(t => t.id == tableId));
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
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

