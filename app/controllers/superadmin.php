<?php
/**
 * Super Admin Controller
 * Manages multiple restaurants (tenants)
 */

require_once 'src/controller.php';

// Load restaurant and middleware classes if not already loaded
if (!class_exists('Restaurant')) {
    require_once 'src/restaurant.php';
}
if (!class_exists('TenantMiddleware')) {
    require_once 'src/tenant_middleware.php';
}
if (!class_exists('SystemSettings')) {
    require_once __DIR__ . '/../core/SystemSettings.php';
}
if (!class_exists('MailService')) {
    require_once __DIR__ . '/../core/MailService.php';
}

class SuperadminController extends Controller {
    
    private $restaurantModel;
    private $dbConnection = null;
    
    public function __construct() {
        // DEBUG: Visible output to trace execution
        error_log('[SUPERADMIN CONSTRUCTOR] Starting constructor');
        
        // Start output buffering to prevent any premature output
        if (ob_get_level() == 0) {
            ob_start();
        }
        
        error_log('[SUPERADMIN CONSTRUCTOR] Calling parent::__construct()');
        try {
            parent::__construct();
            error_log('[SUPERADMIN CONSTRUCTOR] Parent constructor completed');
        } catch (Exception $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] ERROR in parent constructor: ' . $e->getMessage());
            throw $e;
        } catch (Error $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] FATAL ERROR in parent constructor: ' . $e->getMessage());
            throw $e;
        }
        
        error_log('[SUPERADMIN CONSTRUCTOR] Creating Restaurant model');
        try {
            $this->restaurantModel = new Restaurant();
            error_log('[SUPERADMIN CONSTRUCTOR] Restaurant model created');
        } catch (Exception $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] ERROR creating Restaurant: ' . $e->getMessage());
            throw $e;
        } catch (Error $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] FATAL ERROR creating Restaurant: ' . $e->getMessage());
            throw $e;
        }
        
        // Start session if not already started
        error_log('[SUPERADMIN CONSTRUCTOR] Starting session');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log('[SUPERADMIN CONSTRUCTOR] Session started, ID: ' . session_id());
        } else {
            error_log('[SUPERADMIN CONSTRUCTOR] Session already started, ID: ' . session_id());
        }
        
        error_log('[SUPERADMIN CONSTRUCTOR] Calling ensureSupportSchema');
        try {
            $this->ensureSupportSchema();
            error_log('[SUPERADMIN CONSTRUCTOR] ensureSupportSchema completed');
        } catch (Exception $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] ERROR in ensureSupportSchema: ' . $e->getMessage());
            error_log('[SUPERADMIN CONSTRUCTOR] ERROR stack: ' . $e->getTraceAsString());
            // Don't throw - this shouldn't prevent controller from working
        } catch (Error $e) {
            error_log('[SUPERADMIN CONSTRUCTOR] FATAL ERROR in ensureSupportSchema: ' . $e->getMessage());
            error_log('[SUPERADMIN CONSTRUCTOR] FATAL ERROR stack: ' . $e->getTraceAsString());
            // Don't throw - this shouldn't prevent controller from working
        }
        
        error_log('[SUPERADMIN CONSTRUCTOR] Constructor completed successfully');
    }
    
    /**
     * Check if current user is super admin
     */
    private function isSuperAdmin() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log session debugging info
        error_log('[SUPERADMIN AUTH] Checking authentication');
        error_log('[SUPERADMIN AUTH] Session ID: ' . session_id());
        error_log('[SUPERADMIN AUTH] Session status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
        error_log('[SUPERADMIN AUTH] Session data: ' . json_encode($_SESSION ?? []));
        error_log('[SUPERADMIN AUTH] Cookies received: ' . json_encode($_COOKIE ?? []));
        error_log('[SUPERADMIN AUTH] Request headers: ' . json_encode(getallheaders() ?? []));
        
        if (!isset($_SESSION['user_id'])) {
            error_log('[SUPERADMIN AUTH] No user_id in session - DENIED');
            return false;
        }
        
        // Super admin is identified by email or special role
        $email = $_SESSION['email'] ?? '';
        $role = $_SESSION['role'] ?? '';
        
        error_log('[SUPERADMIN AUTH] Session email: ' . $email);
        error_log('[SUPERADMIN AUTH] Session role: ' . $role);
        
        $isSuperAdmin = $email === 'superadmin@restaurant.com' || $role === 'super_admin';
        
        error_log('[SUPERADMIN AUTH] Is super admin: ' . ($isSuperAdmin ? 'YES' : 'NO'));
        
        return $isSuperAdmin;
    }
    
    /**
     * Route handler for actions
     */
    public function handleRequest() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $action = $_GET['action'] ?? '';
        
        // Debug logging
        error_log('[SUPERADMIN] handleRequest called with action: ' . $action);
        error_log('[SUPERADMIN] Session ID: ' . session_id());
        error_log('[SUPERADMIN] Session data: ' . json_encode($_SESSION ?? []));
        error_log('[SUPERADMIN] Request URI: ' . ($_SERVER['REQUEST_URI'] ?? ''));
        error_log('[SUPERADMIN] HTTP Accept: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'none'));
        
        // Allow login without authentication
        if ($action === 'login') {
            $this->login();
            return;
        }
        
        // Allow logout without authentication
        if ($action === 'logout') {
            $this->logout();
            return;
        }
        
        // Check authentication for all other actions
        $isAuthenticated = $this->isSuperAdmin();
        error_log('[SUPERADMIN] Authentication check result: ' . ($isAuthenticated ? 'PASSED' : 'FAILED'));
        
        if (!$isAuthenticated) {
            // Check if this is an AJAX/API request
            // Only treat as AJAX if explicitly requesting JSON or if Accept header ONLY contains JSON
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
            $isJsonOnly = strpos($acceptHeader, 'application/json') !== false && strpos($acceptHeader, 'text/html') === false;
            $isAjax = $isJsonOnly || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $hasFormatJson = isset($_GET['format']) && $_GET['format'] === 'json';
            
            error_log('[SUPERADMIN] Not authenticated.');
            error_log('[SUPERADMIN] Accept header: ' . $acceptHeader);
            error_log('[SUPERADMIN] isAjax: ' . ($isAjax ? 'yes' : 'no') . ', hasFormatJson: ' . ($hasFormatJson ? 'yes' : 'no'));
            
            if ($isAjax || $hasFormatJson) {
                // Return JSON for API requests
                error_log('[SUPERADMIN] Returning JSON error response');
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Access denied. Please login first.'], 403);
            } else {
                // Redirect to login for regular page loads
                error_log('[SUPERADMIN] Redirecting to login: ' . BASE_URL . '/?req=superadmin');
                
                // CRITICAL: Clear ALL output buffers before redirect
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // Also make sure no output was sent
                if (headers_sent($file, $line)) {
                    error_log('[SUPERADMIN] ERROR: Headers already sent! File: ' . $file . ', Line: ' . $line);
                    // Headers already sent, show error page instead
                    header('Content-Type: text/html; charset=utf-8');
                    echo "<!DOCTYPE html><html><head><title>Redirect Error</title></head><body>";
                    echo "<h1>Redirect Error</h1>";
                    echo "<p>Cannot redirect - headers already sent in " . htmlspecialchars($file) . " at line " . $line . ".</p>";
                    echo "<p>Please <a href='" . BASE_URL . "/?req=superadmin'>click here</a> to go to login.</p>";
                    echo "</body></html>";
                    exit;
                }
                
                header('Location: ' . BASE_URL . '/?req=superadmin', true, 302);
                exit;
            }
            return;
        }
        
        switch ($action) {
            case 'debug':
                // Simple debug endpoint - always works
                header('Content-Type: text/html; charset=utf-8');
                echo "<!DOCTYPE html><html><head><title>Debug</title></head><body>";
                echo "<h1>SuperAdmin Controller Debug</h1>";
                echo "<p>Controller is working!</p>";
                echo "<p>Session ID: " . session_id() . "</p>";
                echo "<p>Session Data: <pre>" . print_r($_SESSION, true) . "</pre></p>";
                echo "<p>Action: " . htmlspecialchars($action) . "</p>";
                echo "<p>Authenticated: " . ($this->isSuperAdmin() ? 'YES' : 'NO') . "</p>";
                echo "</body></html>";
                exit;
                
            case 'dashboard':
                error_log('[SUPERADMIN] Calling dashboard() method');
                try {
                    $this->dashboard();
                } catch (Throwable $e) {
                    error_log('[SUPERADMIN] ERROR in dashboard(): ' . $e->getMessage());
                    error_log('[SUPERADMIN] ERROR stack: ' . $e->getTraceAsString());
                    // Show error on page instead of blank
                    header('Content-Type: text/html; charset=utf-8');
                    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
                    echo "<h1>Error Loading Dashboard</h1>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    echo "</body></html>";
                    exit;
                }
                break;
                
            case 'list_restaurants':
                $this->listRestaurants();
                break;
                
            case 'get_restaurant':
                $this->getRestaurant();
                break;
                
            case 'create_restaurant':
                $this->createRestaurant();
                break;
                
            case 'edit_restaurant':
            case 'update_restaurant':
                $this->updateRestaurant();
                break;
                
            case 'delete_restaurant':
                $this->deleteRestaurant();
                break;
                
            case 'restaurant_stats':
                $this->getRestaurantStats();
                break;
                
            case 'toggle_status':
                $this->toggleStatus();
                break;
                
            case 'extend_subscription':
                $this->extendSubscription();
                break;
                
            case 'get_all_users':
                $this->getAllUsers();
                break;
                
            case 'create_user':
                $this->createUser();
                break;
                
            case 'edit_user':
            case 'update_user':
                $this->updateUser();
                break;
                
            case 'get_user_details':
                $this->getUserDetails();
                break;  
                
            case 'toggle_user_status':
                $this->toggleUserStatus();
                break;
                
            case 'reset_user_password':
                $this->resetUserPassword();
                break;
                
            case 'export_users':
                $this->exportUsers();
                break;
                
            case 'get_analytics_data':
                $this->getAnalyticsData();
                break;
                
            case 'get_revenue_data':
                $this->getRevenueData();
                break;
                
            case 'get_restaurant_performance':
                $this->getRestaurantPerformance();
                break;
                
            case 'export_report':
                $this->exportReport();
                break;
                
            case 'get_audit_log':
                $this->getAuditLog();
                break;
            case 'export_audit_log':
                $this->exportAuditLog();
                break;
                
            case 'get_support_overview':
                $this->getSupportOverview();
                break;
                
            case 'get_support_tickets':
                $this->getSupportTickets();
                break;
                
            case 'get_ticket_details':
                $this->getTicketDetails();
                break;
                
            case 'update_ticket_status':
                $this->updateTicketStatusEndpoint();
                break;
                
            case 'reply_ticket':
                $this->replyTicket();
                break;
                
            case 'get_support_messages':
                $this->getSupportMessages();
                break;
                
            case 'get_notifications':
                $this->getSystemNotifications();
                break;
                
            case 'mark_notification_read':
                $this->markNotificationRead();
                break;
                
            case 'get_system_settings':
                $this->getSystemSettings();
                break;
                
            case 'update_system_settings':
                $this->updateSystemSettings();
                break;
                
            case 'trigger_backup':
                $this->triggerBackup();
                break;
                
            case 'get_system_logs':
                $this->getSystemLogs();
                break;
                
            case 'get_system_status':
                $this->getSystemStatus();
                break;
                
            default:
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid action'], 400);
        }
    }
    
    /**
     * Display login form or route to actions
     */
    public function index() {
        error_log('[SUPERADMIN INDEX] index() method called');
        
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log('[SUPERADMIN INDEX] Session started in index(), ID: ' . session_id());
        }
        
        // Check if there's an action parameter  
        $action = $_GET['action'] ?? '';
        error_log('[SUPERADMIN INDEX] Action parameter: ' . ($action ?: 'empty'));
        
        // If there's an action, delegate to handleRequest
        if (!empty($action)) {
            error_log('[SUPERADMIN INDEX] Delegating to handleRequest()');
            try {
                $this->handleRequest();
            } catch (Exception $e) {
                error_log('[SUPERADMIN INDEX] EXCEPTION in handleRequest: ' . $e->getMessage());
                error_log('[SUPERADMIN INDEX] EXCEPTION stack: ' . $e->getTraceAsString());
                // Show error on page
                die("Error: " . $e->getMessage() . "<br>Stack: " . $e->getTraceAsString());
            } catch (Error $e) {
                error_log('[SUPERADMIN INDEX] FATAL ERROR in handleRequest: ' . $e->getMessage());
                error_log('[SUPERADMIN INDEX] FATAL ERROR stack: ' . $e->getTraceAsString());
                // Show error on page
                die("Fatal Error: " . $e->getMessage() . "<br>Stack: " . $e->getTraceAsString());
            } catch (Throwable $e) {
                error_log('[SUPERADMIN INDEX] THROWABLE in handleRequest: ' . $e->getMessage());
                error_log('[SUPERADMIN INDEX] THROWABLE stack: ' . $e->getTraceAsString());
                // Show error on page
                die("Error: " . $e->getMessage() . "<br>Stack: " . $e->getTraceAsString());
            }
            return;
        }
        
        error_log('[SUPERADMIN INDEX] No action, checking session');
        
        // No action - check if already logged in
        // But first, verify the session is valid for super admin
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            error_log('[SUPERADMIN INDEX] Session found - user_id: ' . ($_SESSION['user_id'] ?? 'none') . ', role: ' . ($_SESSION['role'] ?? 'none'));
            // Only proceed if actually super_admin
            if ($_SESSION['role'] === 'super_admin') {
                error_log('[SUPERADMIN INDEX] Role is super_admin, redirecting to dashboard');
                $redirectUrl = BASE_URL . '/?req=superadmin&action=dashboard';
                error_log('[SUPERADMIN INDEX] Redirect URL: ' . $redirectUrl);
                // Clear any output before redirect
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                error_log('[SUPERADMIN INDEX] Invalid role, destroying session');
                // Clear invalid session for super admin area
                session_destroy();
                session_start();
            }
        } else {
            error_log('[SUPERADMIN INDEX] No session found, showing login form');
        }
        
        // Show login form
        $viewPath = __DIR__ . '/../views/superadmin_login.php';
        error_log('[SUPERADMIN INDEX] Login view path: ' . $viewPath);
        error_log('[SUPERADMIN INDEX] Login view exists: ' . (file_exists($viewPath) ? 'yes' : 'no'));
        
        if (file_exists($viewPath)) {
            error_log('[SUPERADMIN INDEX] Including login view');
            try {
                include $viewPath;
                error_log('[SUPERADMIN INDEX] Login view included successfully');
            } catch (Exception $e) {
                error_log('[SUPERADMIN INDEX] ERROR including login view: ' . $e->getMessage());
                die("Error loading login form: " . $e->getMessage());
            } catch (Error $e) {
                error_log('[SUPERADMIN INDEX] FATAL ERROR including login view: ' . $e->getMessage());
                die("Fatal error loading login form: " . $e->getMessage());
            }
        } else {
            error_log('[SUPERADMIN INDEX] ERROR: Login view not found');
            die("Error: Login view not found at: " . $viewPath);
        }
    }
    
    /**
     * Handle login
     */
    private function login() {
        // Check if this is an AJAX/JSON request
        $isAjax = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'POST request required'], 405);
            } else {
                header('Location: ' . BASE_URL . '/?req=superadmin');
                exit;
            }
            return;
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            if ($isAjax) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Email and password are required'], 400);
            } else {
                $this->redirectToLoginWithError('Email and password are required');
            }
            return;
        }
        
        // Query database for super admin user
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM staff_users WHERE email = ? AND role = 'super_admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                if ($isAjax) {
                    $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid credentials or insufficient privileges'], 401);
                } else {
                    $this->redirectToLoginWithError('Invalid credentials or insufficient privileges');
                }
                return;
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                if ($isAjax) {
                    $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid credentials'], 401);
                } else {
                    $this->redirectToLoginWithError('Invalid credentials');
                }
                return;
            }
        } catch (PDOException $e) {
            if ($isAjax) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Database error: ' . $e->getMessage()], 500);
            } else {
                $this->redirectToLoginWithError('Database error: ' . $e->getMessage());
            }
            return;
        }
        
        // Set session variables - ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Force session cookie to be sent with proper settings
        $sessionParams = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            $sessionParams['lifetime'] ? time() + $sessionParams['lifetime'] : 0,
            $sessionParams['path'],
            $sessionParams['domain'],
            $sessionParams['secure'] ?? false,
            $sessionParams['httponly'] ?? true
        );
        
        $currentSessionId = session_id();
        
        // Log session info for debugging
        error_log('[SUPERADMIN LOGIN] Session started for user: ' . $user['email']);
        error_log('[SUPERADMIN LOGIN] Session ID: ' . $currentSessionId);
        error_log('[SUPERADMIN LOGIN] Session name: ' . session_name());
        error_log('[SUPERADMIN LOGIN] Session data: ' . json_encode($_SESSION));
        error_log('[SUPERADMIN LOGIN] Cookie params: ' . json_encode($sessionParams));
        
        if ($isAjax) {
            // Return JSON response for AJAX requests
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Login successful',
                'data' => [
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'session_id' => $currentSessionId,
                    'session_name' => session_name()
                ]
            ]);
        } else {
            // Redirect for form submissions
            $_SESSION['superadmin_success'] = 'Login successful! Redirecting...';
            header('Location: ' . BASE_URL . '/?req=superadmin&action=dashboard');
            exit;
        }
    }
    
    private function redirectToLoginWithError($message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['superadmin_error'] = $message;
        header('Location: ' . BASE_URL . '/?req=superadmin');
        exit;
    }
    
    /**
     * Handle logout
     */
    private function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session variables
        unset($_SESSION['user_id']);
        unset($_SESSION['email']);
        unset($_SESSION['role']);
        
        // Destroy session
        session_destroy();
        
        $this->sendResponse([
            'status' => 'OK',
            'message' => 'Logout successful'
        ]);
    }
    
    /**
     * Show main dashboard
     */
    private function dashboard() {
        // Check if this is an AJAX/JSON request
        $isAjax = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $hasFormatJson = isset($_GET['format']) && $_GET['format'] === 'json';
        
        error_log('[DASHBOARD METHOD] Dashboard method called. isAjax: ' . ($isAjax ? 'yes' : 'no') . ', hasFormatJson: ' . ($hasFormatJson ? 'yes' : 'no'));
        
        if ($isAjax || $hasFormatJson) {
            // Return JSON for API requests
            $this->sendResponse(['status' => 'OK', 'message' => 'Dashboard access granted']);
            return;
        }
        
        // Include dashboard view for page loads
        $viewPath = __DIR__ . '/../views/superadmin/dashboard.php';
        error_log('[DASHBOARD METHOD] View path: ' . $viewPath);
        error_log('[DASHBOARD METHOD] File exists: ' . (file_exists($viewPath) ? 'yes' : 'no'));
        
        if (!file_exists($viewPath)) {
            error_log('[DASHBOARD METHOD] ERROR: Dashboard view not found');
            die("Error: Dashboard view not found at: " . $viewPath);
        }
        
        try {
            error_log('[DASHBOARD METHOD] Including dashboard view...');
            include $viewPath;
            error_log('[DASHBOARD METHOD] Dashboard view included successfully');
            // Flush output after including view
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
        } catch (Exception $e) {
            error_log('[DASHBOARD METHOD] ERROR loading dashboard: ' . $e->getMessage());
            error_log('[DASHBOARD METHOD] ERROR stack trace: ' . $e->getTraceAsString());
            die("Error loading dashboard: " . $e->getMessage());
        } catch (Error $e) {
            error_log('[DASHBOARD METHOD] FATAL ERROR loading dashboard: ' . $e->getMessage());
            error_log('[DASHBOARD METHOD] FATAL ERROR stack trace: ' . $e->getTraceAsString());
            die("Fatal error loading dashboard: " . $e->getMessage());
        }
    }
    
    /**
     * List all restaurants
     */
    private function listRestaurants() {
        $activeOnly = ($_GET['active_only'] ?? 'false') === 'true';
        $restaurants = $this->restaurantModel->getAll($activeOnly);
        
        // Add statistics for each restaurant
        foreach ($restaurants as &$restaurant) {
            $stats = $this->restaurantModel->getStats($restaurant['id']);
            $restaurant['stats'] = $stats;
        }
        
        // If format=json requested, return JSON
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            $this->sendResponse([
                'status' => 'OK',
                'data' => $restaurants,
                'count' => count($restaurants)
            ]);
            return;
        }
        
        // Otherwise show dashboard UI
        include __DIR__ . '/../views/superadmin_dashboard.php';
    }
    
    /**
     * Get single restaurant details
     */
    private function getRestaurant() {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
            return;
        }
        
        $restaurant = $this->restaurantModel->getById($id);
        
        if (!$restaurant) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Restaurant not found'], 404);
            return;
        }
        
        // Add statistics and settings
        $restaurant['stats'] = $this->restaurantModel->getStats($id);
        $restaurant['settings'] = $this->restaurantModel->getSettings($id);
        
        $this->sendResponse([
            'status' => 'OK',
            'data' => $restaurant
        ]);
    }
    
    /**
     * Create new restaurant
     */
    private function createRestaurant() {
        // Show form for GET requests
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurant = null; // New restaurant, no existing data
            include __DIR__ . '/../views/superadmin/restaurant_form.php';
            return;
        }
        
        // Handle POST for creating
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        // Get JSON data
        $json = file_get_contents('php://input');
        $postData = json_decode($json, true) ?: $_POST;
        
        $data = [
            'name' => $this->sanitize($postData['name'] ?? ''),
            'email' => $this->sanitize($postData['email'] ?? ''),
            'phone' => $this->sanitize($postData['phone'] ?? ''),
            'address' => $this->sanitize($postData['address'] ?? ''),
            'slug' => $this->sanitize($postData['slug'] ?? ''),
            'subscription_plan' => $this->sanitize($postData['subscription_plan'] ?? 'trial'),
            'subscription_end' => $this->sanitize($postData['subscription_end'] ?? ''),
            'is_active' => intval($postData['is_active'] ?? 1),
            'max_tables' => intval($postData['max_tables'] ?? 20),
            'max_users' => intval($postData['max_users'] ?? 10)
        ];
        
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['slug'])) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Name, email, and slug are required'], 400);
            return;
        }
        
        // Set subscription start date
        $data['subscription_start'] = date('Y-m-d');
        
        // Set default subscription end if not provided
        if (empty($data['subscription_end'])) {
            $plan = $data['subscription_plan'];
            switch ($plan) {
                case 'trial':
                    $data['subscription_end'] = date('Y-m-d', strtotime('+30 days'));
                    break;
                case 'basic':
                case 'premium':
                case 'enterprise':
                    $data['subscription_end'] = date('Y-m-d', strtotime('+1 year'));
                    break;
                default:
                    $data['subscription_end'] = date('Y-m-d', strtotime('+30 days'));
            }
        }
        
        // Set default values
        $data['country'] = 'Rwanda';
        
        try {
            $restaurantId = $this->restaurantModel->create($data);
            
            // Create default admin user for the restaurant
            $this->createDefaultAdmin($restaurantId, $data['email'], $data['name']);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Restaurant created successfully',
                'restaurant_id' => $restaurantId,
                'slug' => $data['slug']
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to create restaurant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update restaurant
     */
    private function updateRestaurant() {
        // Show edit form for GET requests
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
                return;
            }
            
            $restaurant = $this->restaurantModel->getById($id);
            if (!$restaurant) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Restaurant not found'], 404);
                return;
            }
            
            include __DIR__ . '/../views/superadmin/restaurant_form.php';
            return;
        }
        
        // Handle POST for updating
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        // Get JSON data
        $json = file_get_contents('php://input');
        $postData = json_decode($json, true) ?: $_POST;
        
        $id = intval($postData['id'] ?? 0);
        
        if ($id <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
            return;
        }
        
        $data = [];
        $allowedFields = [
            'name', 'email', 'phone', 'address', 'slug',
            'subscription_plan', 'subscription_end', 'max_tables', 'max_users', 'is_active'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($postData[$field])) {
                if ($field === 'is_active' || $field === 'max_tables' || $field === 'max_users') {
                    $data[$field] = intval($postData[$field]);
                } else {
                    $data[$field] = $this->sanitize($postData[$field]);
                }
            }
        }
        
        try {
            $this->restaurantModel->update($id, $data);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Restaurant updated successfully'
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update restaurant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete restaurant (soft delete)
     */
    private function deleteRestaurant() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $hardDelete = ($_POST['hard_delete'] ?? 'false') === 'true';
        
        if ($id <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
            return;
        }
        
        try {
            if ($hardDelete) {
                // Permanent delete - removes all data
                $this->restaurantModel->hardDelete($id);
                $message = 'Restaurant permanently deleted';
            } else {
                // Soft delete - just marks as inactive
                $this->restaurantModel->delete($id);
                $message = 'Restaurant deactivated';
            }
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => $message
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to delete restaurant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get restaurant statistics
     */
    private function getRestaurantStats() {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
            return;
        }
        
        $stats = $this->restaurantModel->getStats($id);
        
        if (!$stats) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Restaurant not found'], 404);
            return;
        }
        
        $this->sendResponse([
            'status' => 'OK',
            'data' => $stats
        ]);
    }
    
    /**
     * Toggle restaurant active status
     */
    private function toggleStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid restaurant ID'], 400);
            return;
        }
        
        $restaurant = $this->restaurantModel->getById($id);
        if (!$restaurant) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Restaurant not found'], 404);
            return;
        }
        
        $newStatus = $restaurant['is_active'] ? 0 : 1;
        
        try {
            $this->restaurantModel->update($id, ['is_active' => $newStatus]);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => $newStatus ? 'Restaurant activated' : 'Restaurant deactivated',
                'is_active' => $newStatus
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Extend subscription
     */
    private function extendSubscription() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $months = intval($_POST['months'] ?? 1);
        
        if ($id <= 0 || $months <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid parameters'], 400);
            return;
        }
        
        $restaurant = $this->restaurantModel->getById($id);
        if (!$restaurant) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Restaurant not found'], 404);
            return;
        }
        
        $currentEnd = $restaurant['subscription_end'];
        $newEnd = date('Y-m-d', strtotime($currentEnd . " +$months months"));
        
        try {
            $this->restaurantModel->update($id, ['subscription_end' => $newEnd]);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => "Subscription extended by $months month(s)",
                'new_end_date' => $newEnd
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to extend subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create default admin user for new restaurant
     */
    private function createDefaultAdmin($restaurantId, $email, $restaurantName) {
        $query = "INSERT INTO staff_users (restaurant_id, username, email, password_hash, full_name, role, is_active) 
                  VALUES (?, ?, ?, ?, ?, 'admin', 1)";
        
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD
        );
        
        $stmt = $db->prepare($query);
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $username = strtolower(str_replace(' ', '', $restaurantName)) . '_admin';
        
        $stmt->execute([
            $restaurantId,
            $username,
            $email,
            $defaultPassword,
            $restaurantName . ' Admin'
        ]);
    }
    
    /**
     * Get all users across all restaurants
     */
    private function getAllUsers() {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            
            $query = "SELECT u.id, u.full_name as name, u.email, u.role, u.is_active, u.last_login, 
                             u.created_at, u.restaurant_id, r.name as restaurant_name 
                      FROM staff_users u 
                      LEFT JOIN restaurants r ON u.restaurant_id = r.id 
                      WHERE u.role != 'super_admin'
                      ORDER BY u.created_at DESC";
            
            $stmt = $db->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dates for display
            foreach ($users as &$user) {
                if ($user['last_login']) {
                    $user['last_login'] = date('Y-m-d H:i', strtotime($user['last_login']));
                }
                if ($user['created_at']) {
                    $user['created_at'] = date('Y-m-d H:i', strtotime($user['created_at']));
                }
            }
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $users,
                'count' => count($users)
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new user
     */
    private function createUser() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['full_name', 'email', 'username', 'role', 'restaurant_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                    ], 400);
                    return;
                }
            }
            
            // Validate role
            $validRoles = ['admin', 'manager', 'waiter', 'kitchen', 'cashier'];
            if (!in_array($data['role'], $validRoles)) {
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'Invalid role selected'
                ], 400);
                return;
            }
            
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            
            // Check if username already exists
            $checkQuery = "SELECT id FROM staff_users WHERE username = ?";
            $stmt = $db->prepare($checkQuery);
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'Username already exists'
                ], 400);
                return;
            }
            
            // Check if email already exists
            $checkQuery = "SELECT id FROM staff_users WHERE email = ?";
            $stmt = $db->prepare($checkQuery);
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'Email already exists'
                ], 400);
                return;
            }
            
            // Generate default password
            $defaultPassword = 'temp' . rand(1000, 9999) . strtoupper(substr(md5(time()), 0, 4));
            $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);
            
            // Insert new user
            $query = "INSERT INTO staff_users 
                      (restaurant_id, username, email, password_hash, full_name, phone, role, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['restaurant_id'],
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['full_name'],
                $data['phone'] ?? null,
                $data['role']
            ]);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'User created successfully',
                'user_id' => $db->lastInsertId(),
                'temp_password' => $defaultPassword
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update an existing user
     */
    private function updateUser() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id'])) {
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'User ID is required'
                ], 400);
                return;
            }
            
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            
            // Check if username already exists (excluding current user)
            if (!empty($data['username'])) {
                $checkQuery = "SELECT id FROM staff_users WHERE username = ? AND id != ?";
                $stmt = $db->prepare($checkQuery);
                $stmt->execute([$data['username'], $data['user_id']]);
                if ($stmt->fetch()) {
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => 'Username already exists'
                    ], 400);
                    return;
                }
            }
            
            // Check if email already exists (excluding current user)
            if (!empty($data['email'])) {
                $checkQuery = "SELECT id FROM staff_users WHERE email = ? AND id != ?";
                $stmt = $db->prepare($checkQuery);
                $stmt->execute([$data['email'], $data['user_id']]);
                if ($stmt->fetch()) {
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => 'Email already exists'
                    ], 400);
                    return;
                }
            }
            
            // Build update query dynamically
            $updates = [];
            $params = [];
            
            if (!empty($data['full_name'])) {
                $updates[] = 'full_name = ?';
                $params[] = $data['full_name'];
            }
            if (!empty($data['email'])) {
                $updates[] = 'email = ?';
                $params[] = $data['email'];
            }
            if (!empty($data['username'])) {
                $updates[] = 'username = ?';
                $params[] = $data['username'];
            }
            if (!empty($data['phone'])) {
                $updates[] = 'phone = ?';
                $params[] = $data['phone'];
            }
            if (!empty($data['role'])) {
                $updates[] = 'role = ?';
                $params[] = $data['role'];
            }
            if (isset($data['restaurant_id'])) {
                $updates[] = 'restaurant_id = ?';
                $params[] = $data['restaurant_id'];
            }
            
            if (empty($updates)) {
                $this->sendResponse([
                    'status' => 'FAIL',
                    'message' => 'No fields to update'
                ], 400);
                return;
            }
            
            $params[] = $data['user_id'];
            $query = "UPDATE staff_users SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'User updated successfully'
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return aggregated analytics used by the dashboard
     */
    private function getAnalyticsData() {
        try {
            $data = $this->buildAnalyticsData();
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $data
            ]);
        } catch (Exception $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Return revenue timelines and top restaurants for a given period
     */
    private function getRevenueData() {
        $period = $_GET['period'] ?? 'month';
        $allowedPeriods = ['day', 'week', 'month', 'year'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'month';
        }
        
        try {
            $db = $this->getDbConnection();
            $now = new DateTime();
            
            $bucketCount = 30;
            $intervalSpec = 'P1D';
            $labelFormat = 'M j';
            $bucketKeyFormat = 'Y-m-d';
            $sqlBucketFormat = '%Y-%m-%d';
            $timelineStart = (clone $now)->modify('-29 days');
            $timelineStart->setTime(0, 0, 0);
            $queryStartDateTime = $timelineStart->format('Y-m-d 00:00:00');
            
            switch ($period) {
                case 'day':
                    $bucketCount = 24;
                    $intervalSpec = 'PT1H';
                    $labelFormat = 'H:00';
                    $bucketKeyFormat = 'Y-m-d H:00:00';
                    $sqlBucketFormat = '%Y-%m-%d %H:00:00';
                    $alignedNow = (clone $now)->setTime((int)$now->format('H'), 0, 0);
                    $timelineStart = (clone $alignedNow)->modify('-' . ($bucketCount - 1) . ' hours');
                    $queryStartDateTime = $timelineStart->format('Y-m-d H:i:s');
                    break;
                    
                case 'week':
                    $bucketCount = 7;
                    $intervalSpec = 'P1D';
                    $labelFormat = 'M j';
                    $bucketKeyFormat = 'Y-m-d';
                    $sqlBucketFormat = '%Y-%m-%d';
                    $timelineStart = (clone $now)->modify('-' . ($bucketCount - 1) . ' days');
                    $timelineStart->setTime(0, 0, 0);
                    $queryStartDateTime = $timelineStart->format('Y-m-d 00:00:00');
                    break;
                    
                case 'month':
                    $bucketCount = 30;
                    $intervalSpec = 'P1D';
                    $labelFormat = 'M j';
                    $bucketKeyFormat = 'Y-m-d';
                    $sqlBucketFormat = '%Y-%m-%d';
                    $timelineStart = (clone $now)->modify('-' . ($bucketCount - 1) . ' days');
                    $timelineStart->setTime(0, 0, 0);
                    $queryStartDateTime = $timelineStart->format('Y-m-d 00:00:00');
                    break;
                    
                case 'year':
                    $bucketCount = 12;
                    $intervalSpec = 'P1M';
                    $labelFormat = 'M Y';
                    $bucketKeyFormat = 'Y-m';
                    $sqlBucketFormat = '%Y-%m';
                    $timelineStart = new DateTime('first day of this month');
                    $timelineStart->setTime(0, 0, 0);
                    $timelineStart->modify('-' . ($bucketCount - 1) . ' months');
                    $queryStartDateTime = $timelineStart->format('Y-m-01 00:00:00');
                    break;
            }
            
            $timelineBuckets = [];
            $interval = new DateInterval($intervalSpec);
            $cursor = clone $timelineStart;
            
            for ($i = 0; $i < $bucketCount; $i++) {
                $key = $cursor->format($bucketKeyFormat);
                $timelineBuckets[$key] = [
                    'label' => $cursor->format($labelFormat),
                    'revenue' => 0.0
                ];
                $cursor->add($interval);
            }
            
            $timelineQuery = "
                SELECT DATE_FORMAT(created_at, '$sqlBucketFormat') AS bucket,
                       COALESCE(SUM(total_amount), 0) AS revenue
                FROM orders
                WHERE status != 'cancelled' AND created_at >= :start_date
                GROUP BY bucket
                ORDER BY bucket
            ";
            $timelineStmt = $db->prepare($timelineQuery);
            $timelineStmt->execute([':start_date' => $queryStartDateTime]);
            
            while ($row = $timelineStmt->fetch()) {
                if (isset($timelineBuckets[$row['bucket']])) {
                    $timelineBuckets[$row['bucket']]['revenue'] = round((float)$row['revenue'], 2);
                }
            }
            
            $timelineData = [];
            foreach ($timelineBuckets as $bucket) {
                $timelineData[] = [
                    'period' => $bucket['label'],
                    'revenue' => (float)$bucket['revenue']
                ];
            }
            
            $restaurantQuery = "
                SELECT 
                    r.name AS restaurant_name,
                    COUNT(o.id) AS order_count,
                    COALESCE(SUM(o.total_amount), 0) AS revenue
                FROM restaurants r
                LEFT JOIN orders o ON o.restaurant_id = r.id
                    AND o.status != 'cancelled'
                    AND o.created_at >= :start_date
                GROUP BY r.id
                ORDER BY revenue DESC
                LIMIT 5
            ";
            $restaurantStmt = $db->prepare($restaurantQuery);
            $restaurantStmt->execute([':start_date' => $queryStartDateTime]);
            $topRestaurants = $restaurantStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($topRestaurants as &$restaurant) {
                $restaurant['restaurant_name'] = $restaurant['restaurant_name'] ?: 'Unnamed Restaurant';
                $restaurant['order_count'] = (int)$restaurant['order_count'];
                $restaurant['revenue'] = round((float)$restaurant['revenue'], 2);
            }
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => [
                    'revenue_timeline' => $timelineData,
                    'revenue_by_restaurant' => $topRestaurants
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load revenue data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Return table of restaurant performance metrics
     */
    private function getRestaurantPerformance() {
        try {
            $data = $this->fetchRestaurantPerformanceData();
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (Exception $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export analytics/performance datasets as CSV
     */
    private function exportReport() {
        $type = $_GET['type'] ?? 'overview';
        
        try {
            switch ($type) {
                case 'overview':
                    $analytics = $this->buildAnalyticsData();
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="system_overview_' . date('Y-m-d_His') . '.csv"');
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['Metric', 'Value']);
                    fputcsv($output, ['Total Restaurants', $analytics['overview']['total_restaurants']]);
                    fputcsv($output, ['Active Restaurants', $analytics['overview']['active_restaurants']]);
                    fputcsv($output, ['Total Users', $analytics['overview']['total_users']]);
                    fputcsv($output, ['Total Orders', $analytics['overview']['total_orders']]);
                    fputcsv($output, ['Total Revenue', $analytics['overview']['total_revenue']]);
                    fclose($output);
                    exit;
                    
                case 'restaurant_performance':
                    $performance = $this->fetchRestaurantPerformanceData();
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="restaurant_performance_' . date('Y-m-d_His') . '.csv"');
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['Restaurant', 'Plan', 'Users', 'Orders', 'Revenue', 'Average Order', 'Tables', 'Status']);
                    foreach ($performance as $row) {
                        fputcsv($output, [
                            $row['name'],
                            $row['subscription_plan'],
                            $row['user_count'],
                            $row['order_count'],
                            $row['total_revenue'],
                            $row['avg_order_value'],
                            $row['table_count'],
                            $row['is_active'] ? 'Active' : 'Inactive'
                        ]);
                    }
                    fclose($output);
                    exit;
                    
                default:
                    $this->sendResponse([
                        'status' => 'FAIL',
                        'message' => 'Invalid report type'
                    ], 400);
            }
        } catch (Exception $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get audit log entries (with filtering, pagination)
     */
    private function getAuditLog() {
        try {
            $db = $this->getDbConnection();
            
            $limit = max(10, intval($_GET['limit'] ?? 50));
            $limit = min($limit, 200);
            
            $page = intval($_GET['page'] ?? 0);
            $offset = 0;
            if ($page > 0) {
                $offset = ($page - 1) * $limit;
            } else {
                $offset = max(0, intval($_GET['offset'] ?? 0));
                $page = intval(floor($offset / $limit)) + 1;
            }
            
            $params = [];
            $whereClause = $this->buildAuditLogWhereClause($params);
            
            $query = "
                SELECT a.*, s.full_name, s.email, s.role
                FROM staff_activity_log a
                LEFT JOIN staff_users s ON a.staff_id = s.id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countQuery = "
                SELECT COUNT(*)
                FROM staff_activity_log a
                LEFT JOIN staff_users s ON a.staff_id = s.id
                $whereClause
            ";
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();
            
            $actionsStmt = $db->query("SELECT DISTINCT action FROM staff_activity_log ORDER BY action ASC");
            $actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $logs,
                'total' => $total,
                'limit' => $limit,
                'page' => $page,
                'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
                'actions' => $actions
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load audit log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export audit log to CSV
     */
    private function exportAuditLog() {
        try {
            $db = $this->getDbConnection();
            $params = [];
            $whereClause = $this->buildAuditLogWhereClause($params);
            
            $query = "
                SELECT a.*, s.full_name, s.email, s.role
                FROM staff_activity_log a
                LEFT JOIN staff_users s ON a.staff_id = s.id
                $whereClause
                ORDER BY a.created_at DESC
            ";
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'User', 'Email', 'Role', 'Action', 'Description', 'IP Address', 'User Agent', 'Date']);
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'],
                    $log['full_name'],
                    $log['email'],
                    $log['role'],
                    $log['action'],
                    $log['description'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['created_at']
                ]);
            }
            fclose($output);
            exit;
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to export audit log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log an audit event (utility for other controllers)
     */
    public static function logAudit($staffId, $action, $description = '', $ip = null, $userAgent = null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            $query = "INSERT INTO staff_activity_log (staff_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $staffId,
                $action,
                $description,
                $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null)
            ]);
        } catch (PDOException $e) {
            // Optionally log error
        }
    }

    /**
     * Lazy-load a PDO connection for reuse within this controller
     */
    private function getDbConnection() {
        if ($this->dbConnection === null) {
            $this->dbConnection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
        
        return $this->dbConnection;
    }
    
    /**
     * Ensure support/system tables exist for installations that predate the schema update
     */
    private function ensureSupportSchema() {
        try {
            $db = $this->getDbConnection();
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS support_tickets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    restaurant_id INT NULL,
                    subject VARCHAR(150) NOT NULL,
                    description TEXT NULL,
                    status ENUM('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
                    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
                    channel ENUM('email','chat','phone','in_app') DEFAULT 'in_app',
                    contact_name VARCHAR(100) NULL,
                    contact_email VARCHAR(150) NULL,
                    contact_phone VARCHAR(50) NULL,
                    assigned_to INT NULL,
                    tags VARCHAR(255) NULL,
                    last_response_at TIMESTAMP NULL,
                    next_follow_up TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_support_restaurant (restaurant_id),
                    INDEX idx_support_status (status),
                    INDEX idx_support_priority (priority),
                    INDEX idx_support_assigned (assigned_to)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS support_ticket_replies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_id INT NOT NULL,
                    staff_id INT NULL,
                    sender_type ENUM('restaurant','support','system') DEFAULT 'support',
                    message TEXT NOT NULL,
                    attachment_url VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticket (ticket_id),
                    INDEX idx_reply_staff (staff_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS support_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    restaurant_id INT NULL,
                    subject VARCHAR(150) NOT NULL,
                    message TEXT NOT NULL,
                    channel ENUM('email','chat','phone','web') DEFAULT 'web',
                    status ENUM('new','read','archived') DEFAULT 'new',
                    contact_name VARCHAR(100) NULL,
                    contact_email VARCHAR(150) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_message_restaurant (restaurant_id),
                    INDEX idx_message_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS system_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(150) NOT NULL,
                    message TEXT NOT NULL,
                    type ENUM('info','success','warning','danger') DEFAULT 'info',
                    context ENUM('system','billing','support','security') DEFAULT 'system',
                    link_url VARCHAR(255) NULL,
                    icon VARCHAR(50) DEFAULT 'bell',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    read_at TIMESTAMP NULL,
                    INDEX idx_notification_read (is_read),
                    INDEX idx_notification_type (type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS system_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NULL,
                    description VARCHAR(255) NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (PDOException $e) {
            error_log('Failed to ensure support schema: ' . $e->getMessage());
        }
    }
    
    /**
     * Build analytics dataset used by multiple endpoints/exports
     */
    private function buildAnalyticsData() {
        $db = $this->getDbConnection();
        
        $overviewStmt = $db->query("
            SELECT
                (SELECT COUNT(*) FROM restaurants) AS total_restaurants,
                (SELECT COUNT(*) FROM restaurants WHERE is_active = 1) AS active_restaurants,
                (SELECT COUNT(*) FROM staff_users WHERE role != 'super_admin') AS total_users,
                (SELECT COUNT(*) FROM orders) AS total_orders,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled') AS total_revenue
        ");
        $overviewRow = $overviewStmt->fetch() ?: [];
        
        $overview = [
            'total_restaurants' => (int)($overviewRow['total_restaurants'] ?? 0),
            'active_restaurants' => (int)($overviewRow['active_restaurants'] ?? 0),
            'total_users' => (int)($overviewRow['total_users'] ?? 0),
            'total_orders' => (int)($overviewRow['total_orders'] ?? 0),
            'total_revenue' => number_format((float)($overviewRow['total_revenue'] ?? 0), 2)
        ];
        
        $planStmt = $db->query("
            SELECT COALESCE(subscription_plan, 'trial') AS subscription_plan, COUNT(*) AS count
            FROM restaurants
            GROUP BY subscription_plan
        ");
        $planDistribution = [];
        while ($row = $planStmt->fetch()) {
            $planDistribution[] = [
                'subscription_plan' => $row['subscription_plan'],
                'count' => (int)$row['count']
            ];
        }
        
        $monthsBack = 5;
        $currentMonthStart = new DateTime('first day of this month');
        $currentMonthStart->setTime(0, 0, 0);
        $startBoundary = (clone $currentMonthStart)->modify('-' . $monthsBack . ' months');
        
        $userGrowthBuckets = [];
        $cursor = clone $startBoundary;
        for ($i = 0; $i <= $monthsBack; $i++) {
            $key = $cursor->format('Y-m');
            $userGrowthBuckets[$key] = [
                'month' => $cursor->format('M Y'),
                'count' => 0
            ];
            $cursor->modify('+1 month');
        }
        
        $growthStmt = $db->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS count
            FROM staff_users
            WHERE role != 'super_admin' AND created_at >= :start_date
            GROUP BY month_key
            ORDER BY month_key ASC
        ");
        $growthStmt->execute([
            ':start_date' => $startBoundary->format('Y-m-01 00:00:00')
        ]);
        while ($row = $growthStmt->fetch()) {
            if (isset($userGrowthBuckets[$row['month_key']])) {
                $userGrowthBuckets[$row['month_key']]['count'] = (int)$row['count'];
            }
        }
        
        return [
            'overview' => $overview,
            'plan_distribution' => $planDistribution,
            'user_growth' => array_values($userGrowthBuckets)
        ];
    }
    
    /**
     * Fetch per-restaurant performance metrics
     */
    private function fetchRestaurantPerformanceData() {
        $db = $this->getDbConnection();
        
        $query = "
            SELECT 
                r.id,
                r.name,
                COALESCE(r.subscription_plan, 'trial') AS subscription_plan,
                r.is_active,
                COALESCE(u.user_count, 0) AS user_count,
                COALESCE(t.table_count, 0) AS table_count,
                COALESCE(o.order_count, 0) AS order_count,
                COALESCE(o.total_revenue, 0) AS total_revenue,
                COALESCE(o.avg_order_value, 0) AS avg_order_value
            FROM restaurants r
            LEFT JOIN (
                SELECT restaurant_id, COUNT(*) AS user_count
                FROM staff_users
                WHERE role != 'super_admin'
                GROUP BY restaurant_id
            ) u ON r.id = u.restaurant_id
            LEFT JOIN (
                SELECT restaurant_id, COUNT(*) AS table_count
                FROM restaurant_tables
                GROUP BY restaurant_id
            ) t ON r.id = t.restaurant_id
            LEFT JOIN (
                SELECT restaurant_id, COUNT(*) AS order_count,
                       COALESCE(SUM(total_amount), 0) AS total_revenue,
                       COALESCE(AVG(total_amount), 0) AS avg_order_value
                FROM orders
                WHERE status != 'cancelled'
                GROUP BY restaurant_id
            ) o ON r.id = o.restaurant_id
            ORDER BY o.total_revenue DESC, r.name ASC
        ";
        
        $stmt = $db->query($query);
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($restaurants as &$restaurant) {
            $restaurant['user_count'] = (int)$restaurant['user_count'];
            $restaurant['table_count'] = (int)$restaurant['table_count'];
            $restaurant['order_count'] = (int)$restaurant['order_count'];
            $restaurant['total_revenue'] = number_format((float)$restaurant['total_revenue'], 2);
            $restaurant['avg_order_value'] = number_format((float)$restaurant['avg_order_value'], 2);
        }
        
        return $restaurants;
    }
    
    /**
     * Utility: fetch single ticket
     */
    private function getTicketById($ticketId) {
        $db = $this->getDbConnection();
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Utility: fetch staff full name
     */
    private function getStaffNameById($staffId) {
        if (!$staffId) {
            return 'Smart Restaurant Support';
        }
        $db = $this->getDbConnection();
        $stmt = $db->prepare("SELECT full_name FROM staff_users WHERE id = ?");
        $stmt->execute([$staffId]);
        $name = $stmt->fetchColumn();
        return $name ?: 'Smart Restaurant Support';
    }
    
    private function notifyTicketReply(array $ticket, $message, $staffId) {
        if (empty($ticket['contact_email'])) {
            return;
        }
        
        $contactName = $ticket['contact_name'] ?: 'there';
        $staffName = $this->getStaffNameById($staffId);
        $subject = 'Support update: ' . $ticket['subject'];
        $body = "
            <p>Hello {$contactName},</p>
            <p>{$staffName} replied to your support ticket <strong>#{$ticket['id']}</strong>.</p>
            <blockquote style=\"border-left:4px solid #ececec;padding-left:1rem;\">{$this->formatMessageAsHtml($message)}</blockquote>
            <p>You can reply to this email to continue the conversation.</p>
            {$this->getSupportEmailFooter()}
        ";
        
        MailService::send([
            'to' => [
                ['email' => $ticket['contact_email'], 'name' => $ticket['contact_name'] ?: 'Customer']
            ],
            'subject' => $subject,
            'body' => $body,
            'alt_body' => strip_tags($message),
            'bcc' => MAIL_SUPPORT_ADDRESS
        ]);
    }
    
    private function notifyTicketStatusChange(array $ticket, $status) {
        if (empty($ticket['contact_email'])) {
            return;
        }
        
        $statusLabel = ucwords(str_replace('_', ' ', $status));
        $contactName = $ticket['contact_name'] ?: 'there';
        $subject = "Ticket status updated: {$ticket['subject']}";
        $body = "
            <p>Hello {$contactName},</p>
            <p>Your support ticket <strong>#{$ticket['id']}</strong> is now marked as <strong>{$statusLabel}</strong>.</p>
            <p>If this update doesn't resolve your issue, simply reply to this email and our team will jump back in.</p>
            {$this->getSupportEmailFooter()}
        ";
        
        MailService::send([
            'to' => $ticket['contact_email'],
            'subject' => $subject,
            'body' => $body,
            'alt_body' => strip_tags($body),
            'bcc' => MAIL_SUPPORT_ADDRESS
        ]);
    }
    
    private function formatMessageAsHtml($message) {
        return nl2br(htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8'));
    }
    
    private function getSupportEmailFooter() {
        $supportAddress = MAIL_SUPPORT_ADDRESS ?: MAIL_FROM_ADDRESS;
        return "<p style=\"margin-top:1.25rem;color:#7f8c8d;font-size:0.9rem;\">Need urgent help? Email us at <a href=\"mailto:{$supportAddress}\">{$supportAddress}</a>.</p>";
    }
    
    /**
     * Support dashboard overview (cards + charts)
     */
    private function getSupportOverview() {
        try {
            $db = $this->getDbConnection();
            
            $overviewQuery = $db->query("
                SELECT
                    SUM(CASE WHEN status IN ('open','in_progress') THEN 1 ELSE 0 END) AS open_tickets,
                    SUM(CASE WHEN status = 'waiting_customer' THEN 1 ELSE 0 END) AS waiting_customer,
                    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved_tickets,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) AS urgent_tickets
                FROM support_tickets
            ");
            $overviewRow = $overviewQuery->fetch() ?: [];
            
            $overview = [
                'open_tickets' => (int)($overviewRow['open_tickets'] ?? 0),
                'waiting_customer' => (int)($overviewRow['waiting_customer'] ?? 0),
                'resolved_tickets' => (int)($overviewRow['resolved_tickets'] ?? 0),
                'urgent_tickets' => (int)($overviewRow['urgent_tickets'] ?? 0)
            ];
            
            $trendStmt = $db->query("
                SELECT DATE(created_at) AS day_label, COUNT(*) AS ticket_count
                FROM support_tickets
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY day_label
                ORDER BY day_label ASC
            ");
            $trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $recentStmt = $db->query("
                SELECT t.id, t.subject, t.status, t.priority, t.updated_at,
                       r.name AS restaurant_name
                FROM support_tickets t
                LEFT JOIN restaurants r ON t.restaurant_id = r.id
                ORDER BY t.updated_at DESC
                LIMIT 5
            ");
            $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $responseStmt = $db->query("
                SELECT AVG(diff_minutes) AS avg_minutes FROM (
                    SELECT TIMESTAMPDIFF(MINUTE, t.created_at, first_reply.created_at) AS diff_minutes
                    FROM support_tickets t
                    INNER JOIN (
                        SELECT ticket_id, MIN(created_at) AS created_at
                        FROM support_ticket_replies
                        WHERE sender_type = 'support'
                        GROUP BY ticket_id
                    ) first_reply ON t.id = first_reply.ticket_id
                ) AS metrics
            ");
            $responseRow = $responseStmt->fetch(PDO::FETCH_ASSOC);
            $averageResponseMinutes = $responseRow && isset($responseRow['avg_minutes'])
                ? round((float)$responseRow['avg_minutes'], 1)
                : null;
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => [
                    'overview' => $overview,
                    'trend' => $trend,
                    'recent' => $recent,
                    'average_response_minutes' => $averageResponseMinutes
                ]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load support overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function buildSupportTicketWhereClause(&$params) {
        $conditions = [];
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $allowedStatus = ['open','in_progress','waiting_customer','resolved','closed'];
        if ($status && in_array($status, $allowedStatus, true)) {
            $conditions[] = 't.status = :status';
            $params[':status'] = $status;
        }
        
        $allowedPriority = ['low','medium','high','urgent'];
        if ($priority && in_array($priority, $allowedPriority, true)) {
            $conditions[] = 't.priority = :priority';
            $params[':priority'] = $priority;
        }
        
        if (!empty($search)) {
            $conditions[] = '(t.subject LIKE :search OR t.description LIKE :search OR t.contact_name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        
        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }
    
    private function getSupportTickets() {
        try {
            $db = $this->getDbConnection();
            
            $limit = max(10, intval($_GET['limit'] ?? 20));
            $limit = min($limit, 100);
            $page = max(1, intval($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;
            
            $params = [];
            $whereClause = $this->buildSupportTicketWhereClause($params);
            
            $query = "
                SELECT t.*, r.name AS restaurant_name, s.full_name AS assigned_name
                FROM support_tickets t
                LEFT JOIN restaurants r ON t.restaurant_id = r.id
                LEFT JOIN staff_users s ON t.assigned_to = s.id
                $whereClause
                ORDER BY FIELD(t.status, 'open','in_progress','waiting_customer','resolved','closed'),
                         FIELD(t.priority, 'urgent','high','medium','low'),
                         t.updated_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countQuery = "
                SELECT COUNT(*) FROM support_tickets t
                LEFT JOIN restaurants r ON t.restaurant_id = r.id
                $whereClause
            ";
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $tickets,
                'total' => $total,
                'page' => $page,
                'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 1
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load support tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getTicketDetails() {
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid ticket ID'], 400);
            return;
        }
        
        try {
            $db = $this->getDbConnection();
            
            $ticketStmt = $db->prepare("
                SELECT t.*, r.name AS restaurant_name, s.full_name AS assigned_name
                FROM support_tickets t
                LEFT JOIN restaurants r ON t.restaurant_id = r.id
                LEFT JOIN staff_users s ON t.assigned_to = s.id
                WHERE t.id = ?
            ");
            $ticketStmt->execute([$ticketId]);
            $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                $this->sendResponse(['status' => 'FAIL', 'message' => 'Ticket not found'], 404);
                return;
            }
            
            $repliesStmt = $db->prepare("
                SELECT r.*, u.full_name AS staff_name
                FROM support_ticket_replies r
                LEFT JOIN staff_users u ON r.staff_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC
            ");
            $repliesStmt->execute([$ticketId]);
            $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => [
                    'ticket' => $ticket,
                    'replies' => $replies
                ]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load ticket details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function updateTicketStatusEndpoint() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'POST request required'], 405);
            return;
        }
        
        $payload = json_decode(file_get_contents('php://input'), true);
        $ticketId = intval($payload['ticket_id'] ?? 0);
        $status = $payload['status'] ?? '';
        $assignedTo = isset($payload['assigned_to']) ? intval($payload['assigned_to']) : null;
        $allowedStatus = ['open','in_progress','waiting_customer','resolved','closed'];
        
        if ($ticketId <= 0 || !in_array($status, $allowedStatus, true)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid ticket data'], 400);
            return;
        }
        
        $ticket = $this->getTicketById($ticketId);
        if (!$ticket) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Ticket not found'], 404);
            return;
        }
        
        try {
            $db = $this->getDbConnection();
            $stmt = $db->prepare("UPDATE support_tickets SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $assignedTo, $ticketId]);
            
            $ticket['status'] = $status;
            $ticket['assigned_to'] = $assignedTo;
            $this->notifyTicketStatusChange($ticket, $status);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Ticket updated successfully'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function replyTicket() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'POST request required'], 405);
            return;
        }
        
        $payload = json_decode(file_get_contents('php://input'), true);
        $ticketId = intval($payload['ticket_id'] ?? 0);
        $message = trim($payload['message'] ?? '');
        $attachment = $payload['attachment_url'] ?? null;
        
        if ($ticketId <= 0 || empty($message)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Ticket ID and message are required'], 400);
            return;
        }
        
        $ticket = $this->getTicketById($ticketId);
        if (!$ticket) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Ticket not found'], 404);
            return;
        }
        
        $staffId = $_SESSION['user_id'] ?? null;
        
        try {
            $db = $this->getDbConnection();
            $stmt = $db->prepare("
                INSERT INTO support_ticket_replies (ticket_id, staff_id, sender_type, message, attachment_url)
                VALUES (?, ?, 'support', ?, ?)
            ");
            $stmt->execute([$ticketId, $staffId, $message, $attachment]);
            
            $updateStmt = $db->prepare("UPDATE support_tickets SET last_response_at = NOW(), updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$ticketId]);
            $this->notifyTicketReply($ticket, $message, $staffId);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Reply posted'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to post reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getSupportMessages() {
        try {
            $db = $this->getDbConnection();
            $stmt = $db->query("
                SELECT m.*, r.name AS restaurant_name
                FROM support_messages m
                LEFT JOIN restaurants r ON m.restaurant_id = r.id
                ORDER BY m.created_at DESC
                LIMIT 50
            ");
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $messages
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getSystemNotifications() {
        try {
            $db = $this->getDbConnection();
            $limit = max(5, intval($_GET['limit'] ?? 10));
            $limit = min($limit, 100);
            $unreadOnly = ($_GET['unread_only'] ?? 'false') === 'true';
            
            $query = "
                SELECT * FROM system_notifications
                " . ($unreadOnly ? "WHERE is_read = 0" : "") . "
                ORDER BY created_at DESC
                LIMIT :limit
            ";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $unreadCount = (int)$db->query("SELECT COUNT(*) FROM system_notifications WHERE is_read = 0")->fetchColumn();
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function markNotificationRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'POST request required'], 405);
            return;
        }
        
        $payload = json_decode(file_get_contents('php://input'), true);
        $notificationId = $payload['notification_id'] ?? '';
        
        try {
            $db = $this->getDbConnection();
            
            if ($notificationId === 'all') {
                $db->exec("UPDATE system_notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
            } else {
                $id = intval($notificationId);
                if ($id <= 0) {
                    $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid notification ID'], 400);
                    return;
                }
                
                $stmt = $db->prepare("UPDATE system_notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Notification updated'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getSystemSettings() {
        try {
            $db = $this->getDbConnection();
            $stmt = $db->query("SELECT setting_key, setting_value, description, updated_at FROM system_settings ORDER BY setting_key ASC");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = [
                    'value' => $row['setting_value'],
                    'description' => $row['description'],
                    'updated_at' => $row['updated_at']
                ];
            }
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $settings
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function updateSystemSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'POST request required'], 405);
            return;
        }
        
        $rawBody = file_get_contents('php://input');
        $payload = [];
        if ($rawBody !== false && strlen(trim($rawBody)) > 0) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            } else {
                parse_str($rawBody, $payload);
            }
        }
        if (empty($payload) && !empty($_POST)) {
            $payload = $_POST;
        }
        if (!is_array($payload)) {
            $payload = [];
        }
        
        $settings = $payload['settings'] ?? $payload;
        if (!is_array($settings) || empty($settings)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'No settings provided'], 400);
            return;
        }
        
        $cleanSettings = [];
        foreach ($settings as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            if (is_array($value)) {
                $cleanSettings[$key] = json_encode($value);
            } else {
                $cleanSettings[$key] = trim((string) $value);
            }
        }
        
        if (isset($cleanSettings['maintenance_mode'])) {
            $cleanSettings['maintenance_mode'] = strtolower($cleanSettings['maintenance_mode']) === 'on' ? 'on' : 'off';
        }
        
        if (empty($cleanSettings)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'No valid settings provided'], 400);
            return;
        }
        
        $currentSettings = SystemSettings::getAll();
        $previousMode = strtolower($currentSettings['maintenance_mode'] ?? 'off');
        $newMode = isset($cleanSettings['maintenance_mode']) ? $cleanSettings['maintenance_mode'] : $previousMode;
        $maintenanceChanged = isset($cleanSettings['maintenance_mode']) && $previousMode !== $newMode;
        
        try {
            $db = $this->getDbConnection();
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description)
                VALUES (:key, :value, :description)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
            ");
            
            $db->beginTransaction();
            foreach ($cleanSettings as $key => $value) {
                // Get existing description if setting exists
                $descQuery = $db->prepare("SELECT description FROM system_settings WHERE setting_key = ?");
                $descQuery->execute([$key]);
                $existingDesc = $descQuery->fetchColumn();
                $description = $existingDesc !== false ? $existingDesc : '';
                
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value,
                    ':description' => $description
                ]);
            }
            
            if ($maintenanceChanged) {
                try {
                    $noteStmt = $db->prepare("
                        INSERT INTO system_notifications (title, message, type, context, link_url, icon, is_read)
                        VALUES (:title, :message, :type, 'system', NULL, :icon, 0)
                    ");
                    $statusLabel = $newMode === 'on' ? 'enabled' : 'disabled';
                    $actor = $_SESSION['email'] ?? 'Super Admin';
                    $noteStmt->execute([
                        ':title' => 'Maintenance mode ' . $statusLabel,
                        ':message' => 'System maintenance mode was ' . $statusLabel . ' by ' . $actor . ' at ' . date('Y-m-d H:i:s'),
                        ':type' => $newMode === 'on' ? 'warning' : 'success',
                        ':icon' => $newMode === 'on' ? 'tools' : 'check-circle'
                    ]);
                } catch (PDOException $notificationError) {
                    error_log('[SUPERADMIN] Failed to write maintenance notification: ' . $notificationError->getMessage());
                }
            }
            
            $db->commit();
            SystemSettings::refresh();
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Settings updated successfully',
                'maintenance_mode' => $newMode
            ]);
        } catch (PDOException $e) {
            // Rollback transaction on error
            if (isset($db) && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (Exception $rollbackError) {
                    error_log('[SUPERADMIN] Rollback error: ' . $rollbackError->getMessage());
                }
            }
            error_log('[SUPERADMIN] updateSystemSettings PDO error: ' . $e->getMessage());
            error_log('[SUPERADMIN] updateSystemSettings trace: ' . $e->getTraceAsString());
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update settings: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($db) && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (Exception $rollbackError) {
                    error_log('[SUPERADMIN] Rollback error: ' . $rollbackError->getMessage());
                }
            }
            error_log('[SUPERADMIN] updateSystemSettings exception: ' . $e->getMessage());
            error_log('[SUPERADMIN] updateSystemSettings trace: ' . $e->getTraceAsString());
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to update settings: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function triggerBackup() {
        try {
            $db = $this->getDbConnection();
            
            $db->exec("
                INSERT INTO system_notifications (title, message, type, context, icon, is_read)
                VALUES (
                    'Manual backup triggered',
                    'Backup request queued by super admin at " . date('Y-m-d H:i:s') . "',
                    'info',
                    'system',
                    'database',
                    0
                )
            ");
            
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description)
                VALUES ('last_backup', NOW(), 'Timestamp of last manual backup')
                ON DUPLICATE KEY UPDATE setting_value = NOW(), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute();
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Backup request recorded'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to trigger backup',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getSystemLogs() {
        try {
            $db = $this->getDbConnection();
            $limit = max(10, intval($_GET['limit'] ?? 50));
            $limit = min($limit, 200);
            
            $stmt = $db->prepare("
                SELECT l.*, u.full_name, u.email
                FROM staff_activity_log l
                LEFT JOIN staff_users u ON l.staff_id = u.id
                ORDER BY l.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $logs
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load system logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getSystemStatus() {
        try {
            $db = $this->getDbConnection();
            $mysqlVersion = $db->query('SELECT VERSION()')->fetchColumn();
            
            $settingsStmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
            $settings = [];
            while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $status = [
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'mysql_version' => $mysqlVersion,
                'timezone' => $settings['default_timezone'] ?? date_default_timezone_get(),
                'maintenance_mode' => $settings['maintenance_mode'] ?? 'off'
            ];
            
            $this->sendResponse([
                'status' => 'OK',
                'data' => $status
            ]);
        } catch (PDOException $e) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Failed to load system status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Build WHERE clause for audit log filters
     */
    private function buildAuditLogWhereClause(array &$params) {
        $conditions = [];
        
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $conditions[] = "(a.action LIKE :search OR a.description LIKE :search OR s.full_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $actionFilter = trim($_GET['action'] ?? '');
        if ($actionFilter !== '') {
            $conditions[] = "a.action = :action";
            $params[':action'] = $actionFilter;
        }
        
        $staffId = intval($_GET['staff_id'] ?? 0);
        if ($staffId > 0) {
            $conditions[] = "a.staff_id = :staff_id";
            $params[':staff_id'] = $staffId;
        }
        
        $startDate = $_GET['start_date'] ?? '';
        if ($startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $conditions[] = "DATE(a.created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        $endDate = $_GET['end_date'] ?? '';
        if ($endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $conditions[] = "DATE(a.created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set proper headers
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // Output JSON
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Sanitize input
     */
    private function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
?>
