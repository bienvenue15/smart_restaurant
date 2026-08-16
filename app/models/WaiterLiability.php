<?php
/**
 * Waiter Liability Model
 * Tracks waiter accountability for served orders
 * Manages liability lifecycle: create -> clear/loss/waive
 */

class WaiterLiability extends Model {
    
    /**
     * Create liability when order is served
     */
    public function createLiability($orderId, $waiterId, $amount, $restaurantId) {
        try {
            // Check if order is already paid - DO NOT create liability for paid orders
            $paymentCheck = "SELECT payment_status FROM orders WHERE uuid = ?";
            $paymentStmt = $this->db->prepare($paymentCheck);
            $paymentStmt->execute([$orderId]);
            $paymentStatus = $paymentStmt->fetchColumn();
            
            if ($paymentStatus === 'paid') {
                return ['status' => 'SKIP', 'message' => 'Order already paid - no liability needed'];
            }
            
            // Check if liability already exists for this order
            $check = "SELECT uuid FROM waiter_liabilities WHERE order_uuid = ? AND status = 'active'";
            $stmt = $this->db->prepare($check);
            $stmt->execute([$orderId]);
            
            if ($stmt->fetch()) {
                return ['status' => 'EXISTS', 'message' => 'Liability already exists for this order'];
            }
            
            // Generate UUID for liability
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $liabilityUuid = UUIDHelper::generate();
            
            // Create new liability
            $query = "INSERT INTO waiter_liabilities 
                      (uuid, restaurant_uuid, order_uuid, waiter_uuid, order_amount, status)
                      VALUES (?, ?, ?, ?, ?, 'active')";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$liabilityUuid, $restaurantId, $orderId, $waiterId, $amount]);
            
            if ($result) {
                // Mark order as liability recorded
                $updateOrder = "UPDATE orders SET liability_recorded = 1 WHERE uuid = ?";
                $this->db->prepare($updateOrder)->execute([$orderId]);
                
                // Audit trail
                $this->logAudit($restaurantId, $waiterId, 'create_liability', 
                               'waiter_liabilities', $liabilityUuid, 
                               null, $amount, "Liability created for order $orderId - Amount: RWF " . number_format($amount, 2));
                
                return [
                    'status' => 'OK',
                    'message' => 'Liability created successfully',
                    'liability_id' => $liabilityUuid
                ];
            }
            
            return ['status' => 'FAIL', 'message' => 'Failed to create liability'];
            
        } catch (PDOException $e) {
            error_log("Create Liability Error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Clear liability when payment received
     */
    public function clearLiability($orderId, $clearedBy, $paymentMethod) {
        try {
            $query = "UPDATE waiter_liabilities 
                      SET status = 'cleared',
                          liability_cleared_at = NOW(),
                          cleared_by_uuid = ?,
                          payment_method = ?
                      WHERE order_uuid = ? AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$clearedBy, $paymentMethod, $orderId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Get liability details for audit
                $details = $this->getLiabilityByOrderId($orderId);
                
                if ($details) {
                    $this->logAudit($details['restaurant_id'], $clearedBy, 'clear_liability',
                                   'waiter_liabilities', $details['id'], 
                                   'active', 'cleared', "Payment received via $paymentMethod - Waiter cleared");
                }
                
                return ['status' => 'OK', 'message' => 'Liability cleared'];
            }
            
            return ['status' => 'OK', 'message' => 'No active liability to clear'];
            
        } catch (PDOException $e) {
            error_log("Clear Liability Error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error'];
        }
    }
    
    /**
     * Mark liability as loss (customer didn't pay)
     */
    public function markAsLoss($liabilityId, $reason, $markedBy) {
        try {
            $query = "UPDATE waiter_liabilities 
                      SET status = 'loss',
                          notes = ?
                      WHERE uuid = ? AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$reason, $liabilityId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Get details for audit
                $details = $this->getLiabilityById($liabilityId);
                
                $this->logAudit($details['restaurant_id'], $markedBy, 'mark_liability_loss',
                               'waiter_liabilities', $liabilityId, 
                               'active', 'loss', $reason);
                
                return [
                    'status' => 'OK',
                    'message' => 'Liability marked as loss',
                    'amount' => $details['order_amount'],
                    'waiter_id' => $details['waiter_id']
                ];
            }
            
            return ['status' => 'FAIL', 'message' => 'Liability not found or already processed'];
            
        } catch (PDOException $e) {
            error_log("Mark Loss Error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error'];
        }
    }
    
    /**
     * Waive liability (manager action)
     */
    public function waiveLiability($liabilityId, $reason, $waivedBy) {
        try {
            $query = "UPDATE waiter_liabilities 
                      SET status = 'waived',
                          liability_cleared_at = NOW(),
                          cleared_by_uuid = ?,
                          notes = ?
                      WHERE uuid = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$waivedBy, $reason, $liabilityId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Get order ID and mark as waived
                $details = $this->getLiabilityById($liabilityId);
                
                $updateOrder = "UPDATE orders SET liability_waived = 1 WHERE uuid = ?";
                $this->db->prepare($updateOrder)->execute([$details['order_uuid']]);
                
                // Audit trail (requires approval for amounts > threshold)
                $requiresApproval = $details['order_amount'] > 10000 ? 1 : 0;
                
                $this->logAuditWithApproval($details['restaurant_id'], $waivedBy, 
                                           'waive_liability', 'waiter_liabilities', 
                                           $liabilityId, 'loss', 'waived', $reason, $requiresApproval);
                
                return [
                    'status' => 'OK',
                    'message' => 'Liability waived successfully',
                    'requires_approval' => $requiresApproval
                ];
            }
            
            return ['status' => 'FAIL', 'message' => 'Liability not found'];
            
        } catch (PDOException $e) {
            error_log("Waive Liability Error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error'];
        }
    }
    
    /**
     * Get active liabilities for a waiter
     */
    public function getActiveLiabilities($waiterId, $restaurantId) {
        try {
            $query = "SELECT * FROM v_active_waiter_liabilities
                      WHERE waiter_uuid = ? AND restaurant_uuid = ?
                      ORDER BY liability_created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$waiterId, $restaurantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get Active Liabilities Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total active liability amount for waiter
     */
    public function getTotalLiability($waiterId, $restaurantId) {
        try {
            $query = "SELECT COALESCE(SUM(order_amount), 0) as total
                      FROM waiter_liabilities
                      WHERE waiter_uuid = ? 
                      AND restaurant_uuid = ?
                      AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$waiterId, $restaurantId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Get Total Liability Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all losses for waiter (unpaid orders)
     */
    public function getWaiterLosses($waiterId, $restaurantId, $dateFrom = null, $dateTo = null) {
        try {
            $query = "SELECT wl.*, o.order_number, o.table_uuid, t.table_number
                      FROM waiter_liabilities wl
                      INNER JOIN orders o ON wl.order_uuid = o.uuid
                      INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                      WHERE wl.waiter_uuid = ? 
                      AND wl.restaurant_uuid = ?
                      AND wl.status = 'loss'";
            
            $params = [$waiterId, $restaurantId];
            
            if ($dateFrom) {
                $query .= " AND DATE(wl.liability_created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND DATE(wl.liability_created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $query .= " ORDER BY wl.liability_created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get Waiter Losses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get liability statistics for restaurant
     */
    public function getLiabilityStats($restaurantId, $period = 'month') {
        try {
            $dateCondition = "DATE(liability_created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 $period)";
            
            $query = "SELECT 
                        COUNT(*) as total_liabilities,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                        SUM(CASE WHEN status = 'cleared' THEN 1 ELSE 0 END) as cleared_count,
                        SUM(CASE WHEN status = 'loss' THEN 1 ELSE 0 END) as loss_count,
                        SUM(CASE WHEN status = 'waived' THEN 1 ELSE 0 END) as waived_count,
                        SUM(CASE WHEN status = 'active' THEN order_amount ELSE 0 END) as active_amount,
                        SUM(CASE WHEN status = 'cleared' THEN order_amount ELSE 0 END) as cleared_amount,
                        SUM(CASE WHEN status = 'loss' THEN order_amount ELSE 0 END) as loss_amount,
                        SUM(CASE WHEN status = 'waived' THEN order_amount ELSE 0 END) as waived_amount
                      FROM waiter_liabilities
                      WHERE restaurant_uuid = ?
                      AND $dateCondition";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$restaurantId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get Liability Stats Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all liabilities (for manager/admin)
     */
    public function getAllLiabilities($restaurantId, $status = null, $limit = 50) {
        try {
            $query = "SELECT wl.*, 
                             s.full_name as waiter_name,
                             o.order_number,
                             t.table_number,
                             TIMESTAMPDIFF(HOUR, wl.liability_created_at, NOW()) as hours_outstanding
                      FROM waiter_liabilities wl
                      INNER JOIN staff_users s ON wl.waiter_uuid = s.uuid
                      INNER JOIN orders o ON wl.order_uuid = o.uuid
                      INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                      WHERE wl.restaurant_uuid = ?";
            
            $params = [$restaurantId];
            
            if ($status) {
                $query .= " AND wl.status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY wl.liability_created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get All Liabilities Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Auto-detect abandoned orders and mark as loss
     */
    public function detectAbandonedOrders($restaurantId, $timeoutMinutes = 120) {
        try {
            // Find active liabilities where table was reset and order unpaid
            $query = "SELECT wl.uuid, wl.order_uuid, wl.order_amount, wl.waiter_uuid
                      FROM waiter_liabilities wl
                      INNER JOIN orders o ON wl.order_uuid = o.uuid
                      INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                      WHERE wl.restaurant_uuid = ?
                      AND wl.status = 'active'
                      AND t.status = 'available'
                      AND o.payment_status = 'unpaid'
                      AND TIMESTAMPDIFF(MINUTE, wl.liability_created_at, NOW()) > ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$restaurantId, $timeoutMinutes]);
            $abandoned = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $markedCount = 0;
            foreach ($abandoned as $liability) {
                $reason = "Auto-detected: Table reset without payment after {$timeoutMinutes} minutes";
                $result = $this->markAsLoss($liability['uuid'], $reason, null);
                if ($result['status'] === 'OK') {
                    $markedCount++;
                }
            }
            
            return [
                'status' => 'OK',
                'detected' => count($abandoned),
                'marked_as_loss' => $markedCount
            ];
            
        } catch (PDOException $e) {
            error_log("Detect Abandoned Orders Error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Database error'];
        }
    }
    
    // Helper methods
    
    private function getLiabilityById($id) {
        try {
            $query = "SELECT * FROM waiter_liabilities WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function getLiabilityByOrderId($orderId) {
        try {
            $query = "SELECT * FROM waiter_liabilities WHERE order_uuid = ? ORDER BY uuid DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function logAudit($restaurantId, $staffId, $action, $table, $recordId, $oldValue, $newValue, $reason) {
        try {
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $auditUuid = UUIDHelper::generate();
            $query = "INSERT INTO audit_trail 
                      (uuid, restaurant_uuid, staff_uuid, action_type, table_name, record_uuid, 
                       old_value, new_value, reason, status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$auditUuid, $restaurantId, $staffId, $action, $table, $recordId, 
                           $oldValue, $newValue, $reason]);
        } catch (PDOException $e) {
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }
    
    private function logAuditWithApproval($restaurantId, $staffId, $action, $table, $recordId, $oldValue, $newValue, $reason, $requiresApproval = 1) {
        try {
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $auditUuid = UUIDHelper::generate();
            $query = "INSERT INTO audit_trail 
                      (uuid, restaurant_uuid, staff_uuid, action_type, table_name, record_uuid, 
                       old_value, new_value, reason, requires_approval, status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$auditUuid, $restaurantId, $staffId, $action, $table, $recordId, 
                           $oldValue, $newValue, $reason, $requiresApproval]);
        } catch (PDOException $e) {
            error_log("Audit Log with Approval Error: " . $e->getMessage());
        }
    }
}
?>
