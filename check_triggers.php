<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'src/config.php';

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PWD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Checking Triggers ===\n";
    $stmt = $db->query("SHOW TRIGGERS FROM db_restaurant");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($triggers as $trigger) {
        echo "\nTrigger: {$trigger['Trigger']}\n";
        echo "Table: {$trigger['Table']}\n";
        echo "Event: {$trigger['Event']}\n";
        echo "Timing: {$trigger['Timing']}\n";
        
        // Get trigger definition
        $stmt = $db->query("SHOW CREATE TRIGGER `{$trigger['Trigger']}`");
        $def = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($def && isset($def['SQL Original Statement'])) {
            echo "Definition:\n{$def['SQL Original Statement']}\n";
        }
        echo str_repeat("-", 80) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
