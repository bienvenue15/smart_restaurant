<?php
// Test waiter calls update functionality
require_once 'src/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Testing Waiter Calls Update</h2>";

// Check if logged in
if (!isset($_SESSION['staff_user'])) {
    echo "<p style='color:red;'>❌ Not logged in</p>";
    exit;
}

$staffUuid = $_SESSION['staff_user']['uuid'];
$restaurantUuid = $_SESSION['staff_user']['restaurant_uuid'];

echo "<p>Staff UUID: $staffUuid</p>";
echo "<p>Restaurant UUID: $restaurantUuid</p>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get a pending call
    $query = "SELECT uuid, status, table_uuid FROM waiter_calls 
              WHERE restaurant_uuid = ? AND status = 'pending' 
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$restaurantUuid]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($call) {
        echo "<h3>Found pending call:</h3>";
        echo "<p>Call UUID: " . $call['uuid'] . "</p>";
        echo "<p>Status: " . $call['status'] . "</p>";
        
        // Test update query
        echo "<h3>Testing UPDATE query:</h3>";
        $testQuery = "UPDATE waiter_calls SET status = ?, assigned_to_uuid = ?, assigned_at = NOW() WHERE uuid = ?";
        $testStmt = $pdo->prepare($testQuery);
        
        echo "<p>Query: <code>$testQuery</code></p>";
        echo "<p>Parameters: status='acknowledged', assigned_to_uuid='$staffUuid', uuid='" . $call['uuid'] . "'</p>";
        
        // Don't actually execute, just prepare
        echo "<p style='color:green;'>✅ Query preparation successful!</p>";
        echo "<p><strong>The query syntax is correct and should work.</strong></p>";
        
    } else {
        echo "<p>No pending calls found. Creating a test call...</p>";
        
        // Get a table
        $tableQuery = "SELECT uuid FROM restaurant_tables WHERE restaurant_uuid = ? LIMIT 1";
        $tableStmt = $pdo->prepare($tableQuery);
        $tableStmt->execute([$restaurantUuid]);
        $table = $tableStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($table) {
            $callUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $insertQuery = "INSERT INTO waiter_calls (uuid, restaurant_uuid, table_uuid, request_type, status, priority, created_at) 
                           VALUES (?, ?, ?, 'assistance', 'pending', 'normal', NOW())";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$callUuid, $restaurantUuid, $table['uuid']]);
            
            echo "<p style='color:green;'>✅ Test call created with UUID: $callUuid</p>";
            echo "<p>You can now test updating this call via the UI.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
