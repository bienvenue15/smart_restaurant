-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 12:38 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_restaurant`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `requires_approval` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `channel` enum('email','chat','phone','in_app') DEFAULT 'in_app',
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `last_response_at` timestamp NULL DEFAULT NULL,
  `next_follow_up` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `restaurant_id`, `subject`, `description`, `status`, `priority`, `channel`, `contact_name`, `contact_email`, `contact_phone`, `assigned_to`, `tags`, `last_response_at`, `next_follow_up`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cannot print QR table cards', 'Our thermal printer stopped working when printing QR cards. Need guidance ASAP.', 'in_progress', 'high', 'email', 'Grace Mukamana', 'grace@pizzapalace.rw', '+250788000111', 2, 'hardware,qr', '2025-11-12 09:05:00', NULL, '2025-11-12 08:45:00', '2025-11-12 09:05:00'),
(2, 2, 'Subscription renewal invoice request', 'Please share invoice for November renewal and confirm payment instructions.', 'open', 'medium', 'chat', 'Henry Niyonsenga', 'henry@burgerhouse.rw', '+250788000222', NULL, 'billing', NULL, '2025-11-15 10:00:00', '2025-11-11 14:10:00', '2025-11-11 14:10:00'),
(3, 3, 'Cannot add more staff users', 'The system says we reached max users although we upgraded to premium.', 'waiting_customer', 'urgent', 'in_app', 'Keiko Yamamoto', 'keiko@sushigarden.rw', '+250788000333', 3, 'limits,users', '2025-11-12 07:20:00', '2025-11-13 07:20:00', '2025-11-11 06:55:00', '2025-11-12 07:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `support_ticket_replies`
--

CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `sender_type` enum('restaurant','support','system') DEFAULT 'support',
  `message` text NOT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `support_ticket_replies`
--

INSERT INTO `support_ticket_replies` (`id`, `ticket_id`, `staff_id`, `sender_type`, `message`, `attachment_url`, `created_at`) VALUES
(1, 1, 2, 'support', 'Hi Grace, please update the Zebra driver and try again. I attached the how-to guide.', 'https://docs.example.com/qr-printer-guide.pdf', '2025-11-12 09:05:00'),
(2, 3, 3, 'support', 'We see your plan updated but user cache not refreshed. Can you log out and back in? We are forcing sync now.', NULL, '2025-11-12 07:20:00'),
(3, 3, NULL, 'restaurant', 'We tried again and still blocked. Screenshot attached.', 'https://cdn.example.com/uploads/ticket3.png', '2025-11-12 08:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `channel` enum('email','chat','phone','web') DEFAULT 'web',
  `status` enum('new','read','archived') DEFAULT 'new',
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `restaurant_id`, `subject`, `message`, `channel`, `status`, `contact_name`, `contact_email`, `created_at`) VALUES
(1, 1, 'Need onboarding call', 'Can we schedule a call to onboard two new managers?', 'web', 'new', 'Selena Uwase', 'selena@pizzapalace.rw', '2025-11-12 08:10:00'),
(2, 0, 'Partner inquiry', 'Hello, we want to distribute your system in Uganda.', 'email', 'read', 'Wilson Karemera', 'partnerships@ictconnect.africa', '2025-11-10 11:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `context` enum('system','billing','support','security') DEFAULT 'system',
  `link_url` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bell',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `system_notifications`
--

INSERT INTO `system_notifications` (`id`, `title`, `message`, `type`, `context`, `link_url`, `icon`, `is_read`, `created_at`, `read_at`) VALUES
(1, 'New urgent support ticket', 'Sushi Garden reported blocked staff creation.', 'warning', 'support', NULL, 'exclamation-triangle', 0, '2025-11-12 07:00:00', NULL),
(2, 'Backup completed', 'Nightly backup finished successfully.', 'success', 'system', NULL, 'database', 1, '2025-11-12 02:05:00', '2025-11-12 08:00:00'),
(3, 'Subscription expiring soon', 'Burger House subscription expires in 5 days.', 'warning', 'billing', NULL, 'credit-card', 0, '2025-11-11 12:30:00', NULL),
(4, 'New message from partner', 'ICT Connect Africa sent a partnership inquiry.', 'info', 'support', NULL, 'envelope', 0, '2025-11-10 12:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'support_email', 'info@inovasiyo.rw', 'Primary email for escalations', '2025-11-10 09:00:00'),
(2, 'support_phone', '+250 788 000 999', 'Hotline for enterprise clients', '2025-11-10 09:00:00'),
(3, 'maintenance_mode', 'off', 'Set to "on" to restrict tenant access', '2025-11-10 09:00:00'),
(4, 'backup_schedule', '02:00 Africa/Kigali', 'Nightly backup schedule', '2025-11-10 09:00:00'),
(5, 'default_timezone', 'Africa/Kigali', 'System-wide timezone', '2025-11-10 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `cash_sessions`
--

CREATE TABLE `cash_sessions` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `opening_balance` decimal(10,2) NOT NULL,
  `closing_balance` decimal(10,2) DEFAULT NULL,
  `expected_balance` decimal(10,2) DEFAULT NULL,
  `variance` decimal(10,2) DEFAULT 0.00,
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `status` enum('open','closed','auditing','discrepancy') DEFAULT 'open',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `restaurant_id`, `name`, `description`, `display_order`, `is_active`, `created_at`) VALUES
(1, 1, 'Appetizers', 'Start your meal with our delicious appetizers', 1, 1, '2025-11-10 07:55:55'),
(2, 1, 'Main Course', 'Our signature main dishes', 2, 1, '2025-11-10 07:55:55'),
(3, 1, 'Desserts', 'Sweet endings to your meal', 3, 1, '2025-11-10 07:55:55'),
(4, 1, 'Beverages', 'Hot and cold drinks', 4, 1, '2025-11-10 07:55:55');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_special` tinyint(1) DEFAULT 0,
  `preparation_time` int(11) DEFAULT 15 COMMENT 'Time in minutes',
  `dietary_info` varchar(255) DEFAULT NULL COMMENT 'vegetarian, vegan, gluten-free, etc.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `restaurant_id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `is_special`, `preparation_time`, `dietary_info`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Spring Rolls', 'Crispy vegetable spring rolls with sweet chili sauce', '5.99', NULL, 1, 0, 10, 'vegetarian', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(2, 1, 1, 'Chicken Wings', 'Spicy buffalo chicken wings with ranch dressing', '8.99', NULL, 1, 1, 15, NULL, '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(3, 1, 1, 'Bruschetta', 'Toasted bread topped with fresh tomatoes and basil', '6.99', NULL, 1, 0, 8, 'vegetarian', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(4, 1, 2, 'Grilled Salmon', 'Fresh Atlantic salmon with lemon butter sauce', '18.99', NULL, 1, 1, 25, 'gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(5, 1, 2, 'Beef Steak', 'Premium ribeye steak cooked to perfection', '24.99', NULL, 1, 1, 30, 'gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(6, 1, 2, 'Pasta Carbonara', 'Creamy pasta with bacon and parmesan', '14.99', NULL, 1, 0, 20, NULL, '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(7, 1, 2, 'Vegetable Curry', 'Mixed vegetables in aromatic curry sauce with rice', '12.99', NULL, 1, 0, 20, 'vegan, gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(8, 1, 3, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center', '7.99', NULL, 1, 1, 12, 'vegetarian', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(9, 1, 3, 'Tiramisu', 'Classic Italian dessert with coffee and mascarpone', '6.99', NULL, 1, 0, 5, 'vegetarian', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(10, 1, 3, 'Fresh Fruit Platter', 'Seasonal fresh fruits', '5.99', NULL, 1, 0, 5, 'vegan, gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(11, 1, 4, 'Fresh Orange Juice', 'Freshly squeezed orange juice', '3.99', NULL, 1, 0, 5, 'vegan, gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(12, 1, 4, 'Cappuccino', 'Italian coffee with steamed milk', '4.50', NULL, 1, 0, 5, 'vegetarian', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(13, 1, 4, 'Iced Tea', 'Refreshing iced lemon tea', '2.99', NULL, 1, 0, 3, 'vegan, gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(14, 1, 4, 'Mineral Water', 'Sparkling or still water', '1.99', NULL, 1, 0, 2, 'vegan, gluten-free', '2025-11-10 07:55:55', '2025-11-12 05:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','served','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmed_by` int(11) DEFAULT NULL,
  `served_by` int(11) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid','refunded') DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `order_adjustments`
--

CREATE TABLE `order_adjustments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `adjustment_type` enum('discount','refund','void','comp') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `special_request` text DEFAULT NULL,
  `status` enum('pending','preparing','ready','served') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','card','mobile_money','bank_transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `received_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `received_by` int(11) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('orders','payments','tables','menu','staff','reports','system') NOT NULL,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  `requires_approval` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `name`, `description`, `category`, `risk_level`, `requires_approval`, `created_at`) VALUES
(1, 'view_orders', 'View Orders', 'View order details', 'orders', 'low', 0, '2025-11-10 10:50:34'),
(2, 'create_order', 'Create Order', 'Create new customer orders', 'orders', 'low', 0, '2025-11-10 10:50:34'),
(3, 'modify_order', 'Modify Order', 'Edit existing orders', 'orders', 'medium', 0, '2025-11-10 10:50:34'),
(4, 'cancel_order', 'Cancel Order', 'Cancel orders (business loss)', 'orders', 'high', 1, '2025-11-10 10:50:34'),
(5, 'void_order', 'Void Order', 'Void completed orders', 'orders', 'critical', 1, '2025-11-10 10:50:34'),
(6, 'view_all_orders', 'View All Orders', 'See all restaurant orders', 'orders', 'low', 0, '2025-11-10 10:50:34'),
(7, 'accept_payment', 'Accept Payment', 'Receive customer payments', 'payments', 'high', 0, '2025-11-10 10:50:34'),
(8, 'process_refund', 'Process Refund', 'Issue refunds (cash loss)', 'payments', 'critical', 1, '2025-11-10 10:50:34'),
(9, 'apply_discount', 'Apply Discount', 'Give discounts (revenue loss)', 'payments', 'high', 1, '2025-11-10 10:50:34'),
(10, 'void_payment', 'Void Payment', 'Cancel payment transactions', 'payments', 'critical', 1, '2025-11-10 10:50:34'),
(11, 'open_cash_register', 'Open Cash Register', 'Start cash handling session', 'payments', 'high', 0, '2025-11-10 10:50:34'),
(12, 'close_cash_register', 'Close Cash Register', 'End cash session with reconciliation', 'payments', 'high', 0, '2025-11-10 10:50:34'),
(13, 'view_payment_history', 'View Payment History', 'See all payment records', 'payments', 'medium', 0, '2025-11-10 10:50:34'),
(14, 'view_tables', 'View Tables', 'See table status', 'tables', 'low', 0, '2025-11-10 10:50:34'),
(15, 'manage_tables', 'Manage Tables', 'Change table status', 'tables', 'medium', 0, '2025-11-10 10:50:34'),
(16, 'reset_table', 'Reset Table', 'Clear table and make available', 'tables', 'medium', 0, '2025-11-10 10:50:34'),
(17, 'reserve_table', 'Reserve Table', 'Book tables for customers', 'tables', 'low', 0, '2025-11-10 10:50:34'),
(18, 'view_menu', 'View Menu', 'See menu items', 'menu', 'low', 0, '2025-11-10 10:50:34'),
(19, 'edit_menu', 'Edit Menu', 'Modify menu items/prices', 'menu', 'high', 0, '2025-11-10 10:50:34'),
(20, 'toggle_availability', 'Toggle Item Availability', 'Mark items available/unavailable', 'menu', 'low', 0, '2025-11-10 10:50:34'),
(21, 'view_staff', 'View Staff', 'See staff list', 'staff', 'low', 0, '2025-11-10 10:50:34'),
(22, 'manage_staff', 'Manage Staff', 'Add/edit staff accounts', 'staff', 'critical', 0, '2025-11-10 10:50:34'),
(23, 'view_activity_log', 'View Activity Log', 'See staff actions', 'staff', 'medium', 0, '2025-11-10 10:50:34'),
(24, 'manage_shifts', 'Manage Shifts', 'Schedule staff shifts', 'staff', 'medium', 0, '2025-11-10 10:50:34'),
(25, 'approve_actions', 'Approve Actions', 'Approve high-risk operations', 'staff', 'critical', 0, '2025-11-10 10:50:34'),
(26, 'view_reports', 'View Reports', 'Access sales reports', 'reports', 'medium', 0, '2025-11-10 10:50:34'),
(27, 'export_reports', 'Export Reports', 'Download report data', 'reports', 'medium', 0, '2025-11-10 10:50:34'),
(28, 'view_audit_trail', 'View Audit Trail', 'See all security logs', 'reports', 'high', 0, '2025-11-10 10:50:34'),
(29, 'system_settings', 'System Settings', 'Modify system configuration', 'system', 'critical', 0, '2025-11-10 10:50:34'),
(30, 'backup_database', 'Backup Database', 'Create data backups', 'system', 'high', 0, '2025-11-10 10:50:34'),
(31, 'delete_records', 'Delete Records', 'Permanently delete data', 'system', 'critical', 1, '2025-11-10 10:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL COMMENT 'URL-friendly identifier (e.g., pizza-palace)',
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Rwanda',
  `currency` varchar(10) DEFAULT 'RWF',
  `timezone` varchar(50) DEFAULT 'Africa/Kigali',
  `logo_url` varchar(255) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT '#2563eb',
  `secondary_color` varchar(7) DEFAULT '#1e40af',
  `tax_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Tax percentage',
  `service_charge` decimal(5,2) DEFAULT 0.00 COMMENT 'Service charge percentage',
  `is_active` tinyint(1) DEFAULT 1,
  `max_tables` int(11) DEFAULT 50,
  `max_users` int(11) DEFAULT 20,
  `subscription_plan` enum('trial','basic','premium','enterprise') DEFAULT 'trial',
  `subscription_start` date DEFAULT NULL,
  `subscription_end` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `slug`, `email`, `phone`, `address`, `city`, `country`, `currency`, `timezone`, `logo_url`, `primary_color`, `secondary_color`, `tax_rate`, `service_charge`, `is_active`, `max_tables`, `max_users`, `subscription_plan`, `subscription_start`, `subscription_end`, `created_at`, `updated_at`) VALUES
(1, 'Default Restaurant', 'default-restaurant', 'admin@restaurant.com', '+250 788 123 456', '123 Main Street', 'Kigali', 'Rwanda', 'RWF', 'Africa/Kigali', NULL, '#2563eb', '#1e40af', '0.00', '0.00', 1, 50, 20, 'premium', '2025-11-12', '2026-11-12', '2025-11-12 05:53:52', '2025-11-12 05:53:52'),
(2, 'Pizza Palace', 'pizza-palace', 'admin@pizzapalace.rw', '+250 788 111 222', 'KN 5 Ave', 'Kigali', 'Rwanda', 'RWF', 'Africa/Kigali', NULL, '#2563eb', '#1e40af', '0.00', '0.00', 1, 50, 20, 'premium', '2025-11-12', '2026-11-12', '2025-11-12 05:53:53', '2025-11-12 05:53:53'),
(3, 'Burger House', 'burger-house', 'admin@burgerhouse.rw', '+250 788 333 444', 'KN 10 St', 'Kigali', 'Rwanda', 'RWF', 'Africa/Kigali', NULL, '#2563eb', '#1e40af', '0.00', '0.00', 1, 50, 20, 'basic', '2025-11-12', '2026-05-12', '2025-11-12 05:53:53', '2025-11-12 05:53:53'),
(4, 'Sushi Garden', 'sushi-garden', 'admin@sushigarden.rw', '+250 788 555 666', 'KG 15 Ave', 'Kigali', 'Rwanda', 'RWF', 'Africa/Kigali', NULL, '#2563eb', '#1e40af', '0.00', '0.00', 1, 50, 20, 'trial', '2025-11-12', '2025-12-12', '2025-11-12 05:53:53', '2025-11-12 05:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_settings`
--

CREATE TABLE `restaurant_settings` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `restaurant_settings`
--

INSERT INTO `restaurant_settings` (`id`, `restaurant_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'auto_accept_orders', 'false', '2025-11-12 05:53:53', '2025-11-12 05:53:53'),
(2, 2, 'auto_accept_orders', 'false', '2025-11-12 05:53:53', '2025-11-12 05:53:53'),
(3, 3, 'auto_accept_orders', 'false', '2025-11-12 05:53:53', '2025-11-12 05:53:53'),
(4, 4, 'auto_accept_orders', 'false', '2025-11-12 05:53:53', '2025-11-12 05:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 4,
  `status` enum('available','occupied','reserved') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `restaurant_id`, `table_number`, `qr_code`, `seats`, `status`, `created_at`, `updated_at`) VALUES
(6, 1, 'T001', 'QR-T001-ec3e3fb7-be0a-11f0-9b3a-d03957d739cc', 2, 'available', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(7, 1, 'T002', 'QR-T002-ec3e67cd-be0a-11f0-9b3a-d03957d739cc', 4, 'available', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(8, 1, 'T003', 'QR-T003-ec3e694d-be0a-11f0-9b3a-d03957d739cc', 4, 'available', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(9, 1, 'T004', 'QR-T004-ec3e6a25-be0a-11f0-9b3a-d03957d739cc', 6, 'available', '2025-11-10 07:55:55', '2025-11-12 05:53:52'),
(10, 1, 'T005', 'QR-T005-ec3e6abd-be0a-11f0-9b3a-d03957d739cc', 8, 'available', '2025-11-10 07:55:55', '2025-11-12 05:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','manager','waiter','kitchen','cashier') NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_id`, `granted_at`, `granted_by`) VALUES
(1, 'admin', 7, '2025-11-10 10:50:34', NULL),
(2, 'admin', 9, '2025-11-10 10:50:34', NULL),
(3, 'admin', 25, '2025-11-10 10:50:34', NULL),
(4, 'admin', 30, '2025-11-10 10:50:34', NULL),
(5, 'admin', 4, '2025-11-10 10:50:34', NULL),
(6, 'admin', 12, '2025-11-10 10:50:34', NULL),
(7, 'admin', 2, '2025-11-10 10:50:34', NULL),
(8, 'admin', 31, '2025-11-10 10:50:34', NULL),
(9, 'admin', 19, '2025-11-10 10:50:34', NULL),
(10, 'admin', 27, '2025-11-10 10:50:34', NULL),
(11, 'admin', 24, '2025-11-10 10:50:34', NULL),
(12, 'admin', 22, '2025-11-10 10:50:34', NULL),
(13, 'admin', 15, '2025-11-10 10:50:34', NULL),
(14, 'admin', 3, '2025-11-10 10:50:34', NULL),
(15, 'admin', 11, '2025-11-10 10:50:34', NULL),
(16, 'admin', 8, '2025-11-10 10:50:34', NULL),
(17, 'admin', 17, '2025-11-10 10:50:34', NULL),
(18, 'admin', 16, '2025-11-10 10:50:34', NULL),
(19, 'admin', 29, '2025-11-10 10:50:34', NULL),
(20, 'admin', 20, '2025-11-10 10:50:34', NULL),
(21, 'admin', 23, '2025-11-10 10:50:34', NULL),
(22, 'admin', 6, '2025-11-10 10:50:34', NULL),
(23, 'admin', 28, '2025-11-10 10:50:34', NULL),
(24, 'admin', 18, '2025-11-10 10:50:34', NULL),
(25, 'admin', 1, '2025-11-10 10:50:34', NULL),
(26, 'admin', 13, '2025-11-10 10:50:34', NULL),
(27, 'admin', 26, '2025-11-10 10:50:34', NULL),
(28, 'admin', 21, '2025-11-10 10:50:34', NULL),
(29, 'admin', 14, '2025-11-10 10:50:34', NULL),
(30, 'admin', 5, '2025-11-10 10:50:34', NULL),
(31, 'admin', 10, '2025-11-10 10:50:34', NULL),
(32, 'manager', 7, '2025-11-10 10:50:34', NULL),
(33, 'manager', 9, '2025-11-10 10:50:34', NULL),
(34, 'manager', 25, '2025-11-10 10:50:34', NULL),
(35, 'manager', 4, '2025-11-10 10:50:34', NULL),
(36, 'manager', 12, '2025-11-10 10:50:34', NULL),
(37, 'manager', 2, '2025-11-10 10:50:34', NULL),
(38, 'manager', 19, '2025-11-10 10:50:34', NULL),
(39, 'manager', 27, '2025-11-10 10:50:34', NULL),
(40, 'manager', 24, '2025-11-10 10:50:34', NULL),
(41, 'manager', 15, '2025-11-10 10:50:34', NULL),
(42, 'manager', 3, '2025-11-10 10:50:34', NULL),
(43, 'manager', 11, '2025-11-10 10:50:34', NULL),
(44, 'manager', 8, '2025-11-10 10:50:34', NULL),
(45, 'manager', 17, '2025-11-10 10:50:34', NULL),
(46, 'manager', 16, '2025-11-10 10:50:34', NULL),
(47, 'manager', 20, '2025-11-10 10:50:34', NULL),
(48, 'manager', 23, '2025-11-10 10:50:34', NULL),
(49, 'manager', 6, '2025-11-10 10:50:34', NULL),
(50, 'manager', 28, '2025-11-10 10:50:34', NULL),
(51, 'manager', 18, '2025-11-10 10:50:34', NULL),
(52, 'manager', 1, '2025-11-10 10:50:34', NULL),
(53, 'manager', 13, '2025-11-10 10:50:34', NULL),
(54, 'manager', 26, '2025-11-10 10:50:34', NULL),
(55, 'manager', 21, '2025-11-10 10:50:34', NULL),
(56, 'manager', 14, '2025-11-10 10:50:34', NULL),
(57, 'manager', 5, '2025-11-10 10:50:34', NULL),
(58, 'manager', 10, '2025-11-10 10:50:34', NULL),
(63, 'cashier', 7, '2025-11-10 10:50:34', NULL),
(64, 'cashier', 12, '2025-11-10 10:50:34', NULL),
(65, 'cashier', 11, '2025-11-10 10:50:34', NULL),
(66, 'cashier', 16, '2025-11-10 10:50:34', NULL),
(67, 'cashier', 6, '2025-11-10 10:50:34', NULL),
(68, 'cashier', 18, '2025-11-10 10:50:34', NULL),
(69, 'cashier', 1, '2025-11-10 10:50:34', NULL),
(70, 'cashier', 13, '2025-11-10 10:50:34', NULL),
(71, 'cashier', 14, '2025-11-10 10:50:34', NULL),
(78, 'waiter', 2, '2025-11-10 10:50:34', NULL),
(79, 'waiter', 15, '2025-11-10 10:50:34', NULL),
(80, 'waiter', 3, '2025-11-10 10:50:34', NULL),
(81, 'waiter', 17, '2025-11-10 10:50:34', NULL),
(82, 'waiter', 16, '2025-11-10 10:50:34', NULL),
(83, 'waiter', 20, '2025-11-10 10:50:34', NULL),
(84, 'waiter', 18, '2025-11-10 10:50:34', NULL),
(85, 'waiter', 1, '2025-11-10 10:50:34', NULL),
(86, 'waiter', 14, '2025-11-10 10:50:34', NULL),
(93, 'kitchen', 6, '2025-11-10 10:50:34', NULL),
(94, 'kitchen', 18, '2025-11-10 10:50:34', NULL),
(95, 'kitchen', 1, '2025-11-10 10:50:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

CREATE TABLE `staff_activity_log` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staff_activity_log`
--

INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:40:48'),
(2, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:42:35'),
(3, 2, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:42:48'),
(4, 2, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:43:00'),
(5, 3, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:43:30'),
(6, 3, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:44:14'),
(7, 5, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 10:44:29'),
(8, 5, 'clock_in', 'Staff clocked in for shift', '', '', '2025-11-10 11:12:51'),
(9, 5, 'clock_out', 'Staff clocked out from shift', '', '', '2025-11-10 11:13:03'),
(10, 5, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 11:20:28'),
(11, 1, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-10 11:30:27'),
(12, 1, 'clock_in', 'Staff clocked in for shift', '', '', '2025-11-10 12:12:27'),
(13, 1, 'clock_out', 'Staff clocked out from shift', '', '', '2025-11-10 12:12:37'),
(14, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-17 08:55:55'),
(15, 5, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-17 11:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `expected_start` time NOT NULL,
  `expected_end` time NOT NULL,
  `status` enum('scheduled','ongoing','completed','missed','late') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staff_shifts`
--

INSERT INTO `staff_shifts` (`id`, `staff_id`, `shift_date`, `clock_in`, `clock_out`, `expected_start`, `expected_end`, `status`, `notes`, `created_at`) VALUES
(1, 5, '2025-11-10', '2025-11-10 11:12:51', '2025-11-10 11:13:03', '12:12:51', '20:12:51', 'completed', NULL, '2025-11-10 11:12:51'),
(2, 1, '2025-11-10', '2025-11-10 12:12:27', '2025-11-10 12:12:37', '13:12:27', '21:12:27', 'completed', NULL, '2025-11-10 12:12:27');

-- --------------------------------------------------------

--
-- Table structure for table `staff_users`
--

CREATE TABLE `staff_users` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','waiter','kitchen','cashier','super_admin') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `can_handle_cash` tinyint(1) DEFAULT 0,
  `can_approve_refunds` tinyint(1) DEFAULT 0,
  `max_discount_percent` decimal(5,2) DEFAULT 0.00,
  `requires_supervisor` tinyint(1) DEFAULT 0,
  `security_level` enum('standard','elevated','admin') DEFAULT 'standard'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staff_users`
--

INSERT INTO `staff_users` (`id`, `restaurant_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`, `can_handle_cash`, `can_approve_refunds`, `max_discount_percent`, `requires_supervisor`, `security_level`) VALUES
(1, NULL, 'admin', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'System Administrator', 'superadmin@restaurant.com', '+250788000001', 'super_admin', 1, '2025-11-10 11:30:27', '2025-11-10 10:30:33', '2025-11-17 08:39:13', 1, 1, '100.00', 0, 'admin'),
(2, NULL, 'manager', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'Restaurant Manager', 'manager@inovasiyo.com', '+250788000002', 'manager', 1, '2025-11-10 10:42:48', '2025-11-10 10:30:33', '2025-11-10 10:50:34', 1, 1, '50.00', 0, 'elevated'),
(3, NULL, 'waiter1', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'John Waiter', 'waiter1@inovasiyo.com', '+250788000003', 'waiter', 1, '2025-11-10 10:43:30', '2025-11-10 10:30:33', '2025-11-10 10:50:34', 0, 0, '0.00', 1, 'standard'),
(4, NULL, 'waiter2', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'Jane Server', 'waiter2@inovasiyo.com', '+250788000004', 'waiter', 1, NULL, '2025-11-10 10:30:33', '2025-11-10 10:50:34', 0, 0, '0.00', 1, 'standard'),
(5, NULL, 'kitchen', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'Chef Kitchen', 'kitchen@inovasiyo.com', '+250788000005', 'kitchen', 1, '2025-11-17 11:05:48', '2025-11-10 10:30:33', '2025-11-17 11:05:48', 0, 0, '0.00', 1, 'standard'),
(6, NULL, 'cashier', '$2y$10$ynzMhRQKArRs58a46O3r4OGxpFXSkAHP4nCgg76Lw6J9aAFTu723q', 'Cashier Desk', 'cashier@inovasiyo.com', '+250788000006', 'cashier', 0, NULL, '2025-11-10 10:30:33', '2025-11-17 11:05:03', 1, 0, '5.00', 1, 'standard');

-- --------------------------------------------------------

--
-- Table structure for table `table_resets`
--

CREATE TABLE `table_resets` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_restaurant_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_restaurant_stats` (
`id` int(11)
,`name` varchar(255)
,`slug` varchar(100)
,`email` varchar(255)
,`subscription_plan` enum('trial','basic','premium','enterprise')
,`is_active` tinyint(1)
,`total_users` bigint(21)
,`total_tables` bigint(21)
,`total_menu_items` bigint(21)
,`total_orders` bigint(21)
,`today_revenue` decimal(32,2)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `waiter_calls`
--

CREATE TABLE `waiter_calls` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `request_type` enum('order','assistance','bill','complaint','other') NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','acknowledged','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure for view `v_restaurant_stats`
--
DROP TABLE IF EXISTS `v_restaurant_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_restaurant_stats`  AS SELECT `r`.`id` AS `id`, `r`.`name` AS `name`, `r`.`slug` AS `slug`, `r`.`email` AS `email`, `r`.`subscription_plan` AS `subscription_plan`, `r`.`is_active` AS `is_active`, count(distinct `u`.`id`) AS `total_users`, count(distinct `rt`.`id`) AS `total_tables`, count(distinct `mi`.`id`) AS `total_menu_items`, count(distinct `o`.`id`) AS `total_orders`, sum(case when `o`.`created_at` >= curdate() then `o`.`total_amount` else 0 end) AS `today_revenue`, `r`.`created_at` AS `created_at` FROM ((((`restaurants` `r` left join `staff_users` `u` on(`r`.`id` = `u`.`restaurant_id`)) left join `restaurant_tables` `rt` on(`r`.`id` = `rt`.`restaurant_id`)) left join `menu_items` `mi` on(`r`.`id` = `mi`.`restaurant_id`)) left join `orders` `o` on(`r`.`id` = `o`.`restaurant_id`)) GROUP BY `r`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_restaurant` (`restaurant_id`),
  ADD KEY `idx_support_status` (`status`),
  ADD KEY `idx_support_priority` (`priority`),
  ADD KEY `idx_support_assigned` (`assigned_to`);

--
-- Indexes for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_reply_staff` (`staff_id`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_restaurant` (`restaurant_id`),
  ADD KEY `idx_message_status` (`status`);

--
-- Indexes for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_read` (`is_read`),
  ADD KEY `idx_notification_type` (`type`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Indexes for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `closed_by` (`closed_by`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`opened_at`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_per_restaurant` (`restaurant_id`,`order_number`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `served_by` (`served_by`),
  ADD KEY `paid_to` (`paid_to`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `order_adjustments`
--
ALTER TABLE `order_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`adjustment_type`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_staff` (`received_by`),
  ADD KEY `idx_date` (`payment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `restaurant_settings`
--
ALTER TABLE `restaurant_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`restaurant_id`,`setting_key`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD UNIQUE KEY `unique_table_per_restaurant` (`restaurant_id`,`table_number`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_date` (`staff_id`,`shift_date`),
  ADD KEY `idx_date` (`shift_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- Indexes for table `table_resets`
--
ALTER TABLE `table_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_id` (`table_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `waiter_calls`
--
ALTER TABLE `waiter_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_restaurant` (`restaurant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_adjustments`
--
ALTER TABLE `order_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `restaurant_settings`
--
ALTER TABLE `restaurant_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_users`
--
ALTER TABLE `staff_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `table_resets`
--
ALTER TABLE `table_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `waiter_calls`
--
ALTER TABLE `waiter_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_trail_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD CONSTRAINT `cash_sessions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_sessions_ibfk_2` FOREIGN KEY (`closed_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD CONSTRAINT `fk_categories_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_items_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`served_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`confirmed_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_5` FOREIGN KEY (`served_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_6` FOREIGN KEY (`paid_to`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_adjustments`
--
ALTER TABLE `order_adjustments`
  ADD CONSTRAINT `order_adjustments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_adjustments_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_adjustments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `staff_users` (`id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `restaurant_settings`
--
ALTER TABLE `restaurant_settings`
  ADD CONSTRAINT `restaurant_settings_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD CONSTRAINT `fk_tables_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD CONSTRAINT `staff_activity_log_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD CONSTRAINT `staff_shifts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `table_resets`
--
ALTER TABLE `table_resets`
  ADD CONSTRAINT `table_resets_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `table_resets_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waiter_calls`
--
ALTER TABLE `waiter_calls`
  ADD CONSTRAINT `fk_waiter_calls_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waiter_calls_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waiter_calls_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `waiter_calls_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
