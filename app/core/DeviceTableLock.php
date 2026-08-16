<?php
/**
 * Device Table Lock Manager
 * Prevents customers from accessing multiple tables simultaneously
 */

class DeviceTableLock {
    private $db;
    
    public function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PWD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    /**
     * Generate device fingerprint from browser data + IP address
     */
    public static function generateFingerprint() {
        // Get real IP address (handles proxies)
        $ip = self::getRealIpAddress();
        
        $components = [
            $ip,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Get real IP address (handles proxies and load balancers)
     */
    private static function getRealIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs (take first one)
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        return $ip;
    }
    
    /**
     * Check if a table is already locked by another device
     * @return array|false Returns lock info if table is locked by another device, false otherwise
     */
    public function isTableLockedByAnotherDevice($tableId, $currentDeviceFingerprint) {
        try {
            // Clean up expired locks first
            $this->cleanupExpiredLocks();
            
            $stmt = $this->db->prepare("
                SELECT 
                    dtl.*,
                    rt.table_number
                FROM device_table_locks dtl
                JOIN restaurant_tables rt ON dtl.table_id = rt.id
                WHERE dtl.table_id = ?
                    AND dtl.device_fingerprint != ?
                    AND dtl.is_active = 1
                    AND dtl.expires_at > NOW()
                ORDER BY dtl.locked_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$tableId, $currentDeviceFingerprint]);
            $lock = $stmt->fetch();
            
            if ($lock) {
                error_log("[DEVICE_LOCK] Table $tableId is locked by another device: " . substr($lock['device_fingerprint'], 0, 16));
            }
            
            return $lock ?: false;
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Check table lock error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if device is already locked to a table
     * @return array|false Returns lock info if locked, false otherwise
     */
    public function getDeviceLock($deviceFingerprint, $restaurantId) {
        try {
            // Clean up expired locks first
            $this->cleanupExpiredLocks();
            
            $stmt = $this->db->prepare("
                SELECT 
                    dtl.*,
                    rt.table_number,
                    rt.status as table_status,
                    o.order_number,
                    o.status as order_status
                FROM device_table_locks dtl
                JOIN restaurant_tables rt ON dtl.table_id = rt.id
                LEFT JOIN orders o ON dtl.order_id = o.id
                WHERE dtl.device_fingerprint = ?
                    AND dtl.restaurant_id = ?
                    AND dtl.is_active = 1
                    AND dtl.expires_at > NOW()
                ORDER BY dtl.locked_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$deviceFingerprint, $restaurantId]);
            $lock = $stmt->fetch();
            
            if ($lock) {
                // Update last activity
                $this->updateLastActivity($lock['id']);
            }
            
            return $lock ?: false;
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Get lock error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lock a device to a table
     * @param int $lockDurationMinutes How long the lock should last
     */
    public function lockDeviceToTable($restaurantId, $tableId, $deviceFingerprint, $lockDurationMinutes = 120) {
        try {
            // Check if table has any active orders
            $orderCheckQuery = "SELECT COUNT(*) as order_count FROM orders 
                               WHERE table_id = ? 
                               AND (status IN ('pending', 'confirmed', 'preparing', 'ready', 'served')
                                    OR (status = 'completed' AND payment_status = 'pending'))";
            $orderStmt = $this->db->prepare($orderCheckQuery);
            $orderStmt->execute([$tableId]);
            $activeOrderCount = $orderStmt->fetchColumn();
            
            // If there are active orders, check if this table is locked by another device
            if ($activeOrderCount > 0) {
                $tableLock = $this->isTableLockedByAnotherDevice($tableId, $deviceFingerprint);
                if ($tableLock) {
                    error_log("[DEVICE_LOCK] BLOCKED: Table $tableId has active orders and is locked by another device");
                    return [
                        'success' => false,
                        'message' => 'This table is currently in use by another customer. Please wait until they complete their order or contact staff.',
                        'table_occupied' => true,
                        'has_active_orders' => true,
                        'locked_since' => $tableLock['locked_at']
                    ];
                }
            }
            
            // Check if device is already locked to another table
            $existingLock = $this->getDeviceLock($deviceFingerprint, $restaurantId);
            
            if ($existingLock && $existingLock['table_id'] != $tableId) {
                return [
                    'success' => false,
                    'message' => 'Your device is already locked to Table ' . $existingLock['table_number'],
                    'locked_table' => $existingLock['table_number'],
                    'locked_table_id' => $existingLock['table_id']
                ];
            }
            
            // If already locked to same table, just update activity
            if ($existingLock && $existingLock['table_id'] == $tableId) {
                $this->updateLastActivity($existingLock['id']);
                return [
                    'success' => true,
                    'message' => 'Lock refreshed',
                    'lock_id' => $existingLock['id']
                ];
            }
            
            // Create new lock
            $stmt = $this->db->prepare("
                INSERT INTO device_table_locks (
                    restaurant_id, table_id, device_fingerprint, 
                    ip_address, user_agent,
                    locked_at, expires_at, last_activity, is_active
                ) VALUES (
                    ?, ?, ?,
                    ?, ?,
                    NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW(), 1
                )
            ");
            
            $stmt->execute([
                $restaurantId,
                $tableId,
                $deviceFingerprint,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $lockDurationMinutes
            ]);
            
            $lockId = $this->db->lastInsertId();
            
            // Log the lock
            error_log("[DEVICE_LOCK] NEW LOCK: Device locked to table: Restaurant $restaurantId, Table $tableId, Duration: {$lockDurationMinutes}min");
            
            return [
                'success' => true,
                'message' => 'Device locked to table successfully',
                'lock_id' => $lockId
            ];
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Lock creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to lock device to table'
            ];
        }
    }
    
    /**
     * Release device lock (when order is completed/paid)
     */
    public function releaseLock($deviceFingerprint, $restaurantId, $tableId = null) {
        try {
            $sql = "
                UPDATE device_table_locks 
                SET is_active = 0
                WHERE device_fingerprint = ?
                    AND restaurant_id = ?
                    AND is_active = 1
            ";
            
            $params = [$deviceFingerprint, $restaurantId];
            
            if ($tableId !== null) {
                $sql .= " AND table_id = ?";
                $params[] = $tableId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            error_log("[DEVICE_LOCK] Lock released: Restaurant $restaurantId" . ($tableId ? ", Table $tableId" : ""));
            
            return [
                'success' => true,
                'message' => 'Lock released successfully'
            ];
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Release lock error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to release lock'
            ];
        }
    }
    
    /**
     * Link a lock to an order
     */
    public function linkLockToOrder($lockId, $orderId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE device_table_locks 
                SET order_id = ?,
                    last_activity = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$orderId, $lockId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Link order error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last activity timestamp and extend expiration
     * @param int $lockId The lock ID to update
     * @param int $extendMinutes Additional minutes to extend (default: 60)
     */
    private function updateLastActivity($lockId, $extendMinutes = 60) {
        try {
            // Update last activity and extend expiration by additional time
            $stmt = $this->db->prepare("
                UPDATE device_table_locks 
                SET last_activity = NOW(),
                    expires_at = GREATEST(expires_at, DATE_ADD(NOW(), INTERVAL ? MINUTE))
                WHERE id = ?
            ");
            
            $stmt->execute([$extendMinutes, $lockId]);
            
            error_log("[DEVICE_LOCK] Activity updated and extended by {$extendMinutes} minutes for lock ID: $lockId");
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Update activity error: ' . $e->getMessage());
        }
    }
    
    /**
     * Extend lock expiration (called on customer activity)
     * @param string $deviceFingerprint
     * @param int $restaurantId
     * @param int $extendMinutes How many minutes to extend from now
     */
    public function extendLock($deviceFingerprint, $restaurantId, $extendMinutes = 120) {
        try {
            $stmt = $this->db->prepare("
                UPDATE device_table_locks 
                SET expires_at = GREATEST(expires_at, DATE_ADD(NOW(), INTERVAL ? MINUTE)),
                    last_activity = NOW()
                WHERE device_fingerprint = ?
                    AND restaurant_id = ?
                    AND is_active = 1
            ");
            
            $stmt->execute([$extendMinutes, $deviceFingerprint, $restaurantId]);
            
            error_log("[DEVICE_LOCK] Lock extended by {$extendMinutes} minutes");
            
            return [
                'success' => true,
                'message' => 'Lock extended successfully'
            ];
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Extend lock error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to extend lock'
            ];
        }
    }
    
    /**
     * Clean up expired locks
     */
    public function cleanupExpiredLocks() {
        try {
            $stmt = $this->db->prepare("
                UPDATE device_table_locks 
                SET is_active = 0
                WHERE is_active = 1
                    AND expires_at < NOW()
            ");
            
            $stmt->execute();
            
            $count = $stmt->rowCount();
            if ($count > 0) {
                error_log("[DEVICE_LOCK] Cleaned up $count expired locks");
            }
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Cleanup error: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate if device can access a specific table
     */
    public function validateTableAccess($deviceFingerprint, $restaurantId, $requestedTableId) {
        // First check if this device is locked to another table
        $deviceLock = $this->getDeviceLock($deviceFingerprint, $restaurantId);
        
        if ($deviceLock && $deviceLock['table_id'] != $requestedTableId) {
            // Device is locked to a different table
            return [
                'allowed' => false,
                'message' => 'Your device is locked to Table ' . $deviceLock['table_number'] . '. Please complete or cancel your order there first.',
                'locked_table' => $deviceLock['table_number'],
                'locked_table_id' => $deviceLock['table_id']
            ];
        }
        
        // If device already locked to same table, allow access
        if ($deviceLock && $deviceLock['table_id'] == $requestedTableId) {
            return [
                'allowed' => true,
                'message' => 'Access to locked table',
                'lock' => $deviceLock
            ];
        }
        
        // Check if requested table is locked by another device
        $tableLock = $this->isTableLockedByAnotherDevice($requestedTableId, $deviceFingerprint);
        
        if ($tableLock) {
            return [
                'allowed' => false,
                'message' => 'This table is currently being used by another device. Please wait or contact staff.',
                'table_locked' => true
            ];
        }
        
        // No conflicts, access allowed
        return [
            'allowed' => true,
            'message' => 'No active lock'
        ];
    }
    
    /**
     * Force release all locks for a specific table (admin action)
     */
    public function forceReleaseTableLocks($tableId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE device_table_locks 
                SET is_active = 0
                WHERE table_id = ?
                    AND is_active = 1
            ");
            
            $stmt->execute([$tableId]);
            
            $count = $stmt->rowCount();
            error_log("[DEVICE_LOCK] Force released $count locks for table $tableId");
            
            return [
                'success' => true,
                'message' => "$count device lock(s) released"
            ];
            
        } catch (PDOException $e) {
            error_log('[DEVICE_LOCK] Force release error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to release locks'
            ];
        }
    }
}
