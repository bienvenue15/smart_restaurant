<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Restaurant Settings - Admin Dashboard'; ?></title>
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
        .btn-info {
            background: #17a2b8;
            color: white;
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
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .subscription-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .subscription-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
        }
        .badge-trial {
            background: #fff3cd;
            color: #856404;
        }
        .badge-basic {
            background: #cfe2ff;
            color: #084298;
        }
        .badge-premium {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-enterprise {
            background: #d4edda;
            color: #155724;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
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
            }
            
            .card {
                padding: 18px;
                margin-bottom: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
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
            
            .page-header {
                padding: 16px 14px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            .card {
                padding: 14px;
            }
            
            .form-group label {
                font-size: 0.875rem;
            }
            
            .form-control {
                padding: 9px;
                font-size: 0.875rem;
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
    
    // Include sidebar
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <style>
        @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
    </style>
    
    <div class="admin-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h1 style="margin: 0;">
                    <i class="fas fa-cog"></i>
                    Restaurant Settings
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
        
        <?php if ($restaurant): ?>
        <!-- Subscription Info -->
        <div class="card">
            <div class="subscription-info">
                <h3>Subscription Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong>Plan:</strong><br>
                        <span class="badge badge-<?php echo strtolower($restaurant['subscription_plan'] ?? 'trial'); ?>">
                            <?php echo htmlspecialchars(ucfirst($restaurant['subscription_plan'] ?? 'Trial')); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <?php 
                        $isActive = $restaurant['is_active'] == 1;
                        $subscriptionEnd = $restaurant['subscription_end'] ?? null;
                        $isExpired = $subscriptionEnd && strtotime($subscriptionEnd) < time();
                        ?>
                        <span class="badge <?php echo ($isActive && !$isExpired) ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo ($isActive && !$isExpired) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <?php if ($subscriptionEnd): ?>
                    <div>
                        <strong>Expires:</strong><br>
                        <?php echo date('Y-m-d', strtotime($subscriptionEnd)); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Restaurant Profile -->
        <div class="card">
            <div class="card-header">
                <h2>Restaurant Profile</h2>
            </div>
            <form id="restaurant-form" onsubmit="saveRestaurant(event)">
                <div class="form-group">
                    <label>Restaurant Name *</label>
                    <input type="text" class="form-control" id="restaurant-name" name="name" 
                           value="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" id="restaurant-email" name="email" 
                           value="<?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" id="restaurant-phone" name="phone" 
                           value="<?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea class="form-control" id="restaurant-address" name="address" rows="3"><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <!-- QR Code Management -->
        <div class="card">
            <div class="card-header">
                <h2>QR Code Management</h2>
            </div>
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>QR Codes:</strong> QR codes are automatically regenerated when you update menu items, categories, or tables.
                You can manually regenerate all QR codes using the button below.
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-info" onclick="regenerateAllQRCodes()">
                    <i class="fas fa-qrcode"></i> Regenerate All QR Codes
                </button>
                <button class="btn btn-success" onclick="downloadAllQRCodes()">
                    <i class="fas fa-download"></i> Download All QR Codes (ZIP)
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="alert alert-error">
                Restaurant information not found. Please contact support.
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        
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
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        
        function saveRestaurant(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=update_restaurant`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Restaurant settings saved successfully! QR codes have been regenerated.', 'success');
                } else {
                    showAlert(data.message || 'Error saving settings', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function regenerateAllQRCodes() {
            if (!confirm('Regenerate all QR codes for all tables? This may take a moment.')) {
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
                } else {
                    showAlert(data.message || 'Error regenerating QR codes', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function downloadAllQRCodes() {
            showAlert('Generating ZIP file... This may take a moment.', 'info');
            
            // In a real implementation, you would create a ZIP file on the server
            // For now, just show a message
            showAlert('ZIP download feature coming soon. Please download QR codes individually from the Tables Management page.', 'info');
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

