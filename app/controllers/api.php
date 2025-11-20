<?php
require_once 'src/controller.php';
require_once 'app/models/Menu.php';
require_once 'app/models/Order.php';
require_once 'app/models/Staff.php';
require_once 'app/core/Permission.php';
require_once 'src/restaurant.php';
require_once 'src/tenant_middleware.php';

class Api extends Controller {
    
    private $menuModel;
    private $orderModel;
    private $staffModel;
    private $restaurantId;
    
    public function __construct() {
        parent::__construct();
        
        // Start session for validation
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize restaurant context (skip for certain endpoints)
        $action = $_GET['action'] ?? '';
        $skipRestaurantCheck = in_array($action, ['get_plans', 'check_availability']);
        
        if (!$skipRestaurantCheck) {
            if (class_exists('Restaurant')) {
                Restaurant::initialize();
                $this->restaurantId = Restaurant::getCurrentId();
                
                // Check if restaurant context is set
                if ($this->restaurantId === null) {
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => 'Restaurant context not set. Please access via proper URL.'
                    ], 400);
                    exit;
                }
                
                // Check subscription status
                if (!TenantMiddleware::checkSubscription()) {
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => 'Restaurant subscription expired. Please contact support.'
                    ], 403);
                    exit;
                }
            }
        }
        
        $this->menuModel = new Menu();
        $this->orderModel = new Order();
        $this->staffModel = new Staff();
        
        // Set JSON header
        header('Content-Type: application/json');
        
        // Enable CORS for local development
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    public function index() {
        // Get action from query parameter
        $action = isset($_GET['action']) ? $this->sanitize($_GET['action']) : '';
        
        switch ($action) {
            // Public customer-facing endpoints
            case 'get_menu':
                $this->getMenu();
                break;
                
            case 'get_item':
                $this->getMenuItem();
                break;
                
            case 'search_menu':
                $this->searchMenu();
                break;
                
            case 'create_order':
                $this->createOrder();
                break;
                
            case 'get_order':
                $this->getOrder();
                break;
                
            case 'call_waiter':
                $this->callWaiter();
                break;
                
            case 'get_table':
                $this->getTable();
                break;
                
            case 'get_specials':
                $this->getSpecials();
                break;
                
            case 'cancel_order':
                $this->cancelOrder();
                break;
                
            case 'get_order_history':
                $this->getOrderHistory();
                break;
            
            // Staff-only protected endpoints
            case 'staff_get_orders':
                $this->staffGetOrders();
                break;
                
            case 'staff_update_order':
                $this->staffUpdateOrder();
                break;
                
            case 'staff_process_payment':
                $this->staffProcessPayment();
                break;
                
            case 'staff_verify_payment':
                $this->staffVerifyPayment();
                break;
                
            case 'staff_get_waiter_calls':
                $this->staffGetWaiterCalls();
                break;
                
            case 'staff_assign_call':
                $this->staffAssignCall();
                break;
                
            case 'staff_complete_call':
                $this->staffCompleteCall();
                break;
                
            case 'staff_open_cash_session':
                $this->staffOpenCashSession();
                break;
                
            case 'staff_close_cash_session':
                $this->staffCloseCashSession();
                break;
                
            case 'staff_get_cash_session':
                $this->staffGetCashSession();
                break;
                
            case 'staff_request_discount':
                $this->staffRequestDiscount();
                break;
                
            case 'staff_request_refund':
                $this->staffRequestRefund();
                break;
                
            case 'staff_get_report':
                $this->staffGetReport();
                break;
                
            case 'staff_export_report':
                $this->staffExportReport();
                break;
                
            case 'staff_get_calls':
                $this->staffGetCalls();
                break;
                
            case 'staff_get_tables':
                $this->staffGetTables();
                break;
                
            case 'staff_get_menu':
                $this->staffGetMenu();
                break;
                
            case 'staff_reset_table':
                $this->staffResetTable();
                break;
                
            case 'staff_add_table':
                $this->staffAddTable();
                break;
                
            case 'staff_update_table':
                $this->staffUpdateTable();
                break;
                
            case 'staff_delete_table':
                $this->staffDeleteTable();
                break;
                
            case 'staff_add_menu_item':
                $this->staffAddMenuItem();
                break;
                
            case 'staff_update_menu_item':
                $this->staffUpdateMenuItem();
                break;
                
            case 'staff_delete_menu_item':
                $this->staffDeleteMenuItem();
                break;
                
            case 'get_order_history':
                $this->getOrderHistory();
                break;
                
            default:
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'Invalid action'
                ], 400);
        }
    }
    
    /**
     * Get complete menu with categories
     */
    private function getMenu() {
        $result = $this->menuModel->getAllCategoriesWithItems();
        $this->sendResponse($result);
    }
    
    /**
     * Get single menu item
     */
    private function getMenuItem() {
        $itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($itemId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid item ID'], 400);
            return;
        }
        
        $result = $this->menuModel->getItemById($itemId);
        $this->sendResponse($result);
    }
    
    /**
     * Search menu items
     */
    private function searchMenu() {
        $keyword = isset($_GET['q']) ? $this->sanitize($_GET['q']) : '';
        
        if (empty($keyword)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Search keyword required'], 400);
            return;
        }
        
        $result = $this->menuModel->searchItems($keyword);
        $this->sendResponse($result);
    }
    
    /**
     * Get special/featured items
     */
    private function getSpecials() {
        $result = $this->menuModel->getSpecialItems();
        $this->sendResponse($result);
    }
    
    /**
     * Create a new order
     */
    private function createOrder() {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        // Validate session (unless demo mode)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if (!isset($_SESSION['table_id'])) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Session expired. Please scan QR code again.'], 401);
                return;
            }
        }
        
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validate input
        if (!$data || !isset($data['table_id']) || !isset($data['items'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request data'], 400);
            return;
        }
        
        // Sanitize and validate
        $tableId = intval($data['table_id']);
        $items = $data['items'];
        $specialInstructions = isset($data['special_instructions']) ? 
                               $this->sanitize($data['special_instructions']) : '';
        
        // Verify table_id matches session (security check)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if ($tableId !== $_SESSION['table_id']) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Table ID mismatch. Security violation.'], 403);
                return;
            }
        }
        
        if ($tableId <= 0 || empty($items) || !is_array($items)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid order data'], 400);
            return;
        }
        
        // Validate and sanitize each item
        $sanitizedItems = [];
        foreach ($items as $item) {
            if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid item data'], 400);
                return;
            }
            
            $sanitizedItems[] = [
                'id' => intval($item['id']),
                'quantity' => intval($item['quantity']),
                'price' => floatval($item['price']),
                'special_request' => isset($item['special_request']) ? 
                                   $this->sanitize($item['special_request']) : ''
            ];
        }
        
        // Create order
        $result = $this->orderModel->createOrder($tableId, $sanitizedItems, $specialInstructions);
        $this->sendResponse($result);
    }
    
    /**
     * Get order details
     */
    private function getOrder() {
        $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($orderId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid order ID'], 400);
            return;
        }
        
        $result = $this->orderModel->getOrderById($orderId);
        $this->sendResponse($result);
    }
    
    /**
     * Call waiter
     */
    private function callWaiter() {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        // Validate session (unless demo mode)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if (!isset($_SESSION['table_id'])) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Session expired. Please scan QR code again.'], 401);
                return;
            }
        }
        
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validate input
        if (!$data || !isset($data['table_id']) || !isset($data['request_type'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request data'], 400);
            return;
        }
        
        // Sanitize
        $tableId = intval($data['table_id']);
        $requestType = $this->sanitize($data['request_type']);
        $message = isset($data['message']) ? $this->sanitize($data['message']) : '';
        $priority = isset($data['priority']) ? $this->sanitize($data['priority']) : 'normal';
        
        // Verify table_id matches session (security check)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if ($tableId !== $_SESSION['table_id']) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Table ID mismatch. Security violation.'], 403);
                return;
            }
        }
        
        // Validate request type
        $validTypes = ['order', 'assistance', 'bill', 'complaint', 'other'];
        if (!in_array($requestType, $validTypes)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request type'], 400);
            return;
        }
        
        // Validate priority
        $validPriorities = ['low', 'normal', 'high'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'normal';
        }
        
        // Create waiter call
        $result = $this->orderModel->createWaiterCall($tableId, $requestType, $message, $priority);
        $this->sendResponse($result);
    }
    
    /**
     * Get table information
     */
    private function getTable() {
        $qrCode = isset($_GET['qr']) ? $this->sanitize($_GET['qr']) : '';
        $tableNumber = isset($_GET['table']) ? $this->sanitize($_GET['table']) : '';
        
        if (!empty($qrCode)) {
            $result = $this->orderModel->getTableByQRCode($qrCode);
        } elseif (!empty($tableNumber)) {
            $result = $this->orderModel->getTableByNumber($tableNumber);
        } else {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'QR code or table number required'], 400);
            return;
        }
        
        $this->sendResponse($result);
    }
    
    /**
     * Cancel an order (within 1 minute window)
     */
    private function cancelOrder() {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        // Validate session (unless demo mode)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if (!isset($_SESSION['table_id'])) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Session expired. Please scan QR code again.'], 401);
                return;
            }
        }
        
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validate input
        if (!$data || !isset($data['order_id'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request data'], 400);
            return;
        }
        
        $orderId = intval($data['order_id']);
        
        if ($orderId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid order ID'], 400);
            return;
        }
        
        // Verify order belongs to session table (security check)
        $orderResult = $this->orderModel->getOrderById($orderId);
        if ($orderResult['status'] !== 'OK') {
            $this->sendResponse($orderResult);
            return;
        }
        
        $order = $orderResult['data'];
        
        // Security: Verify order belongs to current session table
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if ($order['table_id'] !== $_SESSION['table_id']) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Unauthorized access to order'], 403);
                return;
            }
        }
        
        // Cancel order
        $result = $this->orderModel->cancelOrder($orderId);
        $this->sendResponse($result);
    }
    
    /**
     * Get order history for current table
     */
    private function getOrderHistory() {
        // Validate session (unless demo mode)
        if (!isset($_SESSION['demo_mode']) || !$_SESSION['demo_mode']) {
            if (!isset($_SESSION['table_id'])) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Session expired. Please scan QR code again.'], 401);
                return;
            }
        }
        
        $tableId = $_SESSION['table_id'];
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        if ($limit <= 0 || $limit > 50) {
            $limit = 10;
        }
        
        $result = $this->orderModel->getTableOrderHistory($tableId, $limit);
        $this->sendResponse($result);
    }
    
    // ==================== STAFF ENDPOINTS (RBAC Protected) ====================
    
    /**
     * Get all orders (staff only)
     */
    private function staffGetOrders() {
        Permission::require('view_orders');
        
        $status = isset($_GET['status']) ? $this->sanitize($_GET['status']) : 'all';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        
        $query = "SELECT o.*, t.table_number,
                  (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                  FROM orders o
                  INNER JOIN restaurant_tables t ON o.table_id = t.id";
        
        if ($status !== 'all') {
            $query .= " WHERE o.status = :status";
        }
        
        $query .= " ORDER BY o.created_at DESC LIMIT :limit";
        
        try {
            $stmt = $this->orderModel->db->prepare($query);
            
            if ($status !== 'all') {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(['status' => 'OK', 'data' => $orders]);
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update order status (staff only)
     */
    private function staffUpdateOrder() {
        Permission::require('update_orders');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing required fields'], 400);
            return;
        }
        
        $orderId = intval($data['order_id']);
        $newStatus = $this->sanitize($data['status']);
        $staffId = $_SESSION['staff_id'];
        
        // Get old status for audit
        $query = "SELECT status FROM orders WHERE id = ?";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$orderId]);
        $oldStatus = $stmt->fetchColumn();
        
        // Update order
        $result = $this->staffModel->updateOrderStatus($orderId, $newStatus, $staffId);
        
        if ($result['status'] === 'OK') {
            // Log audit trail
            Permission::logAudit('update_order_status', 'orders', $orderId, $oldStatus, $newStatus);
        }
        
        $this->sendResponse($result);
    }
    
    /**
     * Process payment (staff only)
     */
    private function staffProcessPayment() {
        Permission::require('process_payment');
        Permission::requireShift();  // Must be clocked in
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['order_id']) || !isset($data['payment_method']) || !isset($data['amount'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing required fields'], 400);
            return;
        }
        
        $orderId = intval($data['order_id']);
        $paymentMethod = $this->sanitize($data['payment_method']);
        $amount = floatval($data['amount']);
        $reference = isset($data['reference']) ? $this->sanitize($data['reference']) : null;
        $staffId = $_SESSION['staff_id'];
        
        try {
            // Verify order exists and get total
            $query = "SELECT total_amount, status FROM orders WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Order not found'], 404);
                return;
            }
            
            if ($order['status'] === 'completed') {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Order already completed'], 400);
                return;
            }
            
            // Record payment
            $paymentData = [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'received_by' => $staffId,
                'payment_reference' => $reference,
                'status' => 'pending'  // Requires verification by manager
            ];
            
            $paymentResult = $this->staffModel->save('payments', $paymentData);
            
            if ($paymentResult['status'] !== 'OK') {
                $this->sendResponse($paymentResult);
                return;
            }
            
            // Update order payment status
            $query = "UPDATE orders SET payment_status = 'paid', paid_amount = ?, paid_at = NOW(), paid_to = ? WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$amount, $staffId, $orderId]);
            
            // Log audit trail
            Permission::logAudit('process_payment', 'payments', $paymentResult['data'], null, 
                               json_encode(['order_id' => $orderId, 'amount' => $amount, 'method' => $paymentMethod]));
            
            $this->sendResponse([
                'status' => 'OK', 
                'message' => 'Payment processed successfully',
                'payment_id' => $paymentResult['data']
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Verify payment (manager/admin only)
     */
    private function staffVerifyPayment() {
        Permission::require('verify_payment');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['payment_id'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing payment_id'], 400);
            return;
        }
        
        $paymentId = intval($data['payment_id']);
        $staffId = $_SESSION['staff_id'];
        
        try {
            $query = "UPDATE payments SET verified_by = ?, verified_at = NOW(), status = 'verified' WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId, $paymentId]);
            
            // Log audit trail
            Permission::logAudit('verify_payment', 'payments', $paymentId, 'pending', 'verified');
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Payment verified successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get waiter calls (staff only)
     */
    private function staffGetWaiterCalls() {
        Permission::require('view_tables');
        
        $status = isset($_GET['status']) ? $this->sanitize($_GET['status']) : 'pending';
        
        $result = $this->orderModel->getPendingWaiterCalls($status);
        $this->sendResponse($result);
    }
    
    /**
     * Assign waiter call (staff only)
     */
    private function staffAssignCall() {
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['call_id'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing call_id'], 400);
            return;
        }
        
        $callId = intval($data['call_id']);
        $staffId = $_SESSION['staff_id'];
        
        $result = $this->staffModel->assignWaiterCall($callId, $staffId);
        
        if ($result['status'] === 'OK') {
            Permission::logAudit('assign_waiter_call', 'waiter_calls', $callId, null, $staffId);
        }
        
        $this->sendResponse($result);
    }
    
    /**
     * Complete waiter call (staff only)
     */
    private function staffCompleteCall() {
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['call_id'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing call_id'], 400);
            return;
        }
        
        $callId = intval($data['call_id']);
        
        try {
            $query = "UPDATE waiter_calls SET status = 'completed', resolved_at = NOW() WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$callId]);
            
            Permission::logAudit('complete_waiter_call', 'waiter_calls', $callId, 'pending', 'completed');
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Call completed']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Open cash session (cashier/manager/admin only)
     */
    private function staffOpenCashSession() {
        Permission::require('handle_cash');
        Permission::requireShift();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['opening_balance'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing opening_balance'], 400);
            return;
        }
        
        $openingBalance = floatval($data['opening_balance']);
        $staffId = $_SESSION['staff_id'];
        
        try {
            // Check if already have open session
            $query = "SELECT id FROM cash_sessions WHERE staff_id = ? AND status = 'open'";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId]);
            
            if ($stmt->fetchColumn()) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'You already have an open cash session'], 400);
                return;
            }
            
            // Create new session
            $sessionData = [
                'staff_id' => $staffId,
                'opening_balance' => $openingBalance,
                'status' => 'open'
            ];
            
            $result = $this->staffModel->save('cash_sessions', $sessionData);
            
            if ($result['status'] === 'OK') {
                Permission::logAudit('open_cash_session', 'cash_sessions', $result['data'], null, $openingBalance);
                
                $this->sendResponse([
                    'status' => 'OK',
                    'message' => 'Cash session opened',
                    'session_id' => $result['data']
                ]);
            } else {
                $this->sendResponse($result);
            }
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Close cash session (cashier/manager/admin only)
     */
    private function staffCloseCashSession() {
        Permission::require('handle_cash');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['session_id']) || !isset($data['closing_balance'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing required fields'], 400);
            return;
        }
        
        $sessionId = intval($data['session_id']);
        $closingBalance = floatval($data['closing_balance']);
        $staffId = $_SESSION['staff_id'];
        
        try {
            // Get session details
            $query = "SELECT opening_balance FROM cash_sessions WHERE id = ? AND staff_id = ? AND status = 'open'";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$sessionId, $staffId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Session not found or already closed'], 404);
                return;
            }
            
            // Calculate expected amount (opening + sales during session)
            $query = "SELECT COALESCE(SUM(paid_amount), 0) as total_sales
                      FROM orders
                      WHERE paid_to = ? 
                      AND payment_status = 'paid'
                      AND paid_at >= (SELECT opened_at FROM cash_sessions WHERE id = ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId, $sessionId]);
            $totalSales = $stmt->fetchColumn();
            
            $expectedAmount = $session['opening_balance'] + $totalSales;
            $variance = $closingBalance - $expectedAmount;
            
            // Update session
            $query = "UPDATE cash_sessions 
                      SET closing_balance = ?,
                          expected_amount = ?,
                          variance = ?,
                          closed_at = NOW(),
                          status = 'closed'
                      WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$closingBalance, $expectedAmount, $variance, $sessionId]);
            
            // Log audit trail (flag high variance)
            $requiresApproval = abs($variance) > 50;
            $this->staffModel->logAudit(
                $staffId,
                'close_cash_session',
                'cash_sessions',
                $sessionId,
                $session['opening_balance'],
                $closingBalance,
                $variance != 0 ? "Variance: $variance" : 'Balanced',
                $requiresApproval
            );
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Cash session closed',
                'data' => [
                    'expected' => $expectedAmount,
                    'actual' => $closingBalance,
                    'variance' => $variance,
                    'requires_investigation' => $requiresApproval
                ]
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get current cash session
     */
    private function staffGetCashSession() {
        Permission::require('handle_cash');
        
        $staffId = $_SESSION['staff_id'];
        
        try {
            $query = "SELECT cs.*, 
                      COALESCE(SUM(o.paid_amount), 0) as sales_today
                      FROM cash_sessions cs
                      LEFT JOIN orders o ON o.paid_to = cs.staff_id 
                          AND o.payment_status = 'paid'
                          AND o.paid_at >= cs.opened_at
                      WHERE cs.staff_id = ? AND cs.status = 'open'
                      GROUP BY cs.id";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                $this->sendResponse(['status' => 'OK', 'data' => $session]);
            } else {
                $this->sendResponse(['status' => 'OK', 'data' => null, 'message' => 'No open session']);
            }
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Request discount approval
     */
    private function staffRequestDiscount() {
        Permission::require('update_orders');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['order_id']) || !isset($data['discount_percent']) || !isset($data['reason'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing required fields'], 400);
            return;
        }
        
        $orderId = intval($data['order_id']);
        $discountPercent = floatval($data['discount_percent']);
        $reason = $this->sanitize($data['reason']);
        $staffId = $_SESSION['staff_id'];
        
        try {
            // Get staff max discount
            $query = "SELECT max_discount_percent FROM staff_users WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId]);
            $maxDiscount = $stmt->fetchColumn();
            
            // Get order total
            $query = "SELECT total_amount FROM orders WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId]);
            $orderTotal = $stmt->fetchColumn();
            
            if (!$orderTotal) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Order not found'], 404);
                return;
            }
            
            $discountAmount = ($orderTotal * $discountPercent) / 100;
            
            // Check if within authority
            if ($discountPercent <= $maxDiscount) {
                // Can apply directly
                $newTotal = $orderTotal - $discountAmount;
                $query = "UPDATE orders SET total_amount = ?, discount_percent = ?, discount_reason = ? WHERE id = ?";
                $stmt = $this->orderModel->db->prepare($query);
                $stmt->execute([$newTotal, $discountPercent, $reason, $orderId]);
                
                Permission::logAudit('apply_discount', 'orders', $orderId, $orderTotal, $newTotal, $reason);
                
                $this->sendResponse(['status' => 'OK', 'message' => 'Discount applied']);
            } else {
                // Requires approval
                $adjustmentData = [
                    'order_id' => $orderId,
                    'adjustment_type' => 'discount',
                    'amount' => $discountAmount,
                    'reason' => $reason,
                    'requested_by' => $staffId,
                    'status' => 'pending'
                ];
                
                $result = $this->staffModel->save('order_adjustments', $adjustmentData);
                
                if ($result['status'] === 'OK') {
                    $this->staffModel->logAudit(
                        $staffId,
                        'request_discount',
                        'order_adjustments',
                        $result['data'],
                        null,
                        $discountAmount,
                        "Exceeds authority: $discountPercent% > $maxDiscount%",
                        true
                    );
                    
                    $this->sendResponse([
                        'status' => 'PENDING',
                        'message' => 'Discount request sent for approval',
                        'adjustment_id' => $result['data']
                    ]);
                } else {
                    $this->sendResponse($result);
                }
            }
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Request refund approval
     */
    private function staffRequestRefund() {
        Permission::require('refund_orders');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Method not allowed'], 405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['order_id']) || !isset($data['reason'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing required fields'], 400);
            return;
        }
        
        $orderId = intval($data['order_id']);
        $reason = $this->sanitize($data['reason']);
        $staffId = $_SESSION['staff_id'];
        
        try {
            // Get order total
            $query = "SELECT total_amount, paid_amount FROM orders WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Order not found'], 404);
                return;
            }
            
            $refundAmount = $order['paid_amount'] > 0 ? $order['paid_amount'] : $order['total_amount'];
            
            // Check if can approve own refunds
            $query = "SELECT can_approve_refunds FROM staff_users WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId]);
            $canApprove = $stmt->fetchColumn();
            
            if ($canApprove) {
                // Can process directly
                $query = "UPDATE orders SET status = 'refunded', refund_reason = ?, refunded_by = ?, refunded_at = NOW() WHERE id = ?";
                $stmt = $this->orderModel->db->prepare($query);
                $stmt->execute([$reason, $staffId, $orderId]);
                
                Permission::logAudit('process_refund', 'orders', $orderId, $order['total_amount'], 0, $reason);
                
                $this->sendResponse(['status' => 'OK', 'message' => 'Refund processed']);
            } else {
                // Requires approval
                $adjustmentData = [
                    'order_id' => $orderId,
                    'adjustment_type' => 'refund',
                    'amount' => $refundAmount,
                    'reason' => $reason,
                    'requested_by' => $staffId,
                    'status' => 'pending'
                ];
                
                $result = $this->staffModel->save('order_adjustments', $adjustmentData);
                
                if ($result['status'] === 'OK') {
                    $this->staffModel->logAudit(
                        $staffId,
                        'request_refund',
                        'order_adjustments',
                        $result['data'],
                        $order['total_amount'],
                        0,
                        $reason,
                        true
                    );
                    
                    $this->sendResponse([
                        'status' => 'PENDING',
                        'message' => 'Refund request sent for approval',
                        'adjustment_id' => $result['data']
                    ]);
                } else {
                    $this->sendResponse($result);
                }
            }
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get reports (daily, weekly, monthly, yearly)
     */
    private function staffGetReport() {
        Permission::require('view_reports');
        
        $type = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'daily';
        $date = isset($_GET['date']) ? $this->sanitize($_GET['date']) : null;
        $week = isset($_GET['week']) ? $this->sanitize($_GET['week']) : null;
        $month = isset($_GET['month']) ? $this->sanitize($_GET['month']) : null;
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        try {
            $reportData = [];
            
            switch($type) {
                case 'daily':
                    $reportData = $this->getDailyReport($date ?: date('Y-m-d'));
                    break;
                    
                case 'weekly':
                    $reportData = $this->getWeeklyReport($week ?: date('Y') . '-W' . date('W'));
                    break;
                    
                case 'monthly':
                    $reportData = $this->getMonthlyReport($month ?: date('Y-m'));
                    break;
                    
                case 'yearly':
                    $reportData = $this->getYearlyReport($year);
                    break;
                    
                default:
                    $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid report type'], 400);
                    return;
            }
            
            $this->sendResponse(['status' => 'OK', 'data' => $reportData]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate daily report
     */
    private function getDailyReport($date) {
        // Get today's stats
        $query = "SELECT 
                  COUNT(*) as total_orders,
                  COALESCE(SUM(total_amount), 0) as total_revenue,
                  COALESCE(AVG(total_amount), 0) as avg_order,
                  COUNT(DISTINCT table_id) as unique_tables
                  FROM orders
                  WHERE DATE(created_at) = ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$date]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get yesterday's stats for comparison
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
        $stmt->execute([$yesterday]);
        $yesterdayStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate changes
        $stats['revenue_change'] = $yesterdayStats['total_revenue'] > 0 
            ? (($stats['total_revenue'] - $yesterdayStats['total_revenue']) / $yesterdayStats['total_revenue']) * 100 
            : 0;
        $stats['orders_change'] = $yesterdayStats['total_orders'] > 0 
            ? (($stats['total_orders'] - $yesterdayStats['total_orders']) / $yesterdayStats['total_orders']) * 100 
            : 0;
        $stats['total_customers'] = $stats['unique_tables'];
        
        // Get hourly sales
        $query = "SELECT 
                  HOUR(created_at) as hour,
                  COUNT(*) as orders,
                  SUM(total_amount) as revenue
                  FROM orders
                  WHERE DATE(created_at) = ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')
                  GROUP BY HOUR(created_at)
                  ORDER BY hour";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$date]);
        $stats['hourly_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order details
        $query = "SELECT o.*, t.table_number,
                  (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                  FROM orders o
                  INNER JOIN restaurant_tables t ON o.table_id = t.id
                  WHERE DATE(o.created_at) = ?
                  ORDER BY o.created_at DESC
                  LIMIT 50";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$date]);
        $stats['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Generate weekly report
     */
    private function getWeeklyReport($week) {
        // Parse week string (YYYY-Www)
        list($year, $weekNum) = explode('-W', $week);
        
        // Get start and end dates of the week
        $dto = new DateTime();
        $dto->setISODate($year, $weekNum);
        $startDate = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $endDate = $dto->format('Y-m-d');
        
        // Get week stats
        $query = "SELECT 
                  COUNT(*) as total_orders,
                  COALESCE(SUM(total_amount), 0) as total_revenue,
                  COALESCE(AVG(total_amount), 0) as avg_order,
                  7 as days_count
                  FROM orders
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['avg_daily_revenue'] = $stats['total_revenue'] / 7;
        
        // Get daily breakdown
        $query = "SELECT 
                  DATE(created_at) as date,
                  DAYNAME(created_at) as day_name,
                  COUNT(*) as orders,
                  SUM(total_amount) as revenue
                  FROM orders
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')
                  GROUP BY DATE(created_at)
                  ORDER BY date";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['daily_sales'] = $dailySales;
        
        // Find best day
        if (!empty($dailySales)) {
            $bestDay = array_reduce($dailySales, function($carry, $item) {
                return (!$carry || $item['revenue'] > $carry['revenue']) ? $item : $carry;
            });
            $stats['best_day'] = $bestDay['day_name'];
            $stats['best_day_revenue'] = $bestDay['revenue'];
        }
        
        return $stats;
    }
    
    /**
     * Generate monthly report
     */
    private function getMonthlyReport($month) {
        // Parse month (YYYY-MM)
        list($year, $monthNum) = explode('-', $month);
        
        $startDate = "$year-$monthNum-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Get month stats
        $query = "SELECT 
                  COUNT(*) as total_orders,
                  COALESCE(SUM(total_amount), 0) as total_revenue,
                  COALESCE(AVG(total_amount), 0) as avg_order,
                  DAY(LAST_DAY(?)) as days_in_month
                  FROM orders
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$startDate, $startDate, $endDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['avg_daily_revenue'] = $stats['total_revenue'] / $stats['days_in_month'];
        
        // Get last month for comparison
        $lastMonth = date('Y-m-d', strtotime($startDate . ' -1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime($lastMonth));
        
        $query = "SELECT COALESCE(SUM(total_amount), 0) as revenue
                  FROM orders
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$lastMonth, $lastMonthEnd]);
        $lastMonthRevenue = $stmt->fetchColumn();
        
        $stats['growth'] = $lastMonthRevenue > 0 
            ? (($stats['total_revenue'] - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;
        
        // Get weekly breakdown
        $query = "SELECT 
                  WEEK(created_at, 1) as week,
                  COUNT(*) as orders,
                  SUM(total_amount) as revenue
                  FROM orders
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')
                  GROUP BY WEEK(created_at, 1)
                  ORDER BY week";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $stats['weekly_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get top menu items
        $query = "SELECT 
                  mi.name,
                  mc.name as category,
                  SUM(oi.quantity) as quantity,
                  SUM(oi.quantity * oi.price) as revenue
                  FROM order_items oi
                  INNER JOIN orders o ON oi.order_id = o.id
                  INNER JOIN menu_items mi ON oi.menu_item_id = mi.id
                  INNER JOIN menu_categories mc ON mi.category_id = mc.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ?
                  AND o.status IN ('confirmed', 'preparing', 'ready', 'completed')
                  GROUP BY oi.menu_item_id
                  ORDER BY quantity DESC
                  LIMIT 10";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['top_items'] = $topItems;
        
        if (!empty($topItems)) {
            $stats['top_item'] = $topItems[0]['name'];
            $stats['top_item_qty'] = $topItems[0]['quantity'];
        }
        
        return $stats;
    }
    
    /**
     * Generate yearly report
     */
    private function getYearlyReport($year) {
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        
        // Get year stats
        $query = "SELECT 
                  COUNT(*) as total_orders,
                  COALESCE(SUM(total_amount), 0) as total_revenue,
                  COALESCE(AVG(total_amount), 0) as avg_order
                  FROM orders
                  WHERE YEAR(created_at) = ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$year]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['year'] = $year;
        $stats['avg_monthly_revenue'] = $stats['total_revenue'] / 12;
        
        // Get last year for comparison
        $lastYear = $year - 1;
        $stmt->execute([$lastYear]);
        $lastYearStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['yoy_growth'] = $lastYearStats['total_revenue'] > 0 
            ? (($stats['total_revenue'] - $lastYearStats['total_revenue']) / $lastYearStats['total_revenue']) * 100 
            : 0;
        
        // Get monthly breakdown
        $query = "SELECT 
                  MONTH(created_at) as month,
                  COUNT(*) as orders,
                  SUM(total_amount) as revenue
                  FROM orders
                  WHERE YEAR(created_at) = ?
                  AND status IN ('confirmed', 'preparing', 'ready', 'completed')
                  GROUP BY MONTH(created_at)
                  ORDER BY month";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$year]);
        $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing months with 0
        $monthlySales = array_fill(0, 12, 0);
        foreach ($monthlyData as $data) {
            $monthlySales[$data['month'] - 1] = $data['revenue'];
        }
        
        $stats['monthly_sales'] = $monthlySales;
        
        // Find best month
        if (!empty($monthlyData)) {
            $bestMonth = array_reduce($monthlyData, function($carry, $item) {
                return (!$carry || $item['revenue'] > $carry['revenue']) ? $item : $carry;
            });
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $stats['best_month'] = $monthNames[$bestMonth['month'] - 1];
            $stats['best_month_revenue'] = $bestMonth['revenue'];
        }
        
        return $stats;
    }
    
    /**
     * Export report to PDF/Excel/CSV
     */
    private function staffExportReport() {
        Permission::require('view_reports');
        
        $type = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'daily';
        $format = isset($_GET['format']) ? $this->sanitize($_GET['format']) : 'csv';
        $date = isset($_GET['date']) ? $this->sanitize($_GET['date']) : date('Y-m-d');
        
        try {
            // Get report data
            $data = [];
            switch($type) {
                case 'daily':
                    $data = $this->getDailyReport($date);
                    break;
                case 'weekly':
                    $data = $this->getWeeklyReport($date);
                    break;
                case 'monthly':
                    $data = $this->getMonthlyReport($date);
                    break;
                case 'yearly':
                    $data = $this->getYearlyReport(intval($date));
                    break;
            }
            
            // Export based on format
            switch($format) {
                case 'csv':
                    $this->exportCSV($type, $date, $data);
                    break;
                case 'excel':
                    $this->exportExcel($type, $date, $data);
                    break;
                case 'pdf':
                    $this->exportPDF($type, $date, $data);
                    break;
                default:
                    $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid format'], 400);
            }
            
        } catch (Exception $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Export failed', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Export to CSV
     */
    private function exportCSV($type, $date, $data) {
        $filename = "report_{$type}_{$date}.csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['Smart Restaurant - ' . ucfirst($type) . ' Report']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Period: ' . $date]);
        fputcsv($output, []);
        
        // Write summary stats
        fputcsv($output, ['Summary']);
        fputcsv($output, ['Total Revenue', '$' . number_format($data['total_revenue'], 2)]);
        fputcsv($output, ['Total Orders', $data['total_orders']]);
        fputcsv($output, ['Average Order', '$' . number_format($data['avg_order'], 2)]);
        fputcsv($output, []);
        
        // Write detailed data if available
        if ($type === 'daily' && !empty($data['orders'])) {
            fputcsv($output, ['Order Details']);
            fputcsv($output, ['Order ID', 'Table', 'Time', 'Items', 'Amount', 'Status']);
            
            foreach ($data['orders'] as $order) {
                fputcsv($output, [
                    $order['id'],
                    $order['table_number'],
                    $order['created_at'],
                    $order['item_count'],
                    '$' . number_format($order['total_amount'], 2),
                    $order['status']
                ]);
            }
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Export to Excel (HTML table that Excel can open)
     */
    private function exportExcel($type, $date, $data) {
        $filename = "report_{$type}_{$date}.xls";
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h1>Smart Restaurant - ' . ucfirst($type) . ' Report</h1>';
        echo '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p><strong>Period:</strong> ' . $date . '</p>';
        
        echo '<h2>Summary</h2>';
        echo '<table border="1" cellpadding="5">';
        echo '<tr><td><strong>Total Revenue</strong></td><td>$' . number_format($data['total_revenue'], 2) . '</td></tr>';
        echo '<tr><td><strong>Total Orders</strong></td><td>' . $data['total_orders'] . '</td></tr>';
        echo '<tr><td><strong>Average Order</strong></td><td>$' . number_format($data['avg_order'], 2) . '</td></tr>';
        echo '</table>';
        
        if ($type === 'daily' && !empty($data['orders'])) {
            echo '<h2>Order Details</h2>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><th>Order ID</th><th>Table</th><th>Time</th><th>Items</th><th>Amount</th><th>Status</th></tr>';
            
            foreach ($data['orders'] as $order) {
                echo '<tr>';
                echo '<td>' . $order['id'] . '</td>';
                echo '<td>' . $order['table_number'] . '</td>';
                echo '<td>' . $order['created_at'] . '</td>';
                echo '<td>' . $order['item_count'] . '</td>';
                echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
                echo '<td>' . $order['status'] . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        if ($type === 'monthly' && !empty($data['top_items'])) {
            echo '<h2>Top Menu Items</h2>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><th>Item</th><th>Category</th><th>Quantity</th><th>Revenue</th></tr>';
            
            foreach ($data['top_items'] as $item) {
                echo '<tr>';
                echo '<td>' . $item['name'] . '</td>';
                echo '<td>' . $item['category'] . '</td>';
                echo '<td>' . $item['quantity'] . '</td>';
                echo '<td>$' . number_format($item['revenue'], 2) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        echo '</body></html>';
        exit();
    }
    
    /**
     * Export to PDF (simple HTML-to-PDF)
     */
    private function exportPDF($type, $date, $data) {
        // For now, using HTML format (can be enhanced with TCPDF or DOMPDF later)
        $filename = "report_{$type}_{$date}.pdf";
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Simple text format for PDF (can be enhanced)
        echo "SMART RESTAURANT\n";
        echo ucfirst($type) . " Report\n";
        echo "Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "Period: $date\n\n";
        echo "SUMMARY\n";
        echo "--------\n";
        echo "Total Revenue: $" . number_format($data['total_revenue'], 2) . "\n";
        echo "Total Orders: " . $data['total_orders'] . "\n";
        echo "Average Order: $" . number_format($data['avg_order'], 2) . "\n";
        
        exit();
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    private function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get all waiter calls (wrapper for dashboard)
     */
    private function staffGetCalls() {
        Permission::require('view_tables');
        
        try {
            $query = "SELECT wc.*, t.table_number,
                      s.full_name as assigned_staff
                      FROM waiter_calls wc
                      INNER JOIN restaurant_tables t ON wc.table_id = t.id
                      LEFT JOIN staff s ON wc.assigned_to = s.id
                      WHERE wc.status IN ('pending', 'assigned')
                      ORDER BY wc.created_at DESC";
            
            $stmt = $this->orderModel->db->query($query);
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(['status' => 'OK', 'data' => $calls]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get all tables with current status
     */
    private function staffGetTables() {
        Permission::require('view_tables');
        
        try {
            $query = "SELECT t.*,
                      o.order_number as current_order
                      FROM restaurant_tables t
                      LEFT JOIN orders o ON t.id = o.table_id 
                      AND o.status IN ('pending', 'confirmed', 'preparing', 'ready')
                      ORDER BY t.table_number ASC";
            
            $stmt = $this->orderModel->db->query($query);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(['status' => 'OK', 'data' => $tables]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get menu items (wrapper for staff view)
     */
    private function staffGetMenu() {
        Permission::require('view_menu');
        
        try {
            $query = "SELECT mi.*, mc.name as category
                      FROM menu_items mi
                      INNER JOIN menu_categories mc ON mi.category_id = mc.id
                      ORDER BY mc.display_order, mi.name ASC";
            
            $stmt = $this->orderModel->db->query($query);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(['status' => 'OK', 'data' => $items]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Reset table (wrapper for dashboard)
     */
    private function staffResetTable() {
        Permission::require('reset_table');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $tableId = intval($_POST['table_id'] ?? 0);
        
        if ($tableId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid table ID'], 400);
            return;
        }
        
        $staffId = $_SESSION['staff_user']['id'] ?? 0;
        
        try {
            // Update table status
            $query = "UPDATE restaurant_tables SET status = 'available' WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId]);
            
            // Log audit trail
            Permission::logAudit('reset_table', 'restaurant_tables', $tableId, 'occupied', 'available');
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Table reset successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Add new table
     */
    private function staffAddTable() {
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $tableNumber = $this->sanitize($_POST['table_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if (empty($tableNumber) || $capacity <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid table data'], 400);
            return;
        }
        
        try {
            // Check if table number already exists for this restaurant
            $query = "SELECT id FROM restaurant_tables WHERE restaurant_id = ? AND table_number = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$this->restaurantId, $tableNumber]);
            
            if ($stmt->fetch()) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Table number already exists'], 400);
                return;
            }
            
            // Check table limit
            if (!TenantMiddleware::checkLimit('tables')) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Table limit reached for your subscription plan'], 400);
                return;
            }
            
            // Insert new table with restaurant_id
            $query = "INSERT INTO restaurant_tables (restaurant_id, table_number, capacity, status) VALUES (?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$this->restaurantId, $tableNumber, $capacity, $status]);
            
            $tableId = $this->orderModel->db->lastInsertId();
            
            // Log audit trail
            Permission::logAudit('add_table', 'restaurant_tables', $tableId, null, $tableNumber, "Added table #$tableNumber");
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Table added successfully', 'table_id' => $tableId]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update table
     */
    private function staffUpdateTable() {
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $tableId = intval($_POST['table_id'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 0);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if ($tableId <= 0 || $capacity <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid table data'], 400);
            return;
        }
        
        try {
            // Get old table info for audit
            $query = "SELECT table_number, capacity, status FROM restaurant_tables WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldData) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Table not found'], 404);
                return;
            }
            
            // Update table
            $query = "UPDATE restaurant_tables SET capacity = ?, status = ? WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$capacity, $status, $tableId]);
            
            // Log audit trail
            $changes = "Updated table #{$oldData['table_number']}: ";
            $details = [];
            if ($oldData['capacity'] != $capacity) $details[] = "capacity {$oldData['capacity']} → $capacity";
            if ($oldData['status'] != $status) $details[] = "status {$oldData['status']} → $status";
            $changes .= implode(', ', $details);
            
            Permission::logAudit('update_table', 'restaurant_tables', $tableId, json_encode($oldData), json_encode(['capacity' => $capacity, 'status' => $status]), $changes);
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Table updated successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete table
     */
    private function staffDeleteTable() {
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $tableId = intval($_POST['table_id'] ?? 0);
        
        if ($tableId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid table ID'], 400);
            return;
        }
        
        try {
            // Check if table has active orders
            $query = "SELECT COUNT(*) FROM orders WHERE table_id = ? AND status IN ('pending', 'confirmed', 'preparing', 'ready')";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId]);
            
            if ($stmt->fetchColumn() > 0) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Cannot delete table with active orders'], 400);
                return;
            }
            
            // Get table info for audit
            $query = "SELECT table_number FROM restaurant_tables WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId]);
            $tableNumber = $stmt->fetchColumn();
            
            // Delete table
            $query = "DELETE FROM restaurant_tables WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId]);
            
            // Log audit trail
            Permission::logAudit('delete_table', 'restaurant_tables', $tableId, $tableNumber, null, "Deleted table #$tableNumber");
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Table deleted successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Add new menu item
     */
    private function staffAddMenuItem() {
        Permission::require('manage_menu');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $name = $this->sanitize($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $description = $this->sanitize($_POST['description'] ?? '');
        $available = intval($_POST['available'] ?? 1);
        
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid menu item data'], 400);
            return;
        }
        
        try {
            // Insert new menu item with restaurant_id
            $query = "INSERT INTO menu_items (restaurant_id, name, category_id, price, description, available) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$this->restaurantId, $name, $categoryId, $price, $description, $available]);
            
            $itemId = $this->orderModel->db->lastInsertId();
            
            // Log audit trail
            Permission::logAudit('add_menu_item', 'menu_items', $itemId, null, $name, "Added menu item: $name");
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Menu item added successfully', 'item_id' => $itemId]);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update menu item
     */
    private function staffUpdateMenuItem() {
        Permission::require('manage_menu');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $itemId = intval($_POST['item_id'] ?? 0);
        $name = $this->sanitize($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $description = $this->sanitize($_POST['description'] ?? '');
        $available = intval($_POST['available'] ?? 1);
        
        if ($itemId <= 0 || empty($name) || $categoryId <= 0 || $price <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid menu item data'], 400);
            return;
        }
        
        try {
            // Get old item info for audit
            $query = "SELECT name, category_id, price, description, available FROM menu_items WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$itemId]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldData) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Menu item not found'], 404);
                return;
            }
            
            // Update menu item
            $query = "UPDATE menu_items SET name = ?, category_id = ?, price = ?, description = ?, available = ? WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$name, $categoryId, $price, $description, $available, $itemId]);
            
            // Log audit trail
            $changes = "Updated menu item '{$oldData['name']}': ";
            $details = [];
            if ($oldData['name'] != $name) $details[] = "name '{$oldData['name']}' → '$name'";
            if ($oldData['category_id'] != $categoryId) $details[] = "category changed";
            if ($oldData['price'] != $price) $details[] = "price {$oldData['price']} → $price";
            if ($oldData['available'] != $available) $details[] = "availability " . ($oldData['available'] ? 'available' : 'unavailable') . " → " . ($available ? 'available' : 'unavailable');
            $changes .= implode(', ', $details);
            
            Permission::logAudit('update_menu_item', 'menu_items', $itemId, json_encode($oldData), json_encode(['name' => $name, 'category_id' => $categoryId, 'price' => $price, 'description' => $description, 'available' => $available]), $changes);
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Menu item updated successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete menu item
     */
    private function staffDeleteMenuItem() {
        Permission::require('manage_menu');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $itemId = intval($_POST['item_id'] ?? 0);
        
        if ($itemId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid item ID'], 400);
            return;
        }
        
        try {
            // Get item info for audit
            $query = "SELECT name FROM menu_items WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$itemId]);
            $itemName = $stmt->fetchColumn();
            
            if (!$itemName) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Menu item not found'], 404);
                return;
            }
            
            // Delete menu item
            $query = "DELETE FROM menu_items WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$itemId]);
            
            // Log audit trail
            Permission::logAudit('delete_menu_item', 'menu_items', $itemId, $itemName, null, "Deleted menu item: $itemName");
            
            $this->sendResponse(['status' => 'OK', 'message' => 'Menu item deleted successfully']);
            
        } catch (PDOException $e) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>
