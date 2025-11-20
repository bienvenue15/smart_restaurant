<?php
/**
 * Staff Controller
 * Handles staff portal requests with RBAC
 * 
 * @copyright 2025 Inovasiyo Ltd
 */

require_once 'src/controller.php';
require_once 'app/models/Staff.php';
require_once 'app/models/Order.php';
require_once 'app/core/Permission.php';
require_once 'app/core/SystemSettings.php';
require_once 'app/core/SettingsEnforcement.php';

class StaffController extends Controller {
    
    private $staffModel;
    private $orderModel;
    
    public function __construct() {
        parent::__construct();
        $this->staffModel = new Staff();
        $this->orderModel = new Order();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Enforce system settings
        SettingsEnforcement::checkSessionTimeout();
        
        // Set timezone from system settings
        $timezone = SystemSettings::getDefaultTimezone();
        if ($timezone) {
            date_default_timezone_set($timezone);
        }
    }
    
    /**
     * Default action - show login or dashboard
     */
    public function index() {
        // Check for action parameter
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'authenticate':
                $this->authenticate();
                break;
                
            case 'dashboard':
                $this->dashboard();
                break;
                
            case 'clock_in':
                $this->clockIn();
                break;
                
            case 'clock_out':
                $this->clockOut();
                break;
                
            case 'update_order_status':
                $this->updateOrderStatus();
                break;
                
            case 'assign_waiter_call':
                $this->assignWaiterCall();
                break;
                
            case 'reset_table':
                $this->resetTable();
                break;
                
            case 'pending_approvals':
                $this->pendingApprovals();
                break;
                
            case 'approve_action':
                $this->approveAction();
                break;
                
            case 'cash_management':
                $this->cashManagement();
                break;
                
            case 'reports':
                $this->reports();
                break;
                
            // Admin/Owner specific actions
            case 'menu':
            case 'menu_manage':
                $this->menuManage();
                break;
                
            case 'menu_categories':
                $this->menuCategories();
                break;
                
            case 'menu_items':
                $this->menuItems();
                break;
                
            case 'tables':
            case 'tables_manage':
                $this->tablesManage();
                break;
                
            case 'staff_manage':
                $this->staffManage();
                break;
                
            case 'orders_manage':
                $this->ordersManage();
                break;
                
            case 'settings':
            case 'restaurant_settings':
                $this->restaurantSettings();
                break;
                
            
                
            // API actions for AJAX requests
            case 'api':
                $this->handleApi();
                break;
                
            case 'logout':
                $this->logout();
                break;
                
            default:
                // Show login or dashboard based on login status
                if ($this->isLoggedIn()) {
                    $this->dashboard();
                } else {
                    $this->login();
                }
                break;
        }
    }
    
    /**
     * Show login page
     */
    public function login() {
        if ($this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $data = [
            'title' => 'Staff Login - Smart Restaurant',
            'page' => 'staff_login'
        ];
        
        // Load view directly since it's in subdirectory
        foreach($data as $key => $value){
            $$key = $value; 
        }
        require_once "app/views/staff/login.php";
    }
    
    /**
     * Process login
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/?req=staff');
            exit;
        }
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username and password are required';
            header('Location: ' . BASE_URL . '/?req=staff');
            exit;
        }
        
        $result = $this->staffModel->authenticate($username, $password);
        
        if ($result['status'] === 'OK') {
            $_SESSION['staff_user'] = $result['data'];
            $_SESSION['staff_logged_in'] = true;
            
            // Store permission-specific session data
            $_SESSION['staff_id'] = $result['data']['id'];
            $_SESSION['staff_username'] = $result['data']['username'];
            $_SESSION['staff_full_name'] = $result['data']['full_name'];
            $_SESSION['staff_role'] = $result['data']['role'];
            
            // Log activity
            $this->staffModel->logActivity(
                $result['data']['id'],
                'login',
                'User logged in',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: ' . BASE_URL . '/?req=staff');
            exit;
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $staffId = $_SESSION['staff_user']['id'];
            $this->staffModel->logActivity(
                $staffId,
                'logout',
                'User logged out',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        }
        
        session_destroy();
        header('Location: ' . BASE_URL . '/?req=staff');
        exit;
    }
    
    /**
     * Show dashboard
     */
    public function dashboard() {
        $this->requireLogin();
        
        $user = $_SESSION['staff_user'];
        $restaurantId = $this->getRestaurantId();
        
        $statsResult = $this->staffModel->getDashboardStats();
        $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
        
        if ($restaurantId) {
            $query = "SELECT o.*, t.table_number, 
                      (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                      FROM orders o
                      INNER JOIN restaurant_tables t ON o.table_id = t.id
                      WHERE t.restaurant_id = ? AND o.status IN ('pending', 'confirmed', 'preparing', 'ready')
                      ORDER BY o.created_at ASC";
            try {
                $stmt = $this->orderModel->db->prepare($query);
                $stmt->execute([$restaurantId]);
                $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $pendingOrders = [];
            }
        } else {
            $query = "SELECT o.*, t.table_number, 
                      (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                      FROM orders o
                      INNER JOIN restaurant_tables t ON o.table_id = t.id
                      WHERE o.status IN ('pending', 'confirmed', 'preparing', 'ready')
                      ORDER BY o.created_at ASC";
            try {
                $stmt = $this->orderModel->db->query($query);
                $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $pendingOrders = [];
            }
        }
        
        $callsResult = $this->orderModel->getPendingWaiterCalls();
        $waiterCalls = $callsResult['status'] === 'OK' ? $callsResult['data'] : [];
        
        $data = [
            'title' => 'Dashboard - Staff Portal',
            'page' => 'staff_dashboard',
            'user' => $user,
            'stats' => $stats,
            'pending_orders' => $pendingOrders,
            'waiter_calls' => $waiterCalls,
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value){
            $$key = $value; 
        }
        
        require_once "app/views/staff/dashboard.php";
    }
    
    /**
     * Check if staff is logged in
     */
    private function isLoggedIn() {
        return isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;
    }
    
    /**
     * Require staff login
     */
    private function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/?req=staff');
            exit;
        }
    }
    
    /**
     * Clock in for shift
     */
    public function clockIn() {
        $this->requireLogin();
        
        // Allow both GET and POST
        header('Content-Type: application/json');
        
        try {
            $staffId = $_SESSION['staff_user']['id'] ?? null;
            if (!$staffId) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Staff ID not found in session']);
                exit;
            }
            
            $result = $this->staffModel->clockIn($staffId);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Clock in error: ' . $e->getMessage());
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to clock in: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Clock out from shift
     */
    public function clockOut() {
        $this->requireLogin();
        
        // Allow both GET and POST
        header('Content-Type: application/json');
        
        try {
            $staffId = $_SESSION['staff_user']['id'] ?? null;
            if (!$staffId) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Staff ID not found in session']);
                exit;
            }
            
            $result = $this->staffModel->clockOut($staffId);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Clock out error: ' . $e->getMessage());
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to clock out: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Update order status (with permission check)
     */
    public function updateOrderStatus() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('manage_orders');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $orderId = intval($_POST['order_id'] ?? 0);
        $newStatus = $this->sanitize($_POST['status'] ?? '');
        
        if ($orderId <= 0 || empty($newStatus)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            return;
        }
        
        $staffId = $_SESSION['staff_user']['id'];
        
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
        
        echo json_encode($result);
    }
    
    /**
     * Assign waiter call (with permission check)
     */
    public function assignWaiterCall() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $callId = intval($_POST['call_id'] ?? 0);
        $staffId = $_SESSION['staff_user']['id'];
        
        if ($callId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid call ID']);
            return;
        }
        
        $result = $this->staffModel->assignWaiterCall($callId, $staffId);
        
        if ($result['status'] === 'OK') {
            // Log audit trail
            Permission::logAudit('assign_waiter_call', 'waiter_calls', $callId, null, $staffId);
        }
        
        echo json_encode($result);
    }
    
    /**
     * Reset table (with permission check)
     */
    public function resetTable() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('reset_table');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $tableId = intval($_POST['table_id'] ?? 0);
        $staffId = $_SESSION['staff_user']['id'];
        
        if ($tableId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid table ID']);
            return;
        }
        
        // Get table number for audit
        $query = "SELECT table_number, status FROM restaurant_tables WHERE id = ?";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$tableId]);
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = $this->staffModel->resetTable($tableId, $staffId);
        
        if ($result['status'] === 'OK') {
            // Log audit trail
            Permission::logAudit(
                'reset_table', 
                'restaurant_tables', 
                $tableId, 
                $tableInfo['status'], 
                'available',
                'Table ' . $tableInfo['table_number'] . ' reset'
            );
        }
        
        echo json_encode($result);
    }
    
    /**
     * View pending approvals (managers/admins only)
     */
    public function pendingApprovals() {
        $this->requireLogin();
        
        // Check permission
        Permission::require('approve_actions', false);
        
        $result = $this->staffModel->getPendingApprovals();
        
        $data = [
            'title' => 'Pending Approvals - Staff Portal',
            'page' => 'pending_approvals',
            'user' => $_SESSION['staff_user'],
            'approvals' => $result['status'] === 'OK' ? $result['data'] : []
        ];
        
        // Load view
        foreach($data as $key => $value){
            $$key = $value; 
        }
        require_once "app/views/staff/approvals.php";
    }
    
    /**
     * Approve/reject action
     */
    public function approveAction() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('approve_actions');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $auditId = intval($_POST['audit_id'] ?? 0);
        $action = $this->sanitize($_POST['action'] ?? ''); // 'approve' or 'reject'
        
        if ($auditId <= 0 || !in_array($action, ['approve', 'reject'])) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            return;
        }
        
        $staffId = $_SESSION['staff_user']['id'];
        
        if ($action === 'approve') {
            $result = $this->staffModel->approveAction($auditId, $staffId);
        } else {
            // Reject action
            $query = "UPDATE audit_trail 
                      SET status = 'rejected', approved_by = ?, approved_at = NOW() 
                      WHERE id = ? AND status = 'pending'";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$staffId, $auditId]);
            
            $result = ['status' => 'OK', 'message' => 'Action rejected'];
        }
        
        echo json_encode($result);
    }
    
    /**
     * Cash management page
     */
    public function cashManagement() {
        $this->requireLogin();
        
        // Check permission
        Permission::require('handle_cash', false);
        
        $data = [
            'title' => 'Cash Management - Staff Portal',
            'page' => 'cash_management',
            'user' => $_SESSION['staff_user']
        ];
        
        // Load view
        foreach($data as $key => $value){
            $$key = $value; 
        }
        require_once "app/views/staff/cash_management.php";
    }
    
    /**
     * Reports & Analytics (admin)
     */
    public function reports() {
        Permission::require('view_reports', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        try {
            $revenueQuery = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id)) as total_revenue
                FROM orders o
                INNER JOIN restaurant_tables t ON o.table_id = t.id
                WHERE t.restaurant_id = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
            
            $stmt = $this->orderModel->db->prepare($revenueQuery);
            $stmt->execute([$restaurantId, $startDate, $endDate]);
            $dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalQuery = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id)) as total_revenue,
                AVG((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id)) as avg_order_value
                FROM orders o
                INNER JOIN restaurant_tables t ON o.table_id = t.id
                WHERE t.restaurant_id = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?";
            
            $stmt = $this->orderModel->db->prepare($totalQuery);
            $stmt->execute([$restaurantId, $startDate, $endDate]);
            $totalStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $topItemsQuery = "SELECT 
                mi.name,
                mi.id,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue
                FROM order_items oi
                INNER JOIN menu_items mi ON oi.menu_item_id = mi.id
                INNER JOIN orders o ON oi.order_id = o.id
                INNER JOIN restaurant_tables t ON o.table_id = t.id
                WHERE t.restaurant_id = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY mi.id, mi.name
                ORDER BY total_quantity DESC
                LIMIT 10";
            
            $stmt = $this->orderModel->db->prepare($topItemsQuery);
            $stmt->execute([$restaurantId, $startDate, $endDate]);
            $topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $dailyRevenue = [];
            $totalStats = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];
            $topItems = [];
        }
        
        $data = [
            'title' => 'Reports & Analytics - Admin Dashboard',
            'page' => 'reports',
            'user' => $_SESSION['staff_user'],
            'restaurant_id' => $restaurantId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'daily_revenue' => $dailyRevenue,
            'total_stats' => $totalStats,
            'top_items' => $topItems
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/reports.php";
    }
    
    /**
     * Check if user is admin or manager (can access admin features)
     */
    private function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $role = $_SESSION['staff_role'] ?? '';
        return $role === 'admin';
    }
    
    /**
     * Require admin access
     */
    private function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
    }
    
    /**
     * Get restaurant ID from session
     */
    private function getRestaurantId() {
        return $_SESSION['staff_user']['restaurant_id'] ?? null;
    }
    
    /**
     * Menu management page
     */
    public function menuManage() {
        Permission::require('manage_menu', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $categoriesQuery = "SELECT * FROM menu_categories WHERE restaurant_id = ? ORDER BY display_order, name";
            $stmt = $this->orderModel->db->prepare($categoriesQuery);
            $stmt->execute([$restaurantId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $itemsQuery = "SELECT mi.*, mc.name as category_name 
                          FROM menu_items mi 
                          LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
                          WHERE mi.restaurant_id = ? 
                          ORDER BY mc.display_order, mi.display_order, mi.name";
            $stmt = $this->orderModel->db->prepare($itemsQuery);
            $stmt->execute([$restaurantId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $categories = [];
            $items = [];
        }
        
        $data = [
            'title' => 'Menu Management - Admin Dashboard',
            'page' => 'menu_manage',
            'user' => $_SESSION['staff_user'],
            'categories' => $categories,
            'items' => $items,
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/menu.php";
    }
    
    /**
     * Tables management page
     */
    public function tablesManage() {
        Permission::require('manage_tables', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $query = "SELECT * FROM restaurant_tables WHERE restaurant_id = ? ORDER BY table_number";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tables = [];
        }
        
        $data = [
            'title' => 'Tables Management - Admin Dashboard',
            'page' => 'tables_manage',
            'user' => $_SESSION['staff_user'],
            'tables' => $tables,
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/tables.php";
    }
    
    /**
     * Staff management page - Admin only (not manager)
     */
    public function staffManage() {
        Permission::require('manage_staff', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $query = "SELECT * FROM staff_users WHERE restaurant_id = ? ORDER BY role, full_name";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $staff = [];
        }
        
        $data = [
            'title' => 'Staff Management - Admin Dashboard',
            'page' => 'staff_manage',
            'user' => $_SESSION['staff_user'],
            'staff' => $staff,
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        require_once "app/views/staff/admin/staff.php";
    }
    
    /**
     * Orders management page
     */
    public function ordersManage() {
        Permission::require('manage_orders', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            $query = "SELECT o.*, t.table_number,
                     (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total
                     FROM orders o
                     INNER JOIN restaurant_tables t ON o.table_id = t.id
                     WHERE t.restaurant_id = ?";
            
            $params = [$restaurantId];
            
            if ($status !== 'all') {
                $query .= " AND o.status = ?";
                $params[] = $status;
            }
            
            if ($date) {
                $query .= " AND DATE(o.created_at) = ?";
                $params[] = $date;
            }
            
            $query .= " ORDER BY o.created_at DESC LIMIT 100";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $orders = [];
        }
        
        $data = [
            'title' => 'Orders Management - Admin Dashboard',
            'page' => 'orders_manage',
            'user' => $_SESSION['staff_user'],
            'orders' => $orders,
            'restaurant_id' => $restaurantId,
            'status_filter' => $status,
            'date_filter' => $date
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        require_once "app/views/staff/admin/orders.php";
    }
    
    /**
     * Restaurant settings page
     */
    public function restaurantSettings() {
        Permission::require('manage_settings', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $query = "SELECT * FROM restaurants WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $settingsQuery = "SELECT setting_key, setting_value FROM restaurant_settings WHERE restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($settingsQuery);
            $stmt->execute([$restaurantId]);
            $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($settingsRows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $restaurant = null;
            $settings = [];
        }
        
        $data = [
            'title' => 'Restaurant Settings - Admin Dashboard',
            'page' => 'restaurant_settings',
            'user' => $_SESSION['staff_user'],
            'restaurant' => $restaurant,
            'settings' => $settings,
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        require_once "app/views/staff/admin/settings.php";
    }
    
    
    /**
     * Handle API requests for AJAX calls
     */
    public function handleApi() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        $apiAction = $_GET['api_action'] ?? $_POST['api_action'] ?? '';
        $restaurantId = $this->getRestaurantId();
        
        if (!$restaurantId && $apiAction !== 'get_stats') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Restaurant ID required']);
            exit;
        }
        
        switch ($apiAction) {
            case 'create_category':
                Permission::require('manage_menu');
                $this->apiCreateCategory($restaurantId);
                break;
                
            case 'update_category':
                Permission::require('manage_menu');
                $this->apiUpdateCategory($restaurantId);
                break;
                
            case 'delete_category':
                Permission::require('manage_menu');
                $this->apiDeleteCategory($restaurantId);
                break;
                
            case 'create_menu_item':
                Permission::require('manage_menu');
                $this->apiCreateMenuItem($restaurantId);
                break;
                
            case 'update_menu_item':
                Permission::require('manage_menu');
                $this->apiUpdateMenuItem($restaurantId);
                break;
                
            case 'delete_menu_item':
                Permission::require('manage_menu');
                $this->apiDeleteMenuItem($restaurantId);
                break;
                
            case 'create_table':
                Permission::require('manage_tables');
                $this->apiCreateTable($restaurantId);
                break;
                
            case 'update_table':
                Permission::require('manage_tables');
                $this->apiUpdateTable($restaurantId);
                break;
                
            case 'delete_table':
                Permission::require('manage_tables');
                $this->apiDeleteTable($restaurantId);
                break;
                
            case 'create_staff':
                Permission::require('manage_staff');
                $this->apiCreateStaff($restaurantId);
                break;
                
            case 'update_staff':
                Permission::require('manage_staff');
                $this->apiUpdateStaff($restaurantId);
                break;
                
            case 'delete_staff':
                Permission::require('manage_staff');
                $this->apiDeleteStaff($restaurantId);
                break;
                
            case 'get_stats':
                $this->apiGetStats($restaurantId);
                break;
                
            case 'get_order_details':
                $this->apiGetOrderDetails($restaurantId);
                break;
                
            case 'update_restaurant':
                Permission::require('manage_settings');
                $this->apiUpdateRestaurant($restaurantId);
                break;
                
            case 'regenerate_qrcodes':
                Permission::require('manage_tables');
                $this->apiRegenerateQRCodes($restaurantId);
                break;
                
            default:
                echo json_encode(['status' => 'FAIL', 'message' => 'Unknown API action']);
                exit;
        }
    }
    
    private function apiRegenerateQRCodes($restaurantId) {
        try {
            $this->regenerateQRCodes($restaurantId);
            echo json_encode(['status' => 'OK', 'message' => 'QR codes regenerated successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to regenerate QR codes: ' . $e->getMessage()]);
        }
    }
    
    // API methods for CRUD operations
    private function apiCreateCategory($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Category name required']);
            exit;
        }
        
        try {
            $query = "INSERT INTO menu_categories (restaurant_id, name, description, display_order) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId, $name, $description, $displayOrder]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Category created', 'id' => $this->orderModel->db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to create category: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateCategory($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        
        if ($id <= 0 || empty($name)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $query = "UPDATE menu_categories SET name = ?, description = ?, display_order = ? 
                     WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$name, $description, $displayOrder, $id, $restaurantId]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Category updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update category: ' . $e->getMessage()]);
        }
    }
    
    private function apiDeleteCategory($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid category ID']);
            exit;
        }
        
        try {
            // Check if category has items
            $checkQuery = "SELECT COUNT(*) FROM menu_items WHERE category_id = ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$id]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete category with menu items']);
                exit;
            }
            
            $query = "DELETE FROM menu_categories WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$id, $restaurantId]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Category deleted']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }
    
    private function apiCreateMenuItem($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        // Enforce shift requirement if enabled
        if (!SettingsEnforcement::enforceShiftRequirement()) {
            echo json_encode(['status' => 'FAIL', 'message' => 'You must clock in before performing this action']);
            exit;
        }
        
        $categoryId = intval($_POST['category_id'] ?? 0);
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $isAvailable = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
        $displayOrder = intval($_POST['display_order'] ?? 0);
        
        if ($categoryId <= 0 || empty($name) || $price <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        // Check minimum order amount if enabled
        if ($price > 0 && SystemSettings::getMinimumOrderAmount() > 0) {
            // This is a menu item, not an order, but we can validate price reasonableness
            // Minimum order amount is validated at order creation, not item creation
        }
        
        // Handle image upload
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = $this->handleImageUpload($_FILES['image'], $restaurantId);
            if ($imageUrl === false) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Failed to upload image']);
                exit;
            }
        }
        
        try {
            $query = "INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_available, display_order, image_url) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId, $categoryId, $name, $description, $price, $isAvailable, $displayOrder, $imageUrl]);
            
            $itemId = $this->orderModel->db->lastInsertId();
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Menu item created', 'id' => $itemId]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to create menu item: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateMenuItem($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0);
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $isAvailable = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
        $displayOrder = intval($_POST['display_order'] ?? 0);
        
        if ($id <= 0 || $categoryId <= 0 || empty($name) || $price <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        // Handle image upload
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Get current image to delete later if needed
            $currentQuery = "SELECT image_url FROM menu_items WHERE id = ? AND restaurant_id = ?";
            $currentStmt = $this->orderModel->db->prepare($currentQuery);
            $currentStmt->execute([$id, $restaurantId]);
            $currentImage = $currentStmt->fetchColumn();
            
            $imageUrl = $this->handleImageUpload($_FILES['image'], $restaurantId);
            if ($imageUrl === false) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Failed to upload image']);
                exit;
            }
            
            // Delete old image if exists
            if ($currentImage && file_exists($currentImage)) {
                @unlink($currentImage);
            }
        } else {
            // Keep existing image
            $currentQuery = "SELECT image_url FROM menu_items WHERE id = ? AND restaurant_id = ?";
            $currentStmt = $this->orderModel->db->prepare($currentQuery);
            $currentStmt->execute([$id, $restaurantId]);
            $imageUrl = $currentStmt->fetchColumn();
        }
        
        try {
            $query = "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, 
                     is_available = ?, display_order = ?, image_url = ? 
                     WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$categoryId, $name, $description, $price, $isAvailable, $displayOrder, $imageUrl, $id, $restaurantId]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Menu item updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update menu item: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle image upload for menu items
     */
    private function handleImageUpload($file, $restaurantId) {
        $uploadDir = __DIR__ . '/../../assets/images/menu/' . $restaurantId . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('menu_', true) . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return relative path
            return 'assets/images/menu/' . $restaurantId . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Generate QR code for a specific table
     */
    private function generateTableQRCode($tableId, $tableNumber, $restaurantId) {
        try {
            require_once __DIR__ . '/../../core/QRCodeGenerator.php';
            
            // Get restaurant slug and table qr_code
            $query = "SELECT r.slug, rt.qr_code 
                     FROM restaurants r 
                     INNER JOIN restaurant_tables rt ON r.id = rt.restaurant_id 
                     WHERE rt.id = ? AND rt.restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableId, $restaurantId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data || !$data['slug']) {
                return;
            }
            
            // Use table ID as QR code if qr_code column doesn't exist or is null
            $qrCode = $data['qr_code'] ?? $tableId;
            
            // Generate QR code for this table
            $qrGenerator = new QRCodeGenerator();
            $qrGenerator->generateForTable(
                $tableNumber,
                $qrCode,
                $data['slug'],
                $restaurantId
            );
        } catch (Exception $e) {
            error_log('Error generating QR code: ' . $e->getMessage());
        }
    }
    
    /**
     * Regenerate QR codes when menu/tables change
     */
    private function regenerateQRCodes($restaurantId) {
        try {
            require_once __DIR__ . '/../../core/QRCodeGenerator.php';
            
            // Get restaurant slug
            $query = "SELECT slug FROM restaurants WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $restaurantSlug = $stmt->fetchColumn();
            
            if (!$restaurantSlug) {
                return;
            }
            
            // Get all tables for this restaurant (get qr_code or use id)
            $tablesQuery = "SELECT id, table_number, COALESCE(qr_code, id) as qr_code 
                           FROM restaurant_tables 
                           WHERE restaurant_id = ?";
            $tablesStmt = $this->orderModel->db->prepare($tablesQuery);
            $tablesStmt->execute([$restaurantId]);
            $tables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Regenerate QR code for each table
            $qrGenerator = new QRCodeGenerator();
            foreach ($tables as $table) {
                $qrGenerator->generateForTable(
                    $table['table_number'],
                    $table['qr_code'],
                    $restaurantSlug,
                    $restaurantId
                );
            }
        } catch (Exception $e) {
            error_log('Error regenerating QR codes: ' . $e->getMessage());
        }
    }
    
    private function apiDeleteMenuItem($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid item ID']);
            exit;
        }
        
        try {
            // Get image path before deleting
            $imageQuery = "SELECT image_url FROM menu_items WHERE id = ? AND restaurant_id = ?";
            $imageStmt = $this->orderModel->db->prepare($imageQuery);
            $imageStmt->execute([$id, $restaurantId]);
            $imageUrl = $imageStmt->fetchColumn();
            
            $query = "DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$id, $restaurantId]);
            
            // Delete image file if exists
            if ($imageUrl && file_exists(__DIR__ . '/../../' . $imageUrl)) {
                @unlink(__DIR__ . '/../../' . $imageUrl);
            }
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Menu item deleted']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to delete menu item: ' . $e->getMessage()]);
        }
    }
    
    private function apiCreateTable($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        // Enforce shift requirement if enabled
        if (!SettingsEnforcement::enforceShiftRequirement()) {
            echo json_encode(['status' => 'FAIL', 'message' => 'You must clock in before performing this action']);
            exit;
        }
        
        $tableNumber = $this->sanitize($_POST['table_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 4);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if (empty($tableNumber)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Table number required']);
            exit;
        }
        
        // Check max tables limit
        if (!SettingsEnforcement::checkMaxTables($restaurantId)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Maximum table limit (' . SystemSettings::getMaxTables() . ') reached. Contact support to increase limit.']);
            exit;
        }
        
        try {
            // Check if table number already exists
            $checkQuery = "SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_id = ? AND table_number = ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$restaurantId, $tableNumber]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Table number already exists']);
                exit;
            }
            
            // Generate unique QR code for table
            $qrCode = 'T' . $restaurantId . 'T' . uniqid();
            
            $query = "INSERT INTO restaurant_tables (restaurant_id, table_number, qr_code, capacity, status) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId, $tableNumber, $qrCode, $capacity, $status]);
            
            $tableId = $this->orderModel->db->lastInsertId();
            
            // Generate QR code image for new table
            $this->generateTableQRCode($tableId, $tableNumber, $restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Table created', 'id' => $tableId]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to create table: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateTable($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $tableNumber = $this->sanitize($_POST['table_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 4);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if ($id <= 0 || empty($tableNumber)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $query = "UPDATE restaurant_tables SET table_number = ?, capacity = ?, status = ? 
                     WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableNumber, $capacity, $status, $id, $restaurantId]);
            
            // Regenerate QR code if table number changed
            $oldTableQuery = "SELECT table_number FROM restaurant_tables WHERE id = ?";
            $oldStmt = $this->orderModel->db->prepare($oldTableQuery);
            $oldStmt->execute([$id]);
            $oldTableNumber = $oldStmt->fetchColumn();
            
            if ($oldTableNumber != $tableNumber) {
                $this->generateTableQRCode($id, $tableNumber, $restaurantId);
            }
            
            echo json_encode(['status' => 'OK', 'message' => 'Table updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update table: ' . $e->getMessage()]);
        }
    }
    
    private function apiDeleteTable($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid table ID']);
            exit;
        }
        
        try {
            // Check if table has active orders
            $checkQuery = "SELECT COUNT(*) FROM orders WHERE table_id = ? AND status NOT IN ('completed', 'cancelled')";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete table with active orders']);
                exit;
            }
            
            $query = "DELETE FROM restaurant_tables WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$id, $restaurantId]);
            
            echo json_encode(['status' => 'OK', 'message' => 'Table deleted']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to delete table: ' . $e->getMessage()]);
        }
    }
    
    private function apiCreateStaff($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = $this->sanitize($_POST['full_name'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $phone = $this->sanitize($_POST['phone'] ?? '');
        $role = $this->sanitize($_POST['role'] ?? 'waiter');
        
        if (empty($username) || empty($password) || empty($fullName)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Username, password, and full name required']);
            exit;
        }
        
        if (!in_array($role, ['admin', 'manager', 'waiter', 'kitchen', 'cashier'])) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid role']);
            exit;
        }
        
        try {
            // Check if username exists
            $checkQuery = "SELECT COUNT(*) FROM staff_users WHERE username = ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Username already exists']);
                exit;
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO staff_users (restaurant_id, username, password_hash, full_name, email, phone, role, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId, $username, $passwordHash, $fullName, $email, $phone, $role]);
            
            echo json_encode(['status' => 'OK', 'message' => 'Staff user created', 'id' => $this->orderModel->db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to create staff user: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateStaff($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $username = $this->sanitize($_POST['username'] ?? '');
        $fullName = $this->sanitize($_POST['full_name'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $phone = $this->sanitize($_POST['phone'] ?? '');
        $role = $this->sanitize($_POST['role'] ?? 'waiter');
        $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        
        if ($id <= 0 || empty($username) || empty($fullName)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        if (!in_array($role, ['admin', 'manager', 'waiter', 'kitchen', 'cashier'])) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid role']);
            exit;
        }
        
        try {
            // Check if username exists for another user
            $checkQuery = "SELECT COUNT(*) FROM staff_users WHERE username = ? AND id != ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$username, $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Username already exists']);
                exit;
            }
            
            $query = "UPDATE staff_users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, is_active = ? 
                     WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$username, $fullName, $email, $phone, $role, $isActive, $id, $restaurantId]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pwdQuery = "UPDATE staff_users SET password_hash = ? WHERE id = ?";
                $pwdStmt = $this->orderModel->db->prepare($pwdQuery);
                $pwdStmt->execute([$passwordHash, $id]);
            }
            
            echo json_encode(['status' => 'OK', 'message' => 'Staff user updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update staff user: ' . $e->getMessage()]);
        }
    }
    
    private function apiDeleteStaff($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid staff ID']);
            exit;
        }
        
        // Prevent deleting yourself
        if ($id == $_SESSION['staff_user']['id']) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete your own account']);
            exit;
        }
        
        try {
            $query = "DELETE FROM staff_users WHERE id = ? AND restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$id, $restaurantId]);
            
            echo json_encode(['status' => 'OK', 'message' => 'Staff user deleted']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to delete staff user: ' . $e->getMessage()]);
        }
    }
    
    private function apiGetStats($restaurantId) {
        try {
            $stats = [];
            
            if ($restaurantId) {
                // Get today's stats
                $today = date('Y-m-d');
                
                // Today's revenue
                $revenueQuery = "SELECT SUM(oi.quantity * oi.price) as total
                                FROM orders o
                                INNER JOIN restaurant_tables t ON o.table_id = t.id
                                INNER JOIN order_items oi ON o.id = oi.order_id
                                WHERE t.restaurant_id = ? AND DATE(o.created_at) = ? AND o.status = 'completed'";
                $stmt = $this->orderModel->db->prepare($revenueQuery);
                $stmt->execute([$restaurantId, $today]);
                $stats['today_revenue'] = floatval($stmt->fetchColumn() ?: 0);
                
                // Today's orders
                $ordersQuery = "SELECT COUNT(*) FROM orders o
                               INNER JOIN restaurant_tables t ON o.table_id = t.id
                               WHERE t.restaurant_id = ? AND DATE(o.created_at) = ?";
                $stmt = $this->orderModel->db->prepare($ordersQuery);
                $stmt->execute([$restaurantId, $today]);
                $stats['today_orders'] = intval($stmt->fetchColumn() ?: 0);
                
                // Active tables
                $tablesQuery = "SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_id = ? AND status = 'occupied'";
                $stmt = $this->orderModel->db->prepare($tablesQuery);
                $stmt->execute([$restaurantId]);
                $stats['active_tables'] = intval($stmt->fetchColumn() ?: 0);
                
                // Pending orders
                $pendingQuery = "SELECT COUNT(*) FROM orders o
                                INNER JOIN restaurant_tables t ON o.table_id = t.id
                                WHERE t.restaurant_id = ? AND o.status IN ('pending', 'confirmed', 'preparing')";
                $stmt = $this->orderModel->db->prepare($pendingQuery);
                $stmt->execute([$restaurantId]);
                $stats['pending_orders'] = intval($stmt->fetchColumn() ?: 0);
            }
            
            echo json_encode(['status' => 'OK', 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get stats: ' . $e->getMessage()]);
        }
    }
    
    private function apiGetOrderDetails($restaurantId) {
        $orderId = intval($_GET['order_id'] ?? 0);
        
        if ($orderId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid order ID']);
            exit;
        }
        
        try {
            // Get order with table info
            $query = "SELECT o.*, t.table_number, t.restaurant_id
                     FROM orders o
                     INNER JOIN restaurant_tables t ON o.table_id = t.id
                     WHERE o.id = ? AND t.restaurant_id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId, $restaurantId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Order not found']);
                exit;
            }
            
            // Get order items
            $itemsQuery = "SELECT oi.*, mi.name, mi.image_url
                          FROM order_items oi
                          LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                          WHERE oi.order_id = ?";
            $itemsStmt = $this->orderModel->db->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += ($item['quantity'] * $item['price']);
            }
            
            $order['items'] = $items;
            $order['total'] = $total;
            
            echo json_encode(['status' => 'OK', 'data' => $order]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get order details: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateRestaurant($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $name = $this->sanitize($_POST['name'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $phone = $this->sanitize($_POST['phone'] ?? '');
        $address = $this->sanitize($_POST['address'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Restaurant name required']);
            exit;
        }
        
        try {
            $query = "UPDATE restaurants SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$name, $email, $phone, $address, $restaurantId]);
            
            // Regenerate QR codes after restaurant update (in case slug or base URL changed)
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Restaurant updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update restaurant: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Sanitize input
     */
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
?>
