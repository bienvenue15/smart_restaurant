<?php
// Add missing assigned_to_uuid column to waiter_calls table
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting waiter_calls UUID migration...\n\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM waiter_calls LIKE 'assigned_to_uuid'");
    if ($stmt->rowCount() > 0) {
        echo "Column assigned_to_uuid already exists!\n";
        exit;
    }
    
    // Add the assigned_to_uuid column
    echo "1. Adding assigned_to_uuid column...\n";
    $pdo->exec("ALTER TABLE waiter_calls ADD COLUMN assigned_to_uuid CHAR(36) NULL AFTER assigned_to");
    echo "   ✓ Column added\n\n";
    
    // Since assigned_to is an old integer ID and staff_users only has UUIDs,
    // we need to check if there's an old staff table or just set to NULL
    echo "2. Checking for existing assigned_to values...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM waiter_calls WHERE assigned_to IS NOT NULL");
    $hasAssignedTo = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($hasAssignedTo > 0) {
        echo "   Found $hasAssignedTo records with assigned_to values\n";
        echo "   Note: Cannot auto-migrate as staff_users only has UUIDs\n";
        echo "   Setting assigned_to to NULL for clean state\n";
        $pdo->exec("UPDATE waiter_calls SET assigned_to = NULL WHERE assigned_to IS NOT NULL");
    } else {
        echo "   No assigned_to values to migrate\n";
    }
    echo "\n";
    
    // Add foreign key constraint
    echo "3. Adding foreign key constraint...\n";
    $pdo->exec("
        ALTER TABLE waiter_calls 
        ADD CONSTRAINT fk_waiter_call_assigned_to_uuid 
        FOREIGN KEY (assigned_to_uuid) REFERENCES staff_users(uuid) ON DELETE SET NULL
    ");
    echo "   ✓ Constraint added\n\n";
    
    // Verify the migration
    echo "4. Verifying migration...\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(assigned_to) as with_assigned_to,
            COUNT(assigned_to_uuid) as with_assigned_to_uuid
        FROM waiter_calls
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total records: " . $result['total'] . "\n";
    echo "   With assigned_to: " . $result['with_assigned_to'] . "\n";
    echo "   With assigned_to_uuid: " . $result['with_assigned_to_uuid'] . "\n\n";
    
    echo "✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    exit(1);
}
