<?php
require_once __DIR__ . '/init_session.php';

echo "Session and Shift Status:\n\n";

if (isset($_SESSION['staff_user'])) {
    $staffUuid = $_SESSION['staff_user']['uuid'] ?? 'NOT SET';
    echo "Staff UUID: $staffUuid\n\n";
    
    if ($staffUuid && $staffUuid !== 'NOT SET') {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check for active shift
            $query = "SELECT * FROM staff_shifts 
                      WHERE staff_uuid = ? 
                      AND DATE(clock_in) = CURDATE() 
                      AND clock_out IS NULL 
                      AND status = 'active'";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$staffUuid]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shift) {
                echo "✅ Active shift found:\n";
                echo "  Shift ID: " . $shift['id'] . "\n";
                echo "  Clock In: " . $shift['clock_in'] . "\n";
                echo "  Status: " . $shift['status'] . "\n";
            } else {
                echo "❌ No active shift found\n";
                echo "\nChecking all shifts for today:\n";
                
                $query2 = "SELECT * FROM staff_shifts 
                          WHERE staff_uuid = ? 
                          AND DATE(clock_in) = CURDATE()";
                $stmt2 = $pdo->prepare($query2);
                $stmt2->execute([$staffUuid]);
                $allShifts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($allShifts) > 0) {
                    echo "Found " . count($allShifts) . " shift(s) today:\n";
                    foreach ($allShifts as $s) {
                        echo "  - ID: {$s['id']}, Status: {$s['status']}, Clock In: {$s['clock_in']}, Clock Out: {$s['clock_out']}\n";
                    }
                } else {
                    echo "No shifts found for today.\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "❌ No staff session found\n";
}
