<?php

/**
 * Settings Enforcement Middleware
 * Enforces system settings throughout the application
 */
class SettingsEnforcement
{
    /**
     * Check if system is in maintenance mode
     */
    public static function checkMaintenanceMode(): bool
    {
        if (SystemSettings::isMaintenanceMode()) {
            // Allow super admin access
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
                return false; // Super admin bypasses maintenance mode
            }
            return true; // Maintenance mode is ON
        }
        return false;
    }
    
    /**
     * Enforce maintenance mode by showing maintenance page
     */
    public static function enforceMaintenanceMode()
    {
        if (self::checkMaintenanceMode()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            
            $supportEmail = SystemSettings::getSupportEmail();
            $supportPhone = SystemSettings::getSupportPhone();
            
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - Smart Restaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .maintenance-container {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
        }
        .icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        h1 {
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        p {
            color: #6b7280;
            font-size: 1.125rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .contact-info {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }
        .contact-info h3 {
            color: #374151;
            font-size: 1rem;
            margin-bottom: 15px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #6b7280;
            font-size: 0.95rem;
            margin: 8px 0;
        }
        .contact-item i {
            color: #667eea;
            font-size: 1.1rem;
        }
        .status {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>System Maintenance</h1>
        <p>We are currently performing scheduled maintenance to improve your experience. Our system will be back online shortly.</p>
        <p style="font-size: 1rem;">Thank you for your patience!</p>
        
        <div class="status">
            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 5px;"></i>
            Maintenance in Progress
        </div>
        
        <div class="contact-info">
            <h3>Need Urgent Assistance?</h3>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>' . htmlspecialchars($supportEmail) . '</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span>' . htmlspecialchars($supportPhone) . '</span>
            </div>
        </div>
    </div>
</body>
</html>';
            exit;
        }
    }
    
    /**
     * Validate password against minimum length requirement
     */
    public static function validatePassword($password): array
    {
        $minLength = SystemSettings::getPasswordMinLength();
        $valid = strlen($password) >= $minLength;
        
        return [
            'valid' => $valid,
            'message' => $valid ? 'Password meets requirements' : "Password must be at least {$minLength} characters long"
        ];
    }
    
    /**
     * Check if notifications are enabled
     */
    public static function areNotificationsEnabled(): bool
    {
        return SystemSettings::getNotificationEnabled();
    }
    
    /**
     * Check if email notifications are enabled
     */
    public static function areEmailNotificationsEnabled(): bool
    {
        return SystemSettings::getEmailNotifications();
    }
    
    /**
     * Get allowed payment methods
     */
    public static function getAllowedPaymentMethods(): array
    {
        return SystemSettings::getPaymentMethods();
    }
    
    /**
     * Validate payment method
     */
    public static function isPaymentMethodAllowed($method): bool
    {
        $allowed = self::getAllowedPaymentMethods();
        return in_array($method, $allowed);
    }
    
    /**
     * Check if staff shift exceeds maximum hours
     */
    public static function validateShiftDuration($clockInTime): bool
    {
        $maxHours = SystemSettings::getStaffShiftMaxHours();
        $maxSeconds = $maxHours * 3600;
        $elapsed = time() - strtotime($clockInTime);
        
        return $elapsed <= $maxSeconds;
    }
    
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
                     AND rt.last_occupied_at IS NOT NULL
                     AND rt.last_occupied_at < DATE_SUB(NOW(), INTERVAL ? SECOND)";
            
            $stmt = $model->db->prepare($query);
            $stmt->execute([$timeout]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tables as $table) {
                $updateQuery = "UPDATE restaurant_tables SET status = 'available', last_occupied_at = NULL WHERE id = ?";
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

