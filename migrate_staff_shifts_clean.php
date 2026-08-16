<?php
/**
 * Migration: staff_shifts table to use UUIDs (Clean version)
 */

$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== STAFF_SHIFTS UUID MIGRATION ===\n\n";

try {
    // Check current structure
    echo "Checking current structure...\n";
    $stmt = $db->query("SHOW COLUMNS FROM staff_shifts");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    echo "Current columns: " . implode(', ', $columns) . "\n\n";

    $hasStaffUuid = in_array('staff_uuid', $columns);
    $hasRestaurantUuid = in_array('restaurant_uuid', $columns);
    $hasStaffId = in_array('staff_id', $columns);
    $hasRestaurantId = in_array('restaurant_id', $columns);

    // Step 1: Add UUID columns if they don't exist
    if (!$hasStaffUuid || !$hasRestaurantUuid) {
        echo "Step 1: Adding missing UUID columns...\n";
        if (!$hasStaffUuid) {
            $db->exec("ALTER TABLE staff_shifts ADD COLUMN staff_uuid CHAR(36) NULL AFTER id");
            echo "✓ staff_uuid added\n";
        }
        if (!$hasRestaurantUuid) {
            $db->exec("ALTER TABLE staff_shifts ADD COLUMN restaurant_uuid CHAR(36) NULL AFTER " . ($hasStaffUuid ? "staff_uuid" : "id"));
            echo "✓ restaurant_uuid added\n";
        }
        echo "\n";
    } else {
        echo "Step 1: UUID columns already exist\n\n";
    }

    // Step 2: Clear old records (can't migrate without integer id in staff_users)
    echo "Step 2: Checking for records to migrate...\n";
    $stmt = $db->query("SELECT COUNT(*) FROM staff_shifts");
    $count = $stmt->fetchColumn();
    echo "Found $count shift records\n";
    
    if ($count > 0) {
        echo "Clearing old shift records (cannot map old IDs to UUIDs)...\n";
        $db->exec("TRUNCATE TABLE staff_shifts");
        echo "✓ Table cleared\n\n";
    } else {
        echo "✓ No records to clear\n\n";
    }

    // Step 3: Drop old integer columns if they exist
    if ($hasStaffId || $hasRestaurantId) {
        echo "Step 3: Dropping old integer ID columns...\n";
        if ($hasStaffId) {
            $db->exec("ALTER TABLE staff_shifts DROP COLUMN staff_id");
            echo "✓ staff_id dropped\n";
        }
        if ($hasRestaurantId) {
            $db->exec("ALTER TABLE staff_shifts DROP COLUMN restaurant_id");
            echo "✓ restaurant_id dropped\n";
        }
        echo "\n";
    } else {
        echo "Step 3: Old columns already removed\n\n";
    }

    // Step 4: Add indexes if they don't exist
    echo "Step 4: Adding indexes...\n";
    $stmt = $db->query("SHOW INDEX FROM staff_shifts WHERE Key_name = 'idx_staff_uuid'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE staff_shifts ADD INDEX idx_staff_uuid (staff_uuid)");
        echo "✓ idx_staff_uuid added\n";
    } else {
        echo "✓ idx_staff_uuid already exists\n";
    }
    
    $stmt = $db->query("SHOW INDEX FROM staff_shifts WHERE Key_name = 'idx_restaurant_uuid'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE staff_shifts ADD INDEX idx_restaurant_uuid (restaurant_uuid)");
        echo "✓ idx_restaurant_uuid added\n";
    } else {
        echo "✓ idx_restaurant_uuid already exists\n";
    }
    echo "\n";

    echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n\n";
    
    // Show final structure
    echo "=== FINAL TABLE STRUCTURE ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM staff_shifts");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
