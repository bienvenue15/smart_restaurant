<?php
/**
 * COMPLETE LOGIN FLOW TEST
 * This tests every step of the login process
 */
require_once __DIR__ . '/init_session.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/restaurant');
}
require_once __DIR__ . '/src/config.php';

$step = '';
$results = [];

// Handle login
if (isset($_POST['test_login'])) {
    $step = 'login_attempt';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PWD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Step 1: Find user
        $results[] = "Step 1: Finding user '$username' in database...";
        $stmt = $db->prepare("SELECT * FROM staff_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $results[] = "❌ FAIL: User not found or inactive";
            $step = 'failed';
        } else {
            $results[] = "✅ PASS: User found - UUID: " . $user['uuid'] . ", Role: " . $user['role'];
            
            // Step 2: Verify password
            $results[] = "Step 2: Verifying password...";
            if (password_verify($password, $user['password_hash'])) {
                $results[] = "✅ PASS: Password correct";
                
                // Step 3: Clear old session
                $results[] = "Step 3: Clearing old session data...";
                $oldKeys = array_keys($_SESSION);
                $results[] = "Old session keys: " . (empty($oldKeys) ? "NONE" : implode(', ', $oldKeys));
                
                $keysToRemove = ['staff_user', 'staff_logged_in', 'staff_uuid', 'staff_username', 'staff_full_name', 'staff_role', 'staff_id'];
                foreach ($keysToRemove as $key) {
                    if (isset($_SESSION[$key])) {
                        unset($_SESSION[$key]);
                    }
                }
                $results[] = "✅ PASS: Old session data cleared";
                
                // Step 4: Set new session
                $results[] = "Step 4: Setting new session data...";
                unset($user['password_hash']); // Remove password from session
                
                $_SESSION['staff_user'] = $user;
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_uuid'] = $user['uuid'];
                $_SESSION['staff_username'] = $user['username'];
                $_SESSION['staff_full_name'] = $user['full_name'];
                $_SESSION['staff_role'] = $user['role'];
                
                $results[] = "✅ PASS: Session data set";
                $results[] = "  - staff_uuid: " . $_SESSION['staff_uuid'];
                $results[] = "  - staff_username: " . $_SESSION['staff_username'];
                $results[] = "  - staff_role: " . $_SESSION['staff_role'];
                $results[] = "  - staff_logged_in: " . ($_SESSION['staff_logged_in'] ? 'true' : 'false');
                
                // Step 5: Verify session persistence
                $results[] = "Step 5: Verifying session is saved...";
                session_write_close();
                session_start();
                
                if (isset($_SESSION['staff_uuid']) && $_SESSION['staff_uuid'] === $user['uuid']) {
                    $results[] = "✅ PASS: Session persisted correctly";
                    $step = 'success';
                } else {
                    $results[] = "❌ FAIL: Session did NOT persist!";
                    $results[] = "Current session keys: " . implode(', ', array_keys($_SESSION));
                    $step = 'failed';
                }
                
            } else {
                $results[] = "❌ FAIL: Wrong password";
                $step = 'failed';
            }
        }
        
    } catch (Exception $e) {
        $results[] = "❌ ERROR: " . $e->getMessage();
        $step = 'failed';
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Flow Test</title>
    <style>
        body { font-family: 'Consolas', monospace; background: #0a0a0a; color: #0f0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .box { background: #1a1a1a; border: 2px solid #0f0; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h1 { color: #0f0; text-align: center; font-size: 28px; }
        h2 { color: #ff0; border-bottom: 2px solid #ff0; padding-bottom: 10px; }
        .pass { color: #0f0; }
        .fail { color: #f00; }
        .warn { color: #ff0; }
        pre { background: #000; padding: 10px; border-left: 3px solid #0f0; overflow-x: auto; }
        form { background: #2a2a2a; padding: 20px; border: 2px solid #0f0; border-radius: 8px; }
        input { padding: 10px; margin: 5px 0; width: 100%; background: #000; color: #0f0; border: 1px solid #0f0; font-family: monospace; }
        button { padding: 12px 24px; background: #0f0; color: #000; border: none; font-weight: bold; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px; }
        button:hover { background: #0c0; }
        .result { padding: 8px; margin: 5px 0; border-left: 3px solid #0f0; background: #0a0a0a; }
        .success-box { background: #0a3a0a; border-color: #0f0; }
        .fail-box { background: #3a0a0a; border-color: #f00; }
        a { color: #0ff; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 COMPLETE LOGIN FLOW TEST</h1>

        <?php if ($step === ''): ?>
            <div class="box">
                <h2>Enter Credentials to Test Login</h2>
                <form method="post">
                    <label>Username:</label>
                    <input type="text" name="username" required autofocus>
                    
                    <label>Password:</label>
                    <input type="password" name="password" required>
                    
                    <button type="submit" name="test_login">TEST LOGIN FLOW</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="box <?php echo $step === 'success' ? 'success-box' : 'fail-box'; ?>">
                <h2><?php echo $step === 'success' ? '✅ LOGIN TEST RESULTS' : '❌ LOGIN TEST RESULTS'; ?></h2>
                <?php foreach ($results as $result): ?>
                    <div class="result"><?php echo htmlspecialchars($result); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'success'): ?>
            <div class="box success-box">
                <h2>✅ SESSION SUCCESSFULLY CREATED</h2>
                <p class="pass">Your session is now active. Current session data:</p>
                <pre><?php print_r($_SESSION); ?></pre>
                
                <h3 style="color: #0f0;">Next Steps:</h3>
                <p>1. <a href="root_cause_analysis.php">Check Root Cause Analysis</a> - Should now show you're logged in</p>
                <p>2. <a href="/restaurant/?req=staff">Go to Staff Dashboard</a> - Should work correctly</p>
                <p>3. <a href="check_session.php">Check Session JSON</a> - Verify session data</p>
            </div>
        <?php endif; ?>

        <div class="box">
            <h2>📊 Current Session State</h2>
            <?php
            echo "<p>Session ID: <span class='pass'>" . session_id() . "</span></p>";
            echo "<p>Session Save Path: <span class='pass'>" . session_save_path() . "</span></p>";
            echo "<p>Logged In: " . (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] ? '<span class="pass">YES</span>' : '<span class="fail">NO</span>') . "</p>";
            echo "<p>Session Keys: " . (empty($_SESSION) ? '<span class="warn">EMPTY</span>' : '<span class="pass">' . implode(', ', array_keys($_SESSION)) . '</span>') . "</p>";
            ?>
        </div>

        <div class="box">
            <h2>⚙️ Quick Actions</h2>
            <p><a href="?">⟳ Reset This Page</a></p>
            <p><a href="emergency_fix.php">🔧 Emergency Fix Page</a></p>
            <p><a href="/restaurant/?req=staff">🏠 Staff Portal</a></p>
        </div>
    </div>
</body>
</html>
