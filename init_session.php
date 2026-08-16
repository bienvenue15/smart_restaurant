<?php
/**
 * UNIFIED SESSION INITIALIZATION
 * Use this at the top of every PHP file to ensure consistent session handling
 */

// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Use the SAME session path as the main application
    $sessionPath = __DIR__ . '/sessions';
    if (!is_dir(__DIR__) || !is_writable(__DIR__)) {
        $sessionPath = sys_get_temp_dir() . '/restaurant_sessions';
    }
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        ini_set('session.save_path', $sessionPath);
    }
    
    // Set session cookie parameters to match domain
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
