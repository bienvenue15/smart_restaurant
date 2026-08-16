<?php
$baseDir = dirname(dirname(__DIR__));
require_once $baseDir . '/src/controller.php';
require_once $baseDir . '/app/models/Menu.php';
require_once $baseDir . '/app/models/Order.php';
require_once $baseDir . '/app/core/SubscriptionManager.php';
require_once $baseDir . '/app/core/DeviceTableLock.php';

class MenuController extends Controller {
    
    private $menuModel;
    private $orderModel;
    private $subscriptionManager;
    private $deviceLock;
    
    public function __construct() {
        parent::__construct();
        $this->menuModel = new Menu();
        $this->orderModel = new Order();
        $this->subscriptionManager = SubscriptionManager::getInstance();
        $this->deviceLock = new DeviceTableLock();
        
        // Configure session ONLY if not already active
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = $_SERVER['DOCUMENT_ROOT'] . '/sessions';
            if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
                $sessionPath = sys_get_temp_dir() . '/restaurant_sessions';
            }
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0777, true);
            }
            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                ini_set('session.save_path', $sessionPath);
            }
            session_start();
        }
    }
    
    public function index() {
        // Check for demo mode
        $demoMode = isset($_GET['demo']) && $_GET['demo'] === '1';
        
        if ($demoMode) {
            // Demo mode - use default table for testing
            $tableNumber = isset($_GET['table']) ? $this->sanitize($_GET['table']) : 'T001';
            $result = $this->orderModel->getTableByNumber($tableNumber);
            
            if ($result['status'] !== 'OK') {
                $this->showError('Table Not Found', 'Demo table not found. Please use a valid table number (T001-T005).');
                return;
            }
            
            $tableInfo = $result['data'];
            
            // Set demo session (not locked to QR)
            $_SESSION['demo_mode'] = true;
            $_SESSION['restaurant_id'] = $tableInfo['restaurant_uuid'];
            $_SESSION['table_id'] = $tableInfo['uuid'];
            $_SESSION['table_number'] = $tableInfo['table_number'];
            $_SESSION['qr_code'] = $tableInfo['qr_code'];
            $_SESSION['session_start'] = time();
            
        } else {
            // Production mode - MUST have QR code
            $qrCode = isset($_GET['qr']) ? $this->sanitize($_GET['qr']) : null;
            
            if (!$qrCode) {
                $this->showError('Missing QR Code', 'Please scan the QR code on your table to access the menu.');
                return;
            }
            
            // Validate QR code and get table info
            $result = $this->orderModel->getTableByQRCode($qrCode);
            
            if ($result['status'] !== 'OK') {
                $this->showError('Invalid QR Code', 'The QR code you scanned is invalid. Please scan the QR code placed on your table.');
                return;
            }
            
            $tableInfo = $result['data'];
            
            // Generate device fingerprint
            $deviceFingerprint = DeviceTableLock::generateFingerprint();
            
            // CRITICAL: Check if device is already locked to another table OR if requested table is locked
            $accessValidation = $this->deviceLock->validateTableAccess(
                $deviceFingerprint, 
                $tableInfo['restaurant_uuid'], 
                $tableInfo['uuid']
            );
            
            if (!$accessValidation['allowed']) {
                error_log("[MENU] Access DENIED: " . $accessValidation['message']);
                $this->showError('Access Denied', $accessValidation['message']);
                return;
            }
            
            // Attempt to lock device to this table (2 hour initial duration, extends on activity)
            $lockResult = $this->deviceLock->lockDeviceToTable(
                $tableInfo['restaurant_uuid'],
                $tableInfo['uuid'],
                $deviceFingerprint,
                120 // 2 hours (120 minutes) - extends by 60 min on each activity
            );
            
            if (!$lockResult['success']) {
                error_log('[MENU] Device lock failed: ' . $lockResult['message']);
                // Show error if table is occupied or any other locking issue
                if (isset($lockResult['table_occupied']) || isset($lockResult['locked_table'])) {
                    $this->showError('Table Unavailable', $lockResult['message']);
                    return;
                }
            }
            
            // Store device fingerprint in session
            $_SESSION['device_fingerprint'] = $deviceFingerprint;
            $_SESSION['demo_mode'] = false;
            $_SESSION['restaurant_id'] = $tableInfo['restaurant_uuid'];
            $_SESSION['table_id'] = $tableInfo['uuid'];
            $_SESSION['table_number'] = $tableInfo['table_number'];
            $_SESSION['qr_code'] = $qrCode;
            $_SESSION['session_start'] = time();
            $_SESSION['device_lock_id'] = $lockResult['lock_id'] ?? null;
        }
        
        // Get menu data - filter by restaurant_uuid from table
        $restaurantId = $tableInfo['restaurant_uuid'] ?? null;
        
        // Check subscription status
        if ($restaurantId && !$this->subscriptionManager->isActive($restaurantId)) {
            $this->showError(
                'Service Unavailable', 
                'Online ordering is currently unavailable. Please contact restaurant staff for assistance.'
            );
            return;
        }
        
        $menuResult = $this->menuModel->getAllCategoriesWithItems($restaurantId);
        $menuData = $menuResult['status'] === 'OK' ? $menuResult['data'] : [];
        
        // Filter out unavailable items (keep them but they'll be disabled in view)
        // Items with is_available = 0 will show but be disabled
        
        // Get order history for this table
        $orderHistory = [];
        $historyResult = $this->orderModel->getTableOrderHistory($tableInfo['uuid'], 10);
        $orderHistory = $historyResult['status'] === 'OK' ? $historyResult['data'] : [];
        
        $data = [
            'title' => 'Menu - Smart Restaurant',
            'page' => 'menu',
            'table' => $tableInfo,
            'menu' => $menuData,
            'order_history' => $orderHistory,
            'demo_mode' => $demoMode,
            'session_locked' => true,
            'restaurantName' => $tableInfo['restaurant_name'] ?? 'Our Restaurant'
        ];
        
        $this->view->render('menu', $data);
    }
    
    /**
     * Show error page
     */
    private function showError($title, $message) {
        $data = [
            'title' => $title,
            'error_title' => $title,
            'error_message' => $message
        ];
        $this->view->render('error', $data);
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
?>
