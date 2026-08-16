<?php
/**
 * Secure UUID-Based Tenant Isolation Middleware
 * Prevents ID guessing and cross-tenant data access
 * 
 * Usage:
 *   SecureAccess::validateOrderAccess($orderUuid);
 *   SecureAccess::validatePaymentAccess($paymentUuid);
 */

class SecureAccess {
    
    private static $db = null;
    private static $currentRestaurantUuid = null;
    private static $currentStaffUuid = null;
    
    /**
     * Initialize with database connection and session data
     */
    public static function init($dbConnection) {
        self::$db = $dbConnection;
        
        // Get current restaurant and staff from session
        if (isset($_SESSION['staff_user'])) {
            self::$currentStaffUuid = $_SESSION['staff_user']['uuid'] ?? null;
            self::$currentRestaurantUuid = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
        }
    }
    
    /**
     * Generate a secure UUID v4
     * @return string UUID
     */
    public static function generateUUID() {
        // MySQL UUID() format
        if (self::$db) {
            $result = self::$db->query("SELECT UUID() as uuid");
            if ($row = $result->fetch_assoc()) {
                return $row['uuid'];
            }
        }
        
        // Fallback: PHP-based UUID v4
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Validate UUID format
     * @param string $uuid
     * @return bool
     */
    public static function isValidUUID($uuid) {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
    
    /**
     * Validate restaurant access
     * Ensures current user belongs to the restaurant
     * @param string $restaurantUuid
     * @return bool
     * @throws Exception if access denied
     */
    public static function validateRestaurantAccess($restaurantUuid) {
        if (!self::isValidUUID($restaurantUuid)) {
            self::denyAccess('Invalid restaurant identifier');
        }
        
        if (self::$currentRestaurantUuid !== $restaurantUuid) {
            self::logSecurityEvent('unauthorized_restaurant_access_attempt', [
                'requested_uuid' => $restaurantUuid,
                'user_restaurant_uuid' => self::$currentRestaurantUuid
            ]);
            self::denyAccess('Unauthorized access to restaurant data');
        }
        
        return true;
    }
    
    /**
     * Validate order access
     * Ensures order belongs to current restaurant
     * @param string $orderUuid
     * @return array Order data if valid
     * @throws Exception if access denied
     */
    public static function validateOrderAccess($orderUuid) {
        if (!self::isValidUUID($orderUuid)) {
            self::denyAccess('Invalid order identifier');
        }
        
        $stmt = self::$db->prepare("
            SELECT uuid, restaurant_uuid, order_number, status, total_amount
            FROM orders 
            WHERE uuid = ? AND restaurant_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $orderUuid, self::$currentRestaurantUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($order = $result->fetch_assoc()) {
            return $order;
        }
        
        self::logSecurityEvent('unauthorized_order_access_attempt', [
            'requested_order_uuid' => $orderUuid,
            'user_restaurant_uuid' => self::$currentRestaurantUuid
        ]);
        self::denyAccess('Order not found or access denied');
    }
    
    /**
     * Validate payment access
     * Ensures payment belongs to current restaurant
     * @param string $paymentUuid
     * @return array Payment data if valid
     * @throws Exception if access denied
     */
    public static function validatePaymentAccess($paymentUuid) {
        if (!self::isValidUUID($paymentUuid)) {
            self::denyAccess('Invalid payment identifier');
        }
        
        $stmt = self::$db->prepare("
            SELECT p.uuid, p.restaurant_uuid, p.order_uuid, p.amount, p.status
            FROM payments p
            WHERE p.uuid = ? AND p.restaurant_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $paymentUuid, self::$currentRestaurantUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($payment = $result->fetch_assoc()) {
            return $payment;
        }
        
        self::logSecurityEvent('unauthorized_payment_access_attempt', [
            'requested_payment_uuid' => $paymentUuid,
            'user_restaurant_uuid' => self::$currentRestaurantUuid
        ]);
        self::denyAccess('Payment not found or access denied');
    }
    
    /**
     * Validate staff access
     * Ensures staff member belongs to current restaurant
     * @param string $staffUuid
     * @return array Staff data if valid
     * @throws Exception if access denied
     */
    public static function validateStaffAccess($staffUuid) {
        if (!self::isValidUUID($staffUuid)) {
            self::denyAccess('Invalid staff identifier');
        }
        
        $stmt = self::$db->prepare("
            SELECT uuid, restaurant_uuid, username, full_name, role
            FROM staff 
            WHERE uuid = ? AND restaurant_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $staffUuid, self::$currentRestaurantUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($staff = $result->fetch_assoc()) {
            return $staff;
        }
        
        self::logSecurityEvent('unauthorized_staff_access_attempt', [
            'requested_staff_uuid' => $staffUuid,
            'user_restaurant_uuid' => self::$currentRestaurantUuid
        ]);
        self::denyAccess('Staff member not found or access denied');
    }
    
    /**
     * Validate cash session access
     * Ensures session belongs to current restaurant
     * @param string $sessionUuid
     * @return array Session data if valid
     * @throws Exception if access denied
     */
    public static function validateCashSessionAccess($sessionUuid) {
        if (!self::isValidUUID($sessionUuid)) {
            self::denyAccess('Invalid session identifier');
        }
        
        $stmt = self::$db->prepare("
            SELECT uuid, restaurant_uuid, staff_uuid, session_number, status
            FROM cash_sessions 
            WHERE uuid = ? AND restaurant_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $sessionUuid, self::$currentRestaurantUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($session = $result->fetch_assoc()) {
            return $session;
        }
        
        self::logSecurityEvent('unauthorized_session_access_attempt', [
            'requested_session_uuid' => $sessionUuid,
            'user_restaurant_uuid' => self::$currentRestaurantUuid
        ]);
        self::denyAccess('Cash session not found or access denied');
    }
    
    /**
     * Sanitize UUID input
     * Prevents SQL injection in UUID parameters
     * @param string $uuid
     * @return string Sanitized UUID
     */
    public static function sanitizeUUID($uuid) {
        // Remove any non-UUID characters
        $uuid = preg_replace('/[^a-f0-9-]/i', '', $uuid);
        
        // Validate format
        if (!self::isValidUUID($uuid)) {
            return null;
        }
        
        return strtolower($uuid);
    }
    
    /**
     * Get UUID from legacy ID (for migration period)
     * @param string $table Table name
     * @param int $id Legacy integer ID
     * @return string|null UUID
     */
    public static function getUuidFromId($table, $id) {
        $allowedTables = ['restaurants', 'staff', 'orders', 'payments', 'cash_sessions'];
        
        if (!in_array($table, $allowedTables)) {
            return null;
        }
        
        $stmt = self::$db->prepare("SELECT uuid FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['uuid'];
        }
        
        return null;
    }
    
    /**
     * Deny access and log security event
     * @param string $reason
     * @throws Exception
     */
    private static function denyAccess($reason) {
        http_response_code(403);
        
        // Log to file
        error_log(sprintf(
            '[SECURITY] Access Denied - IP: %s, User: %s, Reason: %s',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            self::$currentStaffUuid ?? 'unknown',
            $reason
        ));
        
        // For API requests
        if (isset($_GET['req']) && $_GET['req'] === 'api') {
            die(json_encode([
                'status' => 'ERROR',
                'message' => $reason,
                'code' => 'ACCESS_DENIED'
            ]));
        }
        
        // For regular requests
        die('Access Denied: ' . htmlspecialchars($reason));
    }
    
    /**
     * Log security event to audit trail
     * @param string $eventType
     * @param array $details
     */
    private static function logSecurityEvent($eventType, $details) {
        try {
            $stmt = self::$db->prepare("
                INSERT INTO audit_trail (
                    uuid, restaurant_uuid, staff_uuid, action_type, 
                    table_name, new_value, ip_address, user_agent, status
                ) VALUES (?, ?, ?, ?, 'security', ?, ?, ?, 'rejected')
            ");
            
            $uuid = self::generateUUID();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $detailsJson = json_encode($details);
            
            $stmt->bind_param('sssssss', 
                $uuid,
                self::$currentRestaurantUuid,
                self::$currentStaffUuid,
                $eventType,
                $detailsJson,
                $ip,
                $userAgent
            );
            
            $stmt->execute();
        } catch (Exception $e) {
            error_log('[SECURITY] Failed to log security event: ' . $e->getMessage());
        }
    }
    
    /**
     * Convert all GET/POST parameters with '_id' to '_uuid'
     * For backwards compatibility during migration
     */
    public static function convertIdsToUuids() {
        $conversions = [
            'restaurant_id' => 'restaurants',
            'order_id' => 'orders',
            'payment_id' => 'payments',
            'staff_id' => 'staff',
            'session_id' => 'cash_sessions'
        ];
        
        foreach ($conversions as $param => $table) {
            if (isset($_GET[$param]) && is_numeric($_GET[$param])) {
                $uuid = self::getUuidFromId($table, $_GET[$param]);
                if ($uuid) {
                    $uuidParam = str_replace('_id', '_uuid', $param);
                    $_GET[$uuidParam] = $uuid;
                }
            }
            
            if (isset($_POST[$param]) && is_numeric($_POST[$param])) {
                $uuid = self::getUuidFromId($table, $_POST[$param]);
                if ($uuid) {
                    $uuidParam = str_replace('_id', '_uuid', $param);
                    $_POST[$uuidParam] = $uuid;
                }
            }
        }
    }
}

/**
 * Helper function for quick UUID validation in controllers
 */
function validateUuid($uuid, $type = 'resource') {
    if (!SecureAccess::isValidUUID($uuid)) {
        http_response_code(400);
        die(json_encode([
            'status' => 'ERROR',
            'message' => "Invalid {$type} identifier",
            'code' => 'INVALID_UUID'
        ]));
    }
    return SecureAccess::sanitizeUUID($uuid);
}

/**
 * Helper function to generate new UUID
 */
function generateUuid() {
    return SecureAccess::generateUUID();
}
