<?php

/**
 * Settings Enforcement Middleware
 * Enforces system settings throughout the application
 */
class SettingsEnforcement
{
    /**
     * Check if staff is on shift (if required)
     */
    public static function enforceShiftRequirement($staffId = null)
    {
        if (!SystemSettings::getStaffClockInRequired()) {
            return true; // Not required
        }
        
        if (!$staffId && isset($_SESSION['staff_id'])) {
            $staffId = $_SESSION['staff_id'];
        }
        
        if (!$staffId) {
            return false;
        }
        
        require_once __DIR__ . '/../models/Staff.php';
        $staffModel = new Staff();
        return $staffModel->isOnShift($staffId);
    }
    
    /**
     * Check business hours
     */
    public static function checkBusinessHours(): bool
    {
        return SystemSettings::isBusinessHours();
    }
    
    /**
     * Validate session timeout
     */
    public static function checkSessionTimeout()
    {
        if (!isset($_SESSION['staff_user']) && !isset($_SESSION['user_id'])) {
            return true; // Not logged in, nothing to check
        }
        
        $timeout = SystemSettings::getSessionTimeout();
        $lastActivity = $_SESSION['last_activity'] ?? time();
        
        if ((time() - $lastActivity) > $timeout) {
            session_destroy();
            session_start();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if max pending orders limit reached
     */
    public static function checkMaxPendingOrders($restaurantId): bool
    {
        $max = SystemSettings::getMaxPendingOrders();
        
        try {
            require_once __DIR__ . '/../../src/model.php';
            $model = new Model();
            $query = "SELECT COUNT(*) as count 
                     FROM orders o 
                     INNER JOIN restaurant_tables t ON o.table_id = t.id 
                     WHERE t.restaurant_id = ? 
                     AND o.status IN ('pending', 'confirmed', 'preparing', 'ready')";
            $stmt = $model->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['count'] ?? 0) < $max;
        } catch (Exception $e) {
            error_log('SettingsEnforcement: Failed to check max pending orders - ' . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    /**
     * Check if minimum order amount met
     */
    public static function validateMinimumOrderAmount($totalAmount): bool
    {
        $minimum = SystemSettings::getMinimumOrderAmount();
        return $totalAmount >= $minimum;
    }
    
    /**
     * Check if table count limit reached
     */
    public static function checkMaxTables($restaurantId): bool
    {
        $max = SystemSettings::getMaxTables();
        
        try {
            require_once __DIR__ . '/../../src/model.php';
            $model = new Model();
            $query = "SELECT COUNT(*) as count FROM restaurant_tables WHERE restaurant_id = ?";
            $stmt = $model->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['count'] ?? 0) < $max;
        } catch (Exception $e) {
            error_log('SettingsEnforcement: Failed to check max tables - ' . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    /**
     * Auto-release tables based on timeout
     */
    public static function autoReleaseTables()
    {
        if (!SystemSettings::getTableAutoRelease()) {
            return;
        }
        
        $timeout = SystemSettings::getTableReleaseTimeout();
        
        try {
            require_once __DIR__ . '/../../src/model.php';
            $model = new Model();
            
            // Find tables that should be auto-released
            $query = "SELECT rt.* 
                     FROM restaurant_tables rt
                     LEFT JOIN orders o ON o.table_id = rt.id 
                     AND o.status NOT IN ('completed', 'cancelled')
                     WHERE rt.status = 'occupied' 
                     AND o.id IS NULL
                     AND rt.last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)";
            
            $stmt = $model->db->prepare($query);
            $stmt->execute([$timeout]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tables as $table) {
                $updateQuery = "UPDATE restaurant_tables SET status = 'available', last_activity = NULL WHERE id = ?";
                $updateStmt = $model->db->prepare($updateQuery);
                $updateStmt->execute([$table['id']]);
            }
            
            if (count($tables) > 0) {
                error_log('SettingsEnforcement: Auto-released ' . count($tables) . ' tables');
            }
        } catch (Exception $e) {
            error_log('SettingsEnforcement: Failed to auto-release tables - ' . $e->getMessage());
        }
    }
}

