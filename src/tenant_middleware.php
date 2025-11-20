<?php
/**
 * Multi-Tenancy Middleware
 * Ensures data isolation between restaurants
 */

class TenantMiddleware {
    
    /**
     * Apply restaurant filter to all queries
     */
    public static function filterQuery($query, $restaurantId = null) {
        if ($restaurantId === null) {
            $restaurantId = Restaurant::getCurrentId();
        }
        
        if ($restaurantId === null) {
            throw new Exception('No restaurant context set');
        }
        
        // Add WHERE clause for restaurant_id
        if (stripos($query, 'WHERE') !== false) {
            // Already has WHERE, add AND
            $query = str_ireplace('WHERE', "WHERE restaurant_id = $restaurantId AND", $query);
        } else {
            // No WHERE clause, add it before ORDER BY, GROUP BY, LIMIT, etc.
            $keywords = ['ORDER BY', 'GROUP BY', 'LIMIT', 'HAVING', 'UNION'];
            $inserted = false;
            
            foreach ($keywords as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    $query = str_ireplace($keyword, "WHERE restaurant_id = $restaurantId $keyword", $query);
                    $inserted = true;
                    break;
                }
            }
            
            if (!$inserted) {
                $query .= " WHERE restaurant_id = $restaurantId";
            }
        }
        
        return $query;
    }
    
    /**
     * Verify record belongs to current restaurant
     */
    public static function verifyOwnership($table, $recordId) {
        $restaurantId = Restaurant::getCurrentId();
        if ($restaurantId === null) {
            return false;
        }
        
        $db = self::getDB();
        $query = "SELECT COUNT(*) FROM $table WHERE id = ? AND restaurant_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$recordId, $restaurantId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Add restaurant_id to INSERT data
     */
    public static function addRestaurantId(&$data) {
        $restaurantId = Restaurant::getCurrentId();
        if ($restaurantId === null) {
            throw new Exception('No restaurant context set');
        }
        
        $data['restaurant_id'] = $restaurantId;
    }
    
    /**
     * Get database connection
     */
    private static function getDB() {
        static $db = null;
        if ($db === null) {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $db;
    }
    
    /**
     * Check if restaurant subscription is active
     */
    public static function checkSubscription() {
        $restaurantId = Restaurant::getCurrentId();
        if ($restaurantId === null) {
            return false;
        }
        
        $restaurant = new Restaurant();
        return $restaurant->isSubscriptionActive($restaurantId);
    }
    
    /**
     * Middleware to require active subscription
     */
    public static function requireActiveSubscription() {
        if (!self::checkSubscription()) {
            http_response_code(403);
            echo json_encode([
                'status' => 'FAIL',
                'message' => 'Subscription expired or inactive. Please renew your subscription.'
            ]);
            exit;
        }
    }
    
    /**
     * Check resource limits
     */
    public static function checkLimit($type) {
        $restaurantId = Restaurant::getCurrentId();
        if ($restaurantId === null) {
            return false;
        }
        
        $restaurant = new Restaurant();
        return $restaurant->checkLimits($restaurantId, $type);
    }
    
    /**
     * Get restaurant-specific setting
     */
    public static function getSetting($key, $default = null) {
        $restaurantId = Restaurant::getCurrentId();
        if ($restaurantId === null) {
            return $default;
        }
        
        $restaurant = new Restaurant();
        $settings = $restaurant->getSettings($restaurantId);
        
        return $settings[$key] ?? $default;
    }
}

/**
 * Tenant-aware PDO wrapper
 * Automatically adds restaurant_id to queries
 */
class TenantDB extends PDO {
    private $restaurantId;
    
    public function __construct() {
        parent::__construct(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD
        );
        
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->restaurantId = Restaurant::getCurrentId();
        
        if ($this->restaurantId === null) {
            throw new Exception('No restaurant context set. Call Restaurant::initialize() first.');
        }
    }
    
    /**
     * Override prepare to add restaurant filter
     */
    #[\ReturnTypeWillChange]
    public function prepare($query, $options = []) {
        // Tables that don't need restaurant_id filter
        $globalTables = ['restaurants', 'restaurant_settings', 'permissions', 'roles', 'role_permissions'];
        
        // Check if query involves global tables
        $isGlobalQuery = false;
        foreach ($globalTables as $table) {
            if (stripos($query, $table) !== false) {
                $isGlobalQuery = true;
                break;
            }
        }
        
        // Add restaurant filter for non-global queries
        if (!$isGlobalQuery && stripos($query, 'SELECT') === 0) {
            $query = TenantMiddleware::filterQuery($query, $this->restaurantId);
        }
        
        return parent::prepare($query, $options);
    }
    
    /**
     * Get current restaurant ID
     */
    public function getRestaurantId() {
        return $this->restaurantId;
    }
}
