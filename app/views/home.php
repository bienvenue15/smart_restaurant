<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartMenu - Complete Restaurant Management System</title>
<meta name="description" content="All-in-one restaurant management platform with QR ordering, staff tracking, payment processing, and real-time analytics. Start your free trial today.">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
<link rel="apple-touch-icon" href="<?php echo APP_LOGO_URL; ?>">
<style>
/* Prevent horizontal scroll */
* {
box-sizing: border-box;
}
html, body {
margin: 0;
padding: 0;
overflow-x: hidden;
width: 100%;
max-width: 100%;
}
.container {
max-width: 100%;
overflow-x: hidden;
}
@keyframes fadeIn {
from { opacity: 0; }
to { opacity: 1; }
}
@keyframes slideInUp {
from {
opacity: 0;
transform: translateY(30px);
}
to {
opacity: 1;
transform: translateY(0);
}
}
@keyframes pulse {
0%, 100% { transform: scale(1); }
50% { transform: scale(1.05); }
}
@keyframes float {
0%, 100% { transform: translateY(0px); }
50% { transform: translateY(-10px); }
}
@keyframes slideInLeft {
from {
opacity: 0;
transform: translateX(-50px);
}
to {
opacity: 1;
transform: translateX(0);
}
}
@keyframes slideInRight {
from {
opacity: 0;
transform: translateX(50px);
}
to {
opacity: 1;
transform: translateX(0);
}
}
@keyframes highlightPulse {
0%, 100% { 
box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.4);
}
50% { 
box-shadow: 0 0 20px 5px rgba(255, 107, 53, 0.6);
}
}
.hero-tagline {
font-size: 1.8rem;
font-weight: 700;
background: linear-gradient(120deg, #FF6B35 0%, #FFC107 50%, #FF6B35 100%);
color: #2C1810;
padding: 18px 35px;
border-radius: 60px;
margin-bottom: 30px;
animation: slideInLeft 0.8s ease-out;
text-shadow: 1px 1px 3px rgba(255,255,255,0.3);
box-shadow: 0 6px 20px rgba(255, 107, 53, 0.35);
display: inline-block;
border: 3px solid #FFD54F;
}
.hero-tagline i {
color: #2C1810;
margin: 0 8px;
text-shadow: none;
}
.hero-benefit-card {
background: linear-gradient(135deg, rgba(255,254,242,0.98) 0%, rgba(255,255,255,0.95) 100%);
border-radius: 20px;
padding: 30px;
margin: 20px 0;
box-shadow: 0 6px 25px rgba(255, 107, 53, 0.15);
border-left: 6px solid #FF6B35;
animation: slideInRight 1s ease-out;
transition: all 0.3s ease;
border: 2px solid #FFE4D6;
}
.hero-benefit-card:hover {
transform: translateY(-8px);
box-shadow: 0 12px 35px rgba(255, 107, 53, 0.25);
animation: highlightPulse 2s infinite;
border-color: #FFC107;
}
.hero-benefit-card h3 {
color: #2C1810;
font-size: 1.4rem;
margin-bottom: 15px;
display: flex;
align-items: center;
gap: 12px;
font-weight: 700;
}
.hero-benefit-card h3 i {
color: #FF6B35;
font-size: 1.6rem;
}
.hero-benefit-card p {
color: #6B4423;
line-height: 1.8;
margin: 0;
font-size: 1.05rem;
}
.hero-benefit-highlight {
display: inline-block;
background: linear-gradient(120deg, #FF6B35 0%, #FFC107 100%);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
background-clip: text;
font-weight: 700;
}
@media screen and (max-width: 768px) {
.hero-tagline {
font-size: 1.4rem;
}
.hero-benefit-card {
padding: 20px;
}
.hero-benefit-card h3 {
font-size: 1.1rem;
}
}
.demo-card-animated {
animation: slideInUp 0.6s ease-out;
}
.demo-card-animated:nth-child(1) {
animation-delay: 0.1s;
}
.demo-card-animated:nth-child(2) {
animation-delay: 0.2s;
}
.demo-card-animated:nth-child(3) {
animation-delay: 0.3s;
}
.qr-scan-pulse {
animation: pulse 2s ease-in-out infinite;
}
.feature-badge-float {
animation: float 3s ease-in-out infinite;
}
/* ========================================
RESPONSIVE DESIGN - MOBILE FIRST
======================================== */
/* Universal card/section constraints for all devices */
.container,
.hero-section,
.features-section,
.how-it-works-section,
.cta-section,
.site-footer {
max-width: 100% !important;
margin-left: auto !important;
margin-right: auto !important;
padding-left: clamp(10px, 3vw, 20px) !important;
padding-right: clamp(10px, 3vw, 20px) !important;
box-sizing: border-box !important;
}
/* Prevent any element from breaking layout */
* {
max-width: 100%;
word-wrap: break-word;
overflow-wrap: break-word;
}
img, video, iframe {
max-width: 100%;
height: auto;
}
/* Base styles for mobile (already mobile-friendly) */
/* Tablets and Small Laptops (768px and up) */
@media screen and (max-width: 768px) {
/* Force proper spacing on all sections */
body > * {
margin-left: 0 !important;
margin-right: 0 !important;
}
/* Header */
.site-header .container {
flex-direction: column;
gap: 20px;
padding: 15px 10px !important;
}
.header-links {
width: 100%;
justify-content: center;
flex-wrap: wrap;
gap: 10px;
}
.header-link {
font-size: 0.9rem;
padding: 8px 15px;
}
.brand-logo {
flex-direction: column;
text-align: center;
gap: 10px;
}
.brand-logo img {
width: 60px;
height: 60px;
}
/* Hero Section */
.hero-section {
padding: 40px 10px !important;
margin: 0 !important;
}
/* Two Column Layout - Stack on Mobile */
.hero-section .container > div {
grid-template-columns: 1fr !important;
gap: 30px !important;
min-height: auto !important;
}
.hero-content {
padding: 0 5px !important;
}
.hero-section h1 {
font-size: 1.75rem !important;
line-height: 1.3;
margin-bottom: 15px !important;
padding: 0 5px !important;
}
.hero-section p {
font-size: 0.95rem !important;
padding: 0 5px !important;
}
.hero-buttons {
flex-direction: column;
gap: 15px;
width: 100%;
padding: 0 5px !important;
}
.hero-buttons .btn {
width: 100%;
justify-content: center;
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}
/* Stats Grid */
.stats-grid {
grid-template-columns: 1fr !important;
gap: 15px;
padding: 0 5px !important;
margin: 20px 0 !important;
}
.stat-card {
padding: 20px 15px !important;
margin: 0 !important;
}
/* Trust Badges */
.trust-badges {
flex-direction: column;
gap: 15px;
padding: 0 5px !important;
}
.trust-badge {
width: 100%;
margin: 0 !important;
}
/* Features Grid */
.features-section {
padding: 50px 10px !important;
margin: 30px 0 !important;
}
.features-grid {
grid-template-columns: 1fr !important;
gap: 20px;
padding: 0 !important;
}
.feature-card {
padding: 20px 15px !important;
margin: 0 !important;
}
/* How It Works Section */
.how-it-works-section {
padding: 40px 10px !important;
margin: 40px 0 !important;
max-width: 100% !important;
overflow-x: hidden !important;
}
.timeline-container {
max-width: 100% !important;
overflow-x: hidden !important;
padding: 0 !important;
}
.how-it-works-section h2 {
font-size: 1.6rem !important;
padding: 0 5px !important;
}
.how-it-works-section p {
font-size: 0.95rem !important;
padding: 0 5px !important;
}
.timeline-item {
flex-direction: column !important;
gap: 15px !important;
margin-bottom: 25px !important;
padding: 0 !important;
}
.timeline-number {
width: 50px !important;
height: 50px !important;
font-size: 1.2rem !important;
margin: 0 auto !important;
}
.timeline-content {
padding: 15px 12px !important;
margin: 0 !important;
width: 100% !important;
}
.timeline-content h3 {
font-size: 1.1rem !important;
margin-bottom: 10px !important;
}
.timeline-content p {
font-size: 0.9rem !important;
line-height: 1.5 !important;
}
/* Speed Comparison */
.how-it-works-section > div > div {
padding: 25px 10px !important;
margin: 20px 0 !important;
}
.how-it-works-section .speed-comparison,
.how-it-works-section > div > div > div[style*="grid"] {
grid-template-columns: 1fr !important;
gap: 15px !important;
padding: 0 !important;
}
/* Section Titles */
.section-title {
font-size: 1.6rem !important;
padding: 0 5px !important;
}
/* CTA Section */
.cta-section {
padding: 40px 10px !important;
margin: 30px 0 !important;
}
.cta-section h2 {
font-size: 1.6rem !important;
padding: 0 5px !important;
}
.cta-section p {
padding: 0 5px !important;
}
.cta-section .btn-lg {
padding: 14px 20px !important;
font-size: 0.95rem !important;
margin: 10px 5px !important;
}
.cta-section > div {
padding: 20px 10px !important;
margin: 20px 0 !important;
}
/* Contact Buttons */
.cta-section a[href^="mailto"],
.cta-section a[href^="tel"] {
padding: 12px 15px !important;
font-size: 0.85rem !important;
width: 100%;
justify-content: center;
margin: 5px 0 !important;
}
/* Footer */
.site-footer {
padding: 30px 10px !important;
}
.site-footer .footer-content {
flex-direction: column;
gap: 30px;
text-align: center;
padding: 0 5px !important;
}
.footer-section {
width: 100% !important;
padding: 0 !important;
}
/* Demo Modal */
.demo-modal {
padding: 10px !important;
}
.demo-modal-content {
width: 95% !important;
max-width: 95% !important;
padding: 25px 15px !important;
max-height: 90vh;
overflow-y: auto;
margin: 0 auto !important;
}
.demo-steps {
grid-template-columns: 1fr !important;
gap: 15px !important;
}
.demo-features {
grid-template-columns: 1fr !important;
gap: 15px !important;
}
/* Info Modal */
.info-modal-content {
width: 95% !important;
max-width: 95% !important;
padding: 25px 15px !important;
max-height: 90vh;
overflow-y: auto;
margin: 0 auto !important;
}
/* Contact Form Modal */
.contact-modal-content {
width: 95% !important;
max-width: 95% !important;
padding: 25px 15px !important;
margin: 0 auto !important;
}
.form-row {
grid-template-columns: 1fr !important;
}
/* Hide decorative food emojis on mobile */
.hero-section > div[style*="position: absolute"],
.features-grid + div > div[style*="position: absolute"],
.cta-section > div[style*="position: absolute"] {
display: none !important;
}
}
/* Small Mobile Devices (480px and down) */
@media screen and (max-width: 480px) {
/* Extreme margin safety for small phones */
body {
padding: 0 !important;
margin: 0 !important;
}
.container,
.hero-section,
.features-section,
.how-it-works-section,
.cta-section {
padding-left: 8px !important;
padding-right: 8px !important;
}
.hero-section h1 {
font-size: 1.5rem !important;
padding: 0 3px !important;
}
.section-title {
font-size: 1.4rem !important;
padding: 0 3px !important;
}
.how-it-works-section h2 {
font-size: 1.4rem !important;
}
.timeline-content {
padding: 12px 10px !important;
}
.timeline-content h3 {
font-size: 1rem !important;
}
.timeline-content p {
font-size: 0.85rem !important;
}
.btn {
font-size: 0.85rem !important;
padding: 10px 15px !important;
}
.stat-card {
padding: 15px 10px !important;
}
.stat-card h3 {
font-size: 1.2rem !important;
}
.stat-card p {
font-size: 0.8rem !important;
}
.feature-card {
padding: 15px 10px !important;
}
/* Ensure buttons don't overflow */
.hero-buttons .btn,
.cta-section .btn {
font-size: 0.8rem !important;
padding: 10px 12px !important;
white-space: normal !important;
line-height: 1.3 !important;
}
/* Contact section improvements */
.cta-section > div {
padding: 15px 8px !important;
margin: 15px 0 !important;
}
.cta-section h3 {
font-size: 1.1rem !important;
}
/* Modal adjustments */
.demo-modal-content,
.info-modal-content,
.contact-modal-content {
width: 98% !important;
padding: 20px 10px !important;
}
}
/* Landscape mode adjustments */
@media screen and (max-height: 600px) and (orientation: landscape) {
.hero-section {
padding: 30px 15px !important;
}
.how-it-works-section {
padding: 40px 15px !important;
}
.stats-grid {
grid-template-columns: repeat(2, 1fr) !important;
}
}
/* Large Tablets (768px to 1024px) */
@media screen and (min-width: 769px) and (max-width: 1024px) {
.container {
max-width: 95%;
}
.features-grid {
grid-template-columns: repeat(2, 1fr) !important;
}
.stats-grid {
grid-template-columns: repeat(2, 1fr) !important;
}
.timeline-item {
gap: 25px !important;
}
}
/* Desktop optimization (1440px and up) */
@media screen and (min-width: 1440px) {
.container {
max-width: 1320px;
}
.hero-section h1 {
font-size: 3.5rem !important;
}
.section-title {
font-size: 2.8rem !important;
}
}
/* Print styles */
@media print {
.site-header,
.hero-buttons,
.cta-section,
.demo-modal,
.contact-modal,
.info-modal,
button {
display: none !important;
}
}
</style>
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
<img src="<?php echo APP_LOGO_URL; ?>" alt="SmartMenu logo">
<div class="brand-copy">
<span class="brand-title">SmartMenu</span>
<span class="brand-tagline">Powered by Inovasiyo Ltd</span>
</div>
</div>
<nav class="header-nav" role="navigation" aria-label="Main navigation">
<a href="<?php echo BASE_URL; ?>/?req=register" class="nav-btn nav-btn-signup" aria-label="Create new account">
<i class="fas fa-user-plus"></i>
<span>Sign Up</span>
</a>
<a href="<?php echo BASE_URL; ?>/?req=staff" class="nav-btn nav-btn-signin" aria-label="Sign in to your account">
<i class="fas fa-sign-in-alt"></i>
<span>Sign In</span>
</a>
</nav>
</div>
</header>
<div class="hero-section" style="position: relative; overflow: visible; z-index: 1;">
<!-- Food Stickers Decoration -->
<div style="position: absolute; top: 15%; left: 5%; font-size: 3rem; opacity: 0.15; transform: rotate(-15deg); animation: float 6s ease-in-out infinite; pointer-events: none;"><i class="fas fa-pizza-slice"></i></div>
<div style="position: absolute; top: 25%; right: 8%; font-size: 2.5rem; opacity: 0.15; transform: rotate(15deg); animation: float 5s ease-in-out infinite; animation-delay: 0.5s; pointer-events: none;"><i class="fas fa-hamburger"></i></div>
<div style="position: absolute; bottom: 15%; left: 8%; font-size: 2.8rem; opacity: 0.15; transform: rotate(-20deg); animation: float 7s ease-in-out infinite; animation-delay: 1s; pointer-events: none;"><i class="fas fa-drumstick-bite"></i></div>
<div style="position: absolute; top: 45%; right: 5%; font-size: 2.2rem; opacity: 0.15; transform: rotate(25deg); animation: float 5.5s ease-in-out infinite; animation-delay: 1.5s; pointer-events: none;"><i class="fas fa-pepper-hot"></i></div>
<div style="position: absolute; bottom: 25%; right: 12%; font-size: 2.5rem; opacity: 0.15; transform: rotate(-10deg); animation: float 6.5s ease-in-out infinite; animation-delay: 2s; pointer-events: none;"><i class="fas fa-cake-candles"></i></div>

<!-- Two Column Layout -->
<div class="container" style="position: relative; z-index: 2;">
<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 60px; align-items: center; min-height: 85vh;">

<!-- LEFT COLUMN - Content -->
<div class="hero-content" style="padding-right: 20px;">
<h1 class="hero-title">
Your Café, Bar or Restaurant <i class="fas fa-coffee"></i><br>
Made <span class="highlight">Super Easy</span>
</h1>
<div class="hero-tagline">
<i class="fas fa-qrcode"></i> Scan & Order <i class="fas fa-clock"></i> Save Time <i class="fas fa-smile"></i> Happy Customers
</div>
<div class="hero-benefit-card" style="animation-delay: 0.2s;">
<h3>
<i class="fas fa-mobile-alt"></i>
Customers Love It
</h3>
<p>
Your guests just <span class="hero-benefit-highlight">scan a QR code</span>, see your menu with photos, and <span class="hero-benefit-highlight">order in seconds</span>. No waiting, no confusion—just <span class="hero-benefit-highlight">fast, friendly service</span>.
</p>
</div>
<div class="hero-benefit-card" style="animation-delay: 0.4s;">
<h3>
<i class="fas fa-heart"></i>
You'll Love It Too
</h3>
<p>
Manage everything from one simple dashboard: <span class="hero-benefit-highlight">menus, orders, staff, payments</span>. See what's selling, track your team, and <span class="hero-benefit-highlight">grow your business</span>—all in one place.
</p>
</div>
<div class="hero-buttons">
<a href="<?php echo BASE_URL; ?>/?req=register" class="btn btn-primary">
<i class="fas fa-rocket"></i> Start Free Trial
</a>
<a href="#" onclick="openDemoModal(event)" class="btn btn-secondary">
<i class="fas fa-play-circle"></i> See How It Works
</a>
</div>
</div>

<!-- RIGHT COLUMN - Stats & Visual -->
<div style="display: flex; flex-direction: column; gap: 30px;">
<!-- Animated Food Icons Display -->
<div style="background: rgba(255,255,255,0.95); padding: 40px; border-radius: 30px; box-shadow: 0 15px 50px rgba(255, 107, 53, 0.2); border: 3px solid #FFC107; position: relative; overflow: hidden;">
<div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: linear-gradient(135deg, #FF6B35, #FFC107); border-radius: 50%; opacity: 0.1;"></div>
<div style="position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: linear-gradient(135deg, #FFC107, #FF6B35); border-radius: 50%; opacity: 0.1;"></div>
<div style="position: relative; z-index: 1;">
<h3 style="text-align: center; color: #2C1810; font-size: 1.5rem; margin-bottom: 30px;">
<i class="fas fa-magic" style="color: #FF6B35; margin-right: 10px;"></i>
Live Demo
</h3>
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
<div style="text-align: center; animation: float 4s ease-in-out infinite;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-pizza-slice" style="color: #FF6B35;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Pizza</span>
</div>
<div style="text-align: center; animation: float 5s ease-in-out infinite; animation-delay: 0.5s;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-hamburger" style="color: #F7931E;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Burgers</span>
</div>
<div style="text-align: center; animation: float 4.5s ease-in-out infinite; animation-delay: 1s;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-ice-cream" style="color: #FFC107;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Desserts</span>
</div>
<div style="text-align: center; animation: float 5.5s ease-in-out infinite; animation-delay: 0.3s;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-coffee" style="color: #FF6B35;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Coffee</span>
</div>
<div style="text-align: center; animation: float 4.8s ease-in-out infinite; animation-delay: 1.2s;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-cocktail" style="color: #F7931E;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Drinks</span>
</div>
<div style="text-align: center; animation: float 5.2s ease-in-out infinite; animation-delay: 0.8s;">
<div style="font-size: 3rem; margin-bottom: 8px;"><i class="fas fa-cake-candles" style="color: #FFC107;"></i></div>
<span style="font-size: 0.85rem; color: #6B4423;">Cakes</span>
</div>
</div>
<div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #FF6B35, #FFC107); border-radius: 15px; color: white; font-weight: 600; font-size: 1.1rem;">
<i class="fas fa-mobile-screen-button"></i> Customers Order Instantly
</div>
</div>
</div>

<!-- Stats Grid -->
<div class="stats-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
<div class="stat-item">
<div class="stat-icon">
<i class="fas fa-store"></i>
</div>
<div class="stat-content">
<h3 class="stat-number"><?php echo $activeRestaurants; ?>+</h3>
<p class="stat-label">Active Venues</p>
</div>
</div>
<div class="stat-item">
<div class="stat-icon">
<i class="fas fa-receipt"></i>
</div>
<div class="stat-content">
<h3 class="stat-number"><?php echo $todayOrders; ?></h3>
<p class="stat-label">Orders Today</p>
</div>
</div>
<div class="stat-item">
<div class="stat-icon">
<i class="fas fa-chair"></i>
</div>
<div class="stat-content">
<h3 class="stat-number"><?php echo $tablesOnline; ?>+</h3>
<p class="stat-label">Tables Managed</p>
</div>
</div>
<div class="stat-item">
<div class="stat-icon">
<i class="fas fa-chart-line"></i>
</div>
<div class="stat-content">
<h3 class="stat-number"><?php echo $ordersServed > 0 ? number_format($ordersServed) : '10K+'; ?></h3>
<p class="stat-label">Orders Served</p>
</div>
</div>
</div>

<!-- Trust Badges -->
<div class="trust-badges" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
<div class="badge">
<i class="fas fa-rocket"></i>
<span>Free Trial</span>
</div>
<div class="badge">
<i class="fas fa-shield-alt"></i>
<span>Secure</span>
</div>
<div class="badge">
<i class="fas fa-headset"></i>
<span>24/7 Support</span>
</div>
</div>
</div>

</div>
</div>
<section id="features" class="features-section" style="position: relative; overflow: hidden;">
<!-- More Food Stickers -->
<div style="position: absolute; top: 5%; right: 10%; font-size: 4rem; opacity: 0.08; transform: rotate(25deg); animation: float 8s ease-in-out infinite;"><i class="fas fa-pizza-slice"></i></div>
<div style="position: absolute; top: 30%; left: 5%; font-size: 3.5rem; opacity: 0.08; transform: rotate(-20deg); animation: float 7s ease-in-out infinite; animation-delay: 1s;"><i class="fas fa-hamburger"></i></div>
<div style="position: absolute; bottom: 20%; right: 8%; font-size: 3rem; opacity: 0.08; transform: rotate(15deg); animation: float 6s ease-in-out infinite; animation-delay: 2s;"><i class="fas fa-drumstick-bite"></i></div>
<div style="position: absolute; top: 60%; left: 12%; font-size: 3.2rem; opacity: 0.08; transform: rotate(-25deg); animation: float 7.5s ease-in-out infinite; animation-delay: 0.5s;"><i class="fas fa-pepper-hot"></i></div>
<div style="position: absolute; bottom: 40%; right: 15%; font-size: 2.8rem; opacity: 0.08; transform: rotate(20deg); animation: float 6.5s ease-in-out infinite; animation-delay: 1.5s;"><i class="fas fa-cake-candles"></i></div>
<div style="position: absolute; top: 15%; left: 20%; font-size: 3rem; opacity: 0.08; transform: rotate(-15deg); animation: float 7s ease-in-out infinite; animation-delay: 2.5s;"><i class="fas fa-bowl-rice"></i></div>
<div class="container" style="position: relative; z-index: 1;">
<h2 class="section-title">Everything Your Business Needs <i class="fas fa-utensils" style="color: #FF6B35;"></i></h2>
<p style="text-align: center; color: #6B4423; font-size: 1.2rem; margin-bottom: 50px; max-width: 800px; margin-left: auto; margin-right: auto;">
Simple tools that help you serve faster, manage better, and earn more
</p>
<div class="features-grid">
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-qrcode"></i>
</div>
<h3><i class="fas fa-mobile-alt" style="color: #FF6B35; margin-right: 8px;"></i> Scan & Order</h3>
<p>Guests scan a code, see your menu with photos, and order instantly. No app needed!</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-users-cog"></i>
</div>
<h3><i class="fas fa-users" style="color: #FF6B35; margin-right: 8px;"></i> Manage Your Team</h3>
<p>Track who's working, monitor performance, and see who served what—all automatically.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-cash-register"></i>
</div>
<h3><i class="fas fa-money-bill-wave" style="color: #FF6B35; margin-right: 8px;"></i> Accept All Payments</h3>
<p>Cash, cards, mobile money—accept them all. Get automatic daily reports.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-chart-line"></i>
</div>
<h3><i class="fas fa-chart-bar" style="color: #FF6B35; margin-right: 8px;"></i> See What's Working</h3>
<p>Know what's selling, when you're busiest, and how much you're earning—updated live!</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-utensils"></i>
</div>
<h3><i class="fas fa-edit" style="color: #FF6B35; margin-right: 8px;"></i> Easy Menu Updates</h3>
<p>Change prices or photos anytime. Customers see updates instantly on their phones.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<i class="fas fa-bell"></i>
</div>
<h3><i class="fas fa-concierge-bell" style="color: #FF6B35; margin-right: 8px;"></i> Kitchen Connection</h3>
<p>Orders go straight to your kitchen screen. No more lost tickets or wrong orders!</p>
</div>
</div>
<!-- How It Works Section -->
<div class="how-it-works-section" style="background: linear-gradient(135deg, #FF6B35 0%, #FFC107 100%); padding: 80px 20px; margin: 80px 0; position: relative; overflow: hidden; max-width: 100%;">
<!-- Decorative Elements -->
<div style="position: absolute; top: 10%; left: 5%; font-size: 4rem; opacity: 0.1; transform: rotate(-15deg); animation: float 7s ease-in-out infinite;"><i class="fas fa-coffee"></i></div>
<div style="position: absolute; top: 60%; right: 8%; font-size: 3.5rem; opacity: 0.1; transform: rotate(20deg); animation: float 6s ease-in-out infinite; animation-delay: 1s;"><i class="fas fa-hamburger"></i></div>
<div style="position: absolute; bottom: 15%; left: 10%; font-size: 3rem; opacity: 0.1; transform: rotate(-25deg); animation: float 8s ease-in-out infinite; animation-delay: 0.5s;"><i class="fas fa-pizza-slice"></i></div>
<div class="container" style="position: relative; z-index: 1;">
<h2 style="text-align: center; color: #2C1810; font-size: 2.5rem; margin-bottom: 20px; font-weight: 700; text-shadow: 1px 1px 2px rgba(255,255,255,0.5);">
<i class="fas fa-magic" style="margin-right: 10px;"></i> So Simple, It Feels Like Magic
</h2>
<p style="text-align: center; color: #2C1810; font-size: 1.3rem; margin-bottom: 60px; max-width: 750px; margin-left: auto; margin-right: auto; font-weight: 500;">
From "I'm hungry" to "This is delicious!" in just minutes
</p>
<!-- Timeline -->
<div class="timeline-container" style="max-width: 1000px; margin: 0 auto;">
<!-- Step 1 -->
<div class="timeline-item" style="display: flex; gap: 30px; margin-bottom: 50px; align-items: flex-start;">
<div class="timeline-number" style="background: white; color: #FF6B35; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; flex-shrink: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
1
</div>
<div class="timeline-content" style="background: rgba(255,255,255,0.25); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; flex: 1; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
<i class="fas fa-qrcode" style="font-size: 2.5rem; color: #2C1810;"></i>
<h3 style="color: #2C1810; margin: 0; font-size: 1.4rem; font-weight: 700;">Guest Scans the QR Code</h3>
</div>
<p style="color: #2C1810; margin: 0; line-height: 1.7; font-size: 1.05rem;">
Customer sits down → Scans code on table → Menu pops up instantly. That's it!</p>
</p>
<div style="margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; border-radius: 5px;">
<small style="color: white; font-weight: 600;"><i class="fas fa-lightbulb" style="margin-right: 5px;"></i> Result: Ordering starts in 5 seconds instead of 5–10 minutes</small>
</div>
</div>
</div>
<!-- Step 2 -->
<div class="timeline-item" style="display: flex; gap: 30px; margin-bottom: 50px; align-items: flex-start;">
<div class="timeline-number" style="background: white; color: #FF6B35; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; flex-shrink: 0; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);">
2
</div>
<div class="timeline-content" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; flex: 1; border: 1px solid rgba(255,255,255,0.2);">
<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
<i class="fas fa-utensils" style="font-size: 2.5rem; color: white;"></i>
<h3 style="color: white; margin: 0; font-size: 1.4rem;">Browse, Select & Order</h3>
</div>
<p style="color: rgba(255,255,255,0.95); margin: 0; line-height: 1.6;">
<strong>[0:15–0:45]</strong> Customer browses full menu with photos & prices → Adds items to cart → Adds special instructions → Submits order with one tap.
</p>
<div style="margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; border-radius: 5px;">
<small style="color: white; font-weight: 600;"><i class="fas fa-lightbulb" style="margin-right: 5px;"></i> Result: 25% higher average order (customers see everything!)</small>
</div>
</div>
</div>
<!-- Step 3 -->
<div class="timeline-item" style="display: flex; gap: 30px; margin-bottom: 50px; align-items: flex-start;">
<div class="timeline-number" style="background: white; color: #FF6B35; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; flex-shrink: 0; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);">
3
</div>
<div class="timeline-content" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; flex: 1; border: 1px solid rgba(255,255,255,0.2);">
<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
<i class="fas fa-fire" style="font-size: 2.5rem; color: white;"></i>
<h3 style="color: white; margin: 0; font-size: 1.4rem;">Kitchen Receives & Prepares</h3>
</div>
<p style="color: rgba(255,255,255,0.95); margin: 0; line-height: 1.6;">
<strong>[0:45–5:00]</strong> Order appears on kitchen screen ding → Chef confirms → Starts preparation → Customer sees "Preparing…" in real-time.
</p>
<div style="margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; border-radius: 5px;">
<small style="color: white; font-weight: 600;"><i class="fas fa-lightbulb" style="margin-right: 5px;"></i> Result: 80% fewer errors (no miscommunication!)</small>
</div>
</div>
</div>
<!-- Step 4 -->
<div class="timeline-item" style="display: flex; gap: 30px; margin-bottom: 50px; align-items: flex-start;">
<div class="timeline-number" style="background: white; color: #FF6B35; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; flex-shrink: 0; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);">
4
</div>
<div class="timeline-content" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; flex: 1; border: 1px solid rgba(255,255,255,0.2);">
<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
<i class="fas fa-bell" style="font-size: 2.5rem; color: white;"></i>
<h3 style="color: white; margin: 0; font-size: 1.4rem;">Ready & Served</h3>
</div>
<p style="color: rgba(255,255,255,0.95); margin: 0; line-height: 1.6;">
<strong>[5:00–9:00]</strong> Chef marks order "Ready" → Waiter gets alert → Delivers food hot & fresh → Marks as "Served" (liability tracked automatically).
</p>
<div style="margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; border-radius: 5px;">
<small style="color: white; font-weight: 600;"><i class="fas fa-lightbulb" style="margin-right: 5px;"></i> Result: Perfect coordination, no forgotten orders</small>
</div>
</div>
</div>
</div>
<!-- Speed Comparison -->
<div style="background: white; border-radius: 20px; padding: 40px; margin-top: 60px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); display: none;">
<h3 style="text-align: center; color: #333; font-size: 1.8rem; margin-bottom: 30px;"><i class="fas fa-bolt"></i> Speed Comparison</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
<div style="text-align: center; padding: 25px; background: #fee; border-radius: 15px; border: 2px solid #f87171;">
<div style="font-size: 3rem; margin-bottom: 15px;"><i class="fas fa-hourglass-half"></i></div>
<h4 style="color: #dc2626; margin: 0 0 10px 0; font-size: 1.2rem;">Traditional Restaurant</h4>
<div style="font-size: 2.5rem; font-weight: bold; color: #dc2626; margin: 10px 0;">18-25 min</div>
<p style="color: #666; margin: 0; font-size: 0.9rem;">From seat to food</p>
</div>
<div style="text-align: center; padding: 25px; background: #d1fae5; border-radius: 15px; border: 2px solid #10b981;">
<div style="font-size: 3rem; margin-bottom: 15px;"><i class="fas fa-rocket"></i></div>
<h4 style="color: #059669; margin: 0 0 10px 0; font-size: 1.2rem;">With SmartMenu</h4>
<div style="font-size: 2.5rem; font-weight: bold; color: #059669; margin: 10px 0;">8-12 min</div>
<p style="color: #666; margin: 0; font-size: 0.9rem;"><strong>60% faster!</strong></p>
</div>
</div>
</div>
</div>
</div>
<!-- Need Help Section -->
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); padding: 60px 20px; margin: 80px 0; position: relative; overflow: hidden; border-radius: 20px;">
<!-- Decorative Elements -->
<div style="position: absolute; top: 10%; right: 5%; font-size: 3.5rem; opacity: 0.1; transform: rotate(20deg); animation: float 6s ease-in-out infinite;"><i class="fas fa-headset"></i></div>
<div style="position: absolute; bottom: 15%; left: 8%; font-size: 3rem; opacity: 0.1; transform: rotate(-15deg); animation: float 7s ease-in-out infinite; animation-delay: 1s;"><i class="fas fa-comment"></i></div>
<div class="container" style="max-width: 900px; margin: 0 auto; position: relative; z-index: 1;">
<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">
<h3 style="color: white; margin-bottom: 15px; text-align: center; font-size: 2rem; font-weight: 700;">
<i class="fas fa-headset"></i> Need Help or Have Questions?
</h3>
<p style="color: rgba(255,255,255,0.95); font-size: 1.15rem; margin-bottom: 35px; text-align: center; line-height: 1.6;">
Our team is ready to help you get started. Reach out via email or phone.
</p>
<div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; align-items: center;">
<a href="mailto:info@inovasiyo.rw?subject=New Restaurant Registration" 
class="btn btn-primary" 
style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); 
color: white; 
padding: 16px 32px; 
border-radius: 12px; 
text-decoration: none; 
font-weight: 600; 
font-size: 1rem;
display: inline-flex; 
align-items: center; 
gap: 12px;
box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
transition: all 0.3s ease;
border: none;"
onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 107, 53, 0.4)';"
onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 107, 53, 0.3)';">
<i class="fas fa-envelope" style="font-size: 1.1rem;"></i>
<span>info@inovasiyo.rw</span>
</a>
<a href="tel:+250781612134" 
class="btn btn-secondary" 
style="background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%); 
color: #2C1810; 
padding: 16px 32px; 
border-radius: 12px; 
text-decoration: none; 
font-weight: 600; 
font-size: 1rem;
display: inline-flex; 
align-items: center; 
gap: 12px;
box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
transition: all 0.3s ease;
border: none;"
onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 193, 7, 0.4)';"
onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 193, 7, 0.3)';">
<i class="fas fa-phone" style="font-size: 1.1rem;"></i>
<span>+250 781 612 134</span>
</a>
</div>
</div>
</div>
</div>
</div>
</section>
<!-- Interactive Demo Modal -->
<div id="demoModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 99999; align-items: center; justify-content: center; animation: fadeIn 0.3s ease; overflow-y: auto; padding: 20px;">
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); border-radius: 30px; max-width: 1200px; width: 100%; position: relative; box-shadow: 0 30px 60px rgba(0,0,0,0.5); margin: auto; max-height: 90vh; overflow-y: auto;">
<button onclick="closeDemoModal()" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.8rem; cursor: pointer; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 10; transition: all 0.3s; backdrop-filter: blur(10px);" onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='rotate(90deg)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='rotate(0)'">
<i class="fas fa-times"></i>
</button>
<section style="padding: 60px 30px; position: relative; overflow: hidden;">
<div class="container" style="position: relative; z-index: 1;">
<div style="text-align: center; margin-bottom: 50px;">
<h2 style="color: white; font-size: 2.5rem; margin-bottom: 15px; font-weight: 700;">Experience It Live</h2>
<p style="color: rgba(255,255,255,0.9); font-size: 1.2rem; max-width: 700px; margin: 0 auto;">Try our system like a real customer. Scan, browse, and order - no app download needed!</p>
</div>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1000px; margin: 0 auto;">
<!-- Step 1 -->
<div style="background: white; border-radius: 20px; padding: 40px 30px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.2); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);">
<i class="fas fa-qrcode" style="font-size: 2rem; color: white;"></i>
</div>
<h3 style="color: #1f2937; font-size: 1.4rem; margin-bottom: 15px; font-weight: 600;">1. Scan QR Code</h3>
<p style="color: #6b7280; line-height: 1.6; margin-bottom: 25px;">Use your phone camera to scan the demo QR code below. No app download required!</p>
<div style="background: white; padding: 15px; border-radius: 12px; border: 3px solid #e5e7eb; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
<div id="demoQRCode" style="width: 150px; height: 150px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #9ca3af;"></i>
</div>
</div>
<p style="color: #9ca3af; font-size: 0.85rem; margin-top: 15px; font-style: italic;">Demo Table: T-DEMO-01</p>
</div>
<!-- Step 2 -->
<div style="background: white; border-radius: 20px; padding: 40px 30px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.2); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
<div style="background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);">
<i class="fas fa-utensils" style="font-size: 2rem; color: white;"></i>
</div>
<h3 style="color: #1f2937; font-size: 1.4rem; margin-bottom: 15px; font-weight: 600;">2. Browse Menu</h3>
<p style="color: #6b7280; line-height: 1.6; margin-bottom: 25px;">Explore our demo menu with mouth-watering dishes, real-time prices, and beautiful photos.</p>
<div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 20px 0;">
<div style="background: #fef3c7; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; color: #92400e; font-weight: 500;">
<i class="fas fa-pizza-slice"></i> 25+ Items
</div>
<div style="background: #dbeafe; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; color: #1e40af; font-weight: 500;">
<i class="fas fa-camera"></i> HD Photos
</div>
<div style="background: #dcfce7; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; color: #166534; font-weight: 500;">
<i class="fas fa-leaf"></i> Diet Tags
</div>
</div>
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; display: inline-block; box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);">
<i class="fas fa-utensils"></i> Live Demo Available
</div>
</div>
<!-- Step 3 -->
<div style="background: white; border-radius: 20px; padding: 40px 30px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.2); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);">
<i class="fas fa-shopping-cart" style="font-size: 2rem; color: white;"></i>
</div>
<h3 style="color: #1f2937; font-size: 1.4rem; margin-bottom: 15px; font-weight: 600;">3. Place Order</h3>
<p style="color: #6b7280; line-height: 1.6; margin-bottom: 25px;">Add items to cart, customize your order, and send it directly to the kitchen. Real-time status tracking!</p>
<div style="background: #f9fafb; padding: 20px; border-radius: 12px; margin: 20px 0;">
<div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap;">
<div style="text-align: center;">
<div style="font-size: 1.8rem; font-weight: 700; color: #FF6B35;">Pending</div>
<div style="font-size: 0.8rem; color: #6b7280; margin-top: 5px;">Order Placed</div>
</div>
<i class="fas fa-arrow-right" style="color: #d1d5db;"></i>
<div style="text-align: center;">
<div style="font-size: 1.8rem; font-weight: 700; color: #10b981;">Preparing</div>
<div style="font-size: 0.8rem; color: #6b7280; margin-top: 5px;">In Kitchen</div>
</div>
<i class="fas fa-arrow-right" style="color: #d1d5db;"></i>
<div style="text-align: center;">
<div style="font-size: 1.8rem; font-weight: 700; color: #f59e0b;">Ready</div>
<div style="font-size: 0.8rem; color: #6b7280; margin-top: 5px;">Enjoy!</div>
</div>
</div>
</div>
<p style="color: #9ca3af; font-size: 0.85rem; font-style: italic;">*Demo mode - orders won't be processed</p>
</div>
</div>
<!-- Additional Features -->
<div style="margin-top: 60px; text-align: center;">
<h3 style="color: white; font-size: 1.8rem; margin-bottom: 30px; font-weight: 600;">Plus These Amazing Features</h3>
<div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.2);">
<i class="fas fa-hand-paper" style="color: white; font-size: 1.5rem; margin-bottom: 10px;"></i>
<div style="color: white; font-weight: 600;">Call Waiter</div>
<div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">One-tap service</div>
</div>
<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.2);">
<i class="fas fa-receipt" style="color: white; font-size: 1.5rem; margin-bottom: 10px;"></i>
<div style="color: white; font-weight: 600;">View Bill</div>
<div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Itemized details</div>
</div>
<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.2);">
<i class="fas fa-language" style="color: white; font-size: 1.5rem; margin-bottom: 10px;"></i>
<div style="color: white; font-weight: 600;">Multi-Language</div>
<div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">English, French, Kinyarwanda</div>
</div>
<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.2);">
<i class="fas fa-mobile-alt" style="color: white; font-size: 1.5rem; margin-bottom: 10px;"></i>
<div style="color: white; font-weight: 600;">No App Needed</div>
<div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Works on any phone</div>
</div>
</div>
</div>
</div>
<!-- Background decoration -->
<div style="position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
<div style="position: absolute; bottom: -100px; left: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%;"></div>
<!-- Food Stickers in Modal -->
<div style="position: absolute; top: 10%; left: 3%; font-size: 2.5rem; opacity: 0.2; transform: rotate(-15deg); animation: float 5s ease-in-out infinite;"><i class="fas fa-pizza-slice"></i></div>
<div style="position: absolute; bottom: 10%; right: 5%; font-size: 2rem; opacity: 0.2; transform: rotate(20deg); animation: float 6s ease-in-out infinite; animation-delay: 1s;"><i class="fas fa-hamburger"></i></div>
<div style="position: absolute; top: 50%; right: 3%; font-size: 2.2rem; opacity: 0.2; transform: rotate(-10deg); animation: float 5.5s ease-in-out infinite; animation-delay: 0.5s;"><i class="fas fa-drumstick-bite"></i></div>
</section>
</div>
</div>
<script>
// Demo Modal Functions
function openDemoModal(event) {
if (event) event.preventDefault();
document.getElementById('demoModal').style.display = 'flex';
document.body.style.overflow = 'hidden';
// Generate QR code after modal opens
setTimeout(function() {
const qrContainer = document.getElementById('demoQRCode');
if (qrContainer && !qrContainer.dataset.initialized) {
// Demo redirects to session-based menu
const demoUrl = '<?php echo BASE_URL; ?>/?req=menu&qr=DEMO';
qrContainer.innerHTML = `
<div style="width: 150px; height: 150px; background: white; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
<svg width="140" height="140" viewBox="0 0 140 140">
<rect width="140" height="140" fill="white"/>
<g fill="black">
<rect x="10" y="10" width="30" height="30"/>
<rect x="100" y="10" width="30" height="30"/>
<rect x="10" y="100" width="30" height="30"/>
<rect x="50" y="20" width="10" height="10"/>
<rect x="70" y="20" width="10" height="10"/>
<rect x="50" y="40" width="10" height="10"/>
<rect x="70" y="40" width="10" height="10"/>
<rect x="20" y="50" width="10" height="10"/>
<rect x="40" y="50" width="10" height="10"/>
<rect x="60" y="50" width="10" height="10"/>
<rect x="80" y="50" width="10" height="10"/>
<rect x="100" y="50" width="10" height="10"/>
<rect x="120" y="50" width="10" height="10"/>
<rect x="30" y="70" width="10" height="10"/>
<rect x="50" y="70" width="10" height="10"/>
<rect x="90" y="70" width="10" height="10"/>
<rect x="110" y="70" width="10" height="10"/>
<rect x="50" y="90" width="10" height="10"/>
<rect x="70" y="90" width="10" height="10"/>
<rect x="90" y="90" width="10" height="10"/>
<rect x="110" y="90" width="10" height="10"/>
<rect x="50" y="110" width="10" height="10"/>
<rect x="70" y="110" width="10" height="10"/>
<rect x="90" y="110" width="10" height="10"/>
<rect x="110" y="110" width="10" height="10"/>
</g>
</svg>
</div>
`;
qrContainer.dataset.initialized = 'true';
}
}, 100);
}
function closeDemoModal() {
document.getElementById('demoModal').style.display = 'none';
document.body.style.overflow = 'auto';
}
// Close modal when clicking outside
const demoModal = document.getElementById('demoModal');
if (demoModal) demoModal.addEventListener('click', function(e) {
if (e.target === this) {
closeDemoModal();
}
});
// Close modal with Escape key
document.addEventListener('keydown', function(e) {
if (e.key === 'Escape' && document.getElementById('demoModal').style.display === 'flex') {
closeDemoModal();
}
});
</script>
<footer class="footer">
<div class="container">
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 30px; text-align: left;">
<div>
<h3 style="color: white; font-size: 1.1rem; margin-bottom: 15px; font-weight: 600;">Support</h3>
<div style="display: flex; flex-direction: column; gap: 10px;">
<a href="#" onclick="showFAQ(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-question-circle" style="margin-right: 8px;"></i>FAQ
</a>
<a href="#" onclick="openContactForm(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-life-ring" style="margin-right: 8px;"></i>Contact Support
</a>
<a href="#" onclick="openContactForm(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-bug" style="margin-right: 8px;"></i>Report Bug
</a>
</div>
</div>
<div>
<h3 style="color: white; font-size: 1.1rem; margin-bottom: 15px; font-weight: 600;">Legal</h3>
<div style="display: flex; flex-direction: column; gap: 10px;">
<a href="#" onclick="showPrivacyPolicy(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-shield-alt" style="margin-right: 8px;"></i>Privacy Policy
</a>
<a href="#" onclick="showTermsOfService(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-file-contract" style="margin-right: 8px;"></i>Terms of Service
</a>
<a href="#" onclick="showCookiePolicy(event); return false;" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
<i class="fas fa-cookie-bite" style="margin-right: 8px;"></i>Cookie Policy
</a>
</div>
</div>
</div>
<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;">
<p>&copy; 2026 SmartMenu by <strong>Inovasiyo Ltd</strong>. All rights reserved.</p>
<p>Powered by Inovasiyo Ltd | <i class="fas fa-shield-alt"></i> GDPR & Rwanda Data Protection Compliant</p>
</div>
</div>
</footer>
<!-- Contact Form Modal -->
<div id="contactModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
<div style="background: white; border-radius: 20px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
<button onclick="closeContactForm()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; color: #999; cursor: pointer; padding: 5px 10px;">
<i class="fas fa-times"></i>
</button>
<h2 style="color: #1f2937; margin-bottom: 10px; font-size: 1.75rem;">Contact Us</h2>
<p style="color: #6b7280; margin-bottom: 30px;">Send us a message and we'll get back to you soon.</p>
<div id="contactFormMessage" style="display: none; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px;"></div>
<form id="contactForm" onsubmit="submitContactForm(event)">
<div style="margin-bottom: 20px;">
<label style="display: block; color: #374151; font-weight: 600; margin-bottom: 8px;">Your Name *</label>
<input type="text" name="contact_name" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;" placeholder="Enter your name">
</div>
<div style="margin-bottom: 20px;">
<label style="display: block; color: #374151; font-weight: 600; margin-bottom: 8px;">Your Email *</label>
<input type="email" name="contact_email" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;" placeholder="your@email.com">
</div>
<div style="margin-bottom: 20px;">
<label style="display: block; color: #374151; font-weight: 600; margin-bottom: 8px;">Restaurant Name (Optional)</label>
<input type="text" name="restaurant_name" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;" placeholder="Your restaurant name">
</div>
<div style="margin-bottom: 20px;">
<label style="display: block; color: #374151; font-weight: 600; margin-bottom: 8px;">Subject *</label>
<input type="text" name="subject" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;" placeholder="What is this about?">
</div>
<div style="margin-bottom: 25px;">
<label style="display: block; color: #374151; font-weight: 600; margin-bottom: 8px;">Message *</label>
<textarea name="message" required rows="5" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; resize: vertical;" placeholder="Tell us how we can help..."></textarea>
</div>
<button type="submit" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
<i class="fas fa-paper-plane"></i> Send Message
</button>
</form>
</div>
</div>
<script>
function openContactForm(event) {
if (event) event.preventDefault();
document.getElementById('contactModal').style.display = 'flex';
document.body.style.overflow = 'hidden';
}
function closeContactForm() {
document.getElementById('contactModal').style.display = 'none';
document.body.style.overflow = 'auto';
document.getElementById('contactForm').reset();
document.getElementById('contactFormMessage').style.display = 'none';
}
async function submitContactForm(event) {
event.preventDefault();
const form = event.target;
const submitBtn = form.querySelector('button[type="submit"]');
const messageDiv = document.getElementById('contactFormMessage');
// Disable submit button
submitBtn.disabled = true;
submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
// Collect form data
const formData = new FormData(form);
const data = {
contact_name: formData.get('contact_name'),
contact_email: formData.get('contact_email'),
restaurant_name: formData.get('restaurant_name'),
subject: formData.get('subject'),
message: formData.get('message')
};
try {
const response = await fetch('<?php echo BASE_URL; ?>/api/submit_contact', {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify(data)
});
const result = await response.json();
if (result.status === 'OK') {
messageDiv.style.display = 'block';
messageDiv.style.background = '#d5f4e6';
messageDiv.style.borderLeft = '4px solid #27ae60';
messageDiv.style.color = '#155724';
messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> Message sent successfully! We\'ll get back to you soon.';
setTimeout(() => {
closeContactForm();
}, 2000);
} else {
throw new Error(result.message || 'Failed to send message');
}
} catch (error) {
messageDiv.style.display = 'block';
messageDiv.style.background = '#fee2e2';
messageDiv.style.borderLeft = '4px solid #ef4444';
messageDiv.style.color = '#991b1b';
messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + error.message;
submitBtn.disabled = false;
submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
}
}
// Close modal when clicking outside
document.getElementById('contactModal').addEventListener('click', function(e) {
if (e.target === this) {
closeContactForm();
}
});
// Generic modal function
function showInfoModal(title, content, icon = 'info-circle') {
const modal = document.createElement('div');
modal.id = 'infoModal';
modal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;';
modal.innerHTML = `
<div style="background: white; border-radius: 20px; padding: 0; max-width: 800px; width: 90%; max-height: 85vh; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative; display: flex; flex-direction: column;">
<div style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); padding: 30px; color: white; border-radius: 20px 20px 0 0;">
<button onclick="document.getElementById('infoModal').remove(); document.body.style.overflow='auto';" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; font-size: 1.5rem; color: white; cursor: pointer; padding: 8px 12px; border-radius: 8px; transition: all 0.3s;">
<i class="fas fa-times"></i>
</button>
<h2 style="margin: 0; font-size: 2rem; display: flex; align-items: center; gap: 15px;">
<i class="fas fa-${icon}" style="font-size: 1.5rem;"></i>
${title}
</h2>
</div>
<div style="padding: 40px; overflow-y: auto; flex: 1;">
${content}
</div>
</div>
`;
document.body.appendChild(modal);
document.body.style.overflow = 'hidden';
modal.addEventListener('click', function(e) {
if (e.target === modal) {
modal.remove();
document.body.style.overflow = 'auto';
}
});
}
function showFAQ(event) {
event.preventDefault();
const content = `
<div style="line-height: 1.8; color: #374151;">
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-utensils" style="color: #FF6B35;"></i>
What is SmartMenu?
</h3>
<p style="margin: 0; padding-left: 35px;">SmartMenu is a comprehensive restaurant management system that helps you manage orders, tables, staff, and customers efficiently. It includes features like QR code ordering, real-time kitchen management, and detailed analytics.</p>
</div>
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-rocket" style="color: #FF6B35;"></i>
How do I get started?
</h3>
<p style="margin: 0; padding-left: 35px;">Contact us via email (<strong>info@inovasiyo.rw</strong>) or phone (<strong>+250 781 612 134</strong>). We'll create your restaurant account, provide login credentials, and guide you through the setup process.</p>
</div>
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-qrcode" style="color: #FF6B35;"></i>
How does QR code ordering work?
</h3>
<p style="margin: 0; padding-left: 35px;">Each table gets a unique QR code. Customers scan it with their phones to access your digital menu, place orders, and request waiter assistance - all without downloading an app.</p>
</div>
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-coins" style="color: #FF6B35;"></i>
What are the pricing plans?
</h3>
<p style="margin: 0; padding-left: 35px;">We offer flexible monthly and annual subscription plans. Contact us for detailed pricing based on your restaurant size and specific needs.</p>
</div>
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-headset" style="color: #FF6B35;"></i>
Do you provide support?
</h3>
<p style="margin: 0; padding-left: 35px;">Yes! We provide comprehensive support via email, phone, and our support ticket system. Our team is ready to help you with any questions or issues.</p>
</div>
<div style="margin-bottom: 35px;">
<h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
<i class="fas fa-mobile-alt" style="color: #FF6B35;"></i>
Is it mobile-friendly?
</h3>
<p style="margin: 0; padding-left: 35px;">Absolutely! SmartMenu is fully responsive and works perfectly on all devices - smartphones, tablets, and desktops.</p>
</div>
</div>
`;
showInfoModal('Frequently Asked Questions', content, 'question-circle');
}
function showPrivacyPolicy(event) {
event.preventDefault();
const content = `
<div style="line-height: 1.8; color: #374151;">
<p style="color: #6b7280; margin-bottom: 30px; font-size: 0.95rem;">Last updated: January 15, 2026</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">1. Information We Collect</h3>
<p>We collect information necessary to provide restaurant management services, including:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>Restaurant business information (name, address, contact details)</li>
<li>Staff account information (names, emails, roles)</li>
<li>Customer order data and preferences</li>
<li>Payment and transaction information</li>
<li>Usage analytics and system logs</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">2. How We Use Your Information</h3>
<p>Your information is used to:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>Provide and maintain restaurant management services</li>
<li>Process orders and manage transactions</li>
<li>Generate reports and analytics for your business</li>
<li>Provide customer support and technical assistance</li>
<li>Improve and optimize our platform</li>
<li>Send important service notifications</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">3. Data Security</h3>
<p>We implement industry-standard security measures to protect your data, including encryption, secure servers, access controls, and regular security audits. Your data is stored securely and accessed only by authorized personnel.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">4. Data Sharing</h3>
<p>We do not sell your personal information. We may share data only:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>With your explicit consent</li>
<li>To comply with legal obligations</li>
<li>With trusted service providers who assist in operations</li>
<li>To protect rights, property, or safety</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">5. Your Rights</h3>
<p>Under GDPR and Rwanda Data Protection Law, you have rights to access, correct, delete, or export your data. Contact us at <strong>info@inovasiyo.rw</strong> to exercise these rights.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">6. Cookies</h3>
<p>We use essential cookies for authentication and system functionality. See our Cookie Policy for details.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">7. Contact Us</h3>
<p>For privacy concerns or questions:<br>Email: <strong>info@inovasiyo.rw</strong><br>Phone: <strong>+250 781 612 134</strong></p>
</div>
`;
showInfoModal('Privacy Policy', content, 'shield-alt');
}
function showTermsOfService(event) {
event.preventDefault();
const content = `
<div style="line-height: 1.8; color: #374151;">
<p style="color: #6b7280; margin-bottom: 30px; font-size: 0.95rem;">Last updated: January 15, 2026</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">1. Acceptance of Terms</h3>
<p>By accessing and using SmartMenu, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">2. Service Description</h3>
<p>SmartMenu provides a cloud-based restaurant management platform including order management, table management, staff coordination, menu management, and analytics tools.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">3. Account Registration</h3>
<p>You must provide accurate and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">4. Subscription and Payment</h3>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>Subscriptions are billed monthly or annually based on your chosen plan</li>
<li>Payment must be made in advance via agreed methods</li>
<li>Subscription fees are non-refundable except where required by law</li>
<li>We reserve the right to modify pricing with 30 days notice</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">5. Acceptable Use</h3>
<p>You agree not to:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>Use the service for unlawful purposes</li>
<li>Attempt to gain unauthorized access to the system</li>
<li>Interfere with or disrupt the service</li>
<li>Share your account with unauthorized users</li>
<li>Reverse engineer or copy any part of the platform</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">6. Service Availability</h3>
<p>We strive for 99.9% uptime but do not guarantee uninterrupted service. Scheduled maintenance will be announced in advance when possible.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">7. Data Backup</h3>
<p>We perform regular backups of your data. However, you are responsible for maintaining your own backup copies of critical information.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">8. Termination</h3>
<p>Either party may terminate the service with 30 days written notice. We may suspend or terminate accounts that violate these terms immediately.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">9. Limitation of Liability</h3>
<p>Inovasiyo Ltd shall not be liable for indirect, incidental, or consequential damages arising from use of the service.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">10. Governing Law</h3>
<p>These terms are governed by the laws of Rwanda. Disputes shall be resolved in Rwandan courts.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">11. Contact</h3>
<p>For questions about these terms:<br>Email: <strong>info@inovasiyo.rw</strong><br>Phone: <strong>+250 781 612 134</strong></p>
</div>
`;
showInfoModal('Terms of Service', content, 'file-contract');
}
function showCookiePolicy(event) {
event.preventDefault();
const content = `
<div style="line-height: 1.8; color: #374151;">
<p style="color: #6b7280; margin-bottom: 30px; font-size: 0.95rem;">Last updated: January 15, 2026</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">1. What Are Cookies?</h3>
<p>Cookies are small text files stored on your device when you visit our website. They help us provide a better, more personalized experience.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">2. How We Use Cookies</h3>
<p>SmartMenu uses cookies for:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li><strong>Authentication:</strong> Keep you logged in securely</li>
<li><strong>Security:</strong> Protect against fraud and unauthorized access</li>
<li><strong>Preferences:</strong> Remember your settings and choices</li>
<li><strong>Performance:</strong> Analyze how you use our platform to improve it</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">3. Types of Cookies We Use</h3>
<div style="margin: 25px 0; padding: 20px; background: #f3f4f6; border-radius: 10px;">
<h4 style="color: #1f2937; margin: 0 0 10px 0; font-size: 1.1rem;">Essential Cookies (Required)</h4>
<p style="margin: 0;">These cookies are necessary for the platform to function. They enable core features like security, authentication, and session management.</p>
</div>
<div style="margin: 25px 0; padding: 20px; background: #f3f4f6; border-radius: 10px;">
<h4 style="color: #1f2937; margin: 0 0 10px 0; font-size: 1.1rem;">Functional Cookies</h4>
<p style="margin: 0;">These cookies remember your preferences and settings to provide a personalized experience.</p>
</div>
<div style="margin: 25px 0; padding: 20px; background: #f3f4f6; border-radius: 10px;">
<h4 style="color: #1f2937; margin: 0 0 10px 0; font-size: 1.1rem;">Performance Cookies</h4>
<p style="margin: 0;">These cookies help us understand how you use our platform so we can improve performance and user experience.</p>
</div>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">4. Third-Party Cookies</h3>
<p>We do not use third-party advertising cookies. Any third-party cookies are limited to essential service providers (e.g., CDN providers for fonts and icons).</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">5. Managing Cookies</h3>
<p>You can control cookies through your browser settings. However, disabling essential cookies may prevent you from using certain features of our platform.</p>
<p><strong>Note:</strong> Most browsers allow you to:</p>
<ul style="margin: 15px 0; padding-left: 25px;">
<li>View and delete cookies</li>
<li>Block third-party cookies</li>
<li>Block all cookies (not recommended)</li>
<li>Clear cookies when closing the browser</li>
</ul>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">6. Cookie Duration</h3>
<p>We use both session cookies (deleted when you close your browser) and persistent cookies (remain for a specified period or until manually deleted).</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">7. Updates to This Policy</h3>
<p>We may update this Cookie Policy periodically. Changes will be posted on this page with an updated revision date.</p>
<h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">8. Contact Us</h3>
<p>Questions about our use of cookies?<br>Email: <strong>info@inovasiyo.rw</strong><br>Phone: <strong>+250 781 612 134</strong></p>
</div>
`;
showInfoModal('Cookie Policy', content, 'cookie-bite');
}
</script>
<!-- Cookie Consent Popup -->
<div id="cookieConsent" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); color: white; padding: 20px; box-shadow: 0 -4px 20px rgba(255,107,53,0.4); z-index: 10000; animation: slideUp 0.5s ease-out;">
<div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
<div style="flex: 1; min-width: 300px;">
<h3 style="margin: 0 0 8px 0; font-size: 1.2rem; font-weight: 600;">
<i class="fas fa-cookie-bite"></i> We Value Your Privacy
</h3>
<p style="margin: 0; font-size: 0.95rem; line-height: 1.5; opacity: 0.95;">
We use cookies to improve your experience and analyze site traffic. 
By clicking "Accept", you agree to our use of cookies.
<a href="#" onclick="showCookiePolicy(event)" style="color: #FFFEF2; text-decoration: underline; font-weight: 500;">Learn more</a>
</p>
</div>
<div style="display: flex; gap: 12px; flex-shrink: 0;">
<button onclick="declineCookies()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white; padding: 12px 28px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
<i class="fas fa-times"></i> Decline
</button>
<button onclick="acceptCookies()" style="background: white; color: #FF6B35; border: none; padding: 12px 32px; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'">
<i class="fas fa-check"></i> Accept All Cookies
</button>
</div>
</div>
</div>
<style>
@keyframes slideUp {
from {
transform: translateY(100%);
opacity: 0;
}
to {
transform: translateY(0);
opacity: 1;
}
}
@media (max-width: 768px) {
#cookieConsent > div {
flex-direction: column;
align-items: stretch;
}
#cookieConsent button {
width: 100%;
}
}
</style>
<script>
// Cookie Consent Functions
function setCookie(name, value, days) {
const expires = new Date();
expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
}
function getCookie(name) {
const nameEQ = name + '=';
const ca = document.cookie.split(';');
for(let i = 0; i < ca.length; i++) {
let c = ca[i];
while (c.charAt(0) === ' ') c = c.substring(1, c.length);
if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
}
return null;
}
function acceptCookies() {
setCookie('cookie_consent', 'accepted', 365);
document.getElementById('cookieConsent').style.display = 'none';
}
function declineCookies() {
setCookie('cookie_consent', 'declined', 365);
document.getElementById('cookieConsent').style.display = 'none';
// Optionally disable analytics or non-essential cookies here
}
function showCookiePolicy(event) {
event.preventDefault();
const content = `
<div style="text-align: left; line-height: 1.8;">
<h3 style="color: #1f2937; font-size: 1.5rem; margin-bottom: 15px;">
<i class="fas fa-cookie-bite"></i> Cookie Policy
</h3>
<p style="margin-bottom: 15px;"><strong>What are cookies?</strong><br>
Cookies are small text files stored on your device to improve your browsing experience.</p>
<div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 15px 0;">
<p style="margin: 0;"><strong><i class="fas fa-lock" style="color: #FF6B35; margin-right: 5px;"></i> Essential Cookies:</strong><br>
• Session management & security<br>
• Table assignment & device lock<br>
• Order cart & payment processing</p>
</div>
<div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 15px 0;">
<p style="margin: 0;"><strong><i class="fas fa-chart-bar" style="color: #FF6B35; margin-right: 5px;"></i> Analytics Cookies:</strong><br>
• Usage statistics & performance<br>
• Feature optimization<br>
• Error tracking</p>
</div>
<p><strong>Your Control:</strong><br>
You can decline optional cookies, but essential cookies are required for the ordering system to function properly.</p>
<p style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
<strong>Questions?</strong> Contact us at <a href="mailto:info@inovasiyo.rw" style="color: #FF6B35;">info@inovasiyo.rw</a>
</p>
</div>
`;
// Create modal
const modal = document.createElement('div');
modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10001; display: flex; align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.3s ease;';
modal.innerHTML = `
<div style="background: white; border-radius: 12px; max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto; padding: 30px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
<button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="position: absolute; top: 15px; right: 15px; background: #f3f4f6; border: none; font-size: 1.5rem; cursor: pointer; color: #666; padding: 8px 12px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
<i class="fas fa-times"></i>
</button>
${content}
</div>
`;
document.body.appendChild(modal);
modal.onclick = (e) => { if(e.target === modal) modal.remove(); };
}
// Check if user has already made a choice
window.addEventListener('DOMContentLoaded', function() {
const consent = getCookie('cookie_consent');
if (!consent) {
// Show cookie banner after 1.5 seconds
setTimeout(function() {
document.getElementById('cookieConsent').style.display = 'block';
}, 1500);
} else {
}
// Fix: Remove focus from links and buttons after click to prevent sticky hover state
document.addEventListener('click', function(e) {
const target = e.target.closest('a, button');
if (target) {
// Remove focus after a short delay to allow click event to complete
setTimeout(() => {
target.blur();
}, 100);
}
});
// Also handle keyboard navigation (Enter key)
document.addEventListener('keydown', function(e) {
if (e.key === 'Enter') {
const target = e.target;
if (target.tagName === 'A' || target.tagName === 'BUTTON') {
setTimeout(() => {
target.blur();
}, 100);
}
}
});
});
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
