<?php
require_once 'src/controller.php';
require_once 'app/models/Menu.php';
require_once 'app/models/Order.php';

class MenuController extends Controller {
    
    private $menuModel;
    private $orderModel;
    
    public function __construct() {
        parent::__construct();
        $this->menuModel = new Menu();
        $this->orderModel = new Order();
        
        // Start session for table locking
        if (session_status() === PHP_SESSION_NONE) {
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
            $_SESSION['table_id'] = $tableInfo['id'];
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
            
            // Check if table is occupied by another session
            if (isset($_SESSION['table_id']) && $_SESSION['table_id'] != $tableInfo['id']) {
                $this->showError('Table Locked', 'You are already seated at another table. Please complete your current session first.');
                return;
            }
            
            // Check if this table is occupied by someone else
            if ($tableInfo['status'] === 'occupied' && !isset($_SESSION['table_id'])) {
                $this->showError('Table In Use', 'This table is currently occupied by other customers. Please check with the waiter.');
                return;
            }
            
            // Lock customer to this table
            $_SESSION['demo_mode'] = false;
            $_SESSION['table_id'] = $tableInfo['id'];
            $_SESSION['table_number'] = $tableInfo['table_number'];
            $_SESSION['qr_code'] = $qrCode;
            $_SESSION['session_start'] = time();
        }
        
        // Get menu data - filter by restaurant_id from table
        $restaurantId = $tableInfo['restaurant_id'] ?? null;
        $menuResult = $this->menuModel->getAllCategoriesWithItems($restaurantId);
        $menuData = $menuResult['status'] === 'OK' ? $menuResult['data'] : [];
        
        // Filter out unavailable items (keep them but they'll be disabled in view)
        // Items with is_available = 0 will show but be disabled
        
        // Get order history for this table
        $orderHistory = [];
        $historyResult = $this->orderModel->getTableOrderHistory($tableInfo['id'], 10);
        $orderHistory = $historyResult['status'] === 'OK' ? $historyResult['data'] : [];
        
        $data = [
            'title' => 'Menu - Smart Restaurant',
            'page' => 'menu',
            'table' => $tableInfo,
            'menu' => $menuData,
            'order_history' => $orderHistory,
            'demo_mode' => $demoMode,
            'session_locked' => true
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
