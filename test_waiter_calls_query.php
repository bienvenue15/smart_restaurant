<?php
// Test the waiter calls query directly
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing waiter calls query...\n\n";
    
    // Get a restaurant UUID
    $stmt = $pdo->query("SELECT uuid FROM restaurants LIMIT 1");
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        echo "No restaurants found\n";
        exit;
    }
    
    $restaurantId = $restaurant['uuid'];
    echo "Using restaurant UUID: $restaurantId\n\n";
    
    // Test the query from Order model
    $query = "SELECT wc.*, t.table_number, s.full_name as assigned_name
              FROM waiter_calls wc
              INNER JOIN restaurant_tables t ON wc.table_uuid = t.uuid
              LEFT JOIN staff_users s ON wc.assigned_to_uuid = s.uuid
              WHERE wc.restaurant_uuid = ?
              ORDER BY wc.priority DESC, wc.created_at ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$restaurantId]);
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($calls) . " waiter calls\n\n";
    
    if (count($calls) > 0) {
        echo "Sample call:\n";
        print_r($calls[0]);
    }
    
    echo "\n✅ Query executed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
