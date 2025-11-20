<?php

/**
 * Lightweight cache for global system settings.
 */
class SystemSettings
{
    private static $settings = null;

    public static function get($key, $default = null)
    {
        self::ensureLoaded();
        return array_key_exists($key, self::$settings) ? self::$settings[$key] : $default;
    }

    public static function getAll(): array
    {
        self::ensureLoaded();
        return self::$settings;
    }

    public static function setCache(array $settings): void
    {
        self::$settings = $settings;
    }

    public static function refresh(): void
    {
        self::$settings = null;
        self::ensureLoaded();
    }

    public static function isMaintenanceMode(): bool
    {
        return strtolower((string) self::get('maintenance_mode', 'off')) === 'on';
    }
    
    // Session & Security Settings
    public static function getSessionTimeout(): int
    {
        return (int) self::get('session_timeout', 7200); // Default 2 hours in seconds
    }
    
    public static function getMaxLoginAttempts(): int
    {
        return (int) self::get('max_login_attempts', 5);
    }
    
    public static function getPasswordMinLength(): int
    {
        return (int) self::get('password_min_length', 8);
    }
    
    // Business Hours Settings
    public static function getBusinessHours(): array
    {
        $hours = self::get('business_hours', '{"mon":"09:00-22:00","tue":"09:00-22:00","wed":"09:00-22:00","thu":"09:00-22:00","fri":"09:00-22:00","sat":"09:00-23:00","sun":"10:00-21:00"}');
        if (is_string($hours)) {
            $hours = json_decode($hours, true) ?? [];
        }
        return $hours;
    }
    
    public static function isBusinessHours(): bool
    {
        $hours = self::getBusinessHours();
        $day = strtolower(date('D'));
        $dayMap = ['mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun'];
        $currentDay = $dayMap[$day] ?? 'mon';
        
        if (!isset($hours[$currentDay]) || empty($hours[$currentDay])) {
            return true; // Default open if not configured
        }
        
        $timeRange = $hours[$currentDay];
        if (strpos($timeRange, '-') === false) {
            return true;
        }
        
        list($open, $close) = explode('-', $timeRange);
        $now = time();
        $openTime = strtotime(date('Y-m-d') . ' ' . trim($open));
        $closeTime = strtotime(date('Y-m-d') . ' ' . trim($close));
        
        return ($now >= $openTime && $now <= $closeTime);
    }
    
    // Order Management Settings
    public static function getMaxPendingOrders(): int
    {
        return (int) self::get('max_pending_orders', 50);
    }
    
    public static function getAutoAssignOrders(): bool
    {
        return strtolower((string) self::get('auto_assign_orders', 'off')) === 'on';
    }
    
    public static function getOrderTimeout(): int
    {
        return (int) self::get('order_timeout', 300); // Default 5 minutes in seconds
    }
    
    // Table Management Settings
    public static function getMaxTables(): int
    {
        return (int) self::get('max_tables_per_restaurant', 100);
    }
    
    public static function getTableAutoRelease(): bool
    {
        return strtolower((string) self::get('table_auto_release', 'on')) === 'on';
    }
    
    public static function getTableReleaseTimeout(): int
    {
        return (int) self::get('table_release_timeout', 3600); // Default 1 hour in seconds
    }
    
    // Notification Settings
    public static function getNotificationEnabled(): bool
    {
        return strtolower((string) self::get('notifications_enabled', 'on')) === 'on';
    }
    
    public static function getEmailNotifications(): bool
    {
        return strtolower((string) self::get('email_notifications', 'on')) === 'on';
    }
    
    // Backup Settings
    public static function getBackupSchedule(): string
    {
        return (string) self::get('backup_schedule', '02:00 Africa/Kigali');
    }
    
    public static function getBackupRetentionDays(): int
    {
        return (int) self::get('backup_retention_days', 30);
    }
    
    // Support Settings
    public static function getSupportEmail(): string
    {
        return (string) self::get('support_email', 'info@inovasiyo.rw');
    }
    
    public static function getSupportPhone(): string
    {
        return (string) self::get('support_phone', '+250 788 000 999');
    }
    
    // Timezone Settings
    public static function getDefaultTimezone(): string
    {
        return (string) self::get('default_timezone', 'Africa/Kigali');
    }
    
    // QR Code Settings
    public static function getQRCodeFormat(): string
    {
        return (string) self::get('qrcode_format', 'png'); // png, svg, pdf
    }
    
    public static function getQRCodeSize(): int
    {
        return (int) self::get('qrcode_size', 300);
    }
    
    // Staff Settings
    public static function getStaffClockInRequired(): bool
    {
        return strtolower((string) self::get('staff_clock_in_required', 'on')) === 'on';
    }
    
    public static function getStaffShiftMaxHours(): int
    {
        return (int) self::get('staff_shift_max_hours', 12);
    }
    
    // Payment Settings
    public static function getPaymentMethods(): array
    {
        $methods = self::get('payment_methods', '["cash","card","mobile_money"]');
        if (is_string($methods)) {
            $methods = json_decode($methods, true) ?? ['cash'];
        }
        return is_array($methods) ? $methods : ['cash'];
    }
    
    public static function getMinimumOrderAmount(): float
    {
        return (float) self::get('minimum_order_amount', 0);
    }

    private static function ensureLoaded(): void
    {
        if (self::$settings !== null) {
            return;
        }

        self::$settings = [];
        try {
            $pdo = new PDO(
                DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PWD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log('SystemSettings load failed: ' . $e->getMessage());
        }
    }
}

