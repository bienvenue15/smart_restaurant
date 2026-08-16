<?php
/**
 * Event Broadcaster
 * Broadcasts real-time events to connected clients via SSE
 */

class EventBroadcaster {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Broadcast an event to connected clients
     * 
     * @param int $restaurantId Restaurant context
     * @param string $eventType Type of event (order_created, order_updated, call_received, etc.)
     * @param array $eventData Event payload
     * @param string $targetRole Target role (admin, manager, waiter, kitchen, cashier, customer, all)
     * @param int|null $targetUserId Specific user ID (null for all users of role)
     */
    public function broadcast($restaurantId, $eventType, $eventData, $targetRole = 'all', $targetUserId = null) {
        try {
            // Create event_stream table if not exists
            $this->ensureEventStreamTable();
            
            // Insert event into stream
            $query = "INSERT INTO event_stream 
                      (restaurant_id, event_type, event_data, target_role, target_user_id, created_at)
                      VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $restaurantId,
                $eventType,
                json_encode($eventData),
                $targetRole,
                $targetUserId
            ]);
            
            // Clean up old events (keep only last hour)
            $this->cleanupOldEvents();
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Event Broadcast Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure event_stream table exists
     */
    private function ensureEventStreamTable() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS event_stream (
                id INT AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                event_data TEXT,
                target_role VARCHAR(20) DEFAULT 'all',
                target_user_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_restaurant_id (restaurant_id),
                INDEX idx_created_at (created_at),
                INDEX idx_target_role (target_role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->exec($query);
        } catch (PDOException $e) {
            // Table might already exist, ignore error
            error_log("Event Stream Table Creation: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up old events (keep only last hour)
     */
    private function cleanupOldEvents() {
        try {
            $query = "DELETE FROM event_stream 
                      WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $this->db->exec($query);
        } catch (PDOException $e) {
            error_log("Event Cleanup Error: " . $e->getMessage());
        }
    }
    
    /**
     * Broadcast order created event
     */
    public function orderCreated($restaurantId, $order) {
        $this->broadcast($restaurantId, 'order_created', [
            'order_id' => $order['id'],
            'order_uuid' => $order['uuid'] ?? null,
            'order_number' => $order['order_number'],
            'table_number' => $order['table_number'] ?? null,
            'total_amount' => $order['total_amount'],
            'status' => $order['status']
        ], 'all');
    }
    
    /**
     * Broadcast order status updated event
     */
    public function orderStatusUpdated($restaurantId, $order) {
        $this->broadcast($restaurantId, 'order_status_updated', [
            'order_id' => $order['id'],
            'order_uuid' => $order['uuid'] ?? null,
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'previous_status' => $order['previous_status'] ?? null
        ], 'all');
    }
    
    /**
     * Broadcast waiter call received event
     */
    public function waiterCallReceived($restaurantId, $call) {
        $this->broadcast($restaurantId, 'waiter_call_received', [
            'call_id' => $call['id'],
            'table_number' => $call['table_number'],
            'request_type' => $call['request_type'] ?? 'assistance',
            'message' => $call['message'] ?? null
        ], 'all'); // Notify all staff
    }
    
    /**
     * Broadcast waiter call completed event
     */
    public function waiterCallCompleted($restaurantId, $callId, $tableNumber) {
        $this->broadcast($restaurantId, 'waiter_call_completed', [
            'call_id' => $callId,
            'table_number' => $tableNumber
        ], 'all');
    }
    
    /**
     * Broadcast payment received event
     */
    public function paymentReceived($restaurantId, $order) {
        $this->broadcast($restaurantId, 'payment_received', [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'amount' => $order['total_amount']
        ], 'all');
    }
    
    /**
     * Broadcast liability created event
     */
    public function liabilityCreated($restaurantId, $liability, $waiterId) {
        // Notify the waiter
        $this->broadcast($restaurantId, 'liability_created', [
            'liability_id' => $liability['id'],
            'order_number' => $liability['order_number'],
            'amount' => $liability['order_amount']
        ], 'waiter', $waiterId);
        
        // Notify managers/admins
        $this->broadcast($restaurantId, 'liability_created', [
            'liability_id' => $liability['id'],
            'waiter_name' => $liability['waiter_name'] ?? 'Unknown',
            'order_number' => $liability['order_number'],
            'amount' => $liability['order_amount']
        ], 'admin');
        
        $this->broadcast($restaurantId, 'liability_created', [
            'liability_id' => $liability['id'],
            'waiter_name' => $liability['waiter_name'] ?? 'Unknown',
            'order_number' => $liability['order_number'],
            'amount' => $liability['order_amount']
        ], 'manager');
    }
}
