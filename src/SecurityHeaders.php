<?php
/**
 * Security Headers Configuration
 * Apply comprehensive HTTP security headers to all responses
 * Protection against common web vulnerabilities
 */

class SecurityHeaders {
    
    /**
     * Apply all security headers
     */
    public static function apply() {
        // Already applied via .htaccess, but apply here as backup
        
        // 1. Prevent clickjacking attacks
        header('X-Frame-Options: SAMEORIGIN');
        
        // 2. Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // 3. Enable XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');
        
        // 4. Content Security Policy (CSP)
        // Only allow resources from same origin + specific CDNs
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'"
        ]);
        header("Content-Security-Policy: $csp");
        
        // 5. Referrer Policy - Don't leak referrer to external sites
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // 6. Permissions Policy (formerly Feature Policy)
        // Disable unnecessary browser features
        $permissions = implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()'
        ]);
        header("Permissions-Policy: $permissions");
        
        // 7. Strict Transport Security (HTTPS only)
        // Uncomment when HTTPS is enabled
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        
        // 8. Prevent browser caching of sensitive data
        // (Apply selectively - not for all pages)
        // header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        // header('Pragma: no-cache');
        // header('Expires: 0');
    }
    
    /**
     * Apply CORS headers for API endpoints
     * @param array $allowedOrigins Array of allowed origins
     */
    public static function applyCors($allowedOrigins = []) {
        // Get origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // If no specific origins, allow same origin only
        if (empty($allowedOrigins)) {
            $allowedOrigins = [
                $_SERVER['HTTP_HOST'] ?? 'localhost'
            ];
        }
        
        // Check if origin is allowed
        foreach ($allowedOrigins as $allowed) {
            if (strpos($origin, $allowed) !== false) {
                header("Access-Control-Allow-Origin: $origin");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
                header('Access-Control-Max-Age: 86400'); // 24 hours
                break;
            }
        }
        
        // Handle OPTIONS preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Apply no-cache headers for sensitive pages
     */
    public static function noCache() {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Remove server signature headers
     */
    public static function removeServerInfo() {
        header_remove('X-Powered-By');
        header_remove('Server');
    }
}

// Auto-apply basic security headers for all requests
SecurityHeaders::apply();
SecurityHeaders::removeServerInfo();
