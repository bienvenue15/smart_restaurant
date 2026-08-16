<?php

// ============================================================
// CONFIGURATION - SMART RESTAURANT SYSTEM
// ============================================================
// Auto-detects environment (local vs production)
// ============================================================

// Detect environment
$isLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'localhost:') !== false
);

// Database Configuration
if(!defined("DB_TYPE"))
    define("DB_TYPE", "mysql");
if(!defined("DB_HOST"))
    define("DB_HOST", "localhost");

if(!defined("DB_NAME"))
    define("DB_NAME", $isLocal ? "db_restaurant" : "inovasiy_smartresto");

if(!defined("DB_USER"))
    define("DB_USER", $isLocal ? "root" : "inovasiy_admin");

if(!defined("DB_PWD"))
    define("DB_PWD", $isLocal ? "" : "shuwadilu@1234");

if(!defined("DEFAULT_CONTROLLER"))
    define("DEFAULT_CONTROLLER", "index");

// Base URL
if(!defined("BASE_URL")) {
    $envBaseUrl = getenv('APP_BASE_URL') ?: getenv('BASE_URL');
    if ($isLocal) {
        $defaultBaseUrl = 'http://localhost/restaurant';
    } else {
        $defaultBaseUrl = 'https://smartresto.inovasiyo.rw';
    }
    define("BASE_URL", $envBaseUrl ? rtrim($envBaseUrl, '/') : $defaultBaseUrl);
}

// Asset URLs
if (!defined('APP_LOGO_URL')) {
    $baseUrl = $isLocal ? 'http://localhost/restaurant' : 'https://smartresto.inovasiyo.rw';
    define('APP_LOGO_URL', $baseUrl . '/assets/images/logo.png');
}

if (!defined('APP_FAVICON_URL')) {
    $baseUrl = $isLocal ? 'http://localhost/restaurant' : 'https://smartresto.inovasiyo.rw';
    define('APP_FAVICON_URL', $baseUrl . '/assets/images/logo.ico');
}

// Mail configuration
if (!defined('MAIL_FROM_ADDRESS')) {
    $defaultMail = $isLocal ? 'dev@restaurant.local' : 'info@inovasiyo.rw';
    define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: $defaultMail);
}

if (!defined('MAIL_FROM_NAME')) {
    $appName = $isLocal ? 'SmartMenu (DEV)' : 'SmartMenu';
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: $appName);
}

if (!defined('MAIL_SUPPORT_ADDRESS')) {
    $supportMail = $isLocal ? 'dev@restaurant.local' : 'info@inovasiyo.rw';
    define('MAIL_SUPPORT_ADDRESS', getenv('MAIL_SUPPORT_ADDRESS') ?: $supportMail);
}

if (!defined('MAIL_SMTP_HOST')) {
    // cPanel mail server (production) or localhost (development)
    $smtpHost = $isLocal ? 'localhost' : 'mail.inovasiyo.rw';
    define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: $smtpHost);
}

if (!defined('MAIL_SMTP_PORT')) {
    // Try port 587 with TLS (more compatible)
    define('MAIL_SMTP_PORT', getenv('MAIL_SMTP_PORT') ?: 587);
}

if (!defined('MAIL_SMTP_USERNAME')) {
    // Email account username
    define('MAIL_SMTP_USERNAME', getenv('MAIL_SMTP_USERNAME') ?: 'info@inovasiyo.rw');
}

if (!defined('MAIL_SMTP_PASSWORD')) {
    // Email password set from cPanel
    define('MAIL_SMTP_PASSWORD', getenv('MAIL_SMTP_PASSWORD') ?: 'shuwadilu@1234');
}

if (!defined('MAIL_SMTP_ENCRYPTION')) {
    // Using TLS encryption for port 587
    define('MAIL_SMTP_ENCRYPTION', getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls');
}

if (!defined('MAIL_DISABLE_DELIVERY')) {
    // Allow forcing mail off in dev environments
    define('MAIL_DISABLE_DELIVERY', filter_var(getenv('MAIL_DISABLE_DELIVERY') ?: 'false', FILTER_VALIDATE_BOOLEAN));
}

// Language Configuration
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

if (!defined('AVAILABLE_LANGUAGES')) {
    define('AVAILABLE_LANGUAGES', ['en', 'fr', 'rw', 'sw']);
}

// Production Environment Settings
if (!$isLocal) {
    // Production error handling
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    
    // Session security for production
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
}

// Initialize Language System
require_once __DIR__ . '/Language.php';
require_once __DIR__ . '/language_helpers.php';
if (session_status() === PHP_SESSION_NONE) {
    // Use local sessions directory to avoid permission issues
    $sessionPath = __DIR__ . '/../sessions';
    if (!file_exists($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    session_start();
}
Language::init();