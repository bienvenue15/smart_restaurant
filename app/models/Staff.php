<?php
/**
 * Staff Model
 * Handles staff authentication and management
 * 
 * @copyright 2025 Inovasiyo Ltd
 */

require_once 'src/model.php';

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
                $this->updateLastLogin($user['id']);
                
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
            $query = "UPDATE staff_users SET last_login = NOW() WHERE id = ?";
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
                'staff_id' => $staffId,
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
            $query = "SELECT id, username, full_name, email, phone, role, is_active, last_login, created_at 
                      FROM staff_users WHERE id = ?";
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
            $query = "SELECT id, username, full_name, email, phone, role, is_active, last_login 
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
     * Reset table status
     */
    public function resetTable($tableId, $staffId, $notes = '') {
        try {
            $this->db->beginTransaction();
            
            // Get current table status
            $query = "SELECT status FROM restaurant_tables WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            $currentStatus = $stmt->fetchColumn();
            
            // Update table status to available
            $query = "UPDATE restaurant_tables SET status = 'available' WHERE id = ?";
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
    public function assignWaiterCall($callId, $staffId) {
        try {
            $query = "UPDATE waiter_calls 
                      SET assigned_to = ?, assigned_at = NOW(), status = 'acknowledged' 
                      WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId, $callId]);
            
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
                      INNER JOIN restaurant_tables t ON wc.table_id = t.id
                      WHERE wc.assigned_to = ? 
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
            $query = "UPDATE orders SET status = ?";
            
            // Track who confirmed or served
            if ($status === 'confirmed') {
                $query .= ", confirmed_by = ?, confirmed_at = NOW()";
            } elseif ($status === 'served') {
                $query .= ", served_by = ?, served_at = NOW()";
            }
            
            $query .= " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            
            if ($status === 'confirmed' || $status === 'served') {
                $stmt->execute([$status, $staffId, $orderId]);
            } else {
                $stmt->execute([$status, $orderId]);
            }
            
            return ['status' => 'OK', 'message' => 'Order status updated'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to update order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total tables
            $query = "SELECT COUNT(*) as total, 
                      SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                      SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                      SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved
                      FROM restaurant_tables";
            $stmt = $this->db->query($query);
            $stats['tables'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending orders
            $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
            $stmt = $this->db->query($query);
            $stats['pending_orders'] = $stmt->fetchColumn();
            
            // Preparing orders
            $query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('confirmed', 'preparing')";
            $stmt = $this->db->query($query);
            $stats['preparing_orders'] = $stmt->fetchColumn();
            
            // Pending waiter calls
            $query = "SELECT COUNT(*) as count FROM waiter_calls WHERE status = 'pending'";
            $stmt = $this->db->query($query);
            $stats['pending_calls'] = $stmt->fetchColumn();
            
            // Today's revenue
            $query = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
                      FROM orders 
                      WHERE status = 'completed' 
                      AND DATE(created_at) = CURDATE()";
            $stmt = $this->db->query($query);
            $stats['today_revenue'] = $stmt->fetchColumn();
            
            // Today's orders count
            $query = "SELECT COUNT(*) as count 
                      FROM orders 
                      WHERE DATE(created_at) = CURDATE()";
            $stmt = $this->db->query($query);
            $stats['today_orders'] = $stmt->fetchColumn();
            
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
            $query = "SELECT role FROM staff_users WHERE id = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $role = $stmt->fetchColumn();
            
            if (!$role) {
                return false;
            }
            
            // Check if role has permission
            $query = "SELECT COUNT(*) FROM role_permissions rp
                      INNER JOIN permissions p ON rp.permission_id = p.id
                      WHERE rp.role = ? AND p.code = ?";
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
            $query = "SELECT role FROM staff_users WHERE id = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $role = $stmt->fetchColumn();
            
            if (!$role) {
                return ['status' => 'FAIL', 'message' => 'User not found'];
            }
            
            $query = "SELECT p.* FROM permissions p
                      INNER JOIN role_permissions rp ON p.id = rp.permission_id
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
            $query = "SELECT id FROM staff_shifts 
                      WHERE staff_id = ? 
                      AND shift_date = CURDATE() 
                      AND clock_in IS NOT NULL 
                      AND clock_out IS NULL 
                      AND status = 'ongoing'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            return $stmt->fetchColumn() !== false;
            
        } catch (PDOException $e) {
            return false;
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
            
            // Check if shift is scheduled
            $query = "SELECT id FROM staff_shifts 
                      WHERE staff_id = ? 
                      AND shift_date = CURDATE() 
                      AND clock_in IS NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            $shiftId = $stmt->fetchColumn();
            
            if ($shiftId) {
                // Update existing scheduled shift
                $query = "UPDATE staff_shifts 
                          SET clock_in = NOW(), status = 'ongoing' 
                          WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$shiftId]);
            } else {
                // Create new shift (unscheduled)
                $data = [
                    'staff_id' => $staffId,
                    'shift_date' => date('Y-m-d'),
                    'expected_start' => date('H:i:s'),
                    'expected_end' => date('H:i:s', strtotime('+8 hours')),
                    'status' => 'ongoing'
                ];
                $this->save('staff_shifts', $data);
                
                $query = "UPDATE staff_shifts 
                          SET clock_in = NOW() 
                          WHERE staff_id = ? AND shift_date = CURDATE() AND clock_in IS NULL";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$staffId]);
            }
            
            // Log activity
            $this->logActivity($staffId, 'clock_in', 'Staff clocked in for shift');
            
            return ['status' => 'OK', 'message' => 'Clocked in successfully'];
            
        } catch (PDOException $e) {
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
                      WHERE staff_id = ? 
                      AND shift_date = CURDATE() 
                      AND clock_out IS NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$staffId]);
            
            // Log activity
            $this->logActivity($staffId, 'clock_out', 'Staff clocked out from shift');
            
            return ['status' => 'OK', 'message' => 'Clocked out successfully'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to clock out', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Log sensitive action to audit trail
     */
    public function logAudit($staffId, $actionType, $tableName, $recordId, $oldValue, $newValue, $reason = '', $requiresApproval = false) {
        try {
            $data = [
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
                      WHERE id = ? AND status = 'pending'";
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
            $query = "SELECT a.*, s.full_name as requested_by_name, s.role as requester_role
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
}
?>
