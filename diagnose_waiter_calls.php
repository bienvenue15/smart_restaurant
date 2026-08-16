<?php
// Root cause analysis for waiter calls error
require_once 'src/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<style>body { font-family: monospace; line-height: 1.6; padding: 20px; } 
      .pass { color: green; font-weight: bold; }
      .fail { color: red; font-weight: bold; }
      .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-left: 4px solid #333; }
      h2 { color: #333; border-bottom: 2px solid #666; padding-bottom: 5px; }
      </style>";

echo "<h1>🔍 Waiter Calls - Root Cause Analysis</h1>";

// 1. Check Session
echo "<div class='section'><h2>1. Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Active: " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='pass'>✅ YES</span>" : "<span class='fail'>❌ NO</span>") . "<br>";

if (isset($_SESSION['staff_user'])) {
    echo "Staff Session: <span class='pass'>✅ EXISTS</span><br>";
    echo "Staff UUID: " . ($_SESSION['staff_user']['uuid'] ?? 'NOT SET') . "<br>";
    echo "Restaurant UUID: " . ($_SESSION['staff_user']['restaurant_uuid'] ?? 'NOT SET') . "<br>";
    echo "Role: " . ($_SESSION['staff_user']['role'] ?? 'NOT SET') . "<br>";
    $staffUuid = $_SESSION['staff_user']['uuid'] ?? null;
} else {
    echo "Staff Session: <span class='fail'>❌ NOT FOUND</span><br>";
    echo "<strong>❌ ROOT CAUSE: Staff not logged in</strong><br>";
    $staffUuid = null;
}
echo "</div>";

if (!$staffUuid) {
    echo "<div class='section'><h2>❌ CRITICAL ERROR</h2>";
    echo "Cannot proceed without staff session. Please log in first.";
    echo "</div>";
    exit;
}

// 2. Check Database Connection
echo "<div class='section'><h2>2. Database Connection</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database: <span class='pass'>✅ CONNECTED</span><br>";
} catch (Exception $e) {
    echo "Database: <span class='fail'>❌ ERROR: " . $e->getMessage() . "</span><br>";
    exit;
}
echo "</div>";

// 3. Check Shift Status
echo "<div class='section'><h2>3. Shift Status</h2>";
try {
    $query = "SELECT * FROM staff_shifts 
              WHERE staff_uuid = ? 
              AND DATE(clock_in) = CURDATE() 
              AND clock_out IS NULL 
              AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$staffUuid]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shift) {
        echo "Active Shift: <span class='pass'>✅ YES</span><br>";
        echo "Shift ID: " . $shift['id'] . "<br>";
        echo "Clock In: " . $shift['clock_in'] . "<br>";
        echo "Status: " . $shift['status'] . "<br>";
    } else {
        echo "Active Shift: <span class='fail'>❌ NO</span><br>";
        echo "<strong>⚠️ ISSUE FOUND: Staff is not clocked in</strong><br>";
        echo "Staff must clock in before responding to waiter calls.<br>";
    }
} catch (Exception $e) {
    echo "Shift Check: <span class='fail'>❌ ERROR: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 4. Check Permissions
echo "<div class='section'><h2>4. Permissions</h2>";
try {
    require_once 'app/models/Staff.php';
    $staffModel = new Staff($pdo);
    
    $perms = ['view_tables', 'manage_tables'];
    foreach ($perms as $perm) {
        $hasPerm = $staffModel->hasPermission($staffUuid, $perm);
        echo "$perm: " . ($hasPerm ? "<span class='pass'>✅ YES</span>" : "<span class='fail'>❌ NO</span>") . "<br>";
    }
} catch (Exception $e) {
    echo "Permission Check: <span class='fail'>❌ ERROR: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 5. Check waiter_calls table structure
echo "<div class='section'><h2>5. Waiter Calls Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE waiter_calls");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['uuid', 'table_uuid', 'assigned_to_uuid', 'restaurant_uuid', 'status'];
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columns);
        echo "$col: " . ($exists ? "<span class='pass'>✅ EXISTS</span>" : "<span class='fail'>❌ MISSING</span>") . "<br>";
    }
} catch (Exception $e) {
    echo "Table Check: <span class='fail'>❌ ERROR: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 6. Test the actual query
echo "<div class='section'><h2>6. Query Test</h2>";
try {
    $restaurantId = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
    
    if ($restaurantId) {
        $query = "SELECT wc.*, t.table_number, s.full_name as assigned_name
                  FROM waiter_calls wc
                  INNER JOIN restaurant_tables t ON wc.table_uuid = t.uuid
                  LEFT JOIN staff_users s ON wc.assigned_to_uuid = s.uuid
                  WHERE wc.restaurant_uuid = ?
                  LIMIT 5";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$restaurantId]);
        $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Query Execution: <span class='pass'>✅ SUCCESS</span><br>";
        echo "Calls Found: " . count($calls) . "<br>";
    } else {
        echo "Query Execution: <span class='fail'>❌ No restaurant ID</span><br>";
    }
} catch (Exception $e) {
    echo "Query Test: <span class='fail'>❌ ERROR: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 7. Summary
echo "<div class='section'><h2>📋 ROOT CAUSE SUMMARY</h2>";
echo "<h3>Primary Issue:</h3>";
if (!$shift) {
    echo "<p><strong style='color:red;'>❌ Staff member is NOT clocked in (no active shift)</strong></p>";
    echo "<p>The application requires staff to clock in before they can:</p>";
    echo "<ul>";
    echo "<li>Acknowledge waiter calls</li>";
    echo "<li>Complete waiter calls</li>";
    echo "<li>Update call status</li>";
    echo "</ul>";
    echo "<p><strong>SOLUTION:</strong> Staff must click the 'Clock In' button on their dashboard before responding to calls.</p>";
} else {
    echo "<p><span class='pass'>✅ All checks passed</span></p>";
    echo "<p>The system should be working correctly.</p>";
}
echo "</div>";
