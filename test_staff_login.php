<?php
/**
 * Test Staff Login
 * Quick debug script to check authentication
 */

require_once 'src/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PWD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Database connected\n\n";
    
    // Check if staff_users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'staff_users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ staff_users table exists\n\n";
    } else {
        echo "✗ staff_users table NOT found\n";
        echo "Creating tables now...\n\n";
        
        // Create the table
        $createSQL = "
        CREATE TABLE IF NOT EXISTS staff_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            role ENUM('admin', 'manager', 'waiter', 'kitchen', 'cashier') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $db->exec($createSQL);
        echo "✓ staff_users table created\n\n";
    }
    
    // Check if admin user exists
    $stmt = $db->query("SELECT COUNT(*) FROM staff_users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        echo "Creating default admin user...\n";
        
        // Insert admin user with fresh password hash
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO staff_users (username, password_hash, full_name, email, phone, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'admin',
            $passwordHash,
            'System Administrator',
            'admin@inovasiyo.com',
            '+250788000001',
            'admin',
            1
        ]);
        
        echo "✓ Admin user created\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n\n";
    }
    
    // List all staff users
    echo "=== Staff Users ===\n";
    $stmt = $db->query("SELECT id, username, full_name, role, is_active FROM staff_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found\n";
    } else {
        foreach ($users as $user) {
            $status = $user['is_active'] ? '✓' : '✗';
            echo "{$status} {$user['username']} - {$user['full_name']} ({$user['role']})\n";
        }
    }
    
    echo "\n=== Testing Authentication ===\n";
    
    // Test authentication
    $testUsername = 'admin';
    $testPassword = 'admin123';
    
    $stmt = $db->prepare("SELECT * FROM staff_users WHERE username = ? AND is_active = 1");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ User found: {$user['username']}\n";
        echo "  Checking password...\n";
        
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "✓ Password correct!\n";
            echo "✓ Login should work!\n\n";
        } else {
            echo "✗ Password incorrect\n";
            echo "  Updating password...\n";
            
            // Update password
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE staff_users SET password_hash = ? WHERE id = ?");
            $update->execute([$newHash, $user['id']]);
            
            echo "✓ Password updated, try logging in again\n\n";
        }
    } else {
        echo "✗ User not found or inactive\n\n";
    }
    
    echo "=== Staff Login URL ===\n";
    echo BASE_URL . "/?req=staff\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
?>
