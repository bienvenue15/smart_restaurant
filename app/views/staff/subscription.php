<?php
// Subscription Details Page for Restaurant Owners
// Shows current plan, usage, limits, and days remaining

if (!isset($user) || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
    exit;
}

require_once __DIR__ . '/../../core/SubscriptionManager.php';

$manager = SubscriptionManager::getInstance();
$restaurantId = $user['restaurant_id'];

// Get restaurant and plan details
$restaurant = $manager->getPlanDetails($restaurantId);
if (!$restaurant) {
    header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
    exit;
}

// Get plan from database
$planName = $restaurant['plan'];
$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PWD);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_name = ?");
$stmt->execute([$planName]);
$planLimits = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planLimits) {
    // Fallback to trial
    $planLimits = [
        'display_name' => 'Trial Plan',
        'description' => 'Basic trial plan',
        'max_tables' => 10,
        'max_users' => 5,
        'max_menu_items' => 50,
        'max_orders_per_month' => 200,
        'features' => json_encode(['basic_pos', 'qr_ordering'])
    ];
}

// Parse features
$planLimits['features'] = json_decode($planLimits['features'], true) ?? [];

// Get subscription info
$daysRemaining = $restaurant['days_remaining'];
$isExpired = $restaurant['is_expired'];
$isExpiringSoon = $daysRemaining <= 14 && $daysRemaining > 0 && !$isExpired;

// Build subscription info array for display
$subscriptionInfo = [
    'plan_display_name' => $planLimits['display_name'] ?? ucfirst($planName),
    'plan_description' => $planLimits['description'] ?? '',
    'subscription_start' => $restaurant['subscription_start'],
    'subscription_end' => $restaurant['subscription_end'],
    'days_remaining' => $daysRemaining,
    'status' => $restaurant['is_active'] && !$isExpired ? 'active' : 'expired'
];

