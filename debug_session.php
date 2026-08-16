<?php
require_once __DIR__ . '/init_session.php';

echo "Session Debug Info:\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n\n";

if (isset($_SESSION)) {
    echo "Session Variables:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . "\n";
        } else {
            echo "  $key: " . $value . "\n";
        }
    }
} else {
    echo "No session variables found\n";
}

echo "\n";

// Check if staff is logged in
if (isset($_SESSION['staff_uuid'])) {
    echo "✅ Staff UUID is set: " . $_SESSION['staff_uuid'] . "\n";
    
    // Check permissions
    try {
        require_once 'src/config.php';
        require_once 'app/models/Staff.php';
        
        $staff = new Staff();
        $permissions = $staff->hasPermission($_SESSION['staff_uuid'], 'manage_tables');
        
        echo "Has 'manage_tables' permission: " . ($permissions ? 'YES' : 'NO') . "\n";
        
    } catch (Exception $e) {
        echo "Error checking permissions: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Staff UUID is NOT set in session\n";
}
