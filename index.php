<?php
// Start output buffering immediately to catch any unexpected output
if (ob_get_level() == 0) {
    ob_start();
}

//echo $_GET['req'];
require_once 'src/config.php';
require_once 'src/autoload.php';

// Load system settings and enforcement
require_once 'app/core/SystemSettings.php';
require_once 'app/core/SettingsEnforcement.php';

// Enforce system-wide settings on every request
SettingsEnforcement::checkSessionTimeout();
SettingsEnforcement::autoReleaseTables(); // Run periodically (could be optimized with cron)

$autoload = new Autoload();