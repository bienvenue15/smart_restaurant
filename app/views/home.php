<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Smart Restaurant'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <link rel="apple-touch-icon" href="<?php echo APP_LOGO_URL; ?>">
</head>
<body>
    <?php
        $metrics = $metrics ?? [];
        $menuCount = number_format((int)($metrics['menu_items'] ?? 0));
        $ordersServed = number_format((int)($metrics['orders_completed'] ?? 0));
        $activeRestaurants = number_format((int)($metrics['restaurants_active'] ?? 0));
        $tablesOnline = number_format((int)($metrics['tables_online'] ?? 0));
        $todayOrders = number_format((int)($metrics['today_orders'] ?? 0));
        $todayCalls = number_format((int)($metrics['waiter_calls_today'] ?? 0));
        $avgOrderValue = number_format((float)($metrics['avg_order_value'] ?? 0), 2);
    ?>
    <header class="site-header">
        <div class="container">
            <div class="brand-logo">
                <img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
                <div class="brand-copy">
                    <span class="brand-title">Smart Restaurant Cloud</span>
                    <span class="brand-tagline">Powered by Inovasiyo Ltd</span>
                </div>
            </div>
            <div class="header-links">
                <a href="<?php echo BASE_URL; ?>/?req=register" class="header-link">Register Restaurant</a>
                <a href="<?php echo BASE_URL; ?>/?req=staff" class="header-link highlight">Staff Portal</a>
            </div>
        </div>
    </header>
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                Welcome to<br>
                Smart <span class="highlight">Restaurant</span>
            </h1>
            <p class="hero-description">
                Experience the future of dining with our contactless QR platform. 
                Today alone we’ve handled <strong><?php echo $todayOrders; ?></strong> table orders and <strong><?php echo $todayCalls; ?></strong> waiter calls across all partner restaurants.
            </p>
            
            <div class="hero-buttons">
                <a href="?req=menu&table=T001" class="btn btn-primary">
                    <i class="fas fa-utensils"></i> View Our Menu
                </a>
                <a href="#features" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
            
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $menuCount; ?></h3>
                        <p class="stat-label">Menu Items Live</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $ordersServed; ?></h3>
                        <p class="stat-label">Orders Served</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $activeRestaurants; ?></h3>
                        <p class="stat-label">Restaurants Live</p>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $tablesOnline; ?></h3>
                        <p class="stat-label">Tables Online</p>
                    </div>
                </div>
            </div>
            
            <div class="trust-badges">
                <div class="badge">
                    <i class="fas fa-leaf"></i>
                    <span><?php echo $avgOrderValue > 0 ? 'Avg Order RWF ' . $avgOrderValue : 'Real-time Insights'; ?></span>
                </div>
                <div class="badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure & Audited</span>
                </div>
                <div class="badge">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $todayCalls; ?> Calls Today</span>
                </div>
            </div>
        </div>
        
        <div class="hero-image">
            <div class="food-preview">
                <i class="fas fa-utensils"></i>
                <div class="food-items">
                    <i class="fas fa-pizza-slice"></i>
                    <i class="fas fa-hamburger"></i>
                    <i class="fas fa-ice-cream"></i>
                    <i class="fas fa-coffee"></i>
                </div>
            </div>
        </div>
    </div>
    
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose Smart Restaurant?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Dynamic Menu</h3>
                    <p>QR codes on each table for instant menu access. Real-time updates for availability and prices.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-hand-paper"></i>
                    </div>
                    <h3>Waiter Call Widget</h3>
                    <p>Call waiters directly from your phone. Quick service requests without physical attention.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Easy Ordering</h3>
                    <p>Select items, customize orders, and send directly to the kitchen with a few taps.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Real-Time Updates</h3>
                    <p>Track your order status and estimated preparation time in real-time.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Contactless Experience</h3>
                    <p>Complete dining experience from your smartphone. Safe and convenient.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics Dashboard</h3>
                    <p>Management insights on popular items, order frequency, and customer preferences.</p>
                </div>
            </div>
            
            <div class="cta-section">
                <h2>Ready for a Smarter Dining Experience?</h2>
                <p>Scan the QR code on your table and start ordering now!</p>
                <a href="?req=menu&table=T001" class="btn btn-primary btn-lg">
                    <i class="fas fa-utensils"></i> Browse Menu Now
                </a>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Smart Restaurant by <strong>Inovasiyo Ltd</strong>. All rights reserved.</p>
            <p>Powered by Inovasiyo Ltd| <i class="fas fa-shield-alt"></i> GDPR & Rwanda Data Protection Compliant | 
            <a href="<?php echo BASE_URL; ?>/?req=staff" style="color: white; text-decoration: underline;">
                <i class="fas fa-user-shield"></i> Staff Portal
            </a>
            </p>
        </div>
    </footer>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
