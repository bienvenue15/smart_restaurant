<?php
/**
 * API Request Validator
 * Ensures all API requests are properly validated before processing
 * Rejects invalid requests with appropriate HTTP status codes
 */

class ApiValidator {
    
    /**
     * Validate HTTP method
     * @param array $allowedMethods Array of allowed HTTP methods (e.g., ['POST', 'GET'])
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateMethod($allowedMethods) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            return [
                'status' => 'FAIL',
                'message' => 'Method not allowed. Allowed methods: ' . implode(', ', $allowedMethods),
                'http_code' => 405
            ];
        }
        
        return null;
    }
    
    /**
     * Validate Content-Type for JSON requests
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateJsonContentType() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            // Allow application/json or multipart/form-data (for file uploads)
            if (strpos($contentType, 'application/json') === false && 
                strpos($contentType, 'multipart/form-data') === false &&
                strpos($contentType, 'application/x-www-form-urlencoded') === false) {
                
                return [
                    'status' => 'FAIL',
                    'message' => 'Invalid Content-Type. Expected: application/json or multipart/form-data',
                    'http_code' => 415
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Validate JSON body
     * @return array Returns ['data' => parsed_data, 'error' => null] or ['data' => null, 'error' => error_array]
     */
    public static function validateJsonBody() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Skip validation for multipart/form-data (file uploads)
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return ['data' => $_POST, 'error' => null];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $rawBody = file_get_contents('php://input');
            
            // Allow empty body for form-data
            if (empty($rawBody) && !empty($_POST)) {
                return ['data' => $_POST, 'error' => null];
            }
            
            if (empty($rawBody)) {
                return [
                    'data' => null,
                    'error' => [
                        'status' => 'FAIL',
                        'message' => 'Request body is required',
                        'http_code' => 400
                    ]
                ];
            }
            
