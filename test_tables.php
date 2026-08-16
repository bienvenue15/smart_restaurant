<?php
require_once 'src/config.php';

// Start session the same way as the staff controller
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/sessions';
    if (!is_dir(__DIR__) || !is_writable(__DIR__)) {
        $sessionPath = sys_get_temp_dir() . '/restaurant_sessions';
    }
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        ini_set('session.save_path', $sessionPath);
    }
    session_start();
}

// Check if logged in - need both staff_user and staff_uuid
if (!isset($_SESSION['staff_user']) || !isset($_SESSION['staff_uuid'])) {
    // Debug info
    $debug = [
        'session_id' => session_id(),
        'session_path' => session_save_path(),
        'has_staff_user' => isset($_SESSION['staff_user']),
        'has_staff_uuid' => isset($_SESSION['staff_uuid']),
        'session_keys' => array_keys($_SESSION)
    ];
    die('<h1>Error: Not logged in</h1><p>Please login first at <a href="' . BASE_URL . '/?req=staff&action=login">Staff Login</a></p><pre>' . print_r($debug, true) . '</pre>');
}

$staffUuid = $_SESSION['staff_user']['uuid'];
$restaurantUuid = $_SESSION['staff_user']['restaurant_uuid'];
$staffName = $_SESSION['staff_user']['full_name'];
$staffRole = $_SESSION['staff_user']['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management Test Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 30px;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .session-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .section { padding: 30px; border-bottom: 1px solid #eee; }
        .section h2 { margin-bottom: 20px; color: #333; }
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .table-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }
        .table-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .table-card.available { border-color: #27ae60; background: #f0fff4; }
        .table-card.occupied { border-color: #e74c3c; background: #fff5f5; }
        .table-card.reserved { border-color: #f39c12; background: #fffbf0; }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .table-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .table-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-available { background: #27ae60; color: white; }
        .status-occupied { background: #e74c3c; color: white; }
        .status-reserved { background: #f39c12; color: white; }
        .table-info {
            margin: 15px 0;
            color: #666;
        }
        .table-info div { margin: 5px 0; }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-reset { background: #3498db; color: white; }
        .btn-edit { background: #9b59b6; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-add { background: #27ae60; color: white; padding: 15px 30px; font-size: 16px; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .response-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .response-box.success { border-left: 5px solid #27ae60; }
        .response-box.error { border-left: 5px solid #e74c3c; }
        .add-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .loading { text-align: center; padding: 40px; color: #667eea; }
        .loading i { font-size: 48px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-table"></i> Table Management Test Page</h1>
            <p>Test all table operations</p>
            <div class="session-info">
                <strong>Staff:</strong> <?php echo htmlspecialchars($staffName); ?> (<?php echo htmlspecialchars($staffRole); ?>)<br>
                <strong>Staff UUID:</strong> <?php echo htmlspecialchars($staffUuid); ?><br>
                <strong>Restaurant UUID:</strong> <?php echo htmlspecialchars($restaurantUuid); ?>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-plus-circle"></i> Add New Table</h2>
            <div class="add-form">
                <div class="form-group">
                    <label>Table Number:</label>
                    <input type="text" id="newTableNumber" placeholder="e.g., 101">
                </div>
                <div class="form-group">
                    <label>Capacity:</label>
                    <input type="number" id="newTableCapacity" placeholder="e.g., 4" min="1">
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select id="newTableStatus">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                <button class="btn btn-add" onclick="addTable()">
                    <i class="fas fa-plus"></i> Add Table
                </button>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-chair"></i> All Tables</h2>
            <button class="btn btn-reset" onclick="loadTables()" style="margin-bottom: 20px;">
                <i class="fas fa-sync"></i> Refresh Tables
            </button>
            <div id="tablesContainer" class="loading">
                <i class="fas fa-spinner"></i>
                <p>Loading tables...</p>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-terminal"></i> API Response</h2>
            <div id="responseBox" class="response-box">
                Waiting for API calls...
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const RESTAURANT_ID = '<?php echo $restaurantUuid; ?>';

        function logResponse(response, type = 'info') {
            const box = document.getElementById('responseBox');
            const timestamp = new Date().toLocaleTimeString();
            const formatted = JSON.stringify(response, null, 2);
            
            box.className = 'response-box ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : '');
            box.textContent = `[${timestamp}] Response:\n\n${formatted}`;
            box.scrollTop = box.scrollHeight;
        }

        async function loadTables() {
            try {
                const response = await fetch(`${BASE_URL}/?req=api&action=staff_get_tables`, {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                logResponse(data, data.status === 'OK' ? 'success' : 'error');
                
                if (data.status === 'OK') {
                    renderTables(data.data);
                } else {
                    document.getElementById('tablesContainer').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #e74c3c;">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <p>${data.message || 'Failed to load tables'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('tablesContainer').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #e74c3c;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <p>${error.message}</p>
                    </div>
                `;
                logResponse({ error: error.message }, 'error');
            }
        }

        function renderTables(tables) {
            const container = document.getElementById('tablesContainer');
            
            if (tables.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 60px; color: #6c757d;">
                        <i class="fas fa-chair" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
                        <p>No tables found. Add your first table above!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="table-grid">
                    ${tables.map(table => `
                        <div class="table-card ${table.status}">
                            <div class="table-header">
                                <div class="table-number">
                                    <i class="fas fa-chair"></i> ${table.table_number}
                                </div>
                                <span class="table-status status-${table.status}">${table.status}</span>
                            </div>
                            <div class="table-info">
                                <div><i class="fas fa-users"></i> <strong>Capacity:</strong> ${table.capacity} people</div>
                                <div><i class="fas fa-fingerprint"></i> <strong>UUID:</strong> ${table.uuid}</div>
                                ${table.qr_code ? `<div><i class="fas fa-qrcode"></i> <strong>QR:</strong> ${table.qr_code}</div>` : ''}
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-reset" onclick="resetTable('${table.uuid}')" ${table.status === 'available' ? 'disabled' : ''}>
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                                <button class="btn btn-edit" onclick="editTable('${table.uuid}')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="deleteTable('${table.uuid}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        async function addTable() {
            const tableNumber = document.getElementById('newTableNumber').value;
            const capacity = document.getElementById('newTableCapacity').value;
            const status = document.getElementById('newTableStatus').value;

            if (!tableNumber || !capacity) {
                alert('Please fill in all fields');
                return;
            }

            const formData = new FormData();
            formData.append('table_number', tableNumber);
            formData.append('capacity', capacity);
            formData.append('status', status);

            try {
                const response = await fetch(`${BASE_URL}/?req=api&action=staff_add_table`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const data = await response.json();
                logResponse(data, data.status === 'OK' ? 'success' : 'error');

                if (data.status === 'OK') {
                    alert('✅ Table added successfully!');
                    document.getElementById('newTableNumber').value = '';
                    document.getElementById('newTableCapacity').value = '';
                    loadTables();
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to add table'));
                }
            } catch (error) {
                logResponse({ error: error.message }, 'error');
                alert('❌ Error: ' + error.message);
            }
        }

        async function resetTable(tableUuid) {
            if (!confirm('Reset this table to available status?')) return;

            const formData = new FormData();
            formData.append('table_id', tableUuid);

            try {
                const response = await fetch(`${BASE_URL}/?req=api&action=staff_reset_table`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const data = await response.json();
                logResponse(data, data.status === 'OK' ? 'success' : 'error');

                if (data.status === 'OK') {
                    alert('✅ Table reset successfully!');
                    loadTables();
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to reset table'));
                }
            } catch (error) {
                logResponse({ error: error.message }, 'error');
                alert('❌ Error: ' + error.message);
            }
        }

        async function editTable(tableUuid) {
            alert('Edit functionality - Would open a modal to edit table details');
            // TODO: Implement edit modal
        }

        async function deleteTable(tableUuid) {
            if (!confirm('Are you sure you want to delete this table? This action cannot be undone.')) return;

            const formData = new FormData();
            formData.append('table_id', tableUuid);

            try {
                const response = await fetch(`${BASE_URL}/?req=api&action=staff_delete_table`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const data = await response.json();
                logResponse(data, data.status === 'OK' ? 'success' : 'error');

                if (data.status === 'OK') {
                    alert('✅ Table deleted successfully!');
                    loadTables();
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to delete table'));
                }
            } catch (error) {
                logResponse({ error: error.message }, 'error');
                alert('❌ Error: ' + error.message);
            }
        }

        // Load tables on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTables();
        });
    </script>
</body>
</html>
