<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'src/config.php';

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PWD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Updating Database Triggers for UUID ===\n\n";
    
    // Drop and recreate trg_order_update_table_status
    echo "1. Updating trg_order_update_table_status...\n";
    $db->exec("DROP TRIGGER IF EXISTS trg_order_update_table_status");
    $db->exec("
        CREATE TRIGGER trg_order_update_table_status 
        AFTER INSERT ON orders 
        FOR EACH ROW 
        BEGIN
            UPDATE restaurant_tables
            SET status = 'occupied', last_occupied_at = NOW()
            WHERE uuid = NEW.table_uuid AND status = 'available';
        END
    ");
    echo "✓ Updated\n\n";
    
    // Update menu_items trigger
    echo "2. Updating before_menu_item_insert...\n";
    $db->exec("DROP TRIGGER IF EXISTS before_menu_item_insert");
    $db->exec("
        CREATE TRIGGER before_menu_item_insert
        BEFORE INSERT ON menu_items
        FOR EACH ROW
        BEGIN
            IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
                SET NEW.uuid = UUID();
            END IF;
        END
    ");
    echo "✓ Updated (removed restaurant_id fallback)\n\n";
    
    // Update order_items trigger
    echo "3. Updating before_order_item_insert...\n";
    $db->exec("DROP TRIGGER IF EXISTS before_order_item_insert");
    $db->exec("
        CREATE TRIGGER before_order_item_insert
        BEFORE INSERT ON order_items
        FOR EACH ROW
        BEGIN
            IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
                SET NEW.uuid = UUID();
            END IF;
        END
    ");
    echo "✓ Updated (removed id fallbacks)\n\n";
    
    echo "=== All Triggers Updated Successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