            $data = json_decode($rawBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'data' => null,
                    'error' => [
                        'status' => 'FAIL',
                        'message' => 'Invalid JSON: ' . json_last_error_msg(),
                        'http_code' => 400
                    ]
                ];
            }
            
            return ['data' => $data, 'error' => null];
        }
        
        return ['data' => null, 'error' => null];
    }
    
    /**
     * Validate required parameters in request
     * @param array $data Request data
     * @param array $required Array of required parameter names
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateRequired($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return [
                'status' => 'FAIL',
                'message' => 'Missing required parameters: ' . implode(', ', $missing),
                'http_code' => 400
            ];
        }
        
        return null;
    }
    
    /**
     * Validate authentication token/session
     * @param bool $requireSession Whether session is required
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateAuth($requireSession = true) {
        if ($requireSession) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
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
            
            if (!isset($_SESSION['table_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['demo_mode'])) {
                return [
                    'status' => 'FAIL',
                    'message' => 'Unauthorized. Please authenticate first.',
                    'http_code' => 401
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Validate rate limiting (simple implementation)
     * @param string $identifier Unique identifier (IP, user_id, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return array|null Returns error array if limit exceeded, null if OK
     */
    public static function validateRateLimit($identifier, $maxRequests = 100, $windowSeconds = 60) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.json';
        
        $now = time();
        $requests = [];
        
        // Load existing requests
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $requests = json_decode($content, true) ?: [];
        }
        
        // Filter out old requests
        $requests = array_filter($requests, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        // Check limit
        if (count($requests) >= $maxRequests) {
            return [
                'status' => 'FAIL',
                'message' => 'Rate limit exceeded. Try again in ' . $windowSeconds . ' seconds.',
                'http_code' => 429
            ];
        }
        
        // Add current request
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode($requests));
        
        return null;
    }
    
    /**
     * Validate request size
     * @param int $maxSize Maximum request size in bytes
     * @return array|null Returns error array if too large, null if OK
     */
    public static function validateRequestSize($maxSize = 10485760) { // Default 10MB
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            $actualMB = round($contentLength / 1024 / 1024, 1);
            
            return [
                'status' => 'FAIL',
                'message' => "Request too large ({$actualMB}MB). Maximum allowed: {$maxMB}MB",
                'http_code' => 413
            ];
        }
        
        return null;
    }
    
    /**
     * Validate request origin (CORS)
     * @param array $allowedOrigins Array of allowed origins
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateOrigin($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        // If no allowed origins specified, allow all (default behavior)
        if (empty($allowedOrigins)) {
            return null;
        }
        
        // Check if origin is allowed
        foreach ($allowedOrigins as $allowed) {
            if (strpos($origin, $allowed) !== false) {
                return null;
            }
        }
        
        return [
            'status' => 'FAIL',
            'message' => 'Origin not allowed',
            'http_code' => 403
        ];
    }
    
    /**
     * Validate API key (if using API key authentication)
     * @param string $expectedKey Expected API key
     * @return array|null Returns error array if invalid, null if valid
     */
    public static function validateApiKey($expectedKey = null) {
        if ($expectedKey === null) {
            return null; // API key not required
        }
        
        $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        
        if ($providedKey !== $expectedKey) {
            return [
                'status' => 'FAIL',
                'message' => 'Invalid or missing API key',
                'http_code' => 401
            ];
        }
        
        return null;
    }
    
    /**
     * Comprehensive API request validation
     * @param array $options Validation options
     * @return array Returns ['valid' => true/false, 'data' => data, 'error' => error_array]
     */
    public static function validateRequest($options = []) {
        $defaults = [
            'methods' => ['POST', 'GET'],
            'require_json' => false,
            'require_auth' => false,
            'required_params' => [],
            'rate_limit' => null, // ['identifier' => 'ip', 'max' => 100, 'window' => 60]
            'max_size' => 10485760, // 10MB
            'allowed_origins' => [],
            'api_key' => null
        ];
        
        $options = array_merge($defaults, $options);
        
        // 1. Validate HTTP method
        $error = self::validateMethod($options['methods']);
        if ($error) {
            return ['valid' => false, 'data' => null, 'error' => $error];
        }
        
        // 2. Validate request size
        $error = self::validateRequestSize($options['max_size']);
        if ($error) {
            return ['valid' => false, 'data' => null, 'error' => $error];
        }
        
        // 3. Validate origin
        if (!empty($options['allowed_origins'])) {
            $error = self::validateOrigin($options['allowed_origins']);
            if ($error) {
                return ['valid' => false, 'data' => null, 'error' => $error];
            }
        }
        
        // 4. Validate API key
        if ($options['api_key'] !== null) {
            $error = self::validateApiKey($options['api_key']);
            if ($error) {
                return ['valid' => false, 'data' => null, 'error' => $error];
            }
        }
        
        // 5. Validate authentication
        if ($options['require_auth']) {
            $error = self::validateAuth(true);
            if ($error) {
                return ['valid' => false, 'data' => null, 'error' => $error];
            }
        }
        
        // 6. Validate JSON content type and body
        $data = null;
        if ($options['require_json']) {
            $error = self::validateJsonContentType();
            if ($error) {
                return ['valid' => false, 'data' => null, 'error' => $error];
            }
            
            $result = self::validateJsonBody();
            if ($result['error']) {
                return ['valid' => false, 'data' => null, 'error' => $result['error']];
            }
            $data = $result['data'];
        } else {
            // Get data from appropriate source
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $data = $_GET;
            } else {
                $data = $_POST;
            }
        }
        
        // 7. Validate required parameters
        if (!empty($options['required_params'])) {
            $error = self::validateRequired($data, $options['required_params']);
            if ($error) {
                return ['valid' => false, 'data' => $data, 'error' => $error];
            }
        }
        
        // 8. Rate limiting
        if ($options['rate_limit']) {
            $identifier = $options['rate_limit']['identifier'] ?? $_SERVER['REMOTE_ADDR'];
            $max = $options['rate_limit']['max'] ?? 100;
            $window = $options['rate_limit']['window'] ?? 60;
            
            $error = self::validateRateLimit($identifier, $max, $window);
            if ($error) {
                return ['valid' => false, 'data' => $data, 'error' => $error];
            }
        }
        
        return ['valid' => true, 'data' => $data, 'error' => null];
    }
    
    /**
     * Send error response and exit
     * @param array $error Error array with status, message, http_code
     */
    public static function sendError($error) {
        $httpCode = $error['http_code'] ?? 400;
        http_response_code($httpCode);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $error['status'] ?? 'FAIL',
            'message' => $error['message'] ?? 'Request validation failed'
        ]);
        exit;
    }
}
