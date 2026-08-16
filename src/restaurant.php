<?php
/**
 * Restaurant (Tenant) Management Class
 * Handles multi-tenancy operations
 */

class Restaurant {
    private $db;
    private static $currentRestaurant = null;
    private static $basePath = null;
    
    public function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Get restaurant by slug (subdomain or path)
     */
    public function getBySlug($slug) {
        $query = "SELECT * FROM restaurants WHERE slug = ? AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get restaurant by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM restaurants WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get restaurant by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM restaurants WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new restaurant
     */
    public function create($data) {
        // Generate secure UUID for restaurant
        $uuid = $this->generateUUID();
        
        $query = "INSERT INTO restaurants (
            uuid, name, slug, email, phone, tin, address, city, country,
            currency, timezone, logo_url, primary_color, secondary_color,
            tax_rate, service_charge, max_tables, max_users,
            subscription_plan, subscription_start, subscription_end, is_active
        ) VALUES (
            :uuid, :name, :slug, :email, :phone, :tin, :address, :city, :country,
            :currency, :timezone, :logo_url, :primary_color, :secondary_color,
            :tax_rate, :service_charge, :max_tables, :max_users,
            :subscription_plan, :subscription_start, :subscription_end, :is_active
        )";
        
        try {
            $stmt = $this->db->prepare($query);
            
            // Set defaults
            $data = array_merge([
                'tin' => null,
                'country' => 'Rwanda',
                'currency' => 'RWF',
                'timezone' => 'Africa/Kigali',
                'logo_url' => null,
                'primary_color' => '#2563eb',
                'secondary_color' => '#1e40af',
                'tax_rate' => 0.00,
                'service_charge' => 0.00,
                'max_tables' => 50,
                'max_users' => 20,
                'subscription_plan' => 'trial',
                'subscription_start' => date('Y-m-d'),
                'subscription_end' => date('Y-m-d', strtotime('+30 days')),
                'is_active' => 1
            ], $data);
            
            // Make slug more secure by adding random string
            if (isset($data['slug'])) {
                $data['slug'] = $this->secureSlug($data['slug']);
            }
            
            // Extract only the fields that match SQL placeholders
            $params = [
                'uuid' => $uuid,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'tin' => $data['tin'],
                'address' => $data['address'],
                'city' => $data['city'],
                'country' => $data['country'],
                'currency' => $data['currency'],
                'timezone' => $data['timezone'],
                'logo_url' => $data['logo_url'],
                'primary_color' => $data['primary_color'],
                'secondary_color' => $data['secondary_color'],
                'tax_rate' => $data['tax_rate'],
                'service_charge' => $data['service_charge'],
                'max_tables' => $data['max_tables'],
                'max_users' => $data['max_users'],
                'subscription_plan' => $data['subscription_plan'],
                'subscription_start' => $data['subscription_start'],
                'subscription_end' => $data['subscription_end'],
                'is_active' => $data['is_active']
            ];
            
            error_log('[Restaurant Model] Creating restaurant with UUID: ' . $uuid);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log('[Restaurant Model] Execute failed: ' . json_encode($errorInfo));
                throw new PDOException('Failed to insert restaurant: ' . $errorInfo[2]);
            }
            
            // Return both UUID and ID for transition period
            $id = $this->db->lastInsertId();
            error_log('[Restaurant Model] Created restaurant with ID: ' . $id . ', UUID: ' . $uuid);
            return [
                'id' => $id,
                'uuid' => $uuid,
                'slug' => $data['slug']
            ];
            
        } catch (PDOException $e) {
            error_log('[Restaurant Model] Create error: ' . $e->getMessage());
            error_log('[Restaurant Model] Error code: ' . $e->getCode());
            
            // Check for duplicate entry errors
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'slug') !== false) {
                    throw new PDOException('A restaurant with this slug already exists');
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    throw new PDOException('A restaurant with this email already exists');
                }
            }
            
            throw $e;
        }
    }
    
    /**
     * Update restaurant
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'name', 'slug', 'email', 'phone', 'tin', 'address', 'city', 'country',
            'currency', 'timezone', 'logo_url', 'primary_color', 'secondary_color',
            'tax_rate', 'service_charge', 'max_tables', 'max_users',
            'subscription_plan', 'subscription_start', 'subscription_end', 'is_active'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            error_log('[Restaurant Model] Update called with no valid fields');
            return false;
        }
        
        $values[] = $id;
        $query = "UPDATE restaurants SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($values);
            
            // Check if any rows were affected
            $rowCount = $stmt->rowCount();
            error_log('[Restaurant Model] Update query affected ' . $rowCount . ' rows for ID: ' . $id);
            
            // Return true if executed successfully (even if no rows changed because values were the same)
            return $result;
        } catch (PDOException $e) {
            error_log('[Restaurant Model] Update error: ' . $e->getMessage());
            error_log('[Restaurant Model] Query: ' . $query);
            throw $e;
        }
    }
    
    /**
     * Delete restaurant (soft delete - set inactive)
     */
    public function delete($id) {
        $query = "UPDATE restaurants SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Hard delete restaurant and all its data
     */
    public function hardDelete($id) {
        // CASCADE will delete all related data
        $query = "DELETE FROM restaurants WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all restaurants
     */
    public function getAll($activeOnly = true) {
        $query = "SELECT * FROM restaurants";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get restaurant statistics
     */
    public function getStats($restaurantId) {
        // Get counts directly from tables instead of using a view
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff_users WHERE restaurant_id = ?");
            $stmt->execute([$restaurantId]);
            $stats['total_users'] = $stmt->fetchColumn() ?: 0;
            
            // Total tables
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_id = ?");
            $stmt->execute([$restaurantId]);
            $stats['total_tables'] = $stmt->fetchColumn() ?: 0;
            
            // Total orders
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ?");
            $stmt->execute([$restaurantId]);
            $stats['total_orders'] = $stmt->fetchColumn() ?: 0;
            
            // Today's orders
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()");
            $stmt->execute([$restaurantId]);
            $stats['today_orders'] = $stmt->fetchColumn() ?: 0;
            
            // Today's revenue
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE() AND status IN ('completed', 'paid')");
            $stmt->execute([$restaurantId]);
            $stats['today_revenue'] = $stmt->fetchColumn() ?: 0;
            
            // Total revenue (all time)
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE restaurant_id = ? AND status IN ('completed', 'paid')");
            $stmt->execute([$restaurantId]);
            $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
            
            // Pending orders
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = 'pending'");
            $stmt->execute([$restaurantId]);
            $stats['pending_orders'] = $stmt->fetchColumn() ?: 0;
            
            // Menu items count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM menu_items WHERE restaurant_id = ?");
            $stmt->execute([$restaurantId]);
            $stats['total_menu_items'] = $stmt->fetchColumn() ?: 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log('[Restaurant::getStats] Error: ' . $e->getMessage());
            // Return default stats on error
            return [
                'total_users' => 0,
                'total_tables' => 0,
                'total_orders' => 0,
                'today_orders' => 0,
                'today_revenue' => 0,
                'total_revenue' => 0,
                'pending_orders' => 0,
                'total_menu_items' => 0
            ];
        }
    }
    
    /**
     * Check if restaurant has reached limits
     */
    public function checkLimits($restaurantId, $type) {
        $restaurant = $this->getById($restaurantId);
        if (!$restaurant) return false;
        
        switch ($type) {
            case 'tables':
                $query = "SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
                $count = $stmt->fetchColumn();
                return $count < $restaurant['max_tables'];
                
            case 'users':
                $query = "SELECT COUNT(*) FROM staff_users WHERE restaurant_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$restaurantId]);
                $count = $stmt->fetchColumn();
                return $count < $restaurant['max_users'];
                
            default:
                return true;
        }
    }
    
    /**
     * Check if subscription is active
     */
    public function isSubscriptionActive($restaurantId) {
        $restaurant = $this->getById($restaurantId);
        if (!$restaurant) return false;
        
        $today = date('Y-m-d');
        return $restaurant['is_active'] && 
               $today >= $restaurant['subscription_start'] && 
               $today <= $restaurant['subscription_end'];
    }
    
    /**
     * Get restaurant settings
     */
    public function getSettings($restaurantId) {
        $query = "SELECT setting_key, setting_value FROM restaurant_settings WHERE restaurant_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$restaurantId]);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
    
    /**
     * Set restaurant setting
     */
    public function setSetting($restaurantId, $key, $value) {
        $query = "INSERT INTO restaurant_settings (restaurant_id, setting_key, setting_value) 
                  VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$restaurantId, $key, $value, $value]);
    }
    
    /**
     * Set current restaurant for session
     */
    public static function setCurrent($restaurantId) {
        if (!isset($_SESSION)) {
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
        $_SESSION['restaurant_id'] = $restaurantId;
        self::$currentRestaurant = $restaurantId;
    }
    
    /**
     * Get current restaurant ID
     */
    public static function getCurrentId() {
        if (self::$currentRestaurant !== null) {
            return self::$currentRestaurant;
        }
        
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
        
        if (isset($_SESSION['restaurant_id'])) {
            self::$currentRestaurant = $_SESSION['restaurant_id'];
            return self::$currentRestaurant;
        }
        
        return null;
    }
    
    /**
     * Get current restaurant data
     */
    public static function getCurrent() {
        $id = self::getCurrentId();
        if (!$id) return null;
        
        $restaurant = new Restaurant();
        return $restaurant->getById($id);
    }
    
    /**
     * Detect restaurant from query parameter (?tenant=slug)
     */
    public static function detectFromQuery() {
        $slug = $_GET['tenant'] ?? $_GET['restaurant'] ?? null;
        if (empty($slug)) {
            return null;
        }
        
        $restaurant = new Restaurant();
        return $restaurant->getBySlug($slug);
    }
    
    /**
     * Detect restaurant from domain/subdomain
     */
    public static function detectFromDomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Extract subdomain
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            // Format: restaurant.example.com
            $subdomain = $parts[0];
            if ($subdomain !== 'www') {
                $restaurant = new Restaurant();
                return $restaurant->getBySlug($subdomain);
            }
        }
        
        return null;
    }
    
    /**
     * Detect restaurant from URL path
     */
    public static function detectFromPath() {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = self::stripBasePath($path);
        $parts = explode('/', trim($path, '/'));
        
        // Format: /restaurant-slug/...
        if (!empty($parts[0])) {
            $restaurant = new Restaurant();
            return $restaurant->getBySlug($parts[0]);
        }
        
        return null;
    }
    
    /**
     * Initialize restaurant context
     * Call this at the beginning of each request
     */
    public static function initialize() {
        // Highest priority: tenant parameter
        $restaurant = self::detectFromQuery();
        
        // Try to detect from domain first
        if (!$restaurant) {
            $restaurant = self::detectFromDomain();
        }
        
        // If not found, try from path
        if (!$restaurant) {
            $restaurant = self::detectFromPath();
        }
        
        // If found, set as current
        if ($restaurant) {
            self::setCurrent($restaurant['uuid'] ?? $restaurant['id'] ?? null);
            return $restaurant;
        }
        
        // If this is a tenant-specific request, allow session fallback
        if (!self::isGlobalLanding()) {
            $currentId = self::getCurrentId();
            if ($currentId) {
                $restaurantObj = new Restaurant();
                return $restaurantObj->getById($currentId);
            }
        } else {
            // Global landing should never inherit previous tenant context
            self::clearCurrent();
        }
        
        return null;
    }
    
    /**
     * Generate unique slug from name
     */
    public static function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $restaurant = new Restaurant();
        $original = $slug;
        $counter = 1;
        
        while ($restaurant->getBySlug($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Generate secure UUID v4
     * @return string UUID
     */
    private function generateUUID() {
        // Try MySQL UUID() first
        try {
            $result = $this->db->query("SELECT UUID() as uuid");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['uuid'])) {
                return $row['uuid'];
            }
        } catch (PDOException $e) {
            // Fallback to PHP
        }
        
        // PHP-based UUID v4
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Make slug more secure by adding random string
     * This prevents enumeration and makes restaurant URLs non-guessable
     * Format: restaurant-name-a7b3c4
     * @param string $slug Original slug
     * @return string Secure slug with random suffix
     */
    private function secureSlug($slug) {
        // Generate 6-character random string (alphanumeric)
        $randomSuffix = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6);
        
        // Combine slug with random suffix
        $secureSlug = $slug . '-' . $randomSuffix;
        
        // Verify uniqueness (very rare collision with 36^6 possibilities)
        $counter = 1;
        while ($this->getBySlug($secureSlug)) {
            $randomSuffix = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6);
            $secureSlug = $slug . '-' . $randomSuffix;
            
            // Safety break after 100 attempts (should never happen)
            if ($counter++ > 100) {
                // Use timestamp as last resort
                $secureSlug = $slug . '-' . time();
                break;
            }
        }
        
        return $secureSlug;
    }
    
    /**
     * Get restaurant by UUID (secure access)
     * @param string $uuid
     * @return array|false
     */
    public function getByUuid($uuid) {
        $query = "SELECT * FROM restaurants WHERE uuid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$uuid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Remove base path (e.g. /restaurant) from request URI before detecting slug
     */
    private static function stripBasePath($path) {
        $trimmedPath = trim($path, '/');
        $basePath = self::getBasePath();
        
        if ($basePath !== '' && stripos($trimmedPath, $basePath) === 0) {
            $trimmedPath = trim(substr($trimmedPath, strlen($basePath)), '/');
        }
        
        return $trimmedPath;
    }
    
    /**
     * Determine application base path from BASE_URL
     */
    private static function getBasePath() {
        if (self::$basePath !== null) {
            return self::$basePath;
        }
        
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
        self::$basePath = trim($baseUrlPath ?? '', '/');
        return self::$basePath;
    }
    
    /**
     * Determine if current request should avoid tenant context inheritance
     */
    private static function isGlobalLanding() {
        if (php_sapi_name() === 'cli') {
            return false;
        }
        
        $req = $_GET['req'] ?? '';
        return $req === '' || $req === 'index';
    }
    
    /**
     * Clear the tenant context from session
     */
    public static function clearCurrent() {
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
        
        unset($_SESSION['restaurant_id']);
        self::$currentRestaurant = null;
    }
}
