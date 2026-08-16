<?php
/**
 * Restaurant Registration Controller
 * Allows restaurants to create their own accounts and choose subscription plans
 */

// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

require_once 'src/config.php';

// Load restaurant class if not already loaded
if (!class_exists('Restaurant')) {
    require_once 'src/restaurant.php';
}
if (!class_exists('MailService')) {
    require_once __DIR__ . '/../core/MailService.php';
}
if (!class_exists('ValidationHelper')) {
    require_once __DIR__ . '/../../src/ValidationHelper.php';
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
     * Main entry point - handle both page display and actions
     */
    public function index() {
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
                $this->showRegistrationPage();
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
            'tin' => $this->sanitize($_POST['tin'] ?? ''),
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
            
            // Create restaurant - returns array with id, uuid, and secure slug
            $restaurantData = $this->restaurantModel->create($data);
            $restaurantId = $restaurantData['id'];
            $restaurantUuid = $restaurantData['uuid'];
            $secureSlug = $restaurantData['slug'];
            
            // Create owner/admin user with restaurant UUID
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $username = strtolower(str_replace(' ', '', $data['name'])) . '_admin';
            
            // Generate UUID for staff user
            $staffUuid = $this->generateUUID();
            
            $userQuery = "INSERT INTO staff_users (uuid, restaurant_id, restaurant_uuid, username, email, password_hash, full_name, role, is_active) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 1)";
            $stmt = $this->db->prepare($userQuery);
            $stmt->execute([
                $staffUuid,
                $restaurantId,
                $restaurantUuid,
                $username,
                $data['email'],
                $hashedPassword,
                $data['owner_name']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Initialize default settings
            $this->initializeDefaultSettings($restaurantUuid);
            
            // Create welcome data (sample menu categories, etc.)
            $this->createWelcomeData($restaurantUuid);
            
            // Commit transaction
            $this->db->commit();
            
            // Send welcome email with credentials and secure slug
            $this->sendWelcomeEmail($data['email'], $data['name'], $secureSlug, $data['password'], $username);
            
            $this->sendResponse([
                'status' => 'OK',
                'message' => 'Restaurant registered successfully!',
                'data' => [
                    'uuid' => $restaurantUuid,
                    'slug' => $secureSlug,
                    'access_url' => BASE_URL . '/' . $secureSlug,
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
        $rules = [
            'name' => ['required' => true, 'length' => [2, 200]],
            'email' => ['required' => true, 'email' => true],
            'owner_name' => ['required' => true, 'length' => [2, 100]],
            'password' => ['required' => true, 'password' => 6],
            'phone' => ['required' => true, 'phone' => true],
            'tin' => ['required' => true, 'tin' => true],
            'address' => ['required' => true, 'length' => [5, 500]],
            'city' => ['required' => true, 'length' => [2, 100]]
        ];
        
        return ValidationHelper::validateMultiple($rules, $data);
    }
    
    /**
     * Initialize default settings for new restaurant
     */
    private function initializeDefaultSettings($restaurantUuid) {
        // Get restaurant by UUID (for internal operations only)
        $restaurant = $this->restaurantModel->getByUuid($restaurantUuid);
        $restaurantId = $restaurant['id'];
        
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
    private function createWelcomeData($restaurantUuid) {
        // Get restaurant by UUID
        $restaurant = $this->restaurantModel->getByUuid($restaurantUuid);
        $restaurantId = $restaurant['id'];
        
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
     * Send welcome email with login credentials
     */
    private function sendWelcomeEmail($email, $restaurantName, $slug, $password, $username) {
        if (empty($email)) {
            return;
        }
        
        $loginUrl = BASE_URL . '/?req=staff&action=login';
        $subject = "🎉 Welcome to Smart Restaurant - Your Account is Ready!";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .credentials-box { background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .credential-item { margin: 10px 0; padding: 10px; background: #f0f4ff; border-radius: 5px; }
                .credential-label { font-weight: bold; color: #667eea; }
                .credential-value { font-size: 16px; color: #333; font-family: monospace; }
                .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .steps { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .steps ol { padding-left: 20px; }
                .steps li { margin: 10px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to Smart Restaurant!</h1>
                    <p>Your account has been created successfully</p>
                </div>
                
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($restaurantName) . "</strong> Team,</p>
                    
                    <p>Congratulations! Your restaurant management system is now ready to use. We're excited to have you on board!</p>
                    
                    <div class='credentials-box'>
                        <h3 style='margin-top: 0; color: #667eea;'>🔐 Your Login Credentials</h3>
                        
                        <div class='credential-item'>
                            <div class='credential-label'>Login URL:</div>
                            <div class='credential-value'><a href='{$loginUrl}'>{$loginUrl}</a></div>
                        </div>
                        
                        <div class='credential-item'>
                            <div class='credential-label'>Email / Username:</div>
                            <div class='credential-value'>{$email}</div>
                        </div>
                        
                        <div class='credential-item'>
                            <div class='credential-label'>Password:</div>
                            <div class='credential-value'>{$password}</div>
                        </div>
                        
                        <div class='credential-item'>
                            <div class='credential-label'>Role:</div>
                            <div class='credential-value'>Administrator (Full Access)</div>
                        </div>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Reminder:</strong> Please change your password after your first login. Go to Settings → Change Password.
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='{$loginUrl}' class='btn'>Login to Your Dashboard</a>
                    </div>
                    
                    <div class='steps'>
                        <h3 style='color: #667eea;'>🚀 Next Steps to Get Started:</h3>
                        <ol>
                            <li><strong>Login:</strong> Visit <a href='{$loginUrl}'>{$loginUrl}</a> and use your credentials above</li>
                            <li><strong>Setup Tables:</strong> Configure your restaurant tables and generate QR codes</li>
                            <li><strong>Add Menu Items:</strong> Create categories and add your delicious menu items</li>
                            <li><strong>Add Staff:</strong> Invite your team members (waiters, kitchen staff, cashiers)</li>
                            <li><strong>Start Taking Orders:</strong> Your customers can scan QR codes and place orders!</li>
                        </ol>
                    </div>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='color: #667eea;'>📊 Your Plan Details:</h3>
                        <p><strong>Restaurant Slug:</strong> {$slug}</p>
                        <p><strong>Customer Menu URL:</strong> <a href='" . BASE_URL . "/{$slug}'>" . BASE_URL . "/{$slug}</a></p>
                    </div>
                    
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>💡 Need Help?</strong> Our support team is here for you!</p>
                        <p style='margin: 10px 0 0 0;'>Email: <a href='mailto:" . MAIL_SUPPORT_ADDRESS . "'>" . MAIL_SUPPORT_ADDRESS . "</a></p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This is an automated email from Smart Restaurant System</p>
                    <p>&copy; 2025 Smart Restaurant. All rights reserved.</p>
                    <p><a href='https://smartresto.inovasiyo.rw'>smartresto.inovasiyo.rw</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
Welcome to Smart Restaurant!

Hello {$restaurantName} Team,

Your restaurant management system is now ready to use!

YOUR LOGIN CREDENTIALS:
------------------------
Login URL: {$loginUrl}
Email: {$email}
Password: {$password}
Role: Administrator

SECURITY REMINDER: Please change your password after your first login.

NEXT STEPS:
1. Login at {$loginUrl}
2. Setup your restaurant tables and generate QR codes
3. Add menu items and categories
4. Add staff members
5. Start taking orders!

YOUR RESTAURANT DETAILS:
Slug: {$slug}
Customer Menu: " . BASE_URL . "/{$slug}

Need Help? Contact us at " . MAIL_SUPPORT_ADDRESS . "

---
Smart Restaurant System
smartresto.inovasiyo.rw
        ";
        
        try {
            MailService::send([
                'to' => $email,
                'subject' => $subject,
                'body' => $body,
                'alt_body' => $altBody,
                'is_html' => true
            ]);
        } catch (Exception $e) {
            error_log("Failed to send welcome email to {$email}: " . $e->getMessage());
        }
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        try {
            $result = $this->db->query("SELECT UUID() as uuid");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return $row['uuid'];
        } catch (PDOException $e) {
            // Fallback to PHP-generated UUID
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
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
