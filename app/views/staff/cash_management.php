<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Management - Staff Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        .cash-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .cash-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .cash-card h2 {
            margin-top: 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .session-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .session-info h3 {
            margin-top: 0;
            color: #0056b3;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #cce5ff;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #212529;
        }
        
        .no-session {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-session i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <?php
    require_once 'app/core/Permission.php';
    Permission::require('handle_cash', false);
    ?>
    
    <div class="cash-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="back-link">
                ← Back to Dashboard
            </a>
            <div style="color: #666; font-weight: 600;">
                <i class="fas fa-clock"></i> <span id="currentTime"></span>
            </div>
        </div>
        
        <h1>Cash Register Management</h1>
        
        <div id="alertContainer"></div>
        
        <div id="sessionContainer">
            <div class="no-session">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading...</p>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let currentSession = null;
        
        // Load cash session on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCashSession();
        });
        
        function loadCashSession() {
            fetch(`${BASE_URL}/?req=api&action=staff_get_cash_session`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        currentSession = data.data;
                        if (currentSession) {
                            showOpenSession(currentSession);
                        } else {
                            showOpenSessionForm();
                        }
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load cash session');
                });
        }
        
        function showOpenSessionForm() {
            document.getElementById('sessionContainer').innerHTML = `
                <div class="cash-card">
                    <h2><i class="fas fa-cash-register"></i> Open Cash Register</h2>
                    <p>Count the cash in the register and enter the opening balance to start your session.</p>
                    
                    <div class="form-group">
                        <label for="openingBalance">Opening Balance ($)</label>
                        <input type="number" id="openingBalance" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    
                    <button class="btn btn-primary" onclick="openCashSession()">
                        <i class="fas fa-play-circle"></i> Open Cash Register
                    </button>
                </div>
            `;
        }
        
        function showOpenSession(session) {
            const expectedTotal = parseFloat(session.opening_balance) + parseFloat(session.sales_today || 0);
            
            document.getElementById('sessionContainer').innerHTML = `
                <div class="session-info">
                    <h3><i class="fas fa-check-circle"></i> Cash Session Active</h3>
                    <div class="info-row">
                        <span class="info-label">Opened At:</span>
                        <span class="info-value">${new Date(session.opened_at).toLocaleString()}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Opening Balance:</span>
                        <span class="info-value">$${parseFloat(session.opening_balance).toFixed(2)}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sales Today:</span>
                        <span class="info-value">$${parseFloat(session.sales_today || 0).toFixed(2)}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Expected Total:</span>
                        <span class="info-value" style="font-size: 18px; font-weight: bold; color: #28a745;">$${expectedTotal.toFixed(2)}</span>
                    </div>
                </div>
                
                <div class="cash-card">
                    <h2><i class="fas fa-lock"></i> Close Cash Register</h2>
                    <p>Count the cash in the register and enter the closing balance. The system will calculate any variance.</p>
                    
                    <div class="form-group">
                        <label for="closingBalance">Actual Closing Balance ($)</label>
                        <input type="number" id="closingBalance" step="0.01" min="0" placeholder="0.00" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Expected: $${expectedTotal.toFixed(2)}
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="closeNotes">Notes (Optional)</label>
                        <textarea id="closeNotes" placeholder="Any notes about the session..."></textarea>
                    </div>
                    
                    <button class="btn btn-danger" onclick="closeCashSession()">
                        <i class="fas fa-stop-circle"></i> Close Cash Register
                    </button>
                </div>
            `;
        }
        
        function openCashSession() {
            const openingBalance = document.getElementById('openingBalance').value;
            
            if (!openingBalance || parseFloat(openingBalance) < 0) {
                showError('Please enter a valid opening balance');
                return;
            }
            
            fetch(`${BASE_URL}/?req=api&action=staff_open_cash_session`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    opening_balance: parseFloat(openingBalance)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'OK') {
                    showSuccess('Cash register opened successfully');
                    setTimeout(() => loadCashSession(), 1000);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to open cash register');
            });
        }
        
        function closeCashSession() {
            const closingBalance = document.getElementById('closingBalance').value;
            
            if (!closingBalance || parseFloat(closingBalance) < 0) {
                showError('Please enter a valid closing balance');
                return;
            }
            
            if (!confirm('Are you sure you want to close the cash register?')) {
                return;
            }
            
            fetch(`${BASE_URL}/?req=api&action=staff_close_cash_session`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: currentSession.id,
                    closing_balance: parseFloat(closingBalance)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'OK') {
                    const variance = data.data.variance;
                    let message = 'Cash register closed successfully\\n\\n';
                    message += `Expected: $${data.data.expected.toFixed(2)}\\n`;
                    message += `Actual: $${data.data.actual.toFixed(2)}\\n`;
                    message += `Variance: $${variance.toFixed(2)}`;
                    
                    if (Math.abs(variance) > 0.01) {
                        if (data.data.requires_investigation) {
                            message += '\\n\\n⚠️ Large variance detected! Manager will be notified.';
                        }
                    } else {
                        message += '\\n\\n✅ Perfect balance!';
                    }
                    
                    alert(message);
                    setTimeout(() => loadCashSession(), 1000);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to close cash register');
            });
        }
        
        function showSuccess(message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> ${message}
                </div>
            `;
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
        
        function showError(message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </div>
            `;
        }
        
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
        setInterval(updateTime, 1000); // Update every second
    </script>
    
    <footer style="text-align: center; padding: 20px; color: #666; margin-top: 40px;">
        © 2025 Inovasiyo Ltd. All rights reserved.
    </footer>
</body>
</html>
