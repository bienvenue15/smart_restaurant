<?php
$baseDir = dirname(dirname(__DIR__));
require_once $baseDir . '/src/model.php';

class Order extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get table information by QR code
     */
    public function getTableByQRCode($qrCode) {
        try {
            $query = "SELECT rt.*, r.name as restaurant_name 
                      FROM restaurant_tables rt 
                      LEFT JOIN restaurants r ON rt.restaurant_uuid = r.uuid 
                      WHERE rt.qr_code = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$qrCode]);
            
            $table = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($table) {
                return ['status' => 'OK', 'data' => $table];
            } else {
                return ['status' => 'FAIL', 'message' => 'Invalid QR code'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch table', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get table by table number
     */
    public function getTableByNumber($tableNumber) {
        try {
            $query = "SELECT rt.*, r.name as restaurant_name 
                      FROM restaurant_tables rt 
                      LEFT JOIN restaurants r ON rt.restaurant_uuid = r.uuid 
                      WHERE rt.table_number = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableNumber]);
            
            $table = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($table) {
                return ['status' => 'OK', 'data' => $table];
            } else {
                return ['status' => 'FAIL', 'message' => 'Table not found'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch table', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create a new order
     */
    public function createOrder($tableId, $items, $specialInstructions = '') {
        try {
            $this->db->beginTransaction();
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            
            // Generate UUID for order
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $orderUuid = UUIDHelper::generate();
            
            // Insert order
            $orderData = [
                'uuid' => $orderUuid,
                'table_uuid' => $tableId,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'special_instructions' => $specialInstructions
            ];
            
            $orderResult = $this->save('orders', $orderData);
            
            if ($orderResult['status'] !== 'OK') {
                $this->db->rollBack();
                return $orderResult;
            }
            
            // Insert order items
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $itemUuid = UUIDHelper::generate();
                $orderItemData = [
                    'uuid' => $itemUuid,
                    'order_uuid' => $orderUuid,
                    'menu_item_uuid' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $subtotal,
                    'special_request' => isset($item['special_request']) ? $item['special_request'] : ''
                ];
                
                $itemResult = $this->save('order_items', $orderItemData);
                
                if ($itemResult['status'] !== 'OK') {
                    $this->db->rollBack();
                    return $itemResult;
                }
            }
            
            // Update table status
            $updateTableQuery = "UPDATE restaurant_tables SET status = 'occupied' WHERE uuid = ?";
            $stmt = $this->db->prepare($updateTableQuery);
            $stmt->execute([$tableId]);
            
            $this->db->commit();
            
            return [
                'status' => 'OK', 
                'message' => 'Order created successfully',
                'data' => [
                    'order_uuid' => $orderUuid,
                    'uuid' => $orderUuid,
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount
                ]
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['status' => 'FAIL', 'message' => 'Failed to create order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get order details by order UUID
     */
    public function getOrderByUuid($orderUuid) {
        try {
            $query = "SELECT o.*, t.table_number, t.qr_code
                      FROM orders o
                      INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                      WHERE o.uuid = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderUuid]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Get order items with item-level status
            $itemsQuery = "SELECT oi.*, m.name as item_name, m.image_url, oi.status as item_status
                          FROM order_items oi
                          INNER JOIN menu_items m ON oi.menu_item_uuid = m.uuid
                          WHERE oi.order_uuid = ?
                          ORDER BY oi.uuid";
            
            $itemsStmt = $this->db->prepare($itemsQuery);
            $itemsStmt->execute([$orderUuid]);
            
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $order];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get order details by order ID (legacy - for internal use only)
     */
    public function getOrderById($orderId) {
        try {
            $query = "SELECT o.*, t.table_number, t.qr_code
                      FROM orders o
                      INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                      WHERE o.uuid = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Get order items with item-level status
            $itemsQuery = "SELECT oi.*, m.name as item_name, m.image_url, oi.status as item_status
                          FROM order_items oi
                          INNER JOIN menu_items m ON oi.menu_item_uuid = m.uuid
                          WHERE oi.order_uuid = ?
                          ORDER BY oi.uuid";
            
            $itemsStmt = $this->db->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $order];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get active orders for a table
     * Shows unpaid orders even if served/completed to prevent theft
     */
    public function getTableOrders($tableId) {
        try {
            $query = "SELECT o.*, 
                      (SELECT COUNT(*) FROM order_items WHERE order_uuid = o.uuid) as item_count
                      FROM orders o
                      WHERE o.table_uuid = ? 
                      AND (o.status NOT IN ('completed', 'cancelled') 
                           OR (o.status = 'completed' AND o.payment_status = 'pending'))
                      ORDER BY 
                          CASE WHEN o.payment_status = 'pending' THEN 0 ELSE 1 END,
                          o.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $orders];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch orders', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create a waiter call
     */
    public function createWaiterCall($tableId, $requestType, $message = '', $priority = 'normal') {
        try {
            // Get restaurant_uuid from table
            $query = "SELECT restaurant_uuid FROM restaurant_tables WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            $restaurantUuid = $stmt->fetchColumn();
            
            if (!$restaurantUuid) {
                return ['status' => 'FAIL', 'message' => 'Table not found'];
            }
            
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $callUuid = UUIDHelper::generate();
            
            $callData = [
                'uuid' => $callUuid,
                'restaurant_uuid' => $restaurantUuid,
                'table_uuid' => $tableId,
                'request_type' => $requestType,
                'message' => $message,
                'priority' => $priority,
                'status' => 'pending'
            ];
            
            $result = $this->save('waiter_calls', $callData);
            
            return $result;
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to call waiter', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get pending waiter calls
     */
    public function getPendingWaiterCalls($status = 'pending', $restaurantId = null, $filters = []) {
        try {
            // Build query with restaurant filter
            $query = "SELECT wc.*, t.table_number, s.full_name as assigned_name
                      FROM waiter_calls wc
                      INNER JOIN restaurant_tables t ON wc.table_uuid = t.uuid
                      LEFT JOIN staff_users s ON wc.assigned_to_uuid = s.uuid
                      WHERE 1=1";
            
            $params = [];
            
            // Filter by restaurant_uuid
            if ($restaurantId !== null) {
                $query .= " AND wc.restaurant_uuid = ?";
                $params[] = $restaurantId;
            }
            
            // Filter by status
            if ($status && $status !== 'all') {
                $query .= " AND wc.status = ?";
                $params[] = $status;
            }
            
            // Filter by request type
            if (!empty($filters['type'])) {
                $query .= " AND wc.request_type = ?";
                $params[] = $filters['type'];
            }
            
            // Filter by priority
            if (!empty($filters['priority'])) {
                $query .= " AND wc.priority = ?";
                $params[] = $filters['priority'];
            }
            
            // Filter by date
            if (!empty($filters['date'])) {
                $query .= " AND DATE(wc.created_at) = ?";
                $params[] = $filters['date'];
            }
            
            $query .= " ORDER BY wc.priority DESC, wc.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => ['calls' => $calls]];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch calls', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status) {
        try {
            // Get current order details before updating
            $getOrderQuery = "SELECT o.* FROM orders o WHERE o.uuid = ?";
            $getStmt = $this->db->prepare($getOrderQuery);
            $getStmt->execute([$orderId]);
            $orderData = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$orderData) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Update order status
            $query = "UPDATE orders SET status = ? WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$status, $orderId]);
            
            // Auto-create liability if order is served/completed but not paid
            if (($status === 'served' || $status === 'completed') && $orderData['payment_status'] !== 'paid') {
                try {
                    require_once __DIR__ . '/WaiterLiability.php';
                    $liabilityModel = new WaiterLiability($this->db);
                    
                    // Check if liability already exists
                    $checkQuery = "SELECT uuid FROM waiter_liabilities WHERE order_uuid = ? AND status = 'active'";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->execute([$orderId]);
                    
                    if (!$checkStmt->fetch()) {
                        // Determine responsible staff - PRIORITY ORDER:
                        // 1. served_by (staff who physically served)
                        // 2. confirmed_by (staff who confirmed order)
                        // 3. created_by_staff (staff who created order)
                        $responsibleStaffUuid = $orderData['served_by_uuid'] ?? $orderData['confirmed_by_uuid'] ?? $orderData['created_by_staff_uuid'];
                        
                        if ($responsibleStaffUuid) {
                            $liabilityModel->createLiability(
                                $orderId,
                                $responsibleStaffUuid,
                                $orderData['total_amount'],
                                $orderData['restaurant_uuid']
                            );
                            error_log('[ORDER MODEL] ✓ Auto-created liability for order ' . $orderId . ' - Staff UUID: ' . $responsibleStaffUuid);
                        } else {
                            error_log('[ORDER MODEL] ⚠ No responsible staff found for order ' . $orderId);
                        }
                    }
                } catch (Exception $e) {
                    error_log('[ORDER MODEL] Failed to auto-create liability: ' . $e->getMessage());
                    // Don't fail the status update
                }
            }
            
            return ['status' => 'OK', 'message' => 'Order status updated'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to update status', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Assign waiter to an order (for self-service orders)
     */
    public function assignWaiterToOrder($orderId, $waiterId, $restaurantId) {
        try {
            // Verify order exists and belongs to restaurant
            $checkQuery = "SELECT uuid, created_by_staff_uuid, table_uuid FROM orders WHERE uuid = ? AND restaurant_uuid = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$orderId, $restaurantId]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Get table number
            $tableQuery = "SELECT table_number FROM restaurant_tables WHERE uuid = ?";
            $tableStmt = $this->db->prepare($tableQuery);
            $tableStmt->execute([$order['table_uuid']]);
            $table = $tableStmt->fetch(PDO::FETCH_ASSOC);
            $tableNumber = $table ? $table['table_number'] : 'Unknown';
            
            // Verify waiter exists and belongs to restaurant
            $waiterQuery = "SELECT uuid, full_name as name FROM staff_users WHERE uuid = ? AND restaurant_uuid = ? AND role = 'waiter'";
            $waiterStmt = $this->db->prepare($waiterQuery);
            $waiterStmt->execute([$waiterId, $restaurantId]);
            $waiter = $waiterStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$waiter) {
                return ['status' => 'FAIL', 'message' => 'Waiter not found'];
            }
            
            // Check if waiter is currently on shift
            $shiftQuery = "SELECT uuid FROM staff_shifts 
                          WHERE staff_uuid = ? 
                          AND clock_in IS NOT NULL 
                          AND clock_out IS NULL 
                          ORDER BY clock_in DESC LIMIT 1";
            $shiftStmt = $this->db->prepare($shiftQuery);
            $shiftStmt->execute([$waiterId]);
            $activeShift = $shiftStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$activeShift) {
                return ['status' => 'FAIL', 'message' => 'Cannot assign order to off-shift waiter'];
            }
            
            // Update order to assign waiter
            $updateQuery = "UPDATE orders SET created_by_staff_uuid = ? WHERE uuid = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$waiterId, $orderId]);
            
            // Create notification for the waiter
            require_once __DIR__ . '/../../src/UUIDHelper.php';
            $notifUuid = UUIDHelper::generate();
            $notifQuery = "INSERT INTO staff_notifications 
                          (uuid, staff_uuid, order_uuid, notification_type, title, message, is_read, created_at) 
                          VALUES (?, ?, ?, 'assignment', ?, ?, 0, NOW())";
            $notifStmt = $this->db->prepare($notifQuery);
            $notifStmt->execute([
                $notifUuid,
                $waiterId,
                $orderId,
                'New Table Assignment',
                'You have been assigned to serve Table ' . $tableNumber . ' (Order ' . $orderId . ')'
            ]);
            
            return [
                'status' => 'OK', 
                'message' => 'Waiter assigned successfully',
                'waiter_name' => $waiter['name']
            ];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to assign waiter', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if an order can be cancelled (within 1 minute window)
     */
    public function canCancelOrder($orderId) {
        try {
            $query = "SELECT uuid, status, created_at, 
                      TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_elapsed
                      FROM orders WHERE uuid = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Check if order is pending
            if ($order['status'] !== 'pending') {
                return [
                    'status' => 'FAIL', 
                    'message' => 'Only pending orders can be cancelled',
                    'can_cancel' => false
                ];
            }
            
            // Check if within 1 minute (60 seconds)
            $canCancel = $order['seconds_elapsed'] <= 60;
            $timeRemaining = max(0, 60 - $order['seconds_elapsed']);
            
            return [
                'status' => 'OK',
                'can_cancel' => $canCancel,
                'seconds_elapsed' => $order['seconds_elapsed'],
                'seconds_remaining' => $timeRemaining
            ];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to check cancellation', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel an order (only if within 1 minute window)
     */
    public function cancelOrder($orderId) {
        try {
            // Check if can cancel
            $canCancelResult = $this->canCancelOrder($orderId);
            
            if ($canCancelResult['status'] !== 'OK' || !$canCancelResult['can_cancel']) {
                return [
                    'status' => 'FAIL', 
                    'message' => 'Order cannot be cancelled. Time limit exceeded or order already processed.'
                ];
            }
            
            // Update order status to cancelled
            $query = "UPDATE orders SET status = 'cancelled' WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            
            return [
                'status' => 'OK', 
                'message' => 'Order cancelled successfully'
            ];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to cancel order', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all orders for a table (including completed and cancelled)
     */
    public function getTableOrderHistory($tableId, $limit = 10) {
        try {
            $query = "SELECT o.*, 
                      (SELECT COUNT(*) FROM order_items WHERE order_uuid = o.uuid) as item_count,
                      TIMESTAMPDIFF(SECOND, o.created_at, NOW()) as seconds_since_order
                      FROM orders o
                      WHERE o.table_uuid = ? 
                      ORDER BY o.created_at DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId, $limit]);
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add cancellation info for pending orders
            foreach ($orders as &$order) {
                if ($order['status'] === 'pending' && $order['seconds_since_order'] <= 60) {
                    $order['can_cancel'] = true;
                    $order['cancel_seconds_remaining'] = 60 - $order['seconds_since_order'];
                } else {
                    $order['can_cancel'] = false;
                    $order['cancel_seconds_remaining'] = 0;
                }
            }
            
            return ['status' => 'OK', 'data' => $orders];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch order history', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get orders with filters - RBAC aware
     * @param int $restaurantId Restaurant ID
     * @param array $filters Filters: status, date, role, limit
     * @param bool $includeItems Whether to include order items
     * @return array
     */
    public function getOrders($restaurantId, $filters = [], $includeItems = true) {
        try {
            $query = "SELECT o.uuid, o.restaurant_uuid, o.table_uuid, o.order_number, o.status, 
                     o.total_amount, o.special_instructions, o.created_at, o.updated_at,
                     o.confirmed_by_uuid, o.served_by_uuid, o.confirmed_at, o.served_at,
                     o.payment_status, o.payment_method, o.paid_amount, o.paid_at, o.paid_to_uuid,
                     o.created_by_staff_uuid, o.customer_name, o.customer_phone,
                     COALESCE(t.table_number, 'N/A') as table_number, 
                     t.seats,
                     su.full_name as created_by_name,
                     su.role as created_by_role,
                     (SELECT COUNT(*) FROM order_items oi WHERE oi.order_uuid = o.uuid) as items_count,
                     (SELECT su2.full_name 
                      FROM orders o2 
                      INNER JOIN staff_users su2 ON o2.created_by_staff_uuid = su2.uuid
                      WHERE o2.table_uuid = o.table_uuid 
                      AND o2.table_uuid IS NOT NULL
                      AND o2.status IN ('pending', 'confirmed', 'preparing', 'ready')
                      ORDER BY o2.created_at ASC
                      LIMIT 1) as table_assigned_waiter
                     FROM orders o
                     LEFT JOIN restaurant_tables t ON o.table_uuid = t.uuid
                     LEFT JOIN staff_users su ON o.created_by_staff_uuid = su.uuid
                     WHERE o.restaurant_uuid = ?";
            
            $params = [$restaurantId];
            
            // Apply role-based filters
            if (isset($filters['role'])) {
                $role = $filters['role'];
                if ($role === 'kitchen') {
                    // Kitchen only sees orders in preparation workflow
                    $query .= " AND o.status IN ('confirmed', 'preparing', 'ready')";
                } elseif ($role === 'waiter') {
                    // Waiters ONLY see orders they created (assigned to them)
                    if (isset($filters['staff_id'])) {
                        $staffId = $filters['staff_id'];
                        $query .= " AND o.created_by_staff_uuid = ?";
                        $params[] = $staffId;
                    }
                }
            }
            
            // Status filter
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $query .= " AND o.status = ?";
                $params[] = $filters['status'];
            }
            
            // Date filter
            if (isset($filters['date']) && $filters['date']) {
                $query .= " AND DATE(o.created_at) = ?";
                $params[] = $filters['date'];
            }
            
            // Search filter (order number or table number)
            if (isset($filters['search']) && $filters['search']) {
                $search = $filters['search'];
                $query .= " AND (o.order_number LIKE ? OR t.table_number LIKE ? OR o.customer_name LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Created by staff filter
            if (isset($filters['created_by_staff'])) {
                $query .= " AND o.created_by_staff_uuid = ?";
                $params[] = $filters['created_by_staff'];
            }
            
            // Table filter
            if (isset($filters['table_id'])) {
                $query .= " AND o.table_uuid = ?";
                $params[] = $filters['table_id'];
            }
            
            // Limit (hardcoded in query to avoid PDO binding issues)
            $limit = isset($filters['limit']) ? intval($filters['limit']) : 100;
            $query .= " ORDER BY o.created_at DESC LIMIT " . $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Include order items if requested
            if ($includeItems) {
                foreach ($orders as &$order) {
                    $itemsQuery = "SELECT oi.*, mi.name as item_name, mc.name as category, mi.description, oi.status as item_status
                                  FROM order_items oi
                                  INNER JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid
                                  LEFT JOIN menu_categories mc ON mi.category_uuid = mc.uuid
                                  WHERE oi.order_uuid = ?
                                  ORDER BY oi.uuid";
                    $stmt = $this->db->prepare($itemsQuery);
                    $stmt->execute([$order['uuid']]);
                    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            return ['status' => 'OK', 'data' => $orders, 'count' => count($orders)];
            
        } catch (PDOException $e) {
            error_log("Order::getOrders error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to fetch orders', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get order statistics for dashboard
     * @param int $restaurantId Restaurant ID
     * @param string|null $date Date filter (null for today)
     * @return array
     */
    public function getOrderStats($restaurantId, $date = null) {
        try {
            $date = $date ?: date('Y-m-d');
            
            $query = "SELECT 
                     COUNT(*) as total_orders,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                     SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                     SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
                     SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders,
                     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                     SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                     SUM(total_amount) as total_revenue,
                     AVG(total_amount) as average_order_value
                     FROM orders
                     WHERE restaurant_uuid = ? AND DATE(created_at) = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$restaurantId, $date]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $stats];
            
        } catch (PDOException $e) {
            error_log("Order::getOrderStats error: " . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to fetch stats', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get active orders for a table (not completed/cancelled)
     * INCLUDES served orders that haven't been paid yet (to prevent theft)
     */
    public function getActiveOrdersForTable($tableId) {
        try {
            $query = "SELECT o.*, 
                      su.full_name as waiter_name,
                      (SELECT GROUP_CONCAT(
                          CONCAT(mi.name, ' x', oi.quantity) 
                          SEPARATOR ', '
                      ) FROM order_items oi 
                      INNER JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid 
                      WHERE oi.order_uuid = o.uuid) as items_summary
                      FROM orders o
                      LEFT JOIN staff_users su ON o.created_by_staff_uuid = su.uuid
                      WHERE o.table_uuid = ? 
                      AND (o.status IN ('pending', 'confirmed', 'preparing', 'ready', 'served') 
                           OR (o.status = 'completed' AND o.payment_status = 'pending'))
                      ORDER BY 
                          CASE 
                              WHEN o.status = 'served' AND o.payment_status = 'pending' THEN 0
                              WHEN o.status = 'completed' AND o.payment_status = 'pending' THEN 1
                              ELSE 2
                          END,
                          o.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items for each order
            foreach ($orders as &$order) {
                $itemsResult = $this->getOrderItems($order['uuid']);
                $order['items'] = $itemsResult['status'] === 'OK' ? $itemsResult['data'] : [];
            }
            
            return ['status' => 'OK', 'data' => $orders];
            
        } catch (PDOException $e) {
            error_log('[Order] getActiveOrdersForTable error: ' . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to fetch active orders', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Add items to an existing order
     */
    public function addItemsToOrder($orderId, $items) {
        try {
            $this->db->beginTransaction();
            
            // Get current order
            $orderResult = $this->getOrderById($orderId);
            if ($orderResult['status'] !== 'OK') {
                $this->db->rollBack();
                return $orderResult;
            }
            
            $order = $orderResult['data'];
            
            // Check if order can be modified
            if (!in_array($order['status'], ['pending', 'confirmed'])) {
                $this->db->rollBack();
                return [
                    'status' => 'FAIL',
                    'message' => 'Cannot add items to ' . $order['status'] . ' order'
                ];
            }
            
            $additionalAmount = 0;
            
            // Insert new items
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $additionalAmount += $subtotal;
                
                require_once __DIR__ . '/../../src/UUIDHelper.php';
                $itemUuid = UUIDHelper::generate();
                
                $orderItemData = [
                    'uuid' => $itemUuid,
                    'order_uuid' => $orderId,
                    'menu_item_uuid' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $subtotal,
                    'special_request' => $item['special_request'] ?? ''
                ];
                
                $itemResult = $this->save('order_items', $orderItemData);
                if ($itemResult['status'] !== 'OK') {
                    $this->db->rollBack();
                    return $itemResult;
                }
            }
            
            // Update order total
            $newTotal = $order['total_amount'] + $additionalAmount;
            $updateQuery = "UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE uuid = ?";
            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([$newTotal, $orderId]);
            
            // If order was confirmed, reset to pending for kitchen to acknowledge new items
            if ($order['status'] === 'confirmed') {
                $statusUpdate = "UPDATE orders SET status = 'pending' WHERE uuid = ?";
                $stmt = $this->db->prepare($statusUpdate);
                $stmt->execute([$orderId]);
            }
            
            $this->db->commit();
            
            return [
                'status' => 'OK',
                'message' => 'Items added successfully',
                'order_id' => $orderId,
                'new_total' => $newTotal,
                'items_added' => count($items)
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[Order] addItemsToOrder error: ' . $e->getMessage());
            return ['status' => 'FAIL', 'message' => 'Failed to add items', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get order items with details
     */
    private function getOrderItems($orderId) {
        try {
            $query = "SELECT oi.*, 
                      mi.name as item_name,
                      mi.description as item_description,
                      mi.image_url,
                      mi.preparation_time,
                      oi.status as item_status,
                      mc.name as category_name
                      FROM order_items oi
                      INNER JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid
                      LEFT JOIN menu_categories mc ON mi.category_uuid = mc.uuid
                      WHERE oi.order_uuid = ?
                      ORDER BY oi.uuid ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $items];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch order items', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get table by ID
     */
    public function getTableById($tableId) {
        try {
            $query = "SELECT * FROM restaurant_tables WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tableId]);
            
            $table = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($table) {
                return ['status' => 'OK', 'data' => $table];
            } else {
                return ['status' => 'FAIL', 'message' => 'Table not found'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch table', 'error' => $e->getMessage()];
        }
    }
}
?>
