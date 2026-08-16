<?php
/**
 * Staff Controller
 * Handles staff portal requests with RBAC
 * 
 * @copyright 2026 Inovasiyo Ltd
 */

require_once 'src/controller.php';
require_once 'app/models/Staff.php';
require_once 'app/models/Order.php';
require_once 'app/core/Permission.php';
require_once 'app/core/SystemSettings.php';
require_once 'app/core/SettingsEnforcement.php';
require_once 'app/core/SubscriptionManager.php';
require_once 'src/ValidationHelper.php';
require_once 'src/ApiValidator.php';

class StaffController extends Controller {
    
    private $staffModel;
    private $orderModel;
    private $subscriptionManager;
    
    public function __construct() {
        parent::__construct();
        $this->staffModel = new Staff();
        $this->orderModel = new Order();
        $this->subscriptionManager = SubscriptionManager::getInstance();
        
        // Configure session save path ONLY if session not already active
        if (session_status() === PHP_SESSION_NONE) {
            // Use absolute path from the restaurant root
            $sessionPath = dirname(dirname(__DIR__)) . '/sessions';
            
            // Fallback to temp directory if path not available
            if (!is_dir(dirname(dirname(__DIR__)))) {
                $sessionPath = sys_get_temp_dir() . '/restaurant_sessions';
            }
            
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0777, true);
            }
            
