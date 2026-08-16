<?php
// Start output buffering FIRST to catch any output
ob_start();

// Apply security headers immediately
require_once __DIR__ . '/src/SecurityHeaders.php';

// Environment-aware error reporting
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || 
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'localhost:') !== false);

if ($isLocal) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

//echo $_GET['req'];
require_once 'src/config.php';
require_once 'src/autoload.php';

// Load system settings and enforcement
require_once 'app/core/SystemSettings.php';
require_once 'app/core/SettingsEnforcement.php';

// Start session if not started - WITH CORRECT PATH
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/sessions';
    if (!file_exists($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// Enforce maintenance mode (super admin bypasses this)
SettingsEnforcement::enforceMaintenanceMode();

// Enforce system-wide settings on every request
SettingsEnforcement::checkSessionTimeout();
SettingsEnforcement::autoReleaseTables(); // Run periodically (could be optimized with cron)

$autoload = new Autoload();