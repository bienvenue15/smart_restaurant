<?php
// Emergency debug for staff_update_call_status
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/init_session.php';

echo "<h2>Emergency Debug - staff_update_call_status</h2>";

// Check session
echo "<h3>1. Session Check</h3>";
if (isset($_SESSION['staff_user'])) {
    echo "✅ Staff session exists<br>";
    echo "UUID: " . ($_SESSION['staff_user']['uuid'] ?? 'NOT SET') . "<br>";
    echo "Role: " . ($_SESSION['staff_user']['role'] ?? 'NOT SET') . "<br>";
    echo "Restaurant: " . ($_SESSION['staff_user']['restaurant_uuid'] ?? 'NOT SET') . "<br>";
} else {
    echo "❌ No staff session<br>";
}

if (isset($_SESSION['staff_uuid'])) {
    echo "✅ staff_uuid set: " . $_SESSION['staff_uuid'] . "<br>";
} else {
    echo "❌ staff_uuid NOT set<br>";
}

// Check permissions
echo "<h3>2. Permission Check</h3>";
require_once 'src/config.php';
require_once 'app/models/Staff.php';
require_once 'app/core/Permission.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $staffModel = new Staff($pdo);
    
    if (isset($_SESSION['staff_uuid'])) {
        $staffUuid = $_SESSION['staff_uuid'];
        
        $hasViewTables = $staffModel->hasPermission($staffUuid, 'view_tables');
        $hasManageTables = $staffModel->hasPermission($staffUuid, 'manage_tables');
        
        echo "view_tables: " . ($hasViewTables ? "✅ YES" : "❌ NO") . "<br>";
        echo "manage_tables: " . ($hasManageTables ? "✅ YES" : "❌ NO") . "<br>";
        
        if (!$hasViewTables && !$hasManageTables) {
            echo "<p style='color:red;'><strong>❌ ROOT CAUSE: Staff has NEITHER view_tables NOR manage_tables permission!</strong></p>";
        } else {
            echo "<p style='color:green;'>✅ Staff has required permissions</p>";
        }
        
        // Check shift
        echo "<h3>3. Shift Check</h3>";
        $query = "SELECT * FROM staff_shifts 
                  WHERE staff_uuid = ? 
                  AND DATE(clock_in) = CURDATE() 
                  AND clock_out IS NULL 
                  AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$staffUuid]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shift) {
            echo "✅ Active shift found<br>";
            echo "Clock In: " . $shift['clock_in'] . "<br>";
        } else {
            echo "❌ No active shift<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Test simulated API call
echo "<h3>4. Simulated API Call</h3>";
echo "<form method='post' action='?req=api&action=staff_update_call_status'>";
echo "<input type='hidden' name='call_id' value='test-uuid-123'>";
echo "<input type='hidden' name='status' value='acknowledged'>";
echo "<button type='submit'>Test Update Call Status</button>";
echo "</form>";
