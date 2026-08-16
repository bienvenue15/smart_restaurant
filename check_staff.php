<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'src/config.php';

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PWD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== STAFF USERS ===\n";
    $stmt = $db->query('SELECT uuid, username, role, restaurant_uuid FROM staff_users LIMIT 5');
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($staff as $s) {
        echo "Username: {$s['username']} | Role: {$s['role']} | UUID: {$s['uuid']} | Restaurant: {$s['restaurant_uuid']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
