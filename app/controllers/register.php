<?php
/**
 * Restaurant Registration Controller
 * Allows restaurants to create their own accounts and choose subscription plans
 */

require_once 'src/config.php';

// Load restaurant class if not already loaded
if (!class_exists('Restaurant')) {
    require_once 'src/restaurant.php';
}
if (!class_exists('MailService')) {
    require_once __DIR__ . '/../core/MailService.php';
}

class RegisterController {
    
    private $db;
    private $restaurantModel;
    
    public function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->restaurantModel = new Restaurant();
    }
    
    /**
     * Display registration page
     */
    public function index() {
        $this->showRegistrationPage();
    }
    
    /**
     * Handle API requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'register_restaurant':
                $this->registerRestaurant();
                break;
                
            case 'check_availability':
                $this->checkAvailability();
                break;
                
            case 'get_plans':
                $this->getSubscriptionPlans();
                break;
                
            default:
                $this->index();
        }
    }
    
    /**
     * Register new restaurant
     */
    private function registerRestaurant() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Invalid request method'], 400);
            return;
        }
        
        // Collect and validate data
        $data = [
            'name' => $this->sanitize($_POST['restaurant_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'phone' => $this->sanitize($_POST['phone'] ?? ''),
            'address' => $this->sanitize($_POST['address'] ?? ''),
            'city' => $this->sanitize($_POST['city'] ?? ''),
            'country' => $this->sanitize($_POST['country'] ?? 'Rwanda'),
            'subscription_plan' => $this->sanitize($_POST['plan'] ?? 'trial'),
            'owner_name' => $this->sanitize($_POST['owner_name'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];
        
        // Validate required fields
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Validation failed',
                'errors' => $errors
            ], 400);
            return;
        }
        
        // Check if email already exists
        if ($this->restaurantModel->getByEmail($data['email'])) {
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Email already registered'
            ], 400);
            return;
        }
        
        // Generate slug
        $data['slug'] = Restaurant::generateSlug($data['name']);
        
        // Set subscription dates based on plan
        $planConfig = $this->getPlanConfig($data['subscription_plan']);
        $data['subscription_start'] = date('Y-m-d');
        $data['subscription_end'] = date('Y-m-d', strtotime($planConfig['duration']));
        $data['max_tables'] = $planConfig['max_tables'];
        $data['max_users'] = $planConfig['max_users'];
        $data['is_active'] = 1;
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Create restaurant
            $restaurantId = $this->restaurantModel->create($data);
            
            // Create owner/admin user
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $username = strtolower(str_replace(' ', '', $data['name'])) . '_admin';
            $userQuery = "INSERT INTO staff_users (restaurant_id, username, email, password_hash, full_name, role, is_active) 
                         VALUES (?, ?, ?, ?, ?, 'admin', 1)";
            $stmt = $this->db->prepare($userQuery);
            $stmt->execute([
                $restaurantId,
                $username,
                $data['email'],
                $hashedPassword,
                $data['owner_name']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Initialize default settings
            $this->initializeDefaultSettings($restaurantId);
            
            // Create welcome data (sample menu categories, etc.)
            $this->createWelcomeData($restaurantId);
            
            // Commit transaction
            $this->db->commit();
            
            // Send welcome email (optional)
            $this->sendWelcomeEmail($data['email'], $data['name'], $data['slug']);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Restaurant registered successfully!',
                'data' => [
                    'restaurant_id' => $restaurantId,
                    'slug' => $data['slug'],
                    'access_url' => BASE_URL . '/' . $data['slug'],
                    'email' => $data['email'],
                    'plan' => $data['subscription_plan'],
                    'trial_ends' => $data['subscription_end']
                ]
            ]);
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse([
                'status' => 'FAIL',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if restaurant name/email is available
     */
    private function checkAvailability() {
        header('Content-Type: application/json');
        
        $type = $_GET['type'] ?? '';
        $value = $_GET['value'] ?? '';
        
        if (empty($type) || empty($value)) {
            $this->sendResponse(['status' => 'FAIL', 'message' => 'Missing parameters'], 400);
            return;
        }
        
        $available = false;
        
        switch ($type) {
            case 'email':
                $available = !$this->restaurantModel->getByEmail($value);
                break;
                
            case 'slug':
                $slug = Restaurant::generateSlug($value);
                $available = !$this->restaurantModel->getBySlug($slug);
                break;
        }
        
        $this->sendResponse([
            'status' => 'OK',
            'available' => $available
        ]);
    }
    
    /**
     * Get subscription plans
     */
    private function getSubscriptionPlans() {
        header('Content-Type: application/json');
        
        $plans = [
            [
                'id' => 'trial',
                'name' => 'Free Trial',
                'price' => 0,
                'currency' => 'RWF',
                'duration' => '30 days',
                'features' => [
                    'max_tables' => 10,
                    'max_users' => 5,
                    'max_orders' => 100,
                    'qr_codes' => true,
                    'online_ordering' => true,
                    'basic_reports' => true,
                    'custom_branding' => false,
                    'priority_support' => false,
                    'api_access' => false
                ],
                'description' => 'Perfect for testing the system',
                'recommended' => false
            ],
            [
                'id' => 'basic',
                'name' => 'Basic Plan',
                'price' => 29000,
                'currency' => 'RWF',
                'duration' => 'per month',
                'features' => [
                    'max_tables' => 20,
                    'max_users' => 10,
                    'max_orders' => 'unlimited',
                    'qr_codes' => true,
                    'online_ordering' => true,
                    'basic_reports' => true,
                    'custom_branding' => false,
                    'priority_support' => false,
                    'api_access' => false
                ],
                'description' => 'Great for small restaurants',
                'recommended' => false
            ],
            [
                'id' => 'premium',
                'name' => 'Premium Plan',
                'price' => 79000,
                'currency' => 'RWF',
                'duration' => 'per month',
                'features' => [
                    'max_tables' => 50,
                    'max_users' => 20,
                    'max_orders' => 'unlimited',
                    'qr_codes' => true,
                    'online_ordering' => true,
                    'basic_reports' => true,
                    'advanced_analytics' => true,
                    'custom_branding' => true,
                    'priority_support' => true,
                    'api_access' => true,
                    'inventory_management' => true
                ],
                'description' => 'Most popular for growing restaurants',
                'recommended' => true
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise Plan',
                'price' => 'Custom',
                'currency' => 'RWF',
                'duration' => 'per month',
                'features' => [
                    'max_tables' => 'unlimited',
                    'max_users' => 'unlimited',
                    'max_orders' => 'unlimited',
                    'qr_codes' => true,
                    'online_ordering' => true,
                    'basic_reports' => true,
                    'advanced_analytics' => true,
                    'custom_branding' => true,
                    'priority_support' => true,
                    'dedicated_support' => true,
                    'api_access' => true,
                    'inventory_management' => true,
                    'multi_location' => true,
                    'custom_integrations' => true
                ],
                'description' => 'For large restaurant chains',
                'recommended' => false
            ]
        ];
        
        $this->sendResponse([
            'status' => 'OK',
            'plans' => $plans
        ]);
    }
    
    /**
     * Get plan configuration
     */
    private function getPlanConfig($plan) {
        $configs = [
            'trial' => [
                'duration' => '+30 days',
                'max_tables' => 10,
                'max_users' => 5
            ],
            'basic' => [
                'duration' => '+1 month',
                'max_tables' => 20,
                'max_users' => 10
            ],
            'premium' => [
                'duration' => '+1 month',
                'max_tables' => 50,
                'max_users' => 20
            ],
            'enterprise' => [
                'duration' => '+1 month',
                'max_tables' => 999,
                'max_users' => 999
            ]
        ];
        
        return $configs[$plan] ?? $configs['trial'];
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['restaurant_name'] = 'Restaurant name is required';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['owner_name'])) {
            $errors['owner_name'] = 'Owner name is required';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        }
        
        return $errors;
    }
    
    /**
     * Initialize default settings for new restaurant
     */
    private function initializeDefaultSettings($restaurantId) {
        $defaultSettings = [
            'auto_accept_orders' => 'false',
            'order_timeout_minutes' => '30',
            'enable_table_qr' => 'true',
            'currency_symbol' => 'RWF',
            'tax_enabled' => 'false',
            'service_charge_enabled' => 'false',
            'allow_cash_payment' => 'true',
            'allow_card_payment' => 'true',
            'allow_mobile_payment' => 'true'
        ];
        
        foreach ($defaultSettings as $key => $value) {
            $this->restaurantModel->setSetting($restaurantId, $key, $value);
        }
    }
    
    /**
     * Create welcome data (sample categories, etc.)
     */
    private function createWelcomeData($restaurantId) {
        // Create default menu categories
        $categories = [
            ['name' => 'Appetizers', 'description' => 'Start your meal right'],
            ['name' => 'Main Course', 'description' => 'Our signature dishes'],
            ['name' => 'Desserts', 'description' => 'Sweet endings'],
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments']
        ];
        
        $query = "INSERT INTO menu_categories (restaurant_id, name, description, display_order) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        $order = 0;
        foreach ($categories as $category) {
            $stmt->execute([$restaurantId, $category['name'], $category['description'], ++$order]);
        }
    }
    
    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($email, $restaurantName, $slug) {
        if (empty($email)) {
            return;
        }
        
        $loginUrl = BASE_URL . '/?req=staff&action=login';
        $subject = 'Welcome to Smart Restaurant Cloud';
        $body = "
            <p>Hello {$this->sanitize($restaurantName)} Team,</p>
            <p>Welcome to <strong>Smart Restaurant Cloud</strong>! Your tenant space has been created successfully.</p>
            <p><strong>Next steps:</strong></p>
            <ol>
                <li>Visit <a href=\"{$loginUrl}\">{$loginUrl}</a></li>
                <li>Log in with the email <strong>{$email}</strong></li>
                <li>Configure your menu, tables, and staff accounts</li>
            </ol>
            <p>If you need help, reply to this email or reach our support team at <a href=\"mailto:" . MAIL_SUPPORT_ADDRESS . "\">" . MAIL_SUPPORT_ADDRESS . "</a>.</p>
            <p>— Smart Restaurant Support</p>
        ";
        
        MailService::send([
            'to' => $email,
            'subject' => $subject,
            'body' => $body,
            'alt_body' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $body)),
            'is_html' => true,
            'bcc' => MAIL_SUPPORT_ADDRESS
        ]);
    }
    
    /**
     * Show registration page
     */
    private function showRegistrationPage() {
        include 'app/views/register.php';
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
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

// Handle request
$controller = new RegisterController();
$controller->handleRequest();
