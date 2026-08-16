<?php
// Check staff_shifts table structure
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking staff_shifts table:\n\n";
    
    $stmt = $pdo->query('DESCRIBE staff_shifts');
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Key'] ? "[" . $row['Key'] . "]" : "") . "\n";
    }
    
    echo "\n";
    echo "Has id column: " . (in_array('id', $columns) ? 'YES' : 'NO') . "\n";
    echo "Has uuid column: " . (in_array('uuid', $columns) ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
