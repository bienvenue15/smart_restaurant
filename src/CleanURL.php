<?php
/**
 * Clean URL Helper
 * Generates secure, clean URLs without exposing implementation details
 */

class CleanURL {
    
    /**
     * Generate clean staff portal URL
     */
    public static function staff($action = 'login') {
        $routes = [
            'login' => '/?req=staff&action=login',
            'dashboard' => '/?req=staff&action=dashboard',
            'logout' => '/?req=staff&action=logout'
        ];
        
        return BASE_URL . ($routes[$action] ?? '/staff');
    }
    
    /**
     * Generate clean admin URL
     */
    public static function admin($action = 'login') {
        $routes = [
            'login' => '/?req=superadmin',
            'dashboard' => '/?req=superadmin&action=dashboard',
            'logout' => '/?req=superadmin&action=logout'
        ];
        
        return BASE_URL . ($routes[$action] ?? '/admin');
    }
    
    /**
     * Generate menu URL from QR code
     */
    public static function menu($qrCode) {
        return BASE_URL . '/?req=menu&qr=' . urlencode($qrCode);
    }
    
    /**
     * Generate API URL (session-based, no parameters)
     */
    public static function api($endpoint) {
        return BASE_URL . '/api/' . $endpoint;
    }
    
    /**
     * Generate registration URL
     */
    public static function register() {
        return BASE_URL . '/?req=register';
    }
    
    /**
     * Home URL
     */
    public static function home() {
        return BASE_URL . '/';
    }
    
    /**
     * Get current clean URL for redirects
     */
    public static function current() {
        return $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Sanitize URL to prevent injection
     */
    public static function sanitize($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
