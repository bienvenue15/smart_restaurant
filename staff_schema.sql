-- Smart Restaurant Staff Portal Schema
-- Copyright © 2025 Inovasiyo Ltd. All rights reserved.

USE db_restaurant;

-- Staff users table
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

-- Staff activity log
CREATE TABLE IF NOT EXISTS staff_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table reset tracking
CREATE TABLE IF NOT EXISTS table_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    staff_id INT NOT NULL,
    previous_status VARCHAR(20),
    new_status VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    INDEX idx_table_id (table_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add staff assignment to waiter calls
ALTER TABLE waiter_calls 
ADD COLUMN IF NOT EXISTS assigned_to INT NULL,
ADD COLUMN IF NOT EXISTS assigned_at TIMESTAMP NULL,
ADD FOREIGN KEY IF NOT EXISTS (assigned_to) REFERENCES staff_users(id) ON DELETE SET NULL;

-- Add staff tracking to orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS confirmed_by INT NULL,
ADD COLUMN IF NOT EXISTS served_by INT NULL,
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS served_at TIMESTAMP NULL,
ADD FOREIGN KEY IF NOT EXISTS (confirmed_by) REFERENCES staff_users(id) ON DELETE SET NULL,
ADD FOREIGN KEY IF NOT EXISTS (served_by) REFERENCES staff_users(id) ON DELETE SET NULL;

-- Insert default staff users (password is 'admin123' for all - CHANGE IN PRODUCTION!)
-- Password hash: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO staff_users (username, password_hash, full_name, email, phone, role, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@inovasiyo.com', '+250788000001', 'admin', 1),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Restaurant Manager', 'manager@inovasiyo.com', '+250788000002', 'manager', 1),
('waiter1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Waiter', 'waiter1@inovasiyo.com', '+250788000003', 'waiter', 1),
('waiter2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Server', 'waiter2@inovasiyo.com', '+250788000004', 'waiter', 1),
('kitchen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chef Kitchen', 'kitchen@inovasiyo.com', '+250788000005', 'kitchen', 1),
('cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier Desk', 'cashier@inovasiyo.com', '+250788000006', 'cashier', 1);

COMMIT;
