<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Waiter Calls - Admin Dashboard'; ?></title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-icon.pending { background: #fff3cd; color: #856404; }
        .stat-icon.acknowledged { background: #d1ecf1; color: #0c5460; }
        .stat-icon.completed { background: #d4edda; color: #155724; }
        .stat-icon.cancelled { background: #f8d7da; color: #721c24; }
        .stat-details h3 {
            margin: 0;
            font-size: 28px;
            color: #2c3e50;
        }
        .stat-details p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
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
            align-items: center;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            padding: 8px 16px;
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
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .calls-grid {
            display: grid;
            gap: 15px;
        }
        .call-card {
            background: white;
            border-left: 4px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: start;
        }
        .call-card.priority-high { border-left-color: #e74c3c; }
        .call-card.priority-normal { border-left-color: #3498db; }
        .call-card.priority-low { border-left-color: #95a5a6; }
        .call-card.status-pending { background: #fffbf0; }
        .call-card.status-acknowledged { background: #f0f9ff; }
        .call-card.status-completed { background: #f0fff4; }
        .call-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .call-icon.order { background: #e3f2fd; color: #1976d2; }
        .call-icon.assistance { background: #fff3e0; color: #f57c00; }
        .call-icon.bill { background: #e8f5e9; color: #388e3c; }
        .call-icon.complaint { background: #ffebee; color: #d32f2f; }
        .call-icon.other { background: #f3e5f5; color: #7b1fa2; }
        .call-details {
            flex: 1;
        }
        .call-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .call-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-acknowledged { background: #d1ecf1; color: #0c5460; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-high { background: #ffebee; color: #c62828; }
        .badge-normal { background: #e3f2fd; color: #1565c0; }
        .badge-low { background: #f5f5f5; color: #616161; }
        .call-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        .info-item i {
            color: #3498db;
        }
        .call-message {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            font-size: 14px;
            color: #555;
            margin-top: 10px;
        }
        .call-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 140px;
        }
        .call-actions .btn {
            width: 100%;
            justify-content: center;
            padding: 10px;
            font-size: 13px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        
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
            
            .tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            
            .tab {
                white-space: nowrap;
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .calls-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .call-card {
                padding: 14px;
            }
            
            .btn {
                padding: 9px 16px;
                font-size: 0.8125rem;
            }
        }
        
        @media (max-width: 480px) {
            .admin-container {
                padding: 12px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            .tab {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .call-card {
                padding: 12px;
            }
            
            .btn {
                padding: 8px 12px;
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
    
    // Include sidebar when not embedded in dashboard iframe
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h1 style="margin: 0;">
                    <i class="fas fa-bell"></i>
                    Waiter Calls Management
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3 id="stat-pending">0</h3>
                    <p>Pending Calls</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon acknowledged">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-details">
                    <h3 id="stat-acknowledged">0</h3>
                    <p>Acknowledged</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-details">
                    <h3 id="stat-completed">0</h3>
                    <p>Completed Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cancelled">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-details">
                    <h3 id="stat-cancelled">0</h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select id="filter-status" onchange="loadCalls()">
                    <option value="all">All Statuses</option>
                    <option value="pending" selected>Pending</option>
                    <option value="acknowledged">Acknowledged</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Type</label>
                <select id="filter-type" onchange="loadCalls()">
                    <option value="all">All Types</option>
                    <option value="order">Order</option>
                    <option value="assistance">Assistance</option>
                    <option value="bill">Bill</option>
                    <option value="complaint">Complaint</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Priority</label>
                <select id="filter-priority" onchange="loadCalls()">
                    <option value="all">All Priorities</option>
                    <option value="high">High</option>
                    <option value="normal">Normal</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" id="filter-date" value="<?php echo date('Y-m-d'); ?>" onchange="loadCalls()">
            </div>
            <button class="btn btn-primary" onclick="loadCalls()" style="align-self: flex-end;">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
        
        <div id="alert-container"></div>
        
        <div class="calls-grid" id="calls-grid">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading waiter calls...</p>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const BASE_PATH = BASE_URL; // Alias for consistency
        const RESTAURANT_ID = '<?php echo isset($_SESSION['staff_user']['restaurant_uuid']) ? $_SESSION['staff_user']['restaurant_uuid'] : ''; ?>';
        const CURRENT_USER_ID = '<?php echo $user['uuid']; ?>';
        
        // Audio notification system for waiter calls
        let audioContext = null;
        let audioUnlocked = false;
        let ringingInterval = null;
        let lastCallIds = [];
        
        function startContinuousRinging() {
            if (ringingInterval) return;
            ringingInterval = setInterval(() => {
                playAlarmSoundNow();
            }, 2000);
        }
        
        function stopContinuousRinging() {
            if (ringingInterval) {
                clearInterval(ringingInterval);
                ringingInterval = null;
            }
        }
        
        function playAlarmSound() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioContext.state === 'suspended') {
                audioContext.resume().then(() => playAlarmSoundNow()).catch(() => {});
            } else {
                playAlarmSoundNow();
            }
        }
        
        function playAlarmSoundNow() {
            if (!audioContext) return;
            const now = audioContext.currentTime;
            const osc1 = audioContext.createOscillator();
            const osc2 = audioContext.createOscillator();
            const osc3 = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            osc1.type = 'square';
            osc2.type = 'square';
            osc3.type = 'square';
            osc1.frequency.setValueAtTime(800, now);
            osc2.frequency.setValueAtTime(1200, now);
            osc3.frequency.setValueAtTime(1450, now);
            
            gainNode.gain.setValueAtTime(0, now);
            gainNode.gain.linearRampToValueAtTime(0.3, now + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
            
            osc1.connect(gainNode);
            osc2.connect(gainNode);
            osc3.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            osc1.start(now);
            osc2.start(now);
            osc3.start(now);
            osc1.stop(now + 0.5);
            osc2.stop(now + 0.5);
            osc3.stop(now + 0.5);
        }
        
        function checkForNewCalls() {
            fetch(`${BASE_URL}/?req=api&action=staff_get_waiter_calls&restaurant_id=${RESTAURANT_ID}&status=pending`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK' && data.data && data.data.calls) {
                        const pendingCalls = data.data.calls;
                        const currentCallIds = pendingCalls.map(call => call.id);
                        
                        const storedIds = localStorage.getItem('lastCallIds');
                        const previousCallIds = storedIds ? JSON.parse(storedIds) : [];
                        
                        const hasNewCalls = currentCallIds.some(id => !previousCallIds.includes(id));
                        
                        if (pendingCalls.length > 0) {
                            if (hasNewCalls || previousCallIds.length === 0) {
                                startContinuousRinging();
                                if ('Notification' in window && Notification.permission === 'granted') {
                                    new Notification('New Waiter Call!', {
                                        body: `${pendingCalls.length} pending call(s) require attention`,
                                        icon: '<?php echo BASE_URL; ?>/assets/images/notification-icon.png',
                                        requireInteraction: true
                                    });
                                }
                            }
                        } else {
                            stopContinuousRinging();
                        }
                        
                        localStorage.setItem('lastCallIds', JSON.stringify(currentCallIds));
                    }
                })
                .catch(err => {});
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            const unlockAudio = () => {
                if (!audioUnlocked && audioContext) {
                    audioContext.resume().then(() => {
                        audioUnlocked = true;
                    }).catch(() => {});
                }
            };
            
            ['click', 'touchstart', 'keydown', 'mousedown', 'touchend', 'mousemove'].forEach(eventType => {
                document.addEventListener(eventType, unlockAudio, { once: true });
            });
            
            setTimeout(() => {
                if (audioContext && audioContext.state === 'suspended') {
                    audioContext.resume().catch(() => {});
                }
            }, 100);
            
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
            
            checkForNewCalls();
            setInterval(checkForNewCalls, 5000);
        });
        
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
        
        // Load calls when page loads (already in DOMContentLoaded above with audio system)
        window.addEventListener('load', function() {
            loadCalls();
            updateStats();
        });
        
        function loadCalls() {
            const status = document.getElementById('filter-status').value;
            const type = document.getElementById('filter-type').value;
            const priority = document.getElementById('filter-priority').value;
            const date = document.getElementById('filter-date').value;
            
            const params = new URLSearchParams({
                restaurant_id: RESTAURANT_ID,
                status,
                type: type !== 'all' ? type : '',
                priority: priority !== 'all' ? priority : '',
                date
            });
            
            
            fetch(`${BASE_PATH}/?req=api&action=staff_get_waiter_calls&${params}`, {
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    const calls = (data.data && data.data.calls) || data.calls || [];
                    renderCalls(calls);
                } else {
                    showAlert(data.message || 'Error loading calls', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function updateStats() {
            fetch(`${BASE_PATH}/?req=api&action=staff_get_waiter_calls_stats&restaurant_id=${RESTAURANT_ID}`, {
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK' && data.stats) {
                    document.getElementById('stat-pending').textContent = data.stats.pending || 0;
                    document.getElementById('stat-acknowledged').textContent = data.stats.acknowledged || 0;
                    document.getElementById('stat-completed').textContent = data.stats.completed || 0;
                    document.getElementById('stat-cancelled').textContent = data.stats.cancelled || 0;
                }
            })
            .catch(() => {});
        }
        
        // Alias for loadStats used by auto-refresh
        function loadStats() {
            updateStats();
        }
        
        function renderCalls(calls) {
            const grid = document.getElementById('calls-grid');
            
            if (calls.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No waiter calls found</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = calls.map(call => {
            const typeIcons = {
                order: 'shopping-cart',
                assistance: 'hand-paper',
                bill: 'file-invoice',
                complaint: 'exclamation-triangle',
                other: 'question-circle'
            };                const elapsed = getElapsedTime(call.created_at);
                const assignedName = call.assigned_name || 'Unassigned';
                
                return `
                    <div class="call-card priority-${call.priority} status-${call.status}">
                        <div class="call-icon ${call.request_type}">
                            <i class="fas fa-${typeIcons[call.request_type] || 'bell'}"></i>
                        </div>
                        <div class="call-details">
                            <div class="call-header">
                                <div class="call-title">
                                    Table ${escapeHtml(call.table_number)}
                                    <span class="badge badge-${call.priority}">${call.priority}</span>
                                </div>
                                <span class="badge badge-${call.status}">${call.status}</span>
                            </div>
                            <div class="call-info">
                                <div class="info-item">
                                    <i class="fas fa-tag"></i>
                                    <span>${capitalizeFirst(call.request_type)}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>${elapsed}</span>
                                </div>
                                ${call.assigned_to ? `
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <span>${escapeHtml(assignedName)}</span>
                                </div>
                                ` : ''}
                            </div>
                            ${call.message ? `
                            <div class="call-message">
                                <strong>Message:</strong> ${escapeHtml(call.message)}
                            </div>
                            ` : ''}
                        </div>
                        <div class="call-actions">
                            ${call.status === 'pending' ? `
                            <button class="btn btn-info" onclick="acknowledgeCall(${call.id})">
                                <i class="fas fa-check"></i> Acknowledge
                            </button>
                            ` : ''}
                            ${call.status === 'acknowledged' ? `
                            <button class="btn btn-success" onclick="completeCall(${call.id})">
                                <i class="fas fa-check-double"></i> Complete
                            </button>
                            ` : ''}
                            ${call.status !== 'completed' && call.status !== 'cancelled' ? `
                            <button class="btn btn-danger" onclick="cancelCall(${call.id})">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            ` : ''}
                            ${call.status === 'pending' && !call.assigned_to ? `
                            <button class="btn btn-primary" onclick="assignToMe(${call.id})">
                                <i class="fas fa-user-check"></i> Assign to Me
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function acknowledgeCall(callId) {
            updateCallStatus(callId, 'acknowledged');
        }
        
        function completeCall(callId) {
            updateCallStatus(callId, 'completed');
        }
        
        function cancelCall(callId) {
            if (!confirm('Are you sure you want to cancel this call?')) return;
            updateCallStatus(callId, 'cancelled');
        }
        
        function assignToMe(callId) {
            const formData = new FormData();
            formData.append('call_id', callId);
            formData.append('staff_id', CURRENT_USER_ID);
            
            fetch(`${BASE_PATH}/?req=api&action=staff_assign_call`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Call assigned to you', 'success');
                    loadCalls();
                    updateStats();
                } else {
                    showAlert(data.message || 'Error assigning call', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function updateCallStatus(callId, status) {
            const formData = new FormData();
            formData.append('call_id', callId);
            formData.append('status', status);
            
            fetch(`${BASE_PATH}/?req=api&action=staff_update_call_status`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => {
                if (!r.ok && r.status === 403) {
                    return r.json().then(data => {
                        if (data.requires_shift) {
                            showAlert('⏰ You must clock in before responding to calls. Please clock in from your dashboard.', 'error');
                            setTimeout(() => {
                                if (confirm('You need to clock in first. Go to dashboard now?')) {
                                    window.location.href = BASE_PATH + '/?req=staff&action=dashboard';
                                }
                            }, 500);
                            throw new Error('Not clocked in');
                        }
                        throw new Error(data.message || 'Access denied');
                    });
                }
                return r.json();
            })
            .then(data => {
                if (data.status === 'OK') {
                    showAlert(`Call ${status}`, 'success');
                    // Stop alarm when staff responds to call
                    if (status === 'acknowledged' || status === 'completed') {
                        stopContinuousRinging();
                    }
                    loadCalls();
                    updateStats();
                } else {
                    showAlert(data.message || 'Error updating call', 'error');
                }
            })
            .catch(err => {
                if (err.message !== 'Not clocked in') {
                    showAlert('Error: ' + err.message, 'error');
                }
            });
        }
        
        function getElapsedTime(timestamp) {
            const now = new Date();
            const created = new Date(timestamp);
            const diff = Math.floor((now - created) / 1000); // seconds
            
            if (diff < 60) return `${diff}s ago`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }
        
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
        
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

