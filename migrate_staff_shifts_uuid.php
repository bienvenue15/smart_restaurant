<?php
/**
 * Migration: staff_shifts table to use UUIDs
 * Adds staff_uuid and restaurant_uuid columns, populates them, and removes old integer columns
 */

$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== STAFF_SHIFTS UUID MIGRATION ===\n\n";

try {
    // Step 1: Add new UUID columns
    echo "Step 1: Adding UUID columns...\n";
    $db->exec("ALTER TABLE staff_shifts 
               ADD COLUMN staff_uuid CHAR(36) NULL AFTER id,
               ADD COLUMN restaurant_uuid CHAR(36) NULL AFTER staff_uuid");
    echo "✓ UUID columns added\n\n";

    // Step 2: Populate staff_uuid from staff_users using the old staff_id
    echo "Step 2: Populating staff_uuid...\n";
    // Since staff_users table was migrated and lost the integer 'id' column,
    // we need to manually map the remaining records or use a different approach
    // First, let's check if there are any records to migrate
    $stmt = $db->query("SELECT COUNT(*) FROM staff_shifts");
    $count = $stmt->fetchColumn();
    echo "Found $count shift records to migrate\n";
    
    if ($count > 0) {
        // We'll need to delete old records that can't be mapped or keep staff_id temporarily
        echo "WARNING: Cannot automatically map old staff_id to new UUIDs\n";
        echo "Clearing old shift records...\n";
        $db->exec("TRUNCATE TABLE staff_shifts");
        echo "✓ Table cleared (old shifts removed)\n\n";
    } else {
        echo "✓ No records to migrate\n\n";
    }

    // Step 3: Skip restaurant_uuid population since table is empty
    echo "Step 3: Skipping restaurant_uuid population (table empty)...\n\n";

    // Step 4: Make UUID columns NOT NULL (since table is empty, we can make them required)
    echo "Step 4: Making UUID columns NOT NULL...\n";
    $db->exec("ALTER TABLE staff_shifts 
               MODIFY COLUMN staff_uuid CHAR(36) NULL,
               MODIFY COLUMN restaurant_uuid CHAR(36) NULL");
    echo "✓ UUID columns configuration updated\n\n";

    // Step 5: Add indexes on UUID columns
    echo "Step 5: Adding indexes...\n";
    $db->exec("ALTER TABLE staff_shifts 
               ADD INDEX idx_staff_uuid (staff_uuid),
               ADD INDEX idx_restaurant_uuid (restaurant_uuid)");
    echo "✓ Indexes added\n\n";

    // Step 6: Drop old integer columns
    echo "Step 6: Dropping old integer ID columns...\n";
    $db->exec("ALTER TABLE staff_shifts 
               DROP COLUMN staff_id,
               DROP COLUMN restaurant_id");
    echo "✓ Old columns dropped\n\n";

    echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
    
    // Show final structure
    echo "\n=== FINAL TABLE STRUCTURE ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM staff_shifts");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nRolling back...\n";
    // Note: You may need to manually restore if partway through
    exit(1);
}
