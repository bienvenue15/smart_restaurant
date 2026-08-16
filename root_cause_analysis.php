<?php
/**
 * ROOT CAUSE ANALYSIS - LOGIN & PERMISSIONS
 * This will trace the entire problem step by step
 */
require_once __DIR__ . '/init_session.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/restaurant');
}
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/app/core/Permission.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Root Cause Analysis</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .section { background: #2a2a2a; border: 2px solid #00ff00; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .pass { color: #00ff00; font-weight: bold; }
        .fail { color: #ff0000; font-weight: bold; }
        .warn { color: #ffaa00; font-weight: bold; }
        h2 { border-bottom: 2px solid #00ff00; padding-bottom: 10px; }
        pre { background: #000; padding: 10px; border-left: 3px solid #00ff00; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #444; text-align: left; }
        th { background: #333; color: #ffaa00; }
    </style>
</head>
<body>
    <h1>🔍 ROOT CAUSE ANALYSIS</h1>

    <!-- STEP 1: Session Check -->
    <div class="section">
        <h2>STEP 1: Session Data Analysis</h2>
        <?php
        $sessionOk = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'];
        $hasUuid = isset($_SESSION['staff_uuid']);
        $hasRole = isset($_SESSION['staff_role']);
        $hasUser = isset($_SESSION['staff_user']);
        
        echo $sessionOk ? '<p class="pass">✅ User is logged in</p>' : '<p class="fail">❌ User NOT logged in</p>';
        echo $hasUuid ? '<p class="pass">✅ staff_uuid exists</p>' : '<p class="fail">❌ staff_uuid missing</p>';
        echo $hasRole ? '<p class="pass">✅ staff_role exists</p>' : '<p class="fail">❌ staff_role missing</p>';
        echo $hasUser ? '<p class="pass">✅ staff_user exists</p>' : '<p class="fail">❌ staff_user missing</p>';
        
        if ($hasUuid) {
            echo "<p>UUID: <span class='pass'>" . $_SESSION['staff_uuid'] . "</span></p>";
        }
        if ($hasRole) {
            echo "<p>Role: <span class='pass'>" . $_SESSION['staff_role'] . "</span></p>";
        }
        
        echo "<h3>All Session Keys:</h3><pre>";
        print_r(array_keys($_SESSION));
        echo "</pre>";
        
        if ($hasUser) {
            echo "<h3>Full staff_user Data:</h3><pre>";
            print_r($_SESSION['staff_user']);
            echo "</pre>";
        }
        ?>
    </div>

    <!-- STEP 2: Database User Check -->
    <div class="section">
        <h2>STEP 2: Database User Verification</h2>
        <?php
        if ($hasUuid) {
            try {
                $db = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PWD,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $stmt = $db->prepare("SELECT uuid, username, role, restaurant_uuid, is_active FROM staff_users WHERE uuid = ?");
                $stmt->execute([$_SESSION['staff_uuid']]);
                $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dbUser) {
                    echo '<p class="pass">✅ User found in database</p>';
                    echo "<table>";
                    foreach ($dbUser as $key => $val) {
                        echo "<tr><th>$key</th><td>$val</td></tr>";
                    }
                    echo "</table>";
                    
                    // Compare session vs database
                    if ($dbUser['role'] !== $_SESSION['staff_role']) {
                        echo '<p class="fail">❌ ROLE MISMATCH!</p>';
                        echo "<p>Session role: " . $_SESSION['staff_role'] . "</p>";
                        echo "<p>Database role: " . $dbUser['role'] . "</p>";
                    } else {
                        echo '<p class="pass">✅ Session role matches database</p>';
                    }
                } else {
                    echo '<p class="fail">❌ User NOT found in database with UUID: ' . $_SESSION['staff_uuid'] . '</p>';
                }
            } catch (Exception $e) {
                echo '<p class="fail">❌ Database Error: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="warn">⚠️ Skipped - no UUID in session</p>';
        }
        ?>
    </div>

    <!-- STEP 3: Permission Check -->
    <div class="section">
        <h2>STEP 3: Permission System Analysis</h2>
        <?php
        if ($hasUuid && $hasRole) {
            echo "<p>Testing Permission class...</p>";
            
            // Check if Permission class exists
            if (class_exists('Permission')) {
                echo '<p class="pass">✅ Permission class loaded</p>';
                
                // Test various permissions
                $permissions = [
                    'view_orders',
                    'manage_orders',
                    'view_tables',
                    'manage_menu',
                    'manage_staff',
                    'view_reports',
                    'handle_cash',
                    'approve_actions'
                ];
                
                echo "<h3>Permission Tests for role: " . $_SESSION['staff_role'] . "</h3>";
                echo "<table>";
                echo "<tr><th>Permission</th><th>Result</th></tr>";
                
                foreach ($permissions as $perm) {
                    try {
                        $hasPermission = Permission::check($perm);
                        $result = $hasPermission ? '<span class="pass">✅ ALLOWED</span>' : '<span class="fail">❌ DENIED</span>';
                        echo "<tr><td>$perm</td><td>$result</td></tr>";
                    } catch (Exception $e) {
                        echo "<tr><td>$perm</td><td><span class='fail'>ERROR: " . $e->getMessage() . "</span></td></tr>";
                    }
                }
                echo "</table>";
                
            } else {
                echo '<p class="fail">❌ Permission class NOT loaded!</p>';
            }
        } else {
            echo '<p class="warn">⚠️ Skipped - user not logged in</p>';
        }
        ?>
    </div>

    <!-- STEP 4: Sidebar Menu Conditions -->
    <div class="section">
        <h2>STEP 4: Sidebar Menu Visibility Check</h2>
        <?php
        if ($hasUser && $hasRole) {
            $user = $_SESSION['staff_user'];
            $role = $_SESSION['staff_role'];
            
            echo "<table>";
            echo "<tr><th>Menu Item</th><th>Condition</th><th>Visible?</th></tr>";
            
            // Orders
            $showOrders = Permission::check('view_orders') || $role === 'kitchen';
            echo "<tr><td>📝 Orders</td><td>Permission::check('view_orders') OR role=kitchen</td><td>" . ($showOrders ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Waiter Calls
            $showCalls = Permission::check('view_tables');
            echo "<tr><td>🔔 Calls</td><td>Permission::check('view_tables')</td><td>" . ($showCalls ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Cash
            $canHandleCash = isset($user['can_handle_cash']) ? $user['can_handle_cash'] : Permission::check('handle_cash');
            echo "<tr><td>💵 Cash</td><td>can_handle_cash OR Permission::check('handle_cash')</td><td>" . ($canHandleCash ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Liabilities
            $showLiabilities = in_array($role, ['admin', 'manager', 'waiter', 'cashier']);
            echo "<tr><td>🧾 Liabilities</td><td>role IN [admin,manager,waiter,cashier]</td><td>" . ($showLiabilities ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Approvals
            $canApprove = Permission::check('approve_actions');
            echo "<tr><td>✅ Approvals</td><td>Permission::check('approve_actions')</td><td>" . ($canApprove ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Reports
            $showReports = Permission::check('view_reports');
            echo "<tr><td>📊 Reports</td><td>Permission::check('view_reports')</td><td>" . ($showReports ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            // Admin Panel
            $hasAdminPanel = Permission::check('manage_menu') || Permission::check('manage_tables') || Permission::check('manage_orders') || Permission::check('manage_staff') || Permission::check('manage_settings') || Permission::check('view_reports');
            echo "<tr><td>⚙️ Admin Panel</td><td>Any manage_* permission</td><td>" . ($hasAdminPanel ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>') . "</td></tr>";
            
            echo "</table>";
        } else {
            echo '<p class="warn">⚠️ Skipped - user not logged in</p>';
        }
        ?>
    </div>

    <!-- STEP 5: Permission Table Check -->
    <div class="section">
        <h2>STEP 5: Database Permissions Table</h2>
        <?php
        if ($hasRole) {
            try {
                $db = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PWD,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $stmt = $db->prepare("SELECT * FROM role_permissions WHERE role = ?");
                $stmt->execute([$_SESSION['staff_role']]);
                $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($permissions) {
                    echo '<p class="pass">✅ Found ' . count($permissions) . ' permissions for role: ' . $_SESSION['staff_role'] . '</p>';
                    echo "<pre>";
                    print_r($permissions);
                    echo "</pre>";
                } else {
                    echo '<p class="fail">❌ NO permissions found for role: ' . $_SESSION['staff_role'] . '</p>';
                    echo '<p class="warn">⚠️ This is the ROOT CAUSE - role has no permissions in database!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="fail">❌ Error: ' . $e->getMessage() . '</p>';
            }
        }
        ?>
    </div>

    <!-- ROOT CAUSE SUMMARY -->
    <div class="section">
        <h2>🎯 ROOT CAUSE SUMMARY</h2>
        <?php
        if (!$sessionOk) {
            echo '<p class="fail">❌ ROOT CAUSE: User is not logged in</p>';
            echo '<p>SOLUTION: Login at <a href="/restaurant/?req=staff" style="color:#00ff00;">/restaurant/?req=staff</a></p>';
        } elseif (!$hasUuid) {
            echo '<p class="fail">❌ ROOT CAUSE: staff_uuid missing from session</p>';
            echo '<p>SOLUTION: Logout and login again to refresh session</p>';
        } elseif (!$hasRole) {
            echo '<p class="fail">❌ ROOT CAUSE: staff_role missing from session</p>';
            echo '<p>SOLUTION: Logout and login again to refresh session</p>';
        } else {
            // Check if permissions exist
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PWD);
            $stmt = $db->prepare("SELECT COUNT(*) FROM role_permissions WHERE role = ?");
            $stmt->execute([$_SESSION['staff_role']]);
            $permCount = $stmt->fetchColumn();
            
            if ($permCount == 0) {
                echo '<p class="fail">❌ ROOT CAUSE: No permissions in database for role: ' . $_SESSION['staff_role'] . '</p>';
                echo '<p class="warn">⚠️ The role_permissions table is missing entries for this role!</p>';
                echo '<p>SOLUTION: Insert default permissions for the ' . $_SESSION['staff_role'] . ' role</p>';
            } else {
                echo '<p class="pass">✅ Everything looks correct!</p>';
                echo '<p>Session is valid, permissions exist, role is set correctly.</p>';
                echo '<p>If sidebar still shows wrong menus, clear browser cache with Ctrl+Shift+R</p>';
            }
        }
        ?>
    </div>

    <p style="text-align: center; margin-top: 40px;">
        <a href="/restaurant/?req=staff" style="color: #00ff00; font-size: 20px;">▶ GO TO DASHBOARD</a>
    </p>
</body>
</html>