// Get current usage
$currentUsage = [
    'tables' => $db->query("SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_id = $restaurantId")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM staff_users WHERE restaurant_id = $restaurantId")->fetchColumn(),
    'menu_items' => $db->query("SELECT COUNT(*) FROM menu_items WHERE restaurant_id = $restaurantId")->fetchColumn(),
    'orders_this_month' => $db->query("SELECT COUNT(*) FROM orders WHERE restaurant_id = $restaurantId AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Details - My Restaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        .subscription-page {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .subscription-status {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            text-transform: capitalize;
        }
        
        .plan-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .plan-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .plan-badge.expiring {
            background: #fff3cd;
            color: #856404;
        }
        
        .plan-badge.expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .subscription-dates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .date-card {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .date-card.warning {
            border-left-color: #f39c12;
        }
        
        .date-card.danger {
            border-left-color: #e74c3c;
        }
        
        .date-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .date-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .plan-features {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .feature-item i {
            color: #27ae60;
            font-size: 1.2rem;
        }
        
        .usage-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .usage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .usage-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }
        
        .usage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .usage-title {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .usage-numbers {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .usage-numbers .current {
            color: #3498db;
        }
        
        .usage-numbers .limit {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .progress-bar {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
        }
        
        .progress-fill.warning {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }
        
        .progress-fill.danger {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .unlimited-badge {
            background: #27ae60;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .actions-section {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #f39c12;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        
        .alert i {
            font-size: 1.3rem;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .subscription-container {
                padding: 16px;
            }
            
            .page-header {
                padding: 18px;
            }
            
            .page-title {
                font-size: 1.375rem;
            }
            
            .plan-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .plan-card {
                padding: 18px;
            }
            
            .subscription-info {
                padding: 16px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .subscription-container {
                padding: 12px;
            }
            
            .page-header {
                padding: 14px;
            }
            
            .page-title {
                font-size: 1.125rem;
            }
            
            .plan-card {
                padding: 14px;
            }
            
            .plan-price {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    $page = 'subscription';
    include __DIR__ . '/_sidebar.php'; 
    ?>
    
    <main class="main-content">
        <div class="subscription-page">
            <div class="page-header">
                <h1><i class="fas fa-crown"></i> Subscription Details</h1>
                <p>Manage your subscription plan and view usage statistics</p>
            </div>
            
            <?php if ($isExpired): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Your subscription has expired!</strong>
                    Please contact support to renew your subscription and restore full access.
                </div>
            </div>
            <?php elseif ($isExpiringSoon): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Your subscription expires in <?php echo $daysRemaining; ?> day<?php echo $daysRemaining != 1 ? 's' : ''; ?>!</strong>
                    Contact support now to avoid service interruption.
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Current Plan Status -->
            <div class="subscription-status">
                <div class="status-header">
                    <div class="plan-name">
                        <?php echo htmlspecialchars($subscriptionInfo['plan_display_name']); ?> Plan
                    </div>
                    <div class="plan-badge <?php echo $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring' : 'active'); ?>">
                        <?php 
                        if ($isExpired) {
                            echo '<i class="fas fa-times-circle"></i> Expired';
                        } elseif ($isExpiringSoon) {
                            echo '<i class="fas fa-exclamation-triangle"></i> Expiring Soon';
                        } else {
                            echo '<i class="fas fa-check-circle"></i> Active';
                        }
                        ?>
                    </div>
                </div>
                
                <?php if (!empty($subscriptionInfo['plan_description'])): ?>
                <p style="color: #7f8c8d; margin-bottom: 20px;"><?php echo htmlspecialchars($subscriptionInfo['plan_description']); ?></p>
                <?php endif; ?>
                
                <div class="subscription-dates">
                    <div class="date-card">
                        <div class="date-label">Start Date</div>
                        <div class="date-value"><?php echo date('F j, Y', strtotime($subscriptionInfo['subscription_start'])); ?></div>
                    </div>
                    <div class="date-card <?php echo $isExpiringSoon ? 'warning' : ($isExpired ? 'danger' : ''); ?>">
                        <div class="date-label">Expiration Date</div>
                        <div class="date-value"><?php echo date('F j, Y', strtotime($subscriptionInfo['subscription_end'])); ?></div>
                    </div>
                    <div class="date-card <?php echo $isExpiringSoon ? 'warning' : ($isExpired ? 'danger' : ''); ?>">
                        <div class="date-label">Days Remaining</div>
                        <div class="date-value"><?php echo max(0, $daysRemaining); ?> days</div>
                    </div>
                </div>
            </div>
            
            <!-- Plan Features -->
            <?php if (!empty($planLimits['features'])): ?>
            <div class="plan-features">
                <div class="section-title">
                    <i class="fas fa-star"></i>
                    Plan Features
                </div>
                <div class="features-grid">
                    <?php foreach ($planLimits['features'] as $feature): ?>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Resource Usage -->
            <div class="usage-section">
                <div class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Resource Usage
                </div>
                
                <div class="usage-grid">
                    <!-- Tables Usage -->
                    <div class="usage-card">
                        <div class="usage-header">
                            <div class="usage-title">
                                <i class="fas fa-table"></i>
                                Tables
                            </div>
                            <?php if ($planLimits['max_tables'] >= 999999): ?>
                                <span class="unlimited-badge">Unlimited</span>
                            <?php endif; ?>
                        </div>
                        <div class="usage-numbers">
                            <span class="current"><?php echo $currentUsage['tables']; ?></span>
                            <?php if ($planLimits['max_tables'] < 999999): ?>
                                <span class="limit"> / <?php echo $planLimits['max_tables']; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($planLimits['max_tables'] < 999999): ?>
                            <?php $percentage = ($currentUsage['tables'] / $planLimits['max_tables']) * 100; ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : ''); ?>" 
                                     style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Staff Users Usage -->
                    <div class="usage-card">
                        <div class="usage-header">
                            <div class="usage-title">
                                <i class="fas fa-users"></i>
                                Staff Users
                            </div>
                            <?php if ($planLimits['max_users'] >= 999999): ?>
                                <span class="unlimited-badge">Unlimited</span>
                            <?php endif; ?>
                        </div>
                        <div class="usage-numbers">
                            <span class="current"><?php echo $currentUsage['users']; ?></span>
                            <?php if ($planLimits['max_users'] < 999999): ?>
                                <span class="limit"> / <?php echo $planLimits['max_users']; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($planLimits['max_users'] < 999999): ?>
                            <?php $percentage = ($currentUsage['users'] / $planLimits['max_users']) * 100; ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : ''); ?>" 
                                     style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Menu Items Usage -->
                    <div class="usage-card">
                        <div class="usage-header">
                            <div class="usage-title">
                                <i class="fas fa-utensils"></i>
                                Menu Items
                            </div>
                            <?php if ($planLimits['max_menu_items'] >= 999999): ?>
                                <span class="unlimited-badge">Unlimited</span>
                            <?php endif; ?>
                        </div>
                        <div class="usage-numbers">
                            <span class="current"><?php echo $currentUsage['menu_items']; ?></span>
                            <?php if ($planLimits['max_menu_items'] < 999999): ?>
                                <span class="limit"> / <?php echo $planLimits['max_menu_items']; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($planLimits['max_menu_items'] < 999999): ?>
                            <?php $percentage = ($currentUsage['menu_items'] / $planLimits['max_menu_items']) * 100; ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : ''); ?>" 
                                     style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Orders This Month -->
                    <div class="usage-card">
                        <div class="usage-header">
                            <div class="usage-title">
                                <i class="fas fa-shopping-cart"></i>
                                Orders This Month
                            </div>
                            <?php if ($planLimits['max_orders_per_month'] >= 999999): ?>
                                <span class="unlimited-badge">Unlimited</span>
                            <?php endif; ?>
                        </div>
                        <div class="usage-numbers">
                            <span class="current"><?php echo $currentUsage['orders_this_month']; ?></span>
                            <?php if ($planLimits['max_orders_per_month'] < 999999): ?>
                                <span class="limit"> / <?php echo $planLimits['max_orders_per_month']; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($planLimits['max_orders_per_month'] < 999999): ?>
                            <?php $percentage = ($currentUsage['orders_this_month'] / $planLimits['max_orders_per_month']) * 100; ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : ''); ?>" 
                                     style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="actions-section">
                <a href="<?php echo BASE_URL; ?>/?req=staff&action=support" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Contact Support
                </a>
                <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </main>
</body>
</html>
