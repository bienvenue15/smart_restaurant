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
        $query = "INSERT INTO restaurants (
            name, slug, email, phone, address, city, country,
            currency, timezone, logo_url, primary_color, secondary_color,
            tax_rate, service_charge, max_tables, max_users,
            subscription_plan, subscription_start, subscription_end, is_active
        ) VALUES (
            :name, :slug, :email, :phone, :address, :city, :country,
            :currency, :timezone, :logo_url, :primary_color, :secondary_color,
            :tax_rate, :service_charge, :max_tables, :max_users,
            :subscription_plan, :subscription_start, :subscription_end, :is_active
        )";
        
        $stmt = $this->db->prepare($query);
        
        // Set defaults
        $data = array_merge([
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
        
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update restaurant
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'name', 'email', 'phone', 'address', 'city', 'country',
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
            return false;
        }
        
        $values[] = $id;
        $query = "UPDATE restaurants SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
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
        $query = "SELECT * FROM v_restaurant_stats WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$restaurantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        
        if (!isset($_SESSION)) {
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
            self::setCurrent($restaurant['id']);
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
        if (!isset($_SESSION)) {
            session_start();
        }
        
        unset($_SESSION['restaurant_id']);
        self::$currentRestaurant = null;
    }
}
