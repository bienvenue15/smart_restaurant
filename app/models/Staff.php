<?php
/**
 * Staff Model
 * Handles staff authentication and management
 * 
 * @copyright 2026 Inovasiyo Ltd
 */

$baseDir = dirname(dirname(__DIR__));
require_once $baseDir . '/src/model.php';

class Staff extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Authenticate staff user
     */
    public function authenticate($username, $password) {
        try {
            $query = "SELECT * FROM staff_users WHERE username = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['uuid']);
                
                // Remove password from return data
                unset($user['password_hash']);
                
                return ['status' => 'OK', 'data' => $user];
            } else {
                return ['status' => 'FAIL', 'message' => 'Invalid username or password'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Authentication failed', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        try {
            $query = "UPDATE staff_users SET last_login = NOW() WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Log error but don't fail authentication
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
    
    /**
     * Log staff activity
     */
    public function logActivity($staffId, $action, $description = '', $ipAddress = '', $userAgent = '') {
        try {
            $data = [
                'staff_uuid' => $staffId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ];
            
            return $this->save('staff_activity_log', $data);
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to log activity', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get staff by ID
     */
    public function getStaffById($staffId) {
        try {
            $query = "SELECT uuid, username, full_name, email, phone, role, is_active, last_login, created_at 
                      FROM staff_users WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff) {
                return ['status' => 'OK', 'data' => $staff];
            } else {
                return ['status' => 'FAIL', 'message' => 'Staff not found'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch staff', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all staff by role
     */
    public function getStaffByRole($role) {
        try {
            $query = "SELECT uuid, username, full_name, email, phone, role, is_active, last_login 
                      FROM staff_users WHERE role = ? ORDER BY full_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$role]);
            
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $staff];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch staff', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all active waiters
     */
    public function getActiveWaiters() {
        return $this->getStaffByRole('waiter');
    }
    
    /**
     * Get all staff by restaurant with shift status
     */
    public function getStaffByRestaurant($restaurantId) {
        try {
            $query = "SELECT su.uuid, su.full_name as name, su.role, 
                      CASE WHEN su.is_active = 1 THEN 'active' ELSE 'inactive' END as status,
                      su.email, su.phone,
                      ss.uuid as shift_uuid,
                      ss.clock_in as clock_in_time,
                      CASE 
                        WHEN ss.clock_in IS NOT NULL AND ss.clock_out IS NULL THEN 1
                        ELSE 0
                      END as is_on_shift
                      FROM staff_users su
                      LEFT JOIN staff_shifts ss ON su.uuid = ss.staff_uuid 
                        AND DATE(ss.clock_in) = CURDATE() 
                        AND ss.clock_out IS NULL
                      WHERE su.restaurant_uuid = ? 
                      ORDER BY is_on_shift DESC, su.role, su.full_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$restaurantId]);
            
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'staff' => $staff];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch staff', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reset table status
     */
    public function resetTable($tableId, $staffId, $notes = '') {
        try {
            $this->db->beginTransaction();
            
            // Get current table status
            $query = "SELECT status FROM restaurant_tables WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            $currentStatus = $stmt->fetchColumn();
            
            // Update table status to available
            $query = "UPDATE restaurant_tables SET status = 'available' WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            
            // Log the reset
            $resetData = [
                'table_id' => $tableId,
                'staff_id' => $staffId,
                'previous_status' => $currentStatus,
                'new_status' => 'available',
                'notes' => $notes
            ];
            
            $this->save('table_resets', $resetData);
            
            $this->db->commit();
            
            return ['status' => 'OK', 'message' => 'Table reset successfully'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['status' => 'FAIL', 'message' => 'Failed to reset table', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get table reset history
     */
    public function getTableResetHistory($limit = 50) {
        try {
            $query = "SELECT tr.*, t.table_number, s.full_name as staff_name
                      FROM table_resets tr
                      INNER JOIN restaurant_tables t ON tr.table_id = t.id
                      INNER JOIN staff_users s ON tr.staff_id = s.id
                      ORDER BY tr.created_at DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $history];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch reset history', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Assign waiter call to staff
     */
    public function assignWaiterCall($callId, $staffId, $restaurantId) {
        try {
            // First check if the call exists and belongs to this restaurant
            $checkQuery = "SELECT uuid FROM waiter_calls WHERE uuid = ? AND restaurant_uuid = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$callId, $restaurantId]);
            
            if ($checkStmt->rowCount() === 0) {
                return ['status' => 'FAIL', 'message' => 'Call not found or access denied'];
            }
            
            $query = "UPDATE waiter_calls 
                      SET assigned_to_uuid = ?, assigned_at = NOW(), status = 'acknowledged' 
                      WHERE uuid = ? AND restaurant_uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $callId, $restaurantId]);
            
            if ($stmt->rowCount() === 0) {
                return ['status' => 'FAIL', 'message' => 'Failed to assign call'];
            }
            
            return ['status' => 'OK', 'message' => 'Call assigned successfully'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to assign call', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get waiter calls for specific staff
     */
    public function getMyWaiterCalls($staffId) {
        try {
            $query = "SELECT wc.*, t.table_number, t.seats
                      FROM waiter_calls wc
                      INNER JOIN restaurant_tables t ON wc.table_uuid = t.uuid
                      WHERE wc.assigned_to_uuid = ? 
                      AND wc.status IN ('acknowledged', 'pending')
                      ORDER BY wc.priority DESC, wc.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $calls];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch calls', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status by kitchen/waiter
     */
    public function updateOrderStatus($orderId, $status, $staffId) {
        try {
            // Get order details first for notification
            $orderQuery = "SELECT o.*, t.table_number, s.id as waiter_id, s.full_name as waiter_name 
                          FROM orders o 
                          LEFT JOIN restaurant_tables t ON o.table_id = t.id 
                          LEFT JOIN staff_users s ON o.served_by = s.id 
                          WHERE o.id = ?";
            $orderStmt = $this->db->prepare($orderQuery);
            $orderStmt->execute([$orderId]);
            $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            $query = "UPDATE orders SET status = ?";
            
            // Track who confirmed or served
            if ($status === 'confirmed') {
                $query .= ", confirmed_by = ?, confirmed_at = NOW()";
            } elseif ($status === 'served') {
                $query .= ", served_by = ?, served_at = NOW()";
            }
            
            $query .= " WHERE uuid = ?";
            
            $stmt = $this->db->prepare($query);
            
            if ($status === 'confirmed' || $status === 'served') {
                $stmt->execute([$status, $staffId, $orderId]);
            } else {
                $stmt->execute([$status, $orderId]);
            }
            
            // Create waiter liability when order is served OR completed (but not paid)
            if (($status === 'served' || $status === 'completed') && $orderData['payment_status'] !== 'paid') {
                try {
                    require_once __DIR__ . '/WaiterLiability.php';
                    $liabilityModel = new WaiterLiability($this->db);
                    
                    // Check if liability already exists for this order
                    $checkQuery = "SELECT id FROM waiter_liabilities WHERE order_uuid = ? AND status = 'active'";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->execute([$orderId]);
                    $existingLiability = $checkStmt->fetch();
                    
                    if (!$existingLiability) {
                        // No existing liability - create one
                        
                        // Determine who is responsible for this order
                        $responsibleStaffId = null;
                        
                        // PRIORITY 1: If order was served by someone, liability goes to them (served_by field)
                        if ($orderData['served_by']) {
                            $responsibleStaffId = $orderData['served_by'];
                            error_log('[AUTO LIABILITY] Order already served by staff ID: ' . $responsibleStaffId . ' - assigning liability');
                        }
                        // PRIORITY 2: If current action is "served" - assign to person doing the serving
                        elseif ($status === 'served' && $staffId) {
                            $responsibleStaffId = $staffId;
                            
                            // Get staff role to log appropriately
                            $staffQuery = "SELECT role, full_name FROM staff_users WHERE uuid = ?";
                            $staffStmt = $this->db->prepare($staffQuery);
                            $staffStmt->execute([$staffId]);
                            $staffInfo = $staffStmt->fetch(PDO::FETCH_ASSOC);
                            
                            error_log('[AUTO LIABILITY] Order being served by ' . ($staffInfo['role'] ?? 'unknown') . ' (ID: ' . $staffId . ') - liability assigned to them');
                        }
                        // PRIORITY 3: If order has assigned waiter, use that
                        elseif (!empty($orderData['waiter_uuid']) && $orderData['waiter_uuid'] !== null) {
                            $responsibleStaffId = $orderData['waiter_uuid'];
                            error_log('[AUTO LIABILITY] Order has assigned waiter - liability goes to waiter ID: ' . $responsibleStaffId);
                        }
                        // PRIORITY 4: Only if NO waiter involved, assign to person marking as completed (if admin/manager)
                        else {
                            // Get the role of person updating status
                            $staffQuery = "SELECT role FROM staff_users WHERE uuid = ?";
                            $staffStmt = $this->db->prepare($staffQuery);
                            $staffStmt->execute([$staffId]);
                            $staffInfo = $staffStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Only assign to manager/admin if they are the ones completing (no waiter involved)
                            if ($staffInfo && in_array($staffInfo['role'], ['admin', 'manager'])) {
                                $responsibleStaffId = $staffId;
                                error_log('[AUTO LIABILITY] Admin/Manager completing order themselves - liability assigned to them ID: ' . $responsibleStaffId);
                            } else {
                                error_log('[AUTO LIABILITY] No clear responsible staff - skipping liability creation');
                            }
                        }
                        
                        // Only create liability if we have a responsible staff member AND payment is not done
                        if ($responsibleStaffId) {
                            $result = $liabilityModel->createLiability(
                                $orderId,
                                $responsibleStaffId,
                                $orderData['total_amount'],
                                $orderData['restaurant_id']
                            );
                            
                            if ($result['status'] === 'OK') {
                                error_log('[AUTO LIABILITY] ✓ Liability created for order #' . $orderId . ' - Amount: RWF ' . number_format($orderData['total_amount'], 2) . ' - Staff ID: ' . $responsibleStaffId);
                            } else {
                                error_log('[AUTO LIABILITY] ✗ Failed to create liability: ' . $result['message']);
                            }
                        }
                    } else {
                        error_log('[AUTO LIABILITY] Liability already exists for order #' . $orderId . ' - skipping creation');
                    }
                } catch (Exception $e) {
                    error_log('[AUTO LIABILITY] Failed to create liability: ' . $e->getMessage());
                    // Don't fail the order status update if liability creation fails
                }
            }
            
            // Send notification to waiter or manager when order is ready
            if ($status === 'ready') {
                $notifyStaffId = null;
                
                // Priority 1: Notify assigned waiter
                if (!empty($orderData['waiter_id'])) {
                    $notifyStaffId = $orderData['waiter_id'];
                } else {
                    // Priority 2: If no waiter assigned, notify on-shift manager
                    $managerQuery = "SELECT DISTINCT su.id 
                                   FROM staff_users su
                                   INNER JOIN staff_shifts ss ON su.id = ss.staff_id
                                   WHERE su.restaurant_id = ? 
                                   AND su.role = 'manager' 
                                   AND su.is_active = 1 
                                   AND ss.clock_out IS NULL
                                   AND DATE(ss.clock_in) = CURDATE()
                                   ORDER BY ss.clock_in DESC
                                   LIMIT 1";
                    $managerStmt = $this->db->prepare($managerQuery);
                    $managerStmt->execute([$orderData['restaurant_id']]);
                    $manager = $managerStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($manager) {
                        $notifyStaffId = $manager['id'];
                    } else {
                        // Priority 3: Notify any on-shift admin
                        $adminQuery = "SELECT DISTINCT su.id 
                                     FROM staff_users su
                                     INNER JOIN staff_shifts ss ON su.id = ss.staff_id
                                     WHERE su.restaurant_id = ? 
                                     AND su.role = 'admin' 
                                     AND su.is_active = 1 
                                     AND ss.clock_out IS NULL
                                     AND DATE(ss.clock_in) = CURDATE()
                                     ORDER BY ss.clock_in DESC
                                     LIMIT 1";
                        $adminStmt = $this->db->prepare($adminQuery);
                        $adminStmt->execute([$orderData['restaurant_id']]);
                        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($admin) {
                            $notifyStaffId = $admin['id'];
                        }
                    }
                }
                
                // Create notification with sound alert
                if ($notifyStaffId) {
                    $notifyQuery = "INSERT INTO staff_notifications 
                                   (staff_id, order_id, notification_type, title, message, requires_sound) 
                                   VALUES (?, ?, 'order_ready', ?, ?, 1)";
                    $notifyStmt = $this->db->prepare($notifyQuery);
                    $title = "🔔 Order Ready - Table " . $orderData['table_number'];
                    $message = "Order #" . $orderId . " for Table " . $orderData['table_number'] . " is ready to serve!";
                    $notifyStmt->execute([$notifyStaffId, $orderId, $title, $message]);
                    
                    error_log("[ORDER READY NOTIFICATION] Sent to staff ID {$notifyStaffId} for Order #{$orderId} Table {$orderData['table_number']}");
                }
            }
            
            return ['status' => 'OK', 'message' => 'Order status updated'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to update order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($restaurantId = null) {
        try {
            $stats = [];
            
            // CRITICAL: All queries MUST filter by restaurant_id for data privacy
            
            // Total tables (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COUNT(*) as total, 
                          SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                          SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                          SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved
                          FROM restaurant_tables WHERE restaurant_uuid = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                // Super admin: all restaurants
                $query = "SELECT COUNT(*) as total, 
                          SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                          SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                          SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved
                          FROM restaurant_tables";
                $stmt = $this->db->query($query);
            }
            $stats['tables'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending orders (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COUNT(*) as count FROM orders o
                          INNER JOIN restaurant_tables t ON o.table_id = t.id
                          WHERE o.status = 'pending' AND t.restaurant_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
                $stmt = $this->db->query($query);
            }
            $stats['pending_orders'] = $stmt->fetchColumn();
            
            // Preparing orders (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COUNT(*) as count FROM orders o
                          INNER JOIN restaurant_tables t ON o.table_id = t.id
                          WHERE o.status IN ('confirmed', 'preparing') AND t.restaurant_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('confirmed', 'preparing')";
                $stmt = $this->db->query($query);
            }
            $stats['preparing_orders'] = $stmt->fetchColumn();
            
            // Pending waiter calls (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COUNT(*) as count FROM waiter_calls WHERE status = 'pending' AND restaurant_uuid = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COUNT(*) as count FROM waiter_calls WHERE status = 'pending'";
                $stmt = $this->db->query($query);
            }
            $stats['pending_calls'] = $stmt->fetchColumn();
            
            // Today's revenue (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COALESCE(SUM(o.total_amount), 0) as revenue 
                          FROM orders o
                          INNER JOIN restaurant_tables t ON o.table_id = t.id
                          WHERE o.status = 'completed' 
                          AND DATE(o.created_at) = CURDATE()
                          AND t.restaurant_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
                          FROM orders 
                          WHERE status = 'completed' 
                          AND DATE(created_at) = CURDATE()";
                $stmt = $this->db->query($query);
            }
            $stats['today_revenue'] = $stmt->fetchColumn();
            
            // Today's orders count (restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COUNT(*) as count 
                          FROM orders o
                          INNER JOIN restaurant_tables t ON o.table_id = t.id
                          WHERE DATE(o.created_at) = CURDATE() AND t.restaurant_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COUNT(*) as count 
                          FROM orders 
                          WHERE DATE(created_at) = CURDATE()";
                $stmt = $this->db->query($query);
            }
            $stats['today_orders'] = $stmt->fetchColumn();
            
            // Cash in hand (for cashiers - restaurant-specific)
            if ($restaurantId) {
                $query = "SELECT COALESCE(SUM(cash_in_hand), 0) as total
                          FROM cash_sessions 
                          WHERE restaurant_uuid = ? AND status = 'open'";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$restaurantId]);
            } else {
                $query = "SELECT COALESCE(SUM(cash_in_hand), 0) as total
                          FROM cash_sessions WHERE status = 'open'";
                $stmt = $this->db->query($query);
            }
            $stats['cash_in_hand'] = $stmt->fetchColumn();
            
            return ['status' => 'OK', 'data' => $stats];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch stats', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if staff has specific permission
     */
    public function hasPermission($staffId, $permissionCode) {
        try {
            // Get staff role
            $query = "SELECT role FROM staff_users WHERE uuid = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $role = $stmt->fetchColumn();
            
            if (!$role) {
                return false;
            }
            
            // Check if role has permission (using permission_code directly)
            $query = "SELECT COUNT(*) FROM role_permissions 
                      WHERE role = ? AND permission_code = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$role, $permissionCode]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all permissions for a staff user
     */
    public function getStaffPermissions($staffId) {
        try {
            $query = "SELECT role FROM staff_users WHERE uuid = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $role = $stmt->fetchColumn();
            
            if (!$role) {
                return ['status' => 'FAIL', 'message' => 'User not found'];
            }
            
            $query = "SELECT p.* FROM permissions p
                      INNER JOIN role_permissions rp ON p.code = rp.permission_code
                      WHERE rp.role = ?
                      ORDER BY p.category, p.name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$role]);
            
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $permissions];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch permissions', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if staff is currently on shift
     */
    public function isOnShift($staffId) {
        try {
            $query = "SELECT uuid FROM staff_shifts 
                      WHERE staff_uuid = ? 
                      AND DATE(clock_in) = CURDATE() 
                      AND clock_out IS NULL 
                      AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            return $stmt->fetchColumn() !== false;
            
        } catch (PDOException $e) {
            error_log("isOnShift check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current active shift details
     */
    public function getCurrentShift($staffId) {
        try {
            $query = "SELECT * FROM staff_shifts 
                      WHERE staff_uuid = ? 
                      AND DATE(clock_in) = CURDATE() 
                      AND clock_out IS NULL 
                      AND status = 'active'
                      ORDER BY clock_in DESC
                      LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shift) {
                return ['status' => 'OK', 'data' => $shift];
            }
            
            return ['status' => 'FAIL', 'message' => 'No active shift found'];
            
        } catch (PDOException $e) {
            error_log("getCurrentShift failed: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to get shift', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Clock in for shift
     */
    public function clockIn($staffId) {
        try {
            // Check if already clocked in
            if ($this->isOnShift($staffId)) {
                return ['status' => 'FAIL', 'message' => 'Already clocked in'];
            }
            
            // Get restaurant_uuid from staff
            $query = "SELECT restaurant_uuid FROM staff_users WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $restaurantUuid = $stmt->fetchColumn();
            
            if (!$restaurantUuid) {
                return ['status' => 'FAIL', 'message' => 'Staff not found'];
            }
            
            // Create new shift using UUIDs
            $query = "INSERT INTO staff_shifts (staff_uuid, restaurant_uuid, clock_in, status) 
                      VALUES (?, ?, NOW(), 'active')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $restaurantUuid]);
            
            // Log activity
            $this->logActivity($staffId, 'clock_in', 'Staff clocked in for shift');
            
            return ['status' => 'OK', 'message' => 'Clocked in successfully'];
            
        } catch (PDOException $e) {
            error_log("Clock in failed: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to clock in', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Clock out from shift
     */
    public function clockOut($staffId) {
        try {
            if (!$this->isOnShift($staffId)) {
                return ['status' => 'FAIL', 'message' => 'Not clocked in'];
            }
            
            $query = "UPDATE staff_shifts 
                      SET clock_out = NOW(), status = 'completed' 
                      WHERE staff_uuid = ? 
                      AND DATE(clock_in) = CURDATE() 
                      AND clock_out IS NULL 
                      AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            // Log activity
            $this->logActivity($staffId, 'clock_out', 'Staff clocked out from shift');
            
            return ['status' => 'OK', 'message' => 'Clocked out successfully'];
            
        } catch (PDOException $e) {
            error_log("Clock out failed: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to clock out', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Log sensitive action to audit trail
     */
    public function logAudit($staffId, $actionType, $tableName, $recordId, $oldValue, $newValue, $reason = '', $requiresApproval = false) {
        try {
            // Get restaurant_id from staff session or database
            $restaurantId = $_SESSION['staff_user']['restaurant_id'] ?? null;
            
            // If not in session, fetch from database
            if (!$restaurantId && $staffId) {
                $query = "SELECT restaurant_id FROM staff_users WHERE uuid = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$staffId]);
                $restaurantId = $stmt->fetchColumn();
            }
            
            $data = [
                'restaurant_id' => $restaurantId,
                'staff_id' => $staffId,
                'action_type' => $actionType,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
                'reason' => $reason,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'requires_approval' => $requiresApproval ? 1 : 0,
                'status' => $requiresApproval ? 'pending' : 'approved'
            ];
            
            return $this->save('audit_trail', $data);
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to log audit', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if action requires approval
     */
    public function requiresApproval($permissionCode) {
        try {
            $query = "SELECT requires_approval FROM permissions WHERE code = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$permissionCode]);
            
            return (bool)$stmt->fetchColumn();
            
        } catch (PDOException $e) {
            return true; // Fail-safe: require approval if uncertain
        }
    }
    
    /**
     * Request approval for high-risk action
     */
    public function requestApproval($staffId, $actionType, $tableName, $recordId, $reason) {
        return $this->logAudit($staffId, $actionType, $tableName, $recordId, null, null, $reason, true);
    }
    
    /**
     * Approve pending action
     */
    public function approveAction($auditId, $approverId) {
        try {
            // Check if approver has permission
            if (!$this->hasPermission($approverId, 'approve_actions')) {
                return ['status' => 'FAIL', 'message' => 'You do not have permission to approve actions'];
            }
            
            $query = "UPDATE audit_trail 
                      SET status = 'approved', approved_by = ?, approved_at = NOW() 
                      WHERE uuid = ? AND status = 'pending'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$approverId, $auditId]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => 'OK', 'message' => 'Action approved'];
            } else {
                return ['status' => 'FAIL', 'message' => 'Action not found or already processed'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to approve action', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get pending approval requests
     */
    public function getPendingApprovals() {
        try {
            $query = "SELECT a.id, a.staff_id, a.action_type, a.table_name, a.record_id, 
                             a.old_value, a.new_value, a.reason, a.ip_address, a.created_at,
                             a.status, a.requires_approval, a.approved_by, a.approval_notes,
                             s.full_name as requested_by_name, s.role as requester_role
                      FROM audit_trail a
                      INNER JOIN staff_users s ON a.staff_id = s.id
                      WHERE a.status = 'pending' AND a.requires_approval = 1
                      ORDER BY a.created_at DESC";
            $stmt = $this->db->query($query);
            
            $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $approvals];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch approvals', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get current cash session for staff
     */
    public function getCurrentCashSession($staffId) {
        try {
            $query = "SELECT * FROM cash_sessions 
                      WHERE staff_uuid = ? AND status = 'open' 
                      ORDER BY opened_at DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get current cash session failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent cash sessions
     */
    public function getRecentCashSessions($staffId, $limit = 10) {
        try {
            $query = "SELECT * FROM cash_sessions 
                      WHERE staff_uuid = ? 
                      ORDER BY opened_at DESC LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get recent cash sessions failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Open cash session
     */
    public function openCashSession($staffId, $restaurantId, $openingBalance, $notes = null) {
        try {
            // Check if already has open session
            if ($this->getCurrentCashSession($staffId)) {
                return ['status' => 'FAIL', 'message' => 'You already have an open cash session'];
            }
            
            $query = "INSERT INTO cash_sessions (staff_id, restaurant_id, opening_balance, opened_at, status, notes) 
                      VALUES (?, ?, ?, NOW(), 'open', ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $restaurantId, $openingBalance, $notes]);
            
            // Log activity
            $this->logActivity($staffId, 'cash_session_opened', "Opened cash session with balance: RWF " . number_format($openingBalance, 0));
            
            return ['status' => 'OK', 'message' => 'Cash session opened successfully', 'session_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            error_log("Open cash session failed: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to open cash session', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Close cash session
     */
    public function closeCashSession($staffId, $closingBalance, $notes = null) {
        try {
            $session = $this->getCurrentCashSession($staffId);
            
            if (!$session) {
                return ['status' => 'FAIL', 'message' => 'No open cash session found'];
            }
            
            // Calculate expected balance (opening + payments received)
            $query = "SELECT COALESCE(SUM(amount), 0) as total_cash 
                      FROM payments 
                      WHERE received_by = ? 
                      AND payment_method = 'cash' 
                      AND payment_date >= ? 
                      AND status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $session['opened_at']]);
            $totalCash = $stmt->fetchColumn();
            
            $expectedBalance = $session['opening_balance'] + $totalCash;
            $variance = $closingBalance - $expectedBalance;
            
            // Determine status
            $status = 'closed';
            if (abs($variance) > 1000) { // More than 1000 RWF variance
                $status = 'discrepancy';
            }
            
            // Update session
            $query = "UPDATE cash_sessions 
                      SET closing_balance = ?, 
                          expected_balance = ?, 
                          variance = ?, 
                          closed_at = NOW(), 
                          closed_by = ?, 
                          status = ?, 
                          notes = CONCAT(COALESCE(notes, ''), ?, ?) 
                      WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $closingBalance,
                $expectedBalance,
                $variance,
                $staffId,
                $status,
                $notes ? "\nClosing notes: " : '',
                $notes ?? '',
                $session['id']
            ]);
            
            // Log activity
            $this->logActivity($staffId, 'cash_session_closed', "Closed cash session. Variance: RWF " . number_format($variance, 0));
            
            return [
                'status' => 'OK', 
                'message' => 'Cash session closed successfully',
                'variance' => $variance,
                'expected' => $expectedBalance,
                'actual' => $closingBalance,
                'session_status' => $status
            ];
            
        } catch (PDOException $e) {
            error_log("Close cash session failed: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to close cash session', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get unread notifications for staff member
     */
    public function getUnreadNotifications($staffId) {
        try {
            // Query from notifications table (used by kitchen availability system)
            $query = "SELECT * FROM notifications 
                      WHERE user_id = ? AND is_read = 0 
                      ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $notifications, 'count' => count($notifications)];
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch notifications'];
        }
    }
    
    /**
     * Get all notifications (including read ones)
     */
    public function getAllNotifications($staffId) {
        try {
            // Query from notifications table (used by kitchen availability system)
            $query = "SELECT * FROM notifications 
                      WHERE user_id = ? 
                      ORDER BY is_read ASC, created_at DESC 
                      LIMIT 50";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $notifications, 'count' => count($notifications)];
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch notifications'];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationId, $staffId) {
        try {
            // Update in notifications table (used by kitchen availability system)
            $query = "UPDATE notifications 
                      SET is_read = 1, read_at = NOW() 
                      WHERE uuid = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$notificationId, $staffId]);
            
            return ['status' => 'OK', 'message' => 'Notification marked as read'];
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to mark notification'];
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead($staffId) {
        try {
            // Update in notifications table (used by kitchen availability system)
            $query = "UPDATE notifications 
                      SET is_read = 1, read_at = NOW() 
                      WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            return ['status' => 'OK', 'message' => 'All notifications marked as read'];
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to mark notifications'];
        }
    }
    
    /**
     * Check for delayed orders and send escalation notifications
     * - 5 min delay: notify waiter
     * - 10 min delay: escalate to admin and manager
     */
    public function checkDelayedOrders($restaurantId) {
        try {
            error_log("[DELAY CHECK] Starting check for restaurant ID: $restaurantId");
            
            // Get orders in 'preparing' status with their estimated completion time
            // Calculation: created_at + MAX(preparation_time of items) = estimated_ready_time
            // Delay starts counting AFTER estimated_ready_time passes
            $query = "SELECT 
                        o.id,
                        o.order_number,
                        o.table_id,
                        o.created_at,
                        o.created_by_staff,
                        rt.table_number,
                        COALESCE(MAX(mi.preparation_time), 15) as max_prep_time,
                        TIMESTAMPDIFF(MINUTE, 
                            DATE_ADD(o.created_at, INTERVAL COALESCE(MAX(mi.preparation_time), 15) MINUTE), 
                            NOW()
                        ) as delay_minutes_past_estimation
                      FROM orders o
                      INNER JOIN restaurant_tables rt ON o.table_id = rt.uuid
                      LEFT JOIN order_items oi ON o.uuid = oi.order_uuid
                      LEFT JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid
                      WHERE o.restaurant_uuid = ?
                      AND o.status IN ('confirmed', 'preparing')
                      AND o.created_at IS NOT NULL
                      GROUP BY o.id, o.order_number, o.table_id, o.created_at, o.created_by_staff, rt.table_number
                      HAVING delay_minutes_past_estimation > 0
                      ORDER BY o.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $delayedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("[DELAY CHECK] Found " . count($delayedOrders) . " orders past their estimated completion time");
            
            $processed = 0;
            
            foreach ($delayedOrders as $order) {
                $delayMinutes = (int)$order['delay_minutes_past_estimation'];
                $orderId = $order['id'];
                $orderNumber = $order['order_number'];
                $tableNumber = $order['table_number'];
                $assignedWaiter = $order['created_by_staff'];
                
                error_log("[DELAY CHECK] Order #{$orderNumber} is {$delayMinutes} mins past estimation (prep time: {$order['max_prep_time']} mins)");
                
                if ($delayMinutes >= 10) {
                    // ESCALATION: 10+ minutes past estimation - notify admin and manager
                    error_log("[DELAY CHECK] Order #{$orderNumber} severely delayed ({$delayMinutes} mins) - escalating to management");
                    
                    // Check if escalation was sent in last 30 minutes to avoid spam
                    $checkQuery = "SELECT id, created_at FROM notifications 
                                   WHERE restaurant_uuid = ? 
                                   AND type = 'order_delay_escalation'
                                   AND JSON_EXTRACT(data, '$.order_id') = ?
                                   AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                                   LIMIT 1";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->execute([$restaurantId, $orderId]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing) {
                        error_log("[DELAY CHECK] No recent escalation notification found, notifying admin and manager");
                        
                        // Get admin and manager UUIDs
                        $staffQuery = "SELECT uuid, role FROM staff_users 
                                      WHERE restaurant_uuid = ? 
                                      AND role IN ('admin', 'manager')
                                      AND is_active = 1";
                        $staffStmt = $this->db->prepare($staffQuery);
                        $staffStmt->execute([$restaurantId]);
                        $managers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        error_log("[DELAY CHECK] Found " . count($managers) . " admin/manager users");
                        
                        foreach ($managers as $staff) {
                            $result = $this->createNotification(
                                $staff['uuid'],
                                'order_delay_escalation',
                                "🚨 URGENT: Order #{$orderNumber} (Table {$tableNumber}) delayed {$delayMinutes} minutes past estimated time!",
                                ['order_id' => $orderId, 'delay_minutes' => $delayMinutes],
                                $restaurantId
                            );
                            error_log("[DELAY CHECK] Escalation notification for staff UUID {$staff['uuid']}: " . ($result ? 'SUCCESS' : 'FAILED'));
                        }
                        $processed++;
                    } else {
                        error_log("[DELAY CHECK] Escalation already sent recently for order #{$orderNumber}");
                    }
                } elseif ($delayMinutes >= 5) {
                    // SECOND WARNING: 5-9 minutes past estimation - notify waiter again
                    error_log("[DELAY CHECK] Order #{$orderNumber} delayed ({$delayMinutes} mins) - second warning to waiter");
                    
                    // Check if warning was sent in last 15 minutes
                    $checkQuery = "SELECT id, created_at FROM notifications 
                                   WHERE restaurant_uuid = ? 
                                   AND type IN ('order_delay_warning', 'order_delay_warning_2')
                                   AND JSON_EXTRACT(data, '$.order_id') = ?
                                   AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                                   LIMIT 1";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->execute([$restaurantId, $orderId]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing && $assignedWaiter) {
                        $result = $this->createNotification(
                            $assignedWaiter,
                            'order_delay_warning_2',
                            "⚠️ REMINDER: Order #{$orderNumber} (Table {$tableNumber}) is {$delayMinutes} mins late - please check!",
                            ['order_id' => $orderId, 'delay_minutes' => $delayMinutes],
                            $restaurantId
                        );
                        error_log("[DELAY CHECK] Second warning for waiter ID {$assignedWaiter}: " . ($result ? 'SUCCESS' : 'FAILED'));
                        $processed++;
                    } else {
                        error_log("[DELAY CHECK] Warning already sent recently for order #{$orderNumber}");
                    }
                } else {
                    // FIRST WARNING: Just passed estimation time - notify waiter ONCE
                    error_log("[DELAY CHECK] Order #{$orderNumber} just became late ({$delayMinutes} mins) - first warning");
                    
                    // Check if any warning was sent for this order
                    $checkQuery = "SELECT id FROM notifications 
                                   WHERE restaurant_uuid = ? 
                                   AND type IN ('order_delay_warning', 'order_delay_warning_1', 'order_delay_warning_2')
                                   AND JSON_EXTRACT(data, '$.order_id') = ?
                                   LIMIT 1";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->execute([$restaurantId, $orderId]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing && $assignedWaiter) {
                        $result = $this->createNotification(
                            $assignedWaiter,
                            'order_delay_warning',
                            "⏰ Order #{$orderNumber} (Table {$tableNumber}) is taking longer than expected - please check kitchen!",
                            ['order_id' => $orderId, 'delay_minutes' => $delayMinutes],
                            $restaurantId
                        );
                        error_log("[DELAY CHECK] First warning for waiter ID {$assignedWaiter}: " . ($result ? 'SUCCESS' : 'FAILED'));
                        $processed++;
                    } else {
                        error_log("[DELAY CHECK] First warning already sent for order #{$orderNumber}");
                    }
                }
            }
            
            error_log("[DELAY CHECK] Completed check - processed $processed notifications");
            
            return [
                'status' => 'OK',
                'delayed_orders' => count($delayedOrders),
                'notifications_sent' => $processed
            ];
            
        } catch (PDOException $e) {
            error_log("[DELAY CHECK] Database error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error'];
        }
    }
    
    /**
     * Create a notification (helper method)
     */
    private function createNotification($userId, $type, $message, $data = [], $restaurantId = null) {
        try {
            // Extract title from message (first line or first 50 chars)
            $title = strlen($message) > 50 ? substr($message, 0, 50) . '...' : $message;
            
            $query = "INSERT INTO notifications 
                      (user_uuid, type, title, message, data, restaurant_uuid, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $userId,
                $type,
                $title,
                $message,
                json_encode($data),
                $restaurantId
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
}
?>
