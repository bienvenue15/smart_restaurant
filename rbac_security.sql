-- Smart Restaurant - Role-Based Access Control & Security System
-- Copyright © 2025 Inovasiyo Ltd
-- Business Risk Prevention & Loss Prevention System

USE db_restaurant;

-- Permissions table - Granular access control
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('orders', 'payments', 'tables', 'menu', 'staff', 'reports', 'system') NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    requires_approval TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role permissions mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'manager', 'waiter', 'kitchen', 'cashier') NOT NULL,
    permission_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INT,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_role_permission (role, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff shifts - Track who's working and when
CREATE TABLE IF NOT EXISTS staff_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    shift_date DATE NOT NULL,
    clock_in TIMESTAMP NULL,
    clock_out TIMESTAMP NULL,
    expected_start TIME NOT NULL,
    expected_end TIME NOT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'missed', 'late') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    INDEX idx_staff_date (staff_id, shift_date),
    INDEX idx_date (shift_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sensitive actions audit trail - CRITICAL for fraud prevention
CREATE TABLE IF NOT EXISTS audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    reason TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    requires_approval TINYINT(1) DEFAULT 0,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_staff (staff_id),
    INDEX idx_action (action_type),
    INDEX idx_date (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment tracking - Prevent loss and fraud
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile_money', 'bank_transfer') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    received_amount DECIMAL(10, 2) NOT NULL,
    change_amount DECIMAL(10, 2) DEFAULT 0.00,
    received_by INT NOT NULL,
    payment_reference VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES staff_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (verified_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_staff (received_by),
    INDEX idx_date (payment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cash register sessions - Track cash handling
CREATE TABLE IF NOT EXISTS cash_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    opening_balance DECIMAL(10, 2) NOT NULL,
    closing_balance DECIMAL(10, 2) NULL,
    expected_balance DECIMAL(10, 2) NULL,
    variance DECIMAL(10, 2) DEFAULT 0.00,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    closed_by INT NULL,
    status ENUM('open', 'closed', 'auditing', 'discrepancy') DEFAULT 'open',
    notes TEXT,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    FOREIGN KEY (closed_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_staff (staff_id),
    INDEX idx_status (status),
    INDEX idx_date (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Discounts/Refunds - Requires approval
CREATE TABLE IF NOT EXISTS order_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    adjustment_type ENUM('discount', 'refund', 'void', 'comp') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT NOT NULL,
    requested_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES staff_users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_type (adjustment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add payment tracking to orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS paid_to INT NULL,
ADD FOREIGN KEY IF NOT EXISTS (paid_to) REFERENCES staff_users(id) ON DELETE SET NULL;

-- Add security flags to staff_users
ALTER TABLE staff_users 
ADD COLUMN IF NOT EXISTS can_handle_cash TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS can_approve_refunds TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS max_discount_percent DECIMAL(5, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS requires_supervisor TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS security_level ENUM('standard', 'elevated', 'admin') DEFAULT 'standard';

-- =====================================================
-- INSERT PERMISSIONS - Granular access control
-- =====================================================

INSERT INTO permissions (code, name, description, category, risk_level, requires_approval) VALUES
-- Orders (Medium to High Risk)
('view_orders', 'View Orders', 'View order details', 'orders', 'low', 0),
('create_order', 'Create Order', 'Create new customer orders', 'orders', 'low', 0),
('modify_order', 'Modify Order', 'Edit existing orders', 'orders', 'medium', 0),
('cancel_order', 'Cancel Order', 'Cancel orders (business loss)', 'orders', 'high', 1),
('void_order', 'Void Order', 'Void completed orders', 'orders', 'critical', 1),
('view_all_orders', 'View All Orders', 'See all restaurant orders', 'orders', 'low', 0),

-- Payments (HIGH RISK - Money handling)
('accept_payment', 'Accept Payment', 'Receive customer payments', 'payments', 'high', 0),
('process_refund', 'Process Refund', 'Issue refunds (cash loss)', 'payments', 'critical', 1),
('apply_discount', 'Apply Discount', 'Give discounts (revenue loss)', 'payments', 'high', 1),
('void_payment', 'Void Payment', 'Cancel payment transactions', 'payments', 'critical', 1),
('open_cash_register', 'Open Cash Register', 'Start cash handling session', 'payments', 'high', 0),
('close_cash_register', 'Close Cash Register', 'End cash session with reconciliation', 'payments', 'high', 0),
('view_payment_history', 'View Payment History', 'See all payment records', 'payments', 'medium', 0),

-- Tables (Medium Risk)
('view_tables', 'View Tables', 'See table status', 'tables', 'low', 0),
('manage_tables', 'Manage Tables', 'Change table status', 'tables', 'medium', 0),
('reset_table', 'Reset Table', 'Clear table and make available', 'tables', 'medium', 0),
('reserve_table', 'Reserve Table', 'Book tables for customers', 'tables', 'low', 0),

-- Menu (Low to Medium Risk)
('view_menu', 'View Menu', 'See menu items', 'menu', 'low', 0),
('edit_menu', 'Edit Menu', 'Modify menu items/prices', 'menu', 'high', 0),
('toggle_availability', 'Toggle Item Availability', 'Mark items available/unavailable', 'menu', 'low', 0),

-- Staff (HIGH RISK - Access control)
('view_staff', 'View Staff', 'See staff list', 'staff', 'low', 0),
('manage_staff', 'Manage Staff', 'Add/edit staff accounts', 'staff', 'critical', 0),
('view_activity_log', 'View Activity Log', 'See staff actions', 'staff', 'medium', 0),
('manage_shifts', 'Manage Shifts', 'Schedule staff shifts', 'staff', 'medium', 0),
('approve_actions', 'Approve Actions', 'Approve high-risk operations', 'staff', 'critical', 0),

-- Reports (Medium Risk - Business intelligence)
('view_reports', 'View Reports', 'Access sales reports', 'reports', 'medium', 0),
('export_reports', 'Export Reports', 'Download report data', 'reports', 'medium', 0),
('view_audit_trail', 'View Audit Trail', 'See all security logs', 'reports', 'high', 0),

-- System (CRITICAL RISK)
('system_settings', 'System Settings', 'Modify system configuration', 'system', 'critical', 0),
('backup_database', 'Backup Database', 'Create data backups', 'system', 'high', 0),
('delete_records', 'Delete Records', 'Permanently delete data', 'system', 'critical', 1);

-- =====================================================
-- ASSIGN PERMISSIONS TO ROLES (Security-First Approach)
-- =====================================================

-- ADMIN: Full access with accountability
INSERT INTO role_permissions (role, permission_id) 
SELECT 'admin', id FROM permissions;

-- MANAGER: Business operations + oversight (no system settings)
INSERT INTO role_permissions (role, permission_id)
SELECT 'manager', id FROM permissions WHERE code IN (
    'view_orders', 'create_order', 'modify_order', 'cancel_order', 'void_order', 'view_all_orders',
    'accept_payment', 'process_refund', 'apply_discount', 'void_payment', 
    'open_cash_register', 'close_cash_register', 'view_payment_history',
    'view_tables', 'manage_tables', 'reset_table', 'reserve_table',
    'view_menu', 'edit_menu', 'toggle_availability',
    'view_staff', 'manage_shifts', 'approve_actions', 'view_activity_log',
    'view_reports', 'export_reports', 'view_audit_trail'
);

-- CASHIER: Payment focused (cannot void or refund without approval)
INSERT INTO role_permissions (role, permission_id)
SELECT 'cashier', id FROM permissions WHERE code IN (
    'view_orders', 'view_all_orders',
    'accept_payment', 'open_cash_register', 'close_cash_register', 'view_payment_history',
    'view_tables', 'reset_table',
    'view_menu'
);

-- WAITER: Order taking + table management (NO payment handling)
INSERT INTO role_permissions (role, permission_id)
SELECT 'waiter', id FROM permissions WHERE code IN (
    'view_orders', 'create_order', 'modify_order',
    'view_tables', 'manage_tables', 'reset_table', 'reserve_table',
    'view_menu', 'toggle_availability'
);

-- KITCHEN: Order fulfillment only (NO money, NO tables)
INSERT INTO role_permissions (role, permission_id)
SELECT 'kitchen', id FROM permissions WHERE code IN (
    'view_orders', 'view_all_orders',
    'view_menu'
);

-- =====================================================
-- UPDATE STAFF SECURITY SETTINGS
-- =====================================================

-- Admin: Full privileges
UPDATE staff_users SET 
    can_handle_cash = 1, 
    can_approve_refunds = 1, 
    max_discount_percent = 100.00,
    security_level = 'admin'
WHERE role = 'admin';

-- Manager: Can handle cash and approve up to 50% discounts
UPDATE staff_users SET 
    can_handle_cash = 1, 
    can_approve_refunds = 1, 
    max_discount_percent = 50.00,
    security_level = 'elevated'
WHERE role = 'manager';

-- Cashier: Can handle cash but needs approval for refunds
UPDATE staff_users SET 
    can_handle_cash = 1, 
    can_approve_refunds = 0, 
    max_discount_percent = 5.00,
    requires_supervisor = 1,
    security_level = 'standard'
WHERE role = 'cashier';

-- Waiter: NO cash handling
UPDATE staff_users SET 
    can_handle_cash = 0, 
    can_approve_refunds = 0, 
    max_discount_percent = 0.00,
    requires_supervisor = 1,
    security_level = 'standard'
WHERE role = 'waiter';

-- Kitchen: NO cash, NO approvals
UPDATE staff_users SET 
    can_handle_cash = 0, 
    can_approve_refunds = 0, 
    max_discount_percent = 0.00,
    requires_supervisor = 1,
    security_level = 'standard'
WHERE role = 'kitchen';

COMMIT;
