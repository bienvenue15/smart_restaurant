<?php
/**
 * Subscription Management and Enforcement
 * Handles all subscription validation and plan limits
 */

class SubscriptionManager {
    
    private $db;
    private static $instance = null;
    private static $planCache = [];
    
    private function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if restaurant subscription is active
     */
    public function isActive($restaurantId) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return false;
        }
        
        // Check if restaurant is active
        if (!$restaurant['is_active']) {
            return false;
        }
        
        // Check subscription dates
        $today = date('Y-m-d');
        $subscriptionStart = $restaurant['subscription_start'];
        $subscriptionEnd = $restaurant['subscription_end'];
        
        if (empty($subscriptionStart) || empty($subscriptionEnd)) {
            return false;
        }
        
        return ($today >= $subscriptionStart && $today <= $subscriptionEnd);
    }
    
    /**
     * Check if restaurant is expired
     */
    public function isExpired($restaurantId) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return true;
        }
        
        if (empty($restaurant['subscription_end'])) {
            return true;
        }
        
        return date('Y-m-d') > $restaurant['subscription_end'];
    }
    
    /**
     * Get days remaining in subscription
     */
    public function getDaysRemaining($restaurantId) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant || empty($restaurant['subscription_end'])) {
            return 0;
        }
        
        $today = new DateTime();
        $endDate = new DateTime($restaurant['subscription_end']);
        $diff = $today->diff($endDate);
        
        return $diff->invert ? 0 : $diff->days;
    }
    
    /**
     * Get plan limits from database
     */
    public function getPlanLimits($planName) {
        if (isset(self::$planCache[$planName])) {
            return self::$planCache[$planName];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE plan_name = ? AND is_active = 1");
            $stmt->execute([$planName]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // Parse JSON features
                $plan['features'] = json_decode($plan['features'], true) ?? [];
                
                // Cache the plan
                self::$planCache[$planName] = $plan;
                return $plan;
            }
        } catch (PDOException $e) {
            error_log('[SubscriptionManager] Error fetching plan: ' . $e->getMessage());
        }
        
        // Fallback to trial if plan not found
        return [
            'max_tables' => 10,
            'max_users' => 5,
            'max_menu_items' => 50,
            'max_orders_per_month' => 200,
            'features' => ['basic_pos', 'qr_ordering'],
            'duration_days' => 30
        ];
    }
    
    /**
     * Check if specific feature is available in plan
     */
    public function hasFeature($restaurantId, $feature) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return false;
        }
        
        $plan = $restaurant['subscription_plan'];
        $limits = $this->getPlanLimits($plan);
        
        return in_array($feature, $limits['features'] ?? []);
    }
    
    /**
     * Check if resource limit has been reached
     */
    public function checkLimit($restaurantId, $resource) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return ['allowed' => false, 'message' => 'Restaurant not found'];
        }
        
        $plan = $restaurant['subscription_plan'];
        $limits = $this->getPlanLimits($plan);
        
        switch ($resource) {
            case 'tables':
                $maxLimit = $limits['max_tables'];
                if ($maxLimit === -1 || $maxLimit >= 999999) {
                    return ['allowed' => true];
                }
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_uuid = ?");
                $stmt->execute([$restaurantId]);
                $current = $stmt->fetchColumn();
                
                $allowed = $current < $maxLimit;
                return [
                    'allowed' => $allowed,
                    'current' => $current,
                    'limit' => $maxLimit,
                    'message' => $allowed ? '' : "Table limit reached. Your {$plan} plan allows {$maxLimit} tables. Upgrade to add more."
                ];
                
            case 'users':
                $maxLimit = $limits['max_users'];
                if ($maxLimit === -1 || $maxLimit >= 999999) {
                    return ['allowed' => true];
                }
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff_users WHERE restaurant_uuid = ?");
                $stmt->execute([$restaurantId]);
                $current = $stmt->fetchColumn();
                
                $allowed = $current < $maxLimit;
                return [
                    'allowed' => $allowed,
                    'current' => $current,
                    'limit' => $maxLimit,
                    'message' => $allowed ? '' : "User limit reached. Your {$plan} plan allows {$maxLimit} users. Upgrade to add more."
                ];
                
            case 'menu_items':
                $maxLimit = $limits['max_menu_items'];
                if ($maxLimit === -1 || $maxLimit >= 999999) {
                    return ['allowed' => true];
                }
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM menu_items WHERE restaurant_uuid = ?");
                $stmt->execute([$restaurantId]);
                $current = $stmt->fetchColumn();
                
                $allowed = $current < $maxLimit;
                return [
                    'allowed' => $allowed,
                    'current' => $current,
                    'limit' => $maxLimit,
                    'message' => $allowed ? '' : "Menu item limit reached. Your {$plan} plan allows {$maxLimit} items. Upgrade to add more."
                ];
                
            case 'orders':
                $maxLimit = $limits['max_orders_per_month'];
                if ($maxLimit === -1 || $maxLimit >= 999999) {
                    return ['allowed' => true];
                }
                
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM orders 
                    WHERE restaurant_uuid = ? 
                    AND MONTH(created_at) = MONTH(CURDATE())
                    AND YEAR(created_at) = YEAR(CURDATE())
                ");
                $stmt->execute([$restaurantId]);
                $current = $stmt->fetchColumn();
                
                $allowed = $current < $maxLimit;
                return [
                    'allowed' => $allowed,
                    'current' => $current,
                    'limit' => $maxLimit,
                    'message' => $allowed ? '' : "Monthly order limit reached. Your {$plan} plan allows {$maxLimit} orders per month. Upgrade for more capacity."
                ];
                
            default:
                return ['allowed' => true];
        }
    }
    
    /**
     * Require active subscription (middleware)
     */
    public function requireActive($restaurantId) {
        if (!$this->isActive($restaurantId)) {
            $daysRemaining = $this->getDaysRemaining($restaurantId);
            $isExpired = $this->isExpired($restaurantId);
            
            $message = $isExpired 
                ? 'Your subscription has expired. Please renew to continue using the service.'
                : 'Your restaurant account is inactive. Please contact support.';
            
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'SUBSCRIPTION_EXPIRED',
                'message' => $message,
                'days_remaining' => $daysRemaining,
                'is_expired' => $isExpired
            ]);
            exit;
        }
    }
    
    /**
     * Require specific feature
     */
    public function requireFeature($restaurantId, $feature, $featureName = null) {
        if (!$this->hasFeature($restaurantId, $feature)) {
            $restaurant = $this->getRestaurant($restaurantId);
            $plan = $restaurant['subscription_plan'] ?? 'trial';
            
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'FEATURE_NOT_AVAILABLE',
                'message' => ($featureName ?? $feature) . " is not available in your {$plan} plan. Please upgrade to access this feature.",
                'current_plan' => $plan,
                'feature' => $feature
            ]);
            exit;
        }
    }
    
    /**
     * Get restaurant plan details
     */
    public function getPlanDetails($restaurantId) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return null;
        }
        
        $plan = $restaurant['subscription_plan'];
        $limits = $this->getPlanLimits($plan);
        
        return [
            'plan' => $plan,
            'limits' => $limits,
            'subscription_start' => $restaurant['subscription_start'],
            'subscription_end' => $restaurant['subscription_end'],
            'days_remaining' => $this->getDaysRemaining($restaurantId),
            'is_active' => $this->isActive($restaurantId),
            'is_expired' => $this->isExpired($restaurantId)
        ];
    }
    
    /**
     * Get all available plans from database
     */
    public static function getAllPlans($activeOnly = false) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT * FROM subscription_plans";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY price_monthly ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse JSON features for each plan
            foreach ($plans as &$plan) {
                $plan['features'] = json_decode($plan['features'], true) ?? [];
            }
            
            return $plans;
        } catch (PDOException $e) {
            error_log('[SubscriptionManager] Error fetching all plans: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear the plan cache to reflect database changes immediately
     */
    public static function clearCache() {
        self::$planCache = [];
        error_log('[SubscriptionManager] Plan cache cleared');
    }
    
    /**
     * Upgrade restaurant plan
     */
    public function upgradePlan($restaurantId, $newPlan, $duration = 365) {
        // Validate plan exists in database
        $planLimits = $this->getPlanLimits($newPlan);
        if (!$planLimits || empty($planLimits['max_tables'])) {
            return ['status' => 'FAIL', 'message' => 'Invalid plan'];
        }
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$duration} days"));
        
        $stmt = $this->db->prepare("
            UPDATE restaurants 
            SET subscription_plan = ?,
                subscription_start = ?,
                subscription_end = ?,
                is_active = 1,
                updated_at = NOW()
            WHERE uuid = ?
        ");
        
        $result = $stmt->execute([$newPlan, $startDate, $endDate, $restaurantId]);
        
        if ($result) {
            // Log the upgrade
            error_log("[SubscriptionManager] Restaurant {$restaurantId} upgraded to {$newPlan} plan");
            // Clear cache
            self::clearCache();
            return ['status' => 'OK', 'message' => 'Plan upgraded successfully'];
        }
        
        return ['status' => 'FAIL', 'message' => 'Failed to upgrade plan'];
    }
    
    /**
     * Extend subscription
     */
    public function extendSubscription($restaurantId, $days) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return ['status' => 'FAIL', 'message' => 'Restaurant not found'];
        }
        
        $currentEnd = $restaurant['subscription_end'];
        $newEnd = date('Y-m-d', strtotime($currentEnd . " +{$days} days"));
        
        $stmt = $this->db->prepare("
            UPDATE restaurants 
            SET subscription_end = ?,
                updated_at = NOW()
            WHERE uuid = ?
        ");
        
        $result = $stmt->execute([$newEnd, $restaurantId]);
        
        if ($result) {
            error_log("[SubscriptionManager] Restaurant {$restaurantId} subscription extended by {$days} days");
            return ['status' => 'OK', 'message' => 'Subscription extended successfully', 'new_end_date' => $newEnd];
        }
        
        return ['status' => 'FAIL', 'message' => 'Failed to extend subscription'];
    }
    
    /**
     * Deactivate restaurant (suspend)
     */
    public function deactivate($restaurantId, $reason = '') {
        $stmt = $this->db->prepare("
            UPDATE restaurants 
            SET is_active = 0,
                updated_at = NOW()
            WHERE uuid = ?
        ");
        
        $result = $stmt->execute([$restaurantId]);
        
        if ($result) {
            error_log("[SubscriptionManager] Restaurant {$restaurantId} deactivated. Reason: {$reason}");
            return ['status' => 'OK', 'message' => 'Restaurant deactivated'];
        }
        
        return ['status' => 'FAIL', 'message' => 'Failed to deactivate restaurant'];
    }
    
    /**
     * Reactivate restaurant
     */
    public function reactivate($restaurantId) {
        $stmt = $this->db->prepare("
            UPDATE restaurants 
            SET is_active = 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$restaurantId]);
        
        if ($result) {
            error_log("[SubscriptionManager] Restaurant {$restaurantId} reactivated");
            return ['status' => 'OK', 'message' => 'Restaurant reactivated'];
        }
        
        return ['status' => 'FAIL', 'message' => 'Failed to reactivate restaurant'];
    }
    
    /**
     * Get restaurant data
     */
    private function getRestaurant($restaurantId) {
        static $cache = [];
        
        if (isset($cache[$restaurantId])) {
            return $cache[$restaurantId];
        }
        
        $stmt = $this->db->prepare("SELECT * FROM restaurants WHERE uuid = ?");
        $stmt->execute([$restaurantId]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cache[$restaurantId] = $restaurant;
        return $restaurant;
    }
    
    /**
     * Send expiration warning email
     */
    public function sendExpirationWarning($restaurantId) {
        $restaurant = $this->getRestaurant($restaurantId);
        if (!$restaurant) {
            return false;
        }
        
        $daysRemaining = $this->getDaysRemaining($restaurantId);
        
        // Only send warnings at 30, 14, 7, 3, 1 days
        if (!in_array($daysRemaining, [30, 14, 7, 3, 1])) {
            return false;
        }
        
        // Send email notification (implement with PHPMailer)
        error_log("[SubscriptionManager] Warning: Restaurant {$restaurantId} ({$restaurant['name']}) has {$daysRemaining} days remaining");
        
        // TODO: Implement email sending
        return true;
    }
}
