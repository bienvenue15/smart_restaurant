<?php
/**
 * Fix All Staff Passwords
 */

require_once 'src/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PWD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Updating all staff passwords to: admin123\n\n";
    
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE staff_users SET password_hash = ?");
    $stmt->execute([$newHash]);
    
    $count = $stmt->rowCount();
    
    echo "✓ Updated {$count} user passwords\n\n";
    
    // Verify all users
    $stmt = $db->query("SELECT username, full_name, role FROM staff_users WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Login Credentials ===\n";
    foreach ($users as $user) {
        echo "Username: {$user['username']} | Password: admin123 | Role: {$user['role']}\n";
    }
    
    echo "\n✓ All passwords updated successfully!\n";
    echo "You can now login at: " . BASE_URL . "/?req=staff\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
