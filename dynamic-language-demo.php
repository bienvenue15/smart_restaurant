<!DOCTYPE html>
<html lang="<?php 
require_once __DIR__ . '/src/config.php';
echo Language::getCurrentLanguage(); 
?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title data-translate-title="app_name"><?php echo __('app_name'); ?> - Dynamic Language Demo</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/language-switcher.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
min-height: 100vh;
padding: 20px;
}
.container { max-width: 1200px; margin: 0 auto; }
.header {
background: white;
border-radius: 15px;
padding: 25px;
margin-bottom: 30px;
box-shadow: 0 10px 30px rgba(0,0,0,0.1);
display: flex;
justify-content: space-between;
align-items: center;
}
.header h1 { color: #333; font-size: 28px; }
.demo-box {
background: white;
border-radius: 15px;
padding: 30px;
margin-bottom: 20px;
box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.demo-section { margin-bottom: 30px; }
.demo-section h2 {
color: #667eea;
margin-bottom: 15px;
border-bottom: 2px solid #f0f0f0;
padding-bottom: 10px;
}
.demo-item {
padding: 15px;
background: #f8f9fa;
border-radius: 8px;
margin-bottom: 10px;
}
.label {
font-weight: 600;
color: #666;
margin-bottom: 5px;
font-size: 13px;
}
.value {
font-size: 18px;
color: #333;
}
.btn {
padding: 12px 24px;
border: none;
border-radius: 8px;
cursor: pointer;
font-size: 15px;
font-weight: 500;
margin-right: 10px;
margin-bottom: 10px;
transition: all 0.3s;
}
.btn-primary { background: #667eea; color: white; }
.btn-success { background: #10b981; color: white; }
.btn-danger { background: #ef4444; color: white; }
.btn:hover { transform: translateY(-2px); opacity: 0.9; }
.badge {
display: inline-block;
padding: 6px 12px;
border-radius: 20px;
font-size: 14px;
margin-right: 8px;
}
.badge.pending { background: #fef3c7; color: #92400e; }
.badge.confirmed { background: #dbeafe; color: #1e40af; }
input[type="text"] {
width: 100%;
padding: 12px;
border: 2px solid #e5e7eb;
border-radius: 8px;
font-size: 15px;
}
.info {
background: #f0f9ff;
border-left: 4px solid #667eea;
padding: 15px;
margin: 20px 0;
border-radius: 5px;
}
.status {
position: fixed;
bottom: 20px;
right: 20px;
background: #10b981;
color: white;
padding: 15px 20px;
border-radius: 10px;
box-shadow: 0 4px 12px rgba(0,0,0,0.2);
display: none;
}
</style>
</head>
<body>
<div class="container">
<!-- Header -->
<div class="header">
<h1 data-translate="app_name"><?php echo __('app_name'); ?></h1>
<?php echo renderLanguageSwitcher(); ?>
</div>
<!-- Info Box -->
<div class="info">
<strong><i class="fas fa-info-circle"></i> Try It:</strong>
Click a language above and watch ALL text change INSTANTLY without page reload!
</div>
<div class="demo-box">
<!-- Common Translations -->
<div class="demo-section">
<h2><i class="fas fa-language"></i> <span data-translate="common">Common Words</span></h2>
<div class="demo-item">
<div class="label">Welcome Message</div>
<div class="value" data-translate="welcome"><?php echo __('welcome'); ?></div>
</div>
<div class="demo-item">
<div class="label">Greeting</div>
<div class="value" data-translate="hello"><?php echo __('hello'); ?></div>
</div>
<div class="demo-item">
<div class="label">Action Buttons</div>
<div>
<button class="btn btn-primary" data-translate="save"><?php echo __('save'); ?></button>
<button class="btn btn-danger" data-translate="cancel"><?php echo __('cancel'); ?></button>
<button class="btn btn-success" data-translate="confirm"><?php echo __('confirm'); ?></button>
</div>
</div>
</div>
<!-- Navigation -->
<div class="demo-section">
<h2><i class="fas fa-bars"></i> <span data-translate="nav">Navigation</span></h2>
<div class="demo-item">
<div class="value">
<i class="fas fa-tachometer-alt"></i> <span data-translate="nav.dashboard"><?php echo __('nav.dashboard'); ?></span>
</div>
</div>
<div class="demo-item">
<div class="value">
<i class="fas fa-utensils"></i> <span data-translate="nav.menu"><?php echo __('nav.menu'); ?></span>
</div>
</div>
<div class="demo-item">
<div class="value">
<i class="fas fa-receipt"></i> <span data-translate="nav.orders"><?php echo __('nav.orders'); ?></span>
</div>
</div>
</div>
<!-- Order Status -->
<div class="demo-section">
<h2><i class="fas fa-tasks"></i> <span data-translate="orders">Order Status</span></h2>
<div class="demo-item">
<div class="label">Status Badges</div>
<div>
<span class="badge pending" data-translate="orders.pending"><?php echo __('orders.pending'); ?></span>
<span class="badge confirmed" data-translate="orders.confirmed"><?php echo __('orders.confirmed'); ?></span>
</div>
</div>
<div class="demo-item">
<div class="label" data-translate="orders.order_number"><?php echo __('orders.order_number'); ?></div>
<div class="value">#12345</div>
</div>
</div>
<!-- Form Elements -->
<div class="demo-section">
<h2><i class="fas fa-edit"></i> <span data-translate="forms">Form Elements</span></h2>
<div class="demo-item">
<div class="label">Search Input</div>
<input type="text" 
placeholder="<?php echo __('search'); ?>" 
data-translate="search">
</div>
<div class="demo-item">
<div class="label">Menu Search</div>
<input type="text" 
placeholder="<?php echo __('menu.search_menu'); ?>" 
data-translate="menu.search_menu">
</div>
</div>
<!-- Menu Items -->
<div class="demo-section">
<h2><i class="fas fa-utensils"></i> <span data-translate="menu.title"><?php echo __('menu.title'); ?></span></h2>
<div class="demo-item">
<div class="value" data-translate="menu.add_to_cart"><?php echo __('menu.add_to_cart'); ?></div>
</div>
<div class="demo-item">
<div class="value" data-translate="menu.view_cart"><?php echo __('menu.view_cart'); ?></div>
</div>
<div class="demo-item">
<div class="label" data-translate="menu.price"><?php echo __('menu.price'); ?></div>
<div class="value" id="demo-price">RWF 5,000.00</div>
</div>
</div>
<!-- Staff Roles -->
<div class="demo-section">
<h2><i class="fas fa-users"></i> <span data-translate="staff">Staff Roles</span></h2>
<div class="demo-item">
<div class="value">
<i class="fas fa-user-shield"></i> <span data-translate="staff.admin"><?php echo __('staff.admin'); ?></span>
</div>
</div>
<div class="demo-item">
<div class="value">
<i class="fas fa-concierge-bell"></i> <span data-translate="staff.waiter"><?php echo __('staff.waiter'); ?></span>
</div>
</div>
</div>
</div>
<!-- Status Indicator -->
<div class="status" id="statusIndicator">
<i class="fas fa-check-circle"></i> <span id="statusText">Language changed!</span>
</div>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/dynamic-language-switcher.js"></script>
<script>
// Set BASE_URL for API calls
window.BASE_URL = '<?php echo BASE_URL; ?>';
// Listen for language changes
document.addEventListener('languageChanged', function(e) {
const lang = e.detail.language;
// Update price with proper formatting
const priceEl = document.getElementById('demo-price');
if (priceEl) {
priceEl.textContent = dynamicLangSwitcher.formatCurrency(5000);
}
// Show status notification
const status = document.getElementById('statusIndicator');
const statusText = document.getElementById('statusText');
const langNames = {
'en': 'English',
'fr': 'Français',
'rw': 'Ikinyarwanda',
'sw': 'Kiswahili'
};
statusText.textContent = `Changed to ${langNames[lang]}!`;
status.style.display = 'block';
setTimeout(() => {
status.style.display = 'none';
}, 3000);
});
// Log when translations are loaded
</script>
</body>
</html>