            // Only set custom session path if directory exists and is writable
            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                ini_set('session.save_path', $sessionPath);
            }
            
            // Start session
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
        // Check for page parameter (for dedicated pages like liabilities)
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        
        if ($page === 'liabilities') {
            $this->liabilitiesPage();
            return;
        }
        
        if ($page === 'approvals') {
            $this->pendingApprovals();
            return;
        }
        
        if ($page === 'reports') {
            $this->reports();
            return;
        }
        
        if ($page === 'support') {
            $this->support();
            return;
        }
        
        // Check for action parameter
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'login':
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
                
            case 'assign_waiter_to_order':
                $this->assignWaiterToOrder();
                break;
                
            case 'get_staff_list':
                $this->get_staff_list();
                break;
                
            case 'reset_table':
                $this->resetTable();
                break;
                
            case 'approvals':
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
                
            case 'support':
                $this->support();
                break;
                
            case 'activity_log':
                $this->activityLog();
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
                
            case 'waiter_calls':
                $this->waiterCallsManage();
                break;
                
            case 'staff_manage':
                $this->staffManage();
                break;
                
            case 'orders_manage':
                $this->ordersManage();
                break;
                
            case 'order_tracking':
                $this->order_tracking();
                break;
                
            case 'settings':
            case 'restaurant_settings':
                $this->restaurantSettings();
                break;
                
            case 'subscription':
                $this->subscription();
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
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Server-side validation
        $usernameError = ValidationHelper::required($username, 'Username') ?? 
                        ValidationHelper::length($username, 3, 50, 'Username');
        $passwordError = ValidationHelper::required($password, 'Password') ?? 
                        ValidationHelper::length($password, 6, 100, 'Password');
        
        if ($usernameError || $passwordError) {
            $_SESSION['error'] = $usernameError ?? $passwordError;
            header('Location: ' . BASE_URL . '/?req=staff');
            exit;
        }
        
        // Sanitize username
        $username = ValidationHelper::sanitize($username);
        
        $result = $this->staffModel->authenticate($username, $password);
        
        if ($result['status'] === 'OK') {
            if (($result['data']['role'] ?? '') === 'super_admin') {
                $_SESSION['error'] = 'Please use the Super Admin portal.';
                header('Location: ' . BASE_URL . '/?req=superadmin');
                exit;
            }

            // Clear only staff-related session data, keep other session variables
            $keysToRemove = ['staff_user', 'staff_logged_in', 'staff_uuid', 'staff_username', 'staff_full_name', 'staff_role', 'staff_id'];
            foreach ($keysToRemove as $key) {
                if (isset($_SESSION[$key])) {
                    unset($_SESSION[$key]);
                }
            }
            
            // Set new session data
            $_SESSION['staff_user'] = $result['data'];
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_uuid'] = $result['data']['uuid'];
            $_SESSION['staff_username'] = $result['data']['username'];
            $_SESSION['staff_full_name'] = $result['data']['full_name'];
            $_SESSION['staff_role'] = $result['data']['role'];
            
            // Log activity
            $this->staffModel->logActivity(
                $result['data']['uuid'],
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
            $staffId = $_SESSION['staff_user']['uuid'];
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
     * Show dashboard with role-based features and restaurant isolation
     */
    public function dashboard() {
        $this->requireLogin();
        
        $user = $_SESSION['staff_user'];
        $restaurantId = $this->getRestaurantId();
        $role = $user['role'];
        
        // Check shift status for non-management roles
        $staffId = $user['uuid'];
        $shiftStatus = $this->staffModel->getCurrentShift($staffId);
        $isOnShift = $shiftStatus['status'] === 'OK' && !empty($shiftStatus['data']);
        
        // Store shift status in session for quick access
        $_SESSION['is_on_shift'] = $isOnShift;
        
        // CRITICAL: Enforce restaurant isolation (except super_admin)
        if (!$restaurantId && $role !== 'super_admin') {
            $_SESSION['error'] = 'No restaurant assigned to your account';
            header('Location: ' . BASE_URL . '/?req=staff&action=login');
            exit;
        }
        
        // Super admin redirects to their own dashboard
        if ($role === 'super_admin') {
            header('Location: ' . BASE_URL . '/?req=superadmin');
            exit;
        }
        
        // Get role-specific dashboard stats with restaurant isolation
        $statsResult = $this->staffModel->getDashboardStats($restaurantId);
        $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
        
        // Get restaurant info for display
        $restaurantInfo = $this->getRestaurantInfo($restaurantId);
        
        // Get pending orders (restaurant-specific ONLY)
        $query = "SELECT o.*, t.table_number, 
                  (SELECT COUNT(*) FROM order_items WHERE order_uuid = o.uuid) as item_count,
                  (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_uuid = o.uuid) as total
                  FROM orders o
                  INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                  WHERE t.restaurant_uuid = ? AND o.status IN ('pending', 'confirmed', 'preparing', 'ready')
                  ORDER BY o.created_at ASC
                  LIMIT 20";
        try {
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard orders error: " . $e->getMessage());
            $pendingOrders = [];
        }
        
        // Get waiter calls (restaurant-specific ONLY)
        $callsQuery = "SELECT wc.*, rt.table_number, su.full_name as assigned_name
                       FROM waiter_calls wc
                       LEFT JOIN restaurant_tables rt ON wc.table_uuid = rt.uuid
                       LEFT JOIN staff_users su ON wc.assigned_to_uuid = su.uuid
                       WHERE wc.restaurant_uuid = ? AND wc.status IN ('pending', 'acknowledged')
                       ORDER BY 
                           CASE wc.priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 END,
                           wc.created_at ASC
                       LIMIT 10";
        try {
            $stmt = $this->orderModel->db->prepare($callsQuery);
            $stmt->execute([$restaurantId]);
            $waiterCalls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard calls error: " . $e->getMessage());
            $waiterCalls = [];
        }
        
        // Get role-specific permissions for UI
        $permissions = [
            'can_manage_menu' => Permission::check('manage_menu'),
            'can_manage_tables' => Permission::check('manage_tables'),
            'can_manage_staff' => Permission::check('manage_staff'),
            'can_manage_orders' => Permission::check('manage_orders'),
            'can_manage_settings' => Permission::check('manage_settings'),
            'can_view_reports' => Permission::check('view_reports'),
            'can_handle_cash' => Permission::check('handle_cash'),
            'can_approve_actions' => Permission::check('approve_actions')
        ];
        
        $data = [
            'title' => 'Dashboard - Staff Portal',
            'page' => 'staff_dashboard',
            'user' => $user,
            'role' => $role,
            'stats' => $stats,
            'pending_orders' => $pendingOrders,
            'waiter_calls' => $waiterCalls,
            'restaurant_id' => $restaurantId,
            'restaurant_info' => $restaurantInfo,
            'permissions' => $permissions,
            'is_on_shift' => $isOnShift,
            'shift_info' => $shiftStatus['data'] ?? null
        ];
        
        foreach($data as $key => $value){
            $$key = $value; 
        }
        
        require_once "app/views/staff/dashboard.php";
    }
    
    /**
     * Liabilities Management Page
     */
    public function liabilitiesPage() {
        $this->requireActiveShift();
        
        $user = $_SESSION['staff_user'];
        $restaurantId = $this->getRestaurantId();
        $role = $user['role'];
        
        // Check permissions - only managers, admins, and waiters can view liabilities
        if (!in_array($role, ['admin', 'manager', 'waiter', 'cashier'])) {
            $_SESSION['error'] = 'You do not have permission to access liability management';
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        // Get restaurant info
        $restaurantInfo = $this->getRestaurantInfo($restaurantId);
        
        $data = [
            'title' => 'Waiter Liabilities - Staff Portal',
            'page' => 'liabilities',
            'user' => $user,
            'role' => $role,
            'restaurant_id' => $restaurantId,
            'restaurant_info' => $restaurantInfo
        ];
        
        foreach($data as $key => $value){
            $$key = $value; 
        }
        
        require_once "app/views/staff/liabilities.php";
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

        // Block super admin accounts from staff portal
        $role = $_SESSION['staff_role'] ?? '';
        if ($role === 'super_admin') {
            header('Location: ' . BASE_URL . '/?req=superadmin');
            exit;
        }
    }
    
    /**
     * Require active shift - staff must be clocked in
     */
    private function requireActiveShift() {
        $this->requireLogin();
        
        $staffId = $_SESSION['staff_user']['uuid'] ?? null;
        
        if (!$staffId) {
            $_SESSION['error'] = 'Staff ID not found';
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        // ALL staff members (including admin and manager) must be on shift to perform actions
        if (!$this->staffModel->isOnShift($staffId)) {
            $_SESSION['error'] = 'You must clock in before performing any actions. Please clock in from the dashboard.';
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
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
            $staffId = $_SESSION['staff_user']['uuid'] ?? null;
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
            $staffId = $_SESSION['staff_user']['uuid'] ?? null;
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
        $this->requireActiveShift();
        header('Content-Type: application/json');
        
        // Check permission - kitchen can modify orders, managers can manage
        $role = $_SESSION['staff_role'] ?? '';
        if ($role === 'kitchen') {
            Permission::require('modify_order');
        } else {
            Permission::require('manage_orders');
        }
        
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
        
        $staffId = $_SESSION['staff_user']['uuid'];
        
        // Get old status for audit
        // Verify order belongs to user's restaurant
        $query = "SELECT o.status FROM orders o
                  INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                  WHERE o.uuid = ? AND t.restaurant_uuid = ?";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$orderId, $this->getRestaurantId()]);
        $oldStatus = $stmt->fetchColumn();
        
        if (!$oldStatus) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied']);
            return;
        }
        
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
        $this->requireActiveShift();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('manage_tables');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $callId = intval($_POST['call_id'] ?? 0);
        $staffId = $_SESSION['staff_user']['uuid'];
        $restaurantId = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
        
        if ($callId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid call ID']);
            return;
        }
        
        if (!$restaurantId) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Restaurant context missing']);
            return;
        }
        
        $result = $this->staffModel->assignWaiterCall($callId, $staffId, $restaurantId);
        
        if ($result['status'] === 'OK') {
            // Log audit trail
            Permission::logAudit('assign_waiter_call', 'waiter_calls', $callId, null, $staffId);
        }
        
        echo json_encode($result);
    }
    
    /**
     * Assign waiter to order (for self-service orders)
     */
    public function assignWaiterToOrder() {
        $this->requireActiveShift();
        header('Content-Type: application/json');
        
        // Check permission - managers and admins can assign
        Permission::require('manage_orders');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $orderId = intval($_POST['order_id'] ?? 0);
        $waiterId = intval($_POST['waiter_id'] ?? 0);
        $restaurantId = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
        
        if ($orderId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid order ID']);
            return;
        }
        
        if ($waiterId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid waiter ID']);
            return;
        }
        
        if (!$restaurantId) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Restaurant context missing']);
            return;
        }
        
        // Check if waiter is currently on shift
        $isOnShift = $this->staffModel->isOnShift($waiterId);
        if (!$isOnShift) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Cannot assign order to off-shift waiter. Please ensure the waiter clocks in first.']);
            return;
        }
        
        $result = $this->orderModel->assignWaiterToOrder($orderId, $waiterId, $restaurantId);
        
        // Log for debugging
        error_log("[ASSIGN WAITER] Order: $orderId, Waiter: $waiterId, Restaurant: $restaurantId");
        error_log("[ASSIGN WAITER] Result: " . json_encode($result));
        
        if ($result['status'] === 'OK') {
            // Log audit trail
            Permission::logAudit('assign_waiter_to_order', 'orders', $orderId, null, $waiterId);
            
            // Notify the assigned waiter
            try {
                $this->notifyWaiterAssignment($restaurantId, $orderId, $waiterId);
            } catch (Exception $e) {
                error_log('[ASSIGN WAITER] Failed to notify waiter: ' . $e->getMessage());
                // Don't fail the assignment if notification fails
            }
        }
        
        echo json_encode($result);
    }
    
    /**
     * Get staff list (for waiter selection)
     */
    public function get_staff_list() {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!isset($_SESSION['staff_user'])) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Not authenticated']);
            return;
        }
        
        $restaurantId = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
        
        if (!$restaurantId) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Restaurant context missing']);
            return;
        }
        
        $result = $this->staffModel->getStaffByRestaurant($restaurantId);
        echo json_encode($result);
    }
    
    /**
     * Reset table (with permission check)
     */
    public function resetTable() {
        $this->requireActiveShift();
        header('Content-Type: application/json');
        
        // Check permission
        Permission::require('reset_table');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $tableId = intval($_POST['table_id'] ?? 0);
        $staffId = $_SESSION['staff_user']['uuid'];
        
        if ($tableId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid table ID']);
            return;
        }
        
        // Get table number for audit
        // Verify table belongs to user's restaurant
        $query = "SELECT table_number, status FROM restaurant_tables WHERE uuid = ? AND restaurant_uuid = ?";
        $stmt = $this->orderModel->db->prepare($query);
        $stmt->execute([$tableId, $this->getRestaurantId()]);
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tableInfo) {
            echo json_encode(['status' => 'error', 'message' => 'Table not found or access denied']);
            return;
        }
        
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
        $this->requireActiveShift();
        
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
        
        // Log incoming request
        error_log('[APPROVE ACTION] Method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('[APPROVE ACTION] POST data: ' . print_r($_POST, true));
        error_log('[APPROVE ACTION] Session: ' . print_r($_SESSION['staff_user'] ?? [], true));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid request method']);
            return;
        }
        
        $auditId = intval($_POST['audit_id'] ?? 0);
        $action = $this->sanitize($_POST['action'] ?? ''); // 'approve' or 'reject'
        
        error_log('[APPROVE ACTION] audit_id: ' . $auditId . ', action: ' . $action);
        
        if ($auditId <= 0 || !in_array($action, ['approve', 'reject'])) {
            error_log('[APPROVE ACTION] Validation failed - audit_id: ' . $auditId . ', action: ' . $action);
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            return;
        }
        
        $staffId = $_SESSION['staff_user']['uuid'];
        
        if ($action === 'approve') {
            $result = $this->staffModel->approveAction($auditId, $staffId);
        } else {
            // Reject action
            $query = "UPDATE audit_trail 
                      SET status = 'rejected', approved_by_uuid = ?, approved_at = NOW() 
                      WHERE uuid = ? AND status = 'pending'";
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
        $this->requireActiveShift();
        
        // Check permission
        Permission::require('handle_cash', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $data = [
            'title' => 'Cash Management - Staff Portal',
            'page' => 'cash_management',
            'user' => $_SESSION['staff_user'],
            'restaurant_id' => $restaurantId
        ];
        
        // Load view
        foreach($data as $key => $value){
            $$key = $value; 
        }
        require_once "app/views/staff/cash_management.php";
    }
    
    /**
     * Support Tickets
     */
    public function support() {
        $this->requireActiveShift();
        
        $user = $_SESSION['staff_user'];
        $restaurantId = $this->getRestaurantId();
        $isOnShift = $_SESSION['is_on_shift'] ?? false;
        
        // Get basic stats for sidebar
        $statsResult = $this->staffModel->getDashboardStats($restaurantId);
        $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
        
        $canHandleCash = Permission::check('handle_cash');
        $canApprove = Permission::check('approve_actions');
        
        include 'app/views/staff/support.php';
    }
    
    public function reports() {
        $this->requireActiveShift();
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
                SUM((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_uuid = o.uuid)) as total_revenue
                FROM orders o
                INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                WHERE t.restaurant_uuid = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
            
            $stmt = $this->orderModel->db->prepare($revenueQuery);
            $stmt->execute([$restaurantId, $startDate, $endDate]);
            $dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalQuery = "SELECT 
                COUNT(DISTINCT o.uuid) as total_orders,
                SUM((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_uuid = o.uuid)) as total_revenue,
                AVG((SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_uuid = o.uuid)) as avg_order_value
                FROM orders o
                INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                WHERE t.restaurant_uuid = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?";
            
            $stmt = $this->orderModel->db->prepare($totalQuery);
            $stmt->execute([$restaurantId, $startDate, $endDate]);
            $totalStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $topItemsQuery = "SELECT 
                mi.name,
                mi.uuid,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue
                FROM order_items oi
                INNER JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid
                INNER JOIN orders o ON oi.order_uuid = o.uuid
                INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                WHERE t.restaurant_uuid = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY mi.uuid, mi.name
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
        
        // Check if this should load the simpler reports view (non-admin)
        $viewPath = file_exists('app/views/staff/reports.php') ? 'app/views/staff/reports.php' : 'app/views/staff/admin/reports.php';
        require_once $viewPath;
    }
    
    /**
     * Activity Log - Admin & Manager Only
     */
    public function activityLog() {
        $this->requireActiveShift();
        // Only admin and manager can view activity logs
        Permission::requireAny(['view_activity_log', 'view_audit_trail'], false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $user = $_SESSION['staff_user'];
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $filterUser = isset($_GET['user']) ? intval($_GET['user']) : null;
        $filterAction = isset($_GET['action_type']) ? $_GET['action_type'] : null;
        $filterDate = isset($_GET['date']) ? $_GET['date'] : null;
        
        try {
            // Combine both activity sources: staff_activity_log and audit_trail
            // First, get activities from staff_activity_log
            $query1 = "SELECT 
                        sal.created_at,
                        sal.staff_uuid as user_id,
                        su.full_name,
                        su.username,
                        su.role,
                        sal.action as action,
                        sal.description,
                        NULL as table_name,
                        NULL as record_id,
                        NULL as old_value,
                        NULL as new_value
                      FROM staff_activity_log sal
                      INNER JOIN staff_users su ON sal.staff_uuid = su.uuid
                      WHERE su.restaurant_uuid = ?";
            
            $params1 = [$restaurantId];
            
            if ($filterUser) {
                $query1 .= " AND sal.staff_uuid = ?";
                $params1[] = $filterUser;
            }
            
            if ($filterAction) {
                $query1 .= " AND sal.action LIKE ?";
                $params1[] = "%$filterAction%";
            }
            
            if ($filterDate) {
                $query1 .= " AND DATE(sal.created_at) = ?";
                $params1[] = $filterDate;
            }
            
            // Second, get activities from audit_trail
            $query2 = "SELECT 
                        at.created_at,
                        at.staff_uuid as user_id,
                        su.full_name,
                        su.username,
                        su.role,
                        at.action_type as action,
                        at.reason as description,
                        at.table_name,
                        at.record_id,
                        at.old_value,
                        at.new_value
                      FROM audit_trail at
                      INNER JOIN staff_users su ON at.staff_uuid = su.uuid
                      WHERE at.restaurant_uuid = ?";
            
            $params2 = [$restaurantId];
            
            if ($filterUser) {
                $query2 .= " AND at.staff_uuid = ?";
                $params2[] = $filterUser;
            }
            
            if ($filterAction) {
                $query2 .= " AND at.action_type LIKE ?";
                $params2[] = "%$filterAction%";
            }
            
            if ($filterDate) {
                $query2 .= " AND DATE(at.created_at) = ?";
                $params2[] = $filterDate;
            }
            
            // Execute both queries separately and merge results
            $stmt1 = $this->orderModel->db->prepare($query1);
            $stmt1->execute($params1);
            $activities1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt2 = $this->orderModel->db->prepare($query2);
            $stmt2->execute($params2);
            $activities2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge and sort by created_at
            $activities = array_merge($activities1, $activities2);
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Apply limit and offset
            $activities = array_slice($activities, $offset, $limit);
            
            // Get shift information from staff_shifts table
            $shiftQuery = "SELECT s.*, su.full_name, su.role,
                          TIMESTAMPDIFF(HOUR, s.clock_in, COALESCE(s.clock_out, NOW())) as hours_worked
                          FROM staff_shifts s
                          INNER JOIN staff_users su ON s.staff_uuid = su.uuid
                          WHERE su.restaurant_uuid = ?";
            
            if ($filterUser) {
                $shiftQuery .= " AND s.staff_uuid = ?";
                $shiftStmt = $this->orderModel->db->prepare($shiftQuery . " ORDER BY s.clock_in DESC LIMIT 20");
                $shiftStmt->execute([$restaurantId, $filterUser]);
            } else {
                $shiftStmt = $this->orderModel->db->prepare($shiftQuery . " ORDER BY s.clock_in DESC LIMIT 20");
                $shiftStmt->execute([$restaurantId]);
            }
            $shifts = $shiftStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all staff for filter
            $staffQuery = "SELECT uuid, full_name, username, role FROM staff_users WHERE restaurant_uuid = ? ORDER BY full_name";
            $stmt = $this->orderModel->db->prepare($staffQuery);
            $stmt->execute([$restaurantId]);
            $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
            $activities = [];
            $shifts = [];
            $staff_list = [];
        }
        
        $data = [
            'title' => 'Activity Log - Admin Dashboard',
            'page' => 'activity_log',
            'user' => $user,
            'activities' => $activities,
            'shifts' => $shifts,
            'staff_list' => $staff_list,
            'restaurant_id' => $restaurantId,
            'filter_user' => $filterUser,
            'filter_action' => $filterAction,
            'filter_date' => $filterDate
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/activity_log.php";
    }
    
    /**
     * Check if user is admin (restaurant owner)
     */
    private function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $role = $_SESSION['staff_role'] ?? '';
        return $role === 'admin';
    }
    
    /**
     * Check if user is manager
     */
    private function isManager() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $role = $_SESSION['staff_role'] ?? '';
        return $role === 'manager';
    }
    
    /**
     * Check if user is admin or manager (can access management features)
     */
    private function canManage() {
        return $this->isAdmin() || $this->isManager();
    }
    
    /**
     * Require admin access (owner only)
     */
    private function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            $_SESSION['error'] = 'This action is restricted to restaurant owners only';
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
    }
    
    /**
     * Require admin or manager access
     */
    private function requireManagementAccess() {
        $this->requireLogin();
        if (!$this->canManage()) {
            $_SESSION['error'] = 'This action is restricted to owners and managers only';
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
    }
    
    /**
     * Get restaurant ID from session
     */
    private function getRestaurantId() {
        return $_SESSION['staff_user']['restaurant_uuid'] ?? null;
    }
    
    /**
     * Get restaurant information
     */
    private function getRestaurantInfo($restaurantId) {
        if (!$restaurantId) return null;
        
        try {
            $query = "SELECT uuid, name, slug, address, phone, email, status FROM restaurants WHERE uuid = ? LIMIT 1";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching restaurant info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Menu management page
     */
    public function menuManage() {
        $this->requireActiveShift();
        // Allow manage_menu OR view_menu (for waiters/cashiers)
        Permission::requireAny(['manage_menu', 'edit_menu', 'view_menu'], false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $categoriesQuery = "SELECT * FROM menu_categories WHERE restaurant_uuid = ? ORDER BY display_order, name";
            $stmt = $this->orderModel->db->prepare($categoriesQuery);
            $stmt->execute([$restaurantId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $itemsQuery = "SELECT mi.*, mc.name as category_name 
                          FROM menu_items mi 
                          LEFT JOIN menu_categories mc ON mi.category_uuid = mc.uuid 
                          WHERE mi.restaurant_uuid = ? 
                          ORDER BY mc.display_order, mi.display_order, mi.name";
            $stmt = $this->orderModel->db->prepare($itemsQuery);
            $stmt->execute([$restaurantId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Menu fetch error: " . $e->getMessage());
            $categories = [];
            $items = [];
        }
        
        $user = $_SESSION['staff_user'];
        $isViewOnly = in_array($user['role'], ['waiter', 'cashier', 'kitchen']);
        
        $data = [
            'title' => $isViewOnly ? 'Menu View - Staff Portal' : 'Menu Management - Admin Dashboard',
            'page' => 'menu_manage',
            'user' => $user,
            'categories' => $categories,
            'items' => $items,
            'restaurant_id' => $restaurantId,
            'is_view_only' => $isViewOnly
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
        $this->requireActiveShift();
        // Allow both manage_tables AND view_tables (for waiters)
        Permission::requireAny(['manage_tables', 'view_tables'], false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $query = "SELECT t.*, 
                      o.uuid as current_order_uuid,
                      su.full_name as assigned_waiter_name,
                      su.uuid as assigned_waiter_uuid
                      FROM restaurant_tables t
                      LEFT JOIN orders o ON t.uuid = o.table_uuid 
                      AND o.status IN ('pending', 'confirmed', 'preparing', 'ready')
                      LEFT JOIN staff_users su ON o.created_by_staff_uuid = su.uuid
                      WHERE t.restaurant_uuid = ? 
                      ORDER BY t.table_number";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tables = [];
        }
        
        $user = $_SESSION['staff_user'];
        $isViewOnly = $user['role'] === 'waiter';
        
        $data = [
            'title' => $isViewOnly ? 'Tables View - Staff Portal' : 'Tables Management - Admin Dashboard',
            'page' => 'tables_manage',
            'user' => $user,
            'tables' => $tables,
            'restaurant_id' => $restaurantId,
            'is_view_only' => $isViewOnly
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/tables.php";
    }
    
    /**
     * Waiter calls management page
     */
    public function waiterCallsManage() {
        $this->requireActiveShift();
        Permission::require('view_tables', false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $data = [
            'title' => 'Waiter Calls - Admin Dashboard',
            'page' => 'waiter_calls',
            'user' => $_SESSION['staff_user'],
            'restaurant_id' => $restaurantId
        ];
        
        foreach($data as $key => $value) {
            $$key = $value;
        }
        
        require_once "app/views/staff/admin/waiter_calls.php";
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
            // First verify we have the restaurant ID
            if (!$restaurantId) {
                error_log("No restaurant ID found in session");
                $staff = [];
            } else {
                $query = "SELECT su.*, 
                          ss.uuid as shift_uuid,
                          ss.clock_in as clock_in_time,
                          ss.clock_out as clock_out_time,
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
                $stmt = $this->staffModel->db->prepare($query);
                $stmt->execute([$restaurantId]);
                $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug logging
                error_log("Restaurant ID: " . $restaurantId);
                error_log("Staff count fetched: " . count($staff));
            }
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("Staff fetch error: " . $e->getMessage());
            error_log("Query failed with restaurant_id: " . ($restaurantId ?? 'NULL'));
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
        $this->requireActiveShift();
        // Allow both manage_orders AND view_orders (for kitchen)
        Permission::requireAny(['manage_orders', 'view_orders'], false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $user = $_SESSION['staff_user'];
        $role = $user['role'];
        
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        // Kitchen staff only see orders that need preparation (confirmed, preparing, ready)
        if ($role === 'kitchen') {
            if ($status === 'all') {
                $status = 'preparing'; // Default to preparing for kitchen
            }
        }
        
        // Build filters for Order model
        $filters = [
            'role' => $role,
            'status' => $status,
            'date' => $date,
            'limit' => 100
        ];
        
        // Waiters only see their own orders
        if ($role === 'waiter') {
            $filters['staff_id'] = $user['uuid'];
        }
        
        // Fetch orders using the model
        $result = $this->orderModel->getOrders($restaurantId, $filters, true);
        $orders = $result['status'] === 'OK' ? $result['data'] : [];
        
        $data = [
            'title' => $role === 'kitchen' ? 'Kitchen Orders - Preparation' : 'Orders Management - Admin Dashboard',
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
     * Professional order tracking dashboard (Kanban view)
     */
    public function order_tracking() {
        // Only admin and manager can access
        if (!isset($_SESSION['staff_user']) || !in_array($_SESSION['staff_user']['role'], ['admin', 'manager'])) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        require_once "app/views/staff/admin/order_tracking.php";
    }
    
    /**
     * Restaurant settings page
     */
    public function subscription() {
        $this->requireActiveShift();
        // Only admin (restaurant owners) can view subscription
        if (!isset($_SESSION['staff_user']) || $_SESSION['staff_user']['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        $user = $_SESSION['staff_user'];
        require __DIR__ . '/../views/staff/subscription.php';
    }
    
    public function restaurantSettings() {
        $this->requireActiveShift();
        // Allow admin and super_admin to access settings
        Permission::requireAny(['manage_settings', 'system_settings'], false);
        
        $restaurantId = $this->getRestaurantId();
        if (!$restaurantId) {
            header('Location: ' . BASE_URL . '/?req=staff&action=dashboard');
            exit;
        }
        
        try {
            $query = "SELECT * FROM restaurants WHERE uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $settingsQuery = "SELECT setting_key, setting_value FROM restaurant_settings WHERE restaurant_uuid = ?";
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
                
            case 'get_waiter_calls':
                Permission::require('view_tables');
                $this->apiGetWaiterCalls($restaurantId);
                break;
                
            case 'get_waiter_calls_stats':
                Permission::require('view_tables');
                $this->apiGetWaiterCallsStats($restaurantId);
                break;
                
            case 'update_waiter_call_status':
                Permission::require('view_tables');
                $this->apiUpdateWaiterCallStatus($restaurantId);
                break;
                
            case 'assign_waiter_call':
                Permission::require('view_tables');
                $this->apiAssignWaiterCall($restaurantId);
                break;
                
            case 'get_waiter_calls_count':
                Permission::require('view_tables');
                $this->apiGetWaiterCallsCount($restaurantId);
                break;
                
            case 'get_onshift_waiters':
                Permission::require('view_tables');
                $this->apiGetOnshiftWaiters($restaurantId);
                break;
                
            case 'get_menu_data':
                Permission::require('view_menu');
                $this->apiGetMenuData($restaurantId);
                break;
                
            case 'export_menu':
                Permission::require('view_menu');
                $this->apiExportMenu($restaurantId);
                break;
                
            case 'import_menu':
                Permission::require('manage_menu');
                $this->apiImportMenu($restaurantId);
                break;
                
            default:
                echo json_encode(['status' => 'FAIL', 'message' => 'Unknown API action']);
                exit;
        }
    }
    
    private function apiRegenerateQRCodes($restaurantId) {
        try {
            // Suppress any output from QR generation
            ob_start();
            $this->regenerateQRCodes($restaurantId);
            ob_end_clean();
            
            echo json_encode(['status' => 'OK', 'message' => 'QR codes regenerated successfully']);
        } catch (Exception $e) {
            ob_end_clean(); // Clean buffer in case of error
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to regenerate QR codes: ' . $e->getMessage()]);
        }
    }
    
    private function apiGetMenuData($restaurantId) {
        try {
            // Get categories
            $catQuery = "SELECT * FROM menu_categories WHERE restaurant_uuid = ? ORDER BY display_order ASC, name ASC";
            $catStmt = $this->orderModel->db->prepare($catQuery);
            $catStmt->execute([$restaurantId]);
            $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items with category names
            $itemQuery = "SELECT mi.*, mc.name as category_name 
                          FROM menu_items mi 
                          LEFT JOIN menu_categories mc ON mi.category_uuid = mc.uuid 
                          WHERE mi.restaurant_uuid = ? 
                          ORDER BY mi.display_order ASC, mi.name ASC";
            $itemStmt = $this->orderModel->db->prepare($itemQuery);
            $itemStmt->execute([$restaurantId]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'OK',
                'categories' => $categories,
                'items' => $items
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to fetch menu data: ' . $e->getMessage()]);
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
            $uuid = UUIDHelper::generate();
            $query = "INSERT INTO menu_categories (uuid, restaurant_uuid, name, description, display_order) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$uuid, $restaurantId, $name, $description, $displayOrder]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Category created', 'uuid' => $uuid]);
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
                     WHERE uuid = ? AND restaurant_uuid = ?";
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
            // Check if category has items and belongs to restaurant
            $checkQuery = "SELECT COUNT(*) FROM menu_items mi 
                          INNER JOIN menu_categories mc ON mi.category_uuid = mc.uuid
                          WHERE mi.category_uuid = ? AND mc.restaurant_uuid = ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$id, $restaurantId]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete category with menu items']);
                exit;
            }
            
            $query = "DELETE FROM menu_categories WHERE uuid = ? AND restaurant_uuid = ?";
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
        
        // Check menu item limit
        $limitCheck = $this->subscriptionManager->checkLimit($restaurantId, 'menu_items');
        if (!$limitCheck['allowed']) {
            echo json_encode([
                'status' => 'LIMIT_REACHED',
                'message' => $limitCheck['message'],
                'current' => $limitCheck['current'],
                'limit' => $limitCheck['limit']
            ]);
            exit;
        }
        
        // Collect input data
        $categoryId = $_POST['category_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? '';
        $preparationTime = $_POST['preparation_time'] ?? '';
        $isAvailable = $_POST['is_available'] ?? '1';
        $displayOrder = $_POST['display_order'] ?? '0';
        
        // Server-side validation (matches frontend validation)
        $rules = [
            'category_id' => ['required' => true, 'integer' => true],
            'name' => ['required' => true, 'length' => [1, 200]],
            'price' => ['required' => true, 'range' => [0, 9999999]],
            'preparation_time' => ['required' => true, 'range' => [1, 180]]
        ];
        
        $data = [
            'category_id' => $categoryId,
            'name' => $name,
            'price' => $price,
            'preparation_time' => $preparationTime
        ];
        
        $errors = ValidationHelper::validateMultiple($rules, $data);
        
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'FAIL', 
                'message' => 'Validation failed: ' . implode(', ', $errors),
                'errors' => $errors
            ]);
            exit;
        }
        
        // Validate and handle image upload
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 1 * 1024 * 1024; // 1MB
            
            $fileError = ValidationHelper::file($_FILES['image'], $maxSize, $allowedTypes, 'Image');
            if ($fileError) {
                echo json_encode(['status' => 'FAIL', 'message' => $fileError]);
                exit;
            }
            
            $imageUrl = $this->handleImageUpload($_FILES['image'], $restaurantId);
            if ($imageUrl === false) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Image upload failed. Please ensure image is under 1MB and in JPEG/PNG/WebP format.']);
                exit;
            }
        }
        
        // Sanitize inputs
        $name = ValidationHelper::sanitize($name);
        $description = ValidationHelper::sanitize($description);
        $categoryId = intval($categoryId);
        $price = floatval($price);
        $preparationTime = intval($preparationTime);
        $isAvailable = intval($isAvailable);
        $displayOrder = intval($displayOrder);
        
        try {
            $uuid = UUIDHelper::generate();
            $query = "INSERT INTO menu_items (uuid, restaurant_uuid, category_uuid, name, description, price, preparation_time, is_available, display_order, image_url) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$uuid, $restaurantId, $categoryId, $name, $description, $price, $preparationTime, $isAvailable, $displayOrder, $imageUrl]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Menu item created', 'uuid' => $uuid]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to create menu item: ' . $e->getMessage()]);
        }
    }
    
    private function apiUpdateMenuItem($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        // Collect input data
        $id = $_POST['id'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? '';
        $preparationTime = $_POST['preparation_time'] ?? '';
        $isAvailable = $_POST['is_available'] ?? '1';
        $displayOrder = $_POST['display_order'] ?? '0';
        
        // Server-side validation (matches frontend validation)
        $rules = [
            'id' => ['required' => true, 'integer' => true],
            'category_id' => ['required' => true, 'integer' => true],
            'name' => ['required' => true, 'length' => [1, 200]],
            'price' => ['required' => true, 'range' => [0, 9999999]],
            'preparation_time' => ['required' => true, 'range' => [1, 180]]
        ];
        
        $data = [
            'id' => $id,
            'category_id' => $categoryId,
            'name' => $name,
            'price' => $price,
            'preparation_time' => $preparationTime
        ];
        
        $errors = ValidationHelper::validateMultiple($rules, $data);
        
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'FAIL', 
                'message' => 'Validation failed: ' . implode(', ', $errors),
                'errors' => $errors
            ]);
            exit;
        }
        
        // Handle image upload
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 1 * 1024 * 1024; // 1MB
            
            $fileError = ValidationHelper::file($_FILES['image'], $maxSize, $allowedTypes, 'Image');
            if ($fileError) {
                echo json_encode(['status' => 'FAIL', 'message' => $fileError]);
                exit;
            }
            
            // Get current image to delete later if needed
            $currentQuery = "SELECT image_url FROM menu_items WHERE uuid = ? AND restaurant_uuid = ?";
            $currentStmt = $this->orderModel->db->prepare($currentQuery);
            $currentStmt->execute([intval($id), $restaurantId]);
            $currentImage = $currentStmt->fetchColumn();
            
            $imageUrl = $this->handleImageUpload($_FILES['image'], $restaurantId);
            if ($imageUrl === false) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Image upload failed. Please ensure image is under 1MB and in JPEG/PNG/WebP format.']);
                exit;
            }
            
            // Delete old image if exists
            if ($currentImage && file_exists($currentImage)) {
                @unlink($currentImage);
            }
        } else {
            // Keep existing image
            $currentQuery = "SELECT image_url FROM menu_items WHERE uuid = ? AND restaurant_uuid = ?";
            $currentStmt = $this->orderModel->db->prepare($currentQuery);
            $currentStmt->execute([intval($id), $restaurantId]);
            $imageUrl = $currentStmt->fetchColumn();
        }
        
        // Sanitize inputs
        $name = ValidationHelper::sanitize($name);
        $description = ValidationHelper::sanitize($description);
        $id = intval($id);
        $categoryId = intval($categoryId);
        $price = floatval($price);
        $preparationTime = intval($preparationTime);
        $isAvailable = intval($isAvailable);
        $displayOrder = intval($displayOrder);
        
        try {
            $query = "UPDATE menu_items SET category_uuid = ?, name = ?, description = ?, price = ?, 
                     preparation_time = ?, is_available = ?, display_order = ?, image_url = ? 
                     WHERE uuid = ? AND restaurant_uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$categoryId, $name, $description, $price, $preparationTime, $isAvailable, $displayOrder, $imageUrl, $id, $restaurantId]);
            
            // Regenerate QR codes after menu change
            $this->regenerateQRCodes($restaurantId);
            
            echo json_encode(['status' => 'OK', 'message' => 'Menu item updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update menu item: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle image upload for menu items
     * Validates size, compresses and resizes images
     */
    private function handleImageUpload($file, $restaurantId) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            error_log("[IMAGE UPLOAD] GD extension not enabled. Skipping image compression.");
            // Save original file without compression
            return $this->saveOriginalImage($file, $restaurantId);
        }
        
        // Configuration - Reduced limits for lower storage usage
        $maxFileSize = 1 * 1024 * 1024; // 1MB max upload (will be compressed much smaller)
        $maxWidth = 600; // Max width in pixels (reduced from 800)
        $maxHeight = 600; // Max height in pixels (reduced from 800)
        $jpegQuality = 75; // JPEG compression quality (reduced from 85 for smaller files)
        $targetMaxSize = 100 * 1024; // Target max 100KB per image after compression
        
        $uploadDir = __DIR__ . '/../../assets/images/menu/' . $restaurantId . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file size (before processing)
        if ($file['size'] > $maxFileSize) {
            error_log("[IMAGE UPLOAD] File too large: " . round($file['size'] / 1024 / 1024, 2) . "MB (max 1MB)");
            return false;
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("[IMAGE UPLOAD] Invalid file type: $mimeType");
            return false;
        }
        
        // Load image based on type
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $sourceImage = @imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($file['tmp_name']);
                break;
        }
        
        if (!$sourceImage) {
            error_log("[IMAGE UPLOAD] Failed to create image resource");
            return false;
        }
        
        // Get original dimensions
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        
        // Calculate new dimensions (maintain aspect ratio)
        $newWidth = $origWidth;
        $newHeight = $origHeight;
        
        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $newWidth = round($origWidth * $ratio);
            $newHeight = round($origHeight * $ratio);
        }
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        
        // Generate filename (always save as JPEG for better compression)
        $filename = uniqid('menu_', true) . '.jpg';
        $filepath = $uploadDir . $filename;
        
        // Save as JPEG with aggressive compression to meet target size
        $quality = $jpegQuality;
        $saved = false;
        
        // Try progressively lower quality until file is under target size
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $saved = imagejpeg($resizedImage, $filepath, $quality);
            
            if ($saved) {
                $finalSize = filesize($filepath);
                
                // If file is under target size or quality is already very low, stop
                if ($finalSize <= $targetMaxSize || $quality <= 50) {
                    error_log("[IMAGE UPLOAD] Success: {$origWidth}x{$origHeight} → {$newWidth}x{$newHeight}, Size: " . round($finalSize / 1024, 2) . "KB (quality: $quality)");
                    break;
                }
                
                // Reduce quality for next attempt
                $quality -= 10;
                error_log("[IMAGE UPLOAD] Attempt $attempt: " . round($finalSize / 1024, 2) . "KB > target, retrying with quality $quality");
            }
        }
        
        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        if ($saved) {
            return 'assets/images/menu/' . $restaurantId . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Save original image without GD compression (fallback when GD is not available)
     */
    private function saveOriginalImage($file, $restaurantId) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $maxFileSize = 500 * 1024; // 500KB max for original files (stricter since no compression)
        
        // Validate file size
        if ($file['size'] > $maxFileSize) {
            error_log("[IMAGE UPLOAD] File too large: " . round($file['size'] / 1024, 2) . "KB (max 500KB without GD)");
            return false;
        }
        
        // Get file MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("[IMAGE UPLOAD] Invalid file type: $mimeType");
            return false;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = 'assets/images/menu/' . $restaurantId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('menu_') . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("[IMAGE UPLOAD] Original file saved (GD not available): " . round($file['size'] / 1024, 2) . "KB");
            return 'assets/images/menu/' . $restaurantId . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Generate QR code for a specific table
     */
    private function generateTableQRCode($tableId, $tableNumber, $restaurantId) {
        try {
            require_once __DIR__ . '/../core/QRCodeGenerator.php';
            
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
            require_once __DIR__ . '/../core/QRCodeGenerator.php';
            
            // Get restaurant slug
            $query = "SELECT slug FROM restaurants WHERE uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $restaurantSlug = $stmt->fetchColumn();
            
            if (!$restaurantSlug) {
                return;
            }
            
            // Get all tables for this restaurant (get qr_code or use id)
            $tablesQuery = "SELECT id, table_number, COALESCE(qr_code, id) as qr_code 
                           FROM restaurant_tables 
                           WHERE restaurant_uuid = ?";
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
            $imageQuery = "SELECT image_url FROM menu_items WHERE uuid = ? AND restaurant_uuid = ?";
            $imageStmt = $this->orderModel->db->prepare($imageQuery);
            $imageStmt->execute([$id, $restaurantId]);
            $imageUrl = $imageStmt->fetchColumn();
            
            $query = "DELETE FROM menu_items WHERE uuid = ? AND restaurant_uuid = ?";
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
        
        // Check table limit (subscription-based)
        $limitCheck = $this->subscriptionManager->checkLimit($restaurantId, 'tables');
        if (!$limitCheck['allowed']) {
            echo json_encode([
                'status' => 'LIMIT_REACHED',
                'message' => $limitCheck['message'],
                'current' => $limitCheck['current'],
                'limit' => $limitCheck['limit']
            ]);
            exit;
        }
        
        $tableNumber = $this->sanitize($_POST['table_number'] ?? '');
        $seats = intval($_POST['seats'] ?? $_POST['capacity'] ?? 4);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if (empty($tableNumber)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Table number required']);
            exit;
        }
        
        try {
            // Check if table number already exists
            $checkQuery = "SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_uuid = ? AND table_number = ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$restaurantId, $tableNumber]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Table number already exists']);
                exit;
            }
            
            // Generate unique QR code for table
            $uuid = UUIDHelper::generate();
            $qrCode = 'T' . $restaurantId . 'T' . uniqid();
            
            $query = "INSERT INTO restaurant_tables (uuid, restaurant_uuid, table_number, qr_code, seats, status) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$uuid, $restaurantId, $tableNumber, $qrCode, $seats, $status]);
            
            // Generate QR code image for new table (suppress any output)
            ob_start();
            $this->generateTableQRCode($uuid, $tableNumber, $restaurantId);
            ob_end_clean();
            
            echo json_encode(['status' => 'OK', 'message' => 'Table created', 'uuid' => $uuid]);
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
        $seats = intval($_POST['seats'] ?? $_POST['capacity'] ?? 4);
        $status = $this->sanitize($_POST['status'] ?? 'available');
        
        if ($id <= 0 || empty($tableNumber)) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $query = "UPDATE restaurant_tables SET table_number = ?, seats = ?, status = ? 
                     WHERE uuid = ? AND restaurant_uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$tableNumber, $seats, $status, $id, $restaurantId]);
            
            // Regenerate QR code if table number changed
            $oldTableQuery = "SELECT table_number FROM restaurant_tables WHERE uuid = ?";
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
            $checkQuery = "SELECT COUNT(*) FROM orders WHERE table_uuid = ? AND status NOT IN ('completed', 'cancelled')";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete table with active orders']);
                exit;
            }
            
            $query = "DELETE FROM restaurant_tables WHERE uuid = ? AND restaurant_uuid = ?";
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
        
        // Check user limit
        $limitCheck = $this->subscriptionManager->checkLimit($restaurantId, 'users');
        if (!$limitCheck['allowed']) {
            echo json_encode([
                'status' => 'LIMIT_REACHED',
                'message' => $limitCheck['message'],
                'current' => $limitCheck['current'],
                'limit' => $limitCheck['limit']
            ]);
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
            $uuid = UUIDHelper::generate();
            
            $query = "INSERT INTO staff_users (uuid, restaurant_uuid, username, password_hash, full_name, email, phone, role, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$uuid, $restaurantId, $username, $passwordHash, $fullName, $email, $phone, $role]);
            
            echo json_encode(['status' => 'OK', 'message' => 'Staff user created', 'uuid' => $uuid]);
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
            $checkQuery = "SELECT COUNT(*) FROM staff_users WHERE username = ? AND uuid != ?";
            $stmt = $this->orderModel->db->prepare($checkQuery);
            $stmt->execute([$username, $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Username already exists']);
                exit;
            }
            
            $query = "UPDATE staff_users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, is_active = ? 
                     WHERE uuid = ? AND restaurant_uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$username, $fullName, $email, $phone, $role, $isActive, $id, $restaurantId]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pwdQuery = "UPDATE staff_users SET password_hash = ? WHERE uuid = ?";
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
        if ($id == $_SESSION['staff_user']['uuid']) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Cannot delete your own account']);
            exit;
        }
        
        try {
            $query = "DELETE FROM staff_users WHERE uuid = ? AND restaurant_uuid = ?";
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
                                INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                                INNER JOIN order_items oi ON o.uuid = oi.order_uuid
                                WHERE t.restaurant_uuid = ? AND DATE(o.created_at) = ? AND o.status = 'completed'";
                $stmt = $this->orderModel->db->prepare($revenueQuery);
                $stmt->execute([$restaurantId, $today]);
                $stats['today_revenue'] = floatval($stmt->fetchColumn() ?: 0);
                
                // Today's cash transactions (for cashier dashboard)
                $transQuery = "SELECT COUNT(*) FROM cash_transactions ct
                              WHERE ct.restaurant_uuid = ? AND DATE(ct.created_at) = ?";
                $stmt = $this->orderModel->db->prepare($transQuery);
                $stmt->execute([$restaurantId, $today]);
                $stats['today_orders'] = intval($stmt->fetchColumn() ?: 0);
                
                // Active tables
                $tablesQuery = "SELECT COUNT(*) FROM restaurant_tables WHERE restaurant_uuid = ? AND status = 'occupied'";
                $stmt = $this->orderModel->db->prepare($tablesQuery);
                $stmt->execute([$restaurantId]);
                $stats['active_tables'] = intval($stmt->fetchColumn() ?: 0);
                
                // Pending orders
                $pendingQuery = "SELECT COUNT(*) FROM orders o
                                INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                                WHERE t.restaurant_uuid = ? AND o.status IN ('pending', 'confirmed', 'preparing')";
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
            $query = "SELECT o.*, t.table_number, t.restaurant_uuid
                     FROM orders o
                     INNER JOIN restaurant_tables t ON o.table_uuid = t.uuid
                     WHERE o.uuid = ? AND t.restaurant_uuid = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId, $restaurantId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode(['status' => 'FAIL', 'message' => 'Order not found']);
                exit;
            }
            
            // Get order items with item-level status
            $itemsQuery = "SELECT oi.*, mi.name, mi.image_url, mc.name as category, oi.status as item_status
                          FROM order_items oi
                          LEFT JOIN menu_items mi ON oi.menu_item_uuid = mi.uuid
                          LEFT JOIN menu_categories mc ON mi.category_uuid = mc.uuid
                          WHERE oi.order_uuid = ?
                          ORDER BY oi.uuid";
            $itemsStmt = $this->orderModel->db->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $order['items'] = $items;
            
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
            $query = "UPDATE restaurants SET name = ?, email = ?, phone = ?, address = ? WHERE uuid = ?";
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
     * Get waiter calls with filters
     */
    private function apiGetWaiterCalls($restaurantId) {
        $status = $_GET['status'] ?? 'pending';
        $type = $_GET['type'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            $query = "SELECT wc.*, rt.table_number, su.full_name as assigned_name 
                     FROM waiter_calls wc 
                     LEFT JOIN restaurant_tables rt ON wc.table_uuid = rt.uuid 
                     LEFT JOIN staff_users su ON wc.assigned_to_uuid = su.uuid 
                     WHERE wc.restaurant_uuid = ? 
                     AND DATE(wc.created_at) = ?";
            
            $params = [$restaurantId, $date];
            
            if ($status !== 'all') {
                $query .= " AND wc.status = ?";
                $params[] = $status;
            }
            
            if (!empty($type)) {
                $query .= " AND wc.request_type = ?";
                $params[] = $type;
            }
            
            if (!empty($priority)) {
                $query .= " AND wc.priority = ?";
                $params[] = $priority;
            }
            
            $query .= " ORDER BY 
                       CASE wc.priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 WHEN 'low' THEN 3 END,
                       wc.created_at DESC";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute($params);
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'OK', 'calls' => $calls]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get waiter calls: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get waiter calls statistics
     */
    private function apiGetWaiterCallsStats($restaurantId) {
        try {
            $today = date('Y-m-d');
            
            $query = "SELECT 
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END) as acknowledged,
                     SUM(CASE WHEN status = 'completed' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as completed,
                     SUM(CASE WHEN status = 'cancelled' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as cancelled
                     FROM waiter_calls 
                     WHERE restaurant_uuid = ?";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$today, $today, $restaurantId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'OK', 'stats' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get stats: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update waiter call status
     */
    private function apiUpdateWaiterCallStatus($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $callId = intval($_POST['call_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($callId <= 0 || !in_array($status, ['pending', 'acknowledged', 'completed', 'cancelled'])) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $updateFields = ['status' => $status];
            
            // Auto-assign to current user when acknowledging
            if ($status === 'acknowledged') {
                $updateFields['assigned_to_uuid'] = $_SESSION['staff_uuid'];
                $updateFields['assigned_at'] = date('Y-m-d H:i:s');
            }
            
            // Set completed timestamp
            if ($status === 'completed') {
                $updateFields['completed_at'] = date('Y-m-d H:i:s');
            }
            
            $setClause = [];
            $params = [];
            foreach ($updateFields as $field => $value) {
                $setClause[] = "$field = ?";
                $params[] = $value;
            }
            $params[] = $callId;
            $params[] = $restaurantId;
            
            $query = "UPDATE waiter_calls SET " . implode(', ', $setClause) . " 
                     WHERE uuid = ? AND restaurant_uuid = ?";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute($params);
            
            echo json_encode(['status' => 'OK', 'message' => 'Call status updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to update call: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Assign waiter call to staff
     */
    private function apiAssignWaiterCall($restaurantId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid method']);
            exit;
        }
        
        $callId = intval($_POST['call_id'] ?? 0);
        $waiterId = intval($_POST['waiter_id'] ?? 0);
        
        if ($callId <= 0 || $waiterId <= 0) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $query = "UPDATE waiter_calls 
                     SET assigned_to_uuid = ?, assigned_at = NOW(), status = 'acknowledged' 
                     WHERE uuid = ? AND restaurant_uuid = ?";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$waiterId, $callId, $restaurantId]);
            
            echo json_encode(['status' => 'OK', 'message' => 'Call assigned to waiter']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to assign call: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get count of pending waiter calls for real-time notifications
     */
    private function apiGetWaiterCallsCount($restaurantId) {
        try {
            $query = "SELECT COUNT(*) as count FROM waiter_calls 
                     WHERE restaurant_uuid = ? AND status = 'pending'";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'OK', 'count' => intval($result['count'])]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get call count: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get list of on-shift waiters for assignment
     */
    private function apiGetOnshiftWaiters($restaurantId) {
        try {
            $query = "SELECT DISTINCT su.uuid, su.full_name, su.role
                     FROM staff_users su
                     INNER JOIN staff_shifts ss ON su.uuid = ss.staff_uuid
                     WHERE su.restaurant_uuid = ? 
                     AND su.role = 'waiter'
                     AND su.status = 'active'
                     AND ss.clock_out IS NULL
                     AND DATE(ss.clock_in) = CURDATE()
                     ORDER BY su.full_name";
            
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$restaurantId]);
            $waiters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'OK', 'waiters' => $waiters]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Failed to get waiters: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Notify waiter about assignment to order
     */
    private function notifyWaiterAssignment($restaurantId, $orderId, $waiterId) {
        try {
            // Get order and table details
            $query = "SELECT o.order_number, t.table_number 
                      FROM orders o 
                      INNER JOIN restaurant_tables t ON o.table_id = t.id 
                      WHERE o.id = ?";
            $stmt = $this->orderModel->db->prepare($query);
            $stmt->execute([$orderId]);
            $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$orderData) {
                error_log('[ASSIGN WAITER] Order not found: ' . $orderId);
                return;
            }
            
            $orderNumber = $orderData['order_number'];
            $tableNumber = $orderData['table_number'];
            
            // Get manager name who assigned
            $managerName = $_SESSION['staff_user']['full_name'] ?? 'Manager';
            
            $message = "👤 You have been assigned to an order!\nOrder: $orderNumber\nTable: $tableNumber\nAssigned by: $managerName";
            
            // Create notification for the waiter
            $notifData = [
                'restaurant_id' => $restaurantId,
                'user_id' => $waiterId,
                'type' => 'waiter_assignment',
                'title' => "New Assignment - Table $tableNumber",
                'message' => $message,
                'data' => json_encode([
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'table_number' => $tableNumber,
                    'assigned_by' => $managerName,
                    'sound' => true,
                    'sound_duration' => 5000 // 5 seconds
                ]),
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $insertQuery = "INSERT INTO notifications (restaurant_id, user_id, type, title, message, data, is_read, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->orderModel->db->prepare($insertQuery);
            $insertStmt->execute([
                $notifData['restaurant_id'],
                $notifData['user_id'],
                $notifData['type'],
                $notifData['title'],
                $notifData['message'],
                $notifData['data'],
                $notifData['is_read'],
                $notifData['created_at']
            ]);
            
            error_log("[ASSIGN WAITER] Notification sent to waiter ID: $waiterId");
            
        } catch (PDOException $e) {
            error_log('[ASSIGN WAITER] Failed to send notification: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Export menu data to CSV format
    private function apiExportMenu($restaurantId) {
        try {
            // Get categories
            $catQuery = "SELECT * FROM menu_categories WHERE restaurant_uuid = ? ORDER BY display_order ASC, name ASC";
            $catStmt = $this->orderModel->db->prepare($catQuery);
            $catStmt->execute([$restaurantId]);
            $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items
            $itemQuery = "SELECT * FROM menu_items WHERE restaurant_uuid = ? ORDER BY category_uuid, display_order ASC, name ASC";
            $itemStmt = $this->orderModel->db->prepare($itemQuery);
            $itemStmt->execute([$restaurantId]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'OK',
                'data' => [
                    'categories' => $categories,
                    'items' => $items
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'FAIL', 'message' => 'Export failed: ' . $e->getMessage()]);
        }
    }
    
    // Import menu data from CSV
    private function apiImportMenu($restaurantId) {
        try {
            if (!isset($_POST['csv_data']) || empty($_POST['csv_data'])) {
                echo json_encode(['status' => 'FAIL', 'message' => 'No data provided']);
                return;
            }
            
            $csvData = $_POST['csv_data'];
            $lines = explode("\n", $csvData);
            
            if (count($lines) < 2) {
                echo json_encode(['status' => 'FAIL', 'message' => 'File is empty or invalid']);
                return;
            }
            
            // Skip header row
            array_shift($lines);
            
            $categoriesCreated = 0;
            $itemsCreated = 0;
            $errors = [];
            $categoryCache = [];
            
            $this->orderModel->db->beginTransaction();
            
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $row = $this->parseCSVLine($line);
                
                if (count($row) < 3) continue; // Need at least category name and item name
                
                $categoryName = trim($row[0]);
                $categoryDesc = trim($row[1] ?? '');
                $itemName = trim($row[2] ?? '');
                $itemDesc = trim($row[3] ?? '');
                $price = floatval($row[4] ?? 0);
                $imageUrl = trim($row[5] ?? '');
                $available = strtolower(trim($row[6] ?? 'yes')) === 'yes' ? 1 : 0;
                
                // Skip if no category name
                if (empty($categoryName)) {
                    $errors[] = "Row " . ($lineNum + 2) . ": Missing category name";
                    continue;
                }
                
                // Get or create category
                if (!isset($categoryCache[$categoryName])) {
                    // Check if category exists
                    $catQuery = "SELECT uuid FROM menu_categories WHERE restaurant_uuid = ? AND name = ?";
                    $catStmt = $this->orderModel->db->prepare($catQuery);
                    $catStmt->execute([$restaurantId, $categoryName]);
                    $existingCat = $catStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingCat) {
                        $categoryCache[$categoryName] = $existingCat['uuid'];
                    } else {
                        // Create new category
                        $catUuid = UUIDHelper::generate();
                        $insertCat = "INSERT INTO menu_categories (uuid, restaurant_uuid, name, description, display_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                        $insertCatStmt = $this->orderModel->db->prepare($insertCat);
                        $insertCatStmt->execute([$catUuid, $restaurantId, $categoryName, $categoryDesc, 0]);
                        $categoryCache[$categoryName] = $catUuid;
                        $categoriesCreated++;
                    }
                }
                
                $categoryId = $categoryCache[$categoryName];
                
                // Skip if no item name
                if (empty($itemName)) continue;
                
                // Check if item exists
                $itemQuery = "SELECT uuid FROM menu_items WHERE restaurant_uuid = ? AND name = ? AND category_uuid = ?";
                $itemStmt = $this->orderModel->db->prepare($itemQuery);
                $itemStmt->execute([$restaurantId, $itemName, $categoryId]);
                $existingItem = $itemStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingItem) {
                    // Update existing item
                    $updateItem = "UPDATE menu_items SET description = ?, price = ?, image = ?, is_available = ?, updated_at = NOW() WHERE uuid = ?";
                    $updateStmt = $this->orderModel->db->prepare($updateItem);
                    $updateStmt->execute([$itemDesc, $price, $imageUrl, $available, $existingItem['uuid']]);
                } else {
                    // Create new item
                    $itemUuid = UUIDHelper::generate();
                    $insertItem = "INSERT INTO menu_items (uuid, restaurant_uuid, category_uuid, name, description, price, image, is_available, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $insertItemStmt = $this->orderModel->db->prepare($insertItem);
                    $insertItemStmt->execute([$itemUuid, $restaurantId, $categoryId, $itemName, $itemDesc, $price, $imageUrl, $available, 0]);
                    $itemsCreated++;
                }
            }
            
            $this->orderModel->db->commit();
            
            echo json_encode([
                'status' => 'OK',
                'message' => 'Import completed successfully',
                'data' => [
                    'categories_created' => $categoriesCreated,
                    'items_created' => $itemsCreated
                ],
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            if ($this->orderModel->db->inTransaction()) {
                $this->orderModel->db->rollBack();
            }
            echo json_encode(['status' => 'FAIL', 'message' => 'Import failed: ' . $e->getMessage()]);
        }
    }
    
    // Parse CSV line handling quoted values
    private function parseCSVLine($line) {
        $cols = [];
        $col = '';
        $inQuotes = false;
        
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            
            if ($char === '"') {
                if ($inQuotes && isset($line[$i + 1]) && $line[$i + 1] === '"') {
                    $col .= '"';
                    $i++;
                } else {
                    $inQuotes = !$inQuotes;
                }
            } elseif ($char === ',' && !$inQuotes) {
                $cols[] = $col;
                $col = '';
            } else {
                $col .= $char;
            }
        }
        $cols[] = $col;
        
        return array_map('trim', $cols);
    }

    
    /**
     * Sanitize input
     */
    private function sanitize($input) {
        return trim($input);
    }
}
?>

