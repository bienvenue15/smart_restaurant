<?php
require_once 'src/model.php';

class Order extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get table information by QR code
     */
    public function getTableByQRCode($qrCode) {
        try {
            $query = "SELECT * FROM restaurant_tables WHERE qr_code = ?";
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
            $query = "SELECT * FROM restaurant_tables WHERE table_number = ?";
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
            
            // Insert order
            $orderData = [
                'table_id' => $tableId,
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
            
            $orderId = $orderResult['id'];
            
            // Insert order items
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $orderItemData = [
                    'order_id' => $orderId,
                    'menu_item_id' => $item['id'],
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
            $updateTableQuery = "UPDATE restaurant_tables SET status = 'occupied' WHERE id = ?";
            $stmt = $this->db->prepare($updateTableQuery);
            $stmt->execute([$tableId]);
            
            $this->db->commit();
            
            return [
                'status' => 'OK', 
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $orderId,
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
     * Get order details by order ID
     */
    public function getOrderById($orderId) {
        try {
            $query = "SELECT o.*, t.table_number, t.qr_code
                      FROM orders o
                      INNER JOIN restaurant_tables t ON o.table_id = t.id
                      WHERE o.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$orderId]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'FAIL', 'message' => 'Order not found'];
            }
            
            // Get order items
            $itemsQuery = "SELECT oi.*, m.name as item_name, m.image_url
                          FROM order_items oi
                          INNER JOIN menu_items m ON oi.menu_item_id = m.id
                          WHERE oi.order_id = ?";
            
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
     */
    public function getTableOrders($tableId) {
        try {
            $query = "SELECT o.*, 
                      (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                      FROM orders o
                      WHERE o.table_id = ? 
                      AND o.status NOT IN ('completed', 'cancelled')
                      ORDER BY o.created_at DESC";
            
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
            $callData = [
                'table_id' => $tableId,
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
    public function getPendingWaiterCalls() {
        try {
            $query = "SELECT wc.*, t.table_number
                      FROM waiter_calls wc
                      INNER JOIN restaurant_tables t ON wc.table_id = t.id
                      WHERE wc.status = 'pending'
                      ORDER BY wc.priority DESC, wc.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $calls];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch calls', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status) {
        try {
            $query = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$status, $orderId]);
            
            return ['status' => 'OK', 'message' => 'Order status updated'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to update status', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if an order can be cancelled (within 1 minute window)
     */
    public function canCancelOrder($orderId) {
        try {
            $query = "SELECT id, status, created_at, 
                      TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_elapsed
                      FROM orders WHERE id = ?";
            
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
            $query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
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
                      (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                      TIMESTAMPDIFF(SECOND, o.created_at, NOW()) as seconds_since_order
                      FROM orders o
                      WHERE o.table_id = ? 
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
}
?>
