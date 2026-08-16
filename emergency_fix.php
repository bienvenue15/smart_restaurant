<?php
/**
 * EMERGENCY SESSION FIX
 * This will completely reset your session and show you what's wrong
 */
require_once __DIR__ . '/init_session.php';

// Force destroy everything
$_SESSION = [];
session_destroy();
require_once __DIR__ . '/init_session.php';

echo "<!DOCTYPE html><html><head><title>Emergency Fix</title><style>
body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
.error { color: #f00; font-weight: bold; }
.success { color: #0f0; font-weight: bold; }
.info { color: #ff0; }
pre { background: #111; border: 1px solid #333; padding: 10px; margin: 10px 0; }
</style></head><body>";

echo "<h1>EMERGENCY SESSION RESET</h1>";
echo "<p class='success'>✅ Session completely destroyed and recreated</p>";
echo "<p class='success'>✅ Session ID: " . session_id() . "</p>";

echo "<h2>STEP 1: Login Now</h2>";
echo "<form method='post' style='background:#222;padding:20px;border:2px solid #0f0;'>";
echo "<p><label>Username: <input type='text' name='username' required style='padding:5px;'></label></p>";
echo "<p><label>Password: <input type='password' name='password' required style='padding:5px;'></label></p>";
echo "<p><button type='submit' name='login' style='padding:10px 20px;background:#0f0;color:#000;border:none;font-weight:bold;cursor:pointer;'>LOGIN NOW</button></p>";
echo "</form>";

if (isset($_POST['login'])) {
    // Load all necessary files
    if (!defined('BASE_URL')) {
        define('BASE_URL', 'http://localhost/restaurant');
    }
    require_once __DIR__ . '/src/config.php';
    
    // Manually create database connection
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h2>STEP 2: Checking Database</h2>";
    
    try {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Get user from database
        $stmt = $db->prepare("SELECT * FROM staff_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<p class='error'>❌ User not found: $username</p>";
            echo "<pre>Available users:\n";
            $stmt = $db->query("SELECT username, role FROM staff_users LIMIT 10");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  - {$row['username']} ({$row['role']})\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='success'>✅ User found in database</p>";
            echo "<pre>";
            echo "UUID: " . ($user['uuid'] ?? 'MISSING') . "\n";
            echo "Username: " . $user['username'] . "\n";
            echo "Role: " . $user['role'] . "\n";
            echo "Full Name: " . $user['full_name'] . "\n";
            echo "</pre>";
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                echo "<p class='success'>✅ Password verified</p>";
                
                echo "<h2>STEP 3: Creating Session</h2>";
                
                // Set session - EXACTLY like the login should do
                $_SESSION['staff_user'] = $user;
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_uuid'] = $user['uuid'];
                $_SESSION['staff_username'] = $user['username'];
                $_SESSION['staff_full_name'] = $user['full_name'];
                $_SESSION['staff_role'] = $user['role'];
                
                echo "<p class='success'>✅ Session created successfully</p>";
                echo "<pre>";
                echo "staff_uuid: " . $_SESSION['staff_uuid'] . "\n";
                echo "staff_username: " . $_SESSION['staff_username'] . "\n";
                echo "staff_role: " . $_SESSION['staff_role'] . "\n";
                echo "</pre>";
                
                echo "<h2>STEP 4: Test API</h2>";
                echo "<p class='info'>Testing API endpoints with your new session...</p>";
                
                // Test the session is working
                if (isset($_SESSION['staff_uuid'])) {
                    echo "<p class='success'>✅ staff_uuid is SET in session</p>";
                } else {
                    echo "<p class='error'>❌ staff_uuid is NOT SET</p>";
                }
                
                echo "<h2>✅ DONE - Session is fixed!</h2>";
                echo "<p><a href='/restaurant/?req=staff' style='color:#0f0;font-size:20px;'>▶ GO TO DASHBOARD NOW</a></p>";
                echo "<p><a href='test_api_direct.php' style='color:#ff0;'>▶ Test API Endpoints</a></p>";
                
            } else {
                echo "<p class='error'>❌ Wrong password</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

echo "</body></html>";
