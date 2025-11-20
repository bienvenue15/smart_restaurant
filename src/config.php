<?php

if(!defined("DB_TYPE"))
    define("DB_TYPE", "mysql");
if(!defined("DB_HOST"))
    define("DB_HOST", "localhost");
if(!defined("DB_NAME"))
    define("DB_NAME", "inovasiy_smartresto");
if(!defined("DB_USER"))
    define("DB_USER", "inovasiy_admin");
 if(!defined("DB_PWD"))
    define("DB_PWD", "shuwadilu@1234");
if(!defined("DEFAULT_CONTROLLER"))
    define("DEFAULT_CONTROLLER", "index");

// Base URL
if(!defined("BASE_URL")) {
    $envBaseUrl = getenv('APP_BASE_URL') ?: getenv('BASE_URL');
    $defaultBaseUrl = 'https://smartresto.inovasiyo.rw';
    define("BASE_URL", $envBaseUrl ? rtrim($envBaseUrl, '/') : $defaultBaseUrl);
}

if (!defined('APP_LOGO_URL')) {
    define('APP_LOGO_URL', BASE_URL . '/assets/images/logo.png');
}

if (!defined('APP_FAVICON_URL')) {
    define('APP_FAVICON_URL', BASE_URL . '/assets/images/logo.ico');
}

// Mail configuration
if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'info@inovasiyo.rw');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Smart Restaurant Cloud');
}

if (!defined('MAIL_SUPPORT_ADDRESS')) {
    define('MAIL_SUPPORT_ADDRESS', getenv('MAIL_SUPPORT_ADDRESS') ?: 'info@inovasiyo.rw');
}

if (!defined('MAIL_SMTP_HOST')) {
    define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: '');
}

if (!defined('MAIL_SMTP_PORT')) {
    define('MAIL_SMTP_PORT', getenv('MAIL_SMTP_PORT') ?: 587);
}

if (!defined('MAIL_SMTP_USERNAME')) {
    define('MAIL_SMTP_USERNAME', getenv('MAIL_SMTP_USERNAME') ?: '');
}

if (!defined('MAIL_SMTP_PASSWORD')) {
    define('MAIL_SMTP_PASSWORD', getenv('MAIL_SMTP_PASSWORD') ?: '');
}

if (!defined('MAIL_SMTP_ENCRYPTION')) {
    define('MAIL_SMTP_ENCRYPTION', getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls');
}

if (!defined('MAIL_DISABLE_DELIVERY')) {
    // Allow forcing mail off in dev environments
    define('MAIL_DISABLE_DELIVERY', filter_var(getenv('MAIL_DISABLE_DELIVERY') ?: 'false', FILTER_VALIDATE_BOOLEAN));
}