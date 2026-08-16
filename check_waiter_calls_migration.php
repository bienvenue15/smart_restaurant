<?php
// Check if waiter_calls has been migrated to UUID
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    
    echo "Checking waiter_calls table:\n\n";
    
    $stmt = $pdo->query('DESCRIBE waiter_calls');
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n";
    echo "Has assigned_to: " . (in_array('assigned_to', $columns) ? 'YES' : 'NO') . "\n";
    echo "Has assigned_to_uuid: " . (in_array('assigned_to_uuid', $columns) ? 'YES' : 'NO') . "\n";
    
    // Check if there are any records
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM waiter_calls');
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal waiter_calls records: " . $count['count'] . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
