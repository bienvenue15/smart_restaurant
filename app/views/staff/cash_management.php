<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Register - Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        
        .cash-container {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .cash-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .cash-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .status-card.success { border-left-color: #10b981; }
        .status-card.info { border-left-color: #3b82f6; }
        .status-card.warning { border-left-color: #f59e0b; }
        
        .status-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .status-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin: 10px 0;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 13px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-btn {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .quick-action-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .quick-action-btn i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .session-info-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 2px solid #667eea30;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #4b5563;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .cash-container {
                margin-left: 0;
                padding: 15px;
            }
            
            .cash-header {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .cash-header h1 {
                font-size: 24px;
            }
            
            .page-header {
                padding: 18px;
            }
            
            .page-title {
                font-size: 1.375rem;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .status-card {
                padding: 20px;
            }
            
            .status-card .value {
                font-size: 28px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .card-title {
                font-size: 18px;
            }
            
            .session-info {
                padding: 16px;
            }
            
            .transactions-table {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 500px;
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.8125rem;
            }
            
            .btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .cash-container {
                margin-left: 0;
                padding: 8px;
            }
            
            .cash-header {
                padding: 10px;
            }
            
            .cash-header h1 {
                font-size: 0.95rem;
            }
            
            .page-title {
                font-size: 0.9rem;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .status-card {
                padding: 12px;
            }
            
            .status-card h3 {
                font-size: 0.7rem;
            }
            
            .status-card .value {
                font-size: 1.25rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 10px;
            }
            
            .card-title {
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 6px 4px;
                font-size: 0.625rem;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.75rem;
                width: 100%;
                justify-content: center;
            }
            
            .form-group {
                margin-bottom: 10px;
            }
            
            .form-group label {
                font-size: 0.75rem;
                margin-bottom: 5px;
            }
            
            .form-control {
                font-size: 14px;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    
    <?php
    require_once 'app/core/Permission.php';
    require_once 'app/models/Staff.php';
    
    Permission::require('handle_cash', false);
    
    // Check if user is manager
    $userRole = $_SESSION['role'] ?? 'waiter';
    $isManager = ($userRole === 'manager');
    
    // For managers, check if they are on shift
    if ($isManager) {
        $staffModel = new Staff();
        $isOnShift = $staffModel->isOnShift($_SESSION['staff_id']);
        
        if (!$isOnShift) {
            // Show "not on shift" message
            ?>
            <div class="cash-container">
                <div style="text-align: center; padding: 100px 30px;">
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; border: 4px solid #f59e0b;">
                        <i class="fas fa-clock" style="font-size: 70px; color: #d97706;"></i>
                    </div>
                    <h2 style="font-size: 32px; color: #1f2937; margin-bottom: 15px;">Not On Shift</h2>
                    <p style="color: #6b7280; font-size: 18px; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        You need to clock in before accessing the cash register.
                    </p>
                    <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); padding: 20px; border-radius: 12px; margin: 30px auto; max-width: 600px; border-left: 4px solid #ef4444;">
                        <i class="fas fa-info-circle" style="color: #dc2626; margin-right: 8px;"></i>
                        <span style="color: #7f1d1d; font-weight: 600;">Managers must be on shift to view cash register information.</span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="btn btn-primary" style="display: inline-block; padding: 15px 40px; font-size: 16px; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    // Check if this is being loaded in fragment mode (inside dashboard iframe)
    $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true';
    ?>
    
    <div class="cash-container">
        <?php if (!$isFragment): ?>
        <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <?php endif; ?>
        
        <div class="cash-header">
            <h1><i class="fas fa-cash-register"></i> Cash Register</h1>
            <p><i class="fas fa-clock"></i> <span id="currentTime"></span></p>
        </div>
        
        <div id="alertContainer"></div>
        <div id="sessionContainer">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Loading...</h3>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const RESTAURANT_ID = '<?php echo $restaurant_id; ?>';
        const USER_ROLE = '<?php echo $_SESSION['role'] ?? 'waiter'; ?>';
        let currentSession = null;
        
        // Audio notification system
        let audioContext = null;
        let audioUnlocked = false;
        let ringingInterval = null;

        function startContinuousRinging() {
            if (!audioContext) audioContext = new AudioContext();
            if (audioContext.state === 'suspended') audioContext.resume();
            playAlarmSound();
            ringingInterval = setInterval(() => playAlarmSound(), 2000);
        }

        function stopContinuousRinging() {
            if (ringingInterval) {
                clearInterval(ringingInterval);
                ringingInterval = null;
            }
            if (audioContext) {
                audioContext.close();
                audioContext = null;
            }
        }

        function playAlarmSound() {
            if (!audioContext || audioContext.state === 'closed') audioContext = new AudioContext();
            if (audioContext.state === 'suspended') {
                audioContext.resume().then(() => playAlarmSoundNow());
            } else {
                playAlarmSoundNow();
            }
        }

        function playAlarmSoundNow() {
            if (!audioContext || audioContext.state === 'closed') return;
            const now = audioContext.currentTime;
            for (let layer = 0; layer < 3; layer++) {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.type = 'square';
                gainNode.gain.value = 0.3;
                const frequencies = [800, 1000, 1200, 1450, 1200, 1000];
                const stepDuration = 0.1;
                frequencies.forEach((freq, index) => {
                    oscillator.frequency.setValueAtTime(freq + (layer * 50), now + (index * stepDuration));
                });
                oscillator.start(now);
                oscillator.stop(now + (stepDuration * frequencies.length));
            }
        }

        function checkForNewCalls() {
            fetch(`${BASE_URL}/staff/api/get_waiter_calls_count`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ restaurant_id: RESTAURANT_ID })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentCount = data.count;
                    const previousCount = parseInt(localStorage.getItem('previous_call_count') || '0');
                    if (currentCount > previousCount && currentCount > 0) {
                        startContinuousRinging();
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('NEW WAITER CALL!', {
                                body: `You have ${currentCount} pending call(s)`,
                                icon: BASE_URL + '/assets/images/logo.png',
                                requireInteraction: true
                            });
                        }
                        setTimeout(() => location.reload(), 3000);
                    }
                    localStorage.setItem('previous_call_count', currentCount);
                }
            })
            .catch(err => {});
        }

        document.addEventListener('DOMContentLoaded', function() {
            audioContext = new AudioContext();
            const banner = document.getElementById('audioEnableBanner');
            if (!banner) return;
            
            if (audioContext.state === 'running') {
                audioUnlocked = true;
                banner.style.display = 'none';
            } else {
                banner.style.display = 'block';
            }
            
            const unlockAudio = () => {
                if (!audioUnlocked && audioContext) {
                    audioContext.resume().then(() => {
                        audioUnlocked = true;
                        banner.style.display = 'none';
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        gainNode.gain.value = 0.001;
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.01);
                    });
                }
            };
            
            ['click', 'touchstart', 'keydown', 'mousedown', 'touchend', 'mousemove'].forEach(event => {
                document.body.addEventListener(event, unlockAudio, { once: true });
            });
            
            if (banner) banner.addEventListener('click', unlockAudio);
            
            setTimeout(() => {
                if (!audioUnlocked) {
                    audioContext.resume().catch(() => {});
                    const clickEvent = new MouseEvent('click', { view: window, bubbles: true, cancelable: true });
                    document.body.dispatchEvent(clickEvent);
                }
            }, 100);
            
            setInterval(checkForNewCalls, 5000);
            checkForNewCalls();
            
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        });
        
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        function loadCashSession() {
            fetch(`${BASE_URL}/?req=api&action=staff_get_cash_session`, {
                credentials: 'include'
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}`);
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.status === 'OK') {
                        currentSession = data.data;
                        currentSession ? showOpenSession(currentSession) : showOpenForm();
                    } else {
                        showError(data.message || 'Failed to load cash session');
                    }
                })
                .catch(e => {
                    showError('Failed to load session. Please refresh the page or contact support.');
                });
        }
        
        function showOpenForm() {
            // Managers and admins can open register
            document.getElementById('sessionContainer').innerHTML = `
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-play-circle"></i> Open Cash Register</h2>
                    <p style="color:#6b7280;margin-bottom:25px;">Count cash in register and enter opening balance</p>
                    <form onsubmit="openSession(event)">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Opening Balance (RWF) *</label>
                            <input type="number" id="openingBalance" step="0.01" min="0" placeholder="0.00" required autofocus>
                            <small>Enter total cash amount in register</small>
                        </div>
                        <button type="submit" class="btn btn-success" style="width:100%;"><i class="fas fa-unlock"></i> Open Register</button>
                    </form>
                </div>
            `;
        }
        
        function showOpenSession(s) {
            const sales = parseFloat(s.sales_today || 0);
            const cash = parseFloat(s.cash_in_hand || s.opening_balance);
            const expected = parseFloat(s.opening_balance) + sales;
            const variance = cash - expected;
            const varianceClass = variance >= 0 ? 'success' : 'danger';
            
            document.getElementById('sessionContainer').innerHTML = `
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 style="margin: 0; color: white;"><i class="fas fa-unlock"></i> Active Session</h2>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">Opened by ${s.opened_by || 'Staff'} on ${new Date(s.opened_at).toLocaleDateString()} at ${new Date(s.opened_at).toLocaleTimeString()}</p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; opacity: 0.9;">Session ID</div>
                            <div style="font-size: 24px; font-weight: bold;">#${s.id}</div>
                        </div>
                    </div>
                </div>
                
                <div class="status-grid">
                    <div class="status-card success">
                        <h3><i class="fas fa-wallet"></i> Opening</h3>
                        <div class="value">${fmt(s.opening_balance)}</div>
                        <div style="font-size:12px;color:#6b7280;">Starting Balance</div>
                    </div>
                    <div class="status-card info">
                        <h3><i class="fas fa-chart-line"></i> Sales</h3>
                        <div class="value">${fmt(sales)}</div>
                        <div style="font-size:12px;color:#6b7280;">Today's Revenue</div>
                    </div>
                    <div class="status-card warning">
                        <h3><i class="fas fa-money-bill-wave"></i> Cash</h3>
                        <div class="value">${fmt(cash)}</div>
                        <div style="font-size:12px;color:#6b7280;">Current Cash</div>
                    </div>
                    <div class="status-card">
                        <h3><i class="fas fa-calculator"></i> Expected</h3>
                        <div class="value">${fmt(expected)}</div>
                        <div style="font-size:12px;color:#6b7280;">Opening + Sales</div>
                    </div>
                </div>
                
                ${variance !== 0 ? `
                <div class="card" style="background: ${variance >= 0 ? 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)' : 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)'}; border-left: 4px solid ${variance >= 0 ? '#28a745' : '#dc3545'}; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0; color: ${variance >= 0 ? '#155724' : '#721c24'};">
                                <i class="fas fa-${variance >= 0 ? 'plus-circle' : 'minus-circle'}"></i> 
                                ${variance >= 0 ? 'Surplus' : 'Shortage'}
                            </h3>
                            <p style="margin: 5px 0 0 0; color: ${variance >= 0 ? '#155724' : '#721c24'}; opacity: 0.8;">
                                Cash vs Expected Difference
                            </p>
                        </div>
                        <div style="font-size: 32px; font-weight: bold; color: ${variance >= 0 ? '#28a745' : '#dc3545'};">
                            ${variance >= 0 ? '+' : ''}${fmt(Math.abs(variance))}
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div class="card" style="margin-bottom: 20px;">
                    <h3 style="margin-top: 0;"><i class="fas fa-info-circle"></i> Session Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Duration</div>
                            <div style="font-size: 18px; font-weight: bold; color: #1f2937;">${getSessionDuration(s.opened_at)}</div>
                        </div>
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Transactions</div>
                            <div style="font-size: 18px; font-weight: bold; color: #1f2937;">${s.transaction_count || 0}</div>
                        </div>
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Avg Transaction</div>
                            <div style="font-size: 18px; font-weight: bold; color: #1f2937;">${s.transaction_count > 0 ? fmt(sales / s.transaction_count) : 'RWF 0'}</div>
                        </div>
                    </div>
                </div>
                
                <div id="closeForm" style="display:none;">
                    <div class="card">
                        <h2 class="card-title" style="color:#ef4444;"><i class="fas fa-lock"></i> Close Register</h2>
                        <div class="session-info-box">
                            <div class="info-row">
                                <span class="info-label">Expected Total:</span>
                                <span class="info-value" style="font-size:20px;color:#667eea;">${fmt(expected)}</span>
                            </div>
                        </div>
                        <form onsubmit="closeSession(event)">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave"></i> Actual Closing Balance (RWF) *</label>
                                <input type="number" id="closingBalance" step="0.01" min="0" required>
                                <small>Count all cash and enter total</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                                <textarea id="closeNotes" placeholder="Any notes..."></textarea>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="submit" class="btn btn-danger" style="flex:1;"><i class="fas fa-lock"></i> Close</button>
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('closeForm').style.display='none'">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="quick-actions" id="quickActionsContainer">
                    <div class="quick-action-btn" onclick="document.getElementById('closeForm').style.display='block';document.getElementById('closingBalance').focus();">
                        <i class="fas fa-lock"></i>
                        <div style="font-weight:600;color:#1f2937;">Close Register</div>
                    </div>
                </div>
            `;
        }
        
        function openSession(e) {
            e.preventDefault();
            const bal = document.getElementById('openingBalance').value;
            if (!bal || parseFloat(bal) < 0) {
                return showError('Please enter a valid opening balance (0 or greater)');
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening...';
            
            
            fetch(`${BASE_URL}/?req=api&action=staff_open_cash_session`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ opening_balance: parseFloat(bal) })
            })
            .then(r => {
                
                // Get response as text first to see what we're getting
                return r.text().then(text => {
                    
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}, body: ${text}`);
                    }
                    
                    // Try to parse as JSON
                    try {
                        const jsonData = JSON.parse(text);
                        return jsonData;
                    } catch (parseError) {
                        throw new Error(`Invalid JSON response: ${text.substring(0, 200)}`);
                    }
                });
            })
            .then(data => {
                
                if (data.status === 'OK') {
                    showSuccess('Register opened successfully!');
                    setTimeout(loadCashSession, 1000);
                } else {
                    showError(data.message || 'Failed to open register');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-unlock"></i> Open Register';
                }
            })
            .catch(err => {
                showError('Failed to open register. Check console for details.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-unlock"></i> Open Register';
            });
        }
        
        function closeSession(e) {
            e.preventDefault();
            const bal = document.getElementById('closingBalance').value;
            const notes = document.getElementById('closeNotes').value;
            
            if (!bal || parseFloat(bal) < 0) return showError('Invalid balance');
            if (!confirm('Close register? Cannot be undone.')) return;
            
            fetch(`${BASE_URL}/?req=api&action=staff_close_cash_session`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: currentSession.id,
                    closing_balance: parseFloat(bal),
                    notes: notes
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    const v = data.data.variance;
                    let msg = Math.abs(v) < 0.01 ? 'Perfect balance!' : (v > 0 ? `Over by ${fmt(Math.abs(v))}` : `Short by ${fmt(Math.abs(v))}`);
                    alert(`Register Closed\\n\\nExpected: ${fmt(data.data.expected)}\\nActual: ${fmt(data.data.actual)}\\n\\n${msg}`);
                    setTimeout(loadCashSession, 1000);
                } else {
                    showError(data.message);
                }
            })
            .catch(() => showError('Failed to close'));
        }
        
        function fmt(amt) {
            return `RWF ${parseFloat(amt).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
        
        function getSessionDuration(openedAt) {
            const start = new Date(openedAt);
            const now = new Date();
            const diff = now - start;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else {
                return `${minutes}m`;
            }
        }
        
        function showSuccess(msg) {
            document.getElementById('alertContainer').innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i><span>${msg}</span></div>`;
            setTimeout(() => document.getElementById('alertContainer').innerHTML = '', 5000);
        }
        
        function showError(msg) {
            document.getElementById('alertContainer').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span>${msg}</span></div>`;
        }
        
        document.addEventListener('DOMContentLoaded', loadCashSession);
    </script>
</body>
</html>

