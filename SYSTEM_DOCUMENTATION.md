# Restaurant Management System - Complete Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Multi-Tenancy Architecture](#multi-tenancy-architecture)
3. [User Roles & Responsibilities](#user-roles--responsibilities)
4. [System Modules](#system-modules)
5. [Security & Access Control](#security--access-control)
6. [Workflows](#workflows)
7. [API Reference](#api-reference)
8. [Database Schema](#database-schema)
9. [Deployment & Configuration](#deployment--configuration)

---

## System Overview

### What is This System?
A **Multi-Tenant Restaurant Management System** that allows multiple restaurants to use the same platform while keeping their data completely isolated. Each restaurant operates independently with their own:
- Staff members
- Menu items
- Tables
- Orders
- Customers
- Financial data

### Key Features
- ✅ **Multi-Restaurant Support** - Multiple restaurants on one platform
- ✅ **Role-Based Access Control (RBAC)** - 6 different user roles with specific permissions
- ✅ **Real-Time Order Management** - Kitchen display, table service, order tracking
- ✅ **QR Code Table Service** - Customers order via QR codes
- ✅ **Cash Management** - Opening/closing sessions, cash tracking
- ✅ **Menu Management** - Categories, items, modifiers, pricing
- ✅ **Staff Management** - Clock in/out, shifts, activity logs
- ✅ **Subscription Plans** - Trial, Basic, Premium, Enterprise
- ✅ **Financial Reports** - Sales, revenue, staff performance
- ✅ **Audit Trail** - Complete history of all actions

### Technology Stack
- **Backend**: PHP 8.1 (MVC Architecture)
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Architecture**: Multi-tenant SaaS
- **Authentication**: Session-based with RBAC

---

## Multi-Tenancy Architecture

### How It Works

#### 1. **Tenant Isolation**
Every restaurant is a "tenant" with a unique ID. All data tables include a `restaurant_id` column:

```
Restaurant A (ID: 1)          Restaurant B (ID: 2)
├── 5 staff users             ├── 8 staff users
├── 10 tables                 ├── 15 tables
├── 50 menu items             ├── 30 menu items
└── All orders/data           └── All orders/data
```

#### 2. **Database Structure**
```sql
-- Master tenant table
restaurants (id, name, slug, subscription_plan, is_active)

-- All data tables have restaurant_id
staff_users (id, restaurant_id, username, role, ...)
restaurant_tables (id, restaurant_id, table_number, ...)
menu_items (id, restaurant_id, name, price, ...)
orders (id, restaurant_id, order_number, ...)
```

#### 3. **Automatic Filtering**
The system automatically adds `restaurant_id` to all queries:

```php
// Developer writes:
SELECT * FROM menu_items WHERE category_id = 5

// System automatically converts to:
SELECT * FROM menu_items WHERE category_id = 5 AND restaurant_id = 1
```

#### 4. **Access Methods**

**Subdomain** (Production):
```
https://pizza-palace.yourdomain.com
https://burger-house.yourdomain.com
```

**Path-based** (Development):
```
http://localhost/restaurant/pizza-palace
http://localhost/restaurant/burger-house
```

**Custom Domain**:
```
https://pizzapalace.com → Restaurant ID 1
https://burgerhouse.com → Restaurant ID 2
```

---

## User Roles & Responsibilities

### 1. **Super Admin** 👑
**Access Level**: ALL RESTAURANTS (Cross-tenant)

**Responsibilities**:
- ✅ Manage all restaurants on the platform
- ✅ Create/edit/delete restaurants
- ✅ View cross-restaurant analytics
- ✅ Manage subscriptions and billing
- ✅ Enable/disable restaurants
- ✅ Technical support and troubleshooting
- ✅ System configuration

**Dashboard Access**: `/?req=superadmin`

**Key Actions**:
- View list of all restaurants with statistics
- Activate/deactivate restaurants
- Extend subscription periods
- View aggregate revenue across all tenants
- Access any restaurant's data for support

**Limitations**:
- Cannot access individual restaurant's daily operations
- Not tied to any specific restaurant (`restaurant_id = NULL`)

---

### 2. **Restaurant Admin** 🔧
**Access Level**: SINGLE RESTAURANT (Full control)

**Responsibilities**:
- ✅ Complete restaurant management
- ✅ Staff management (hire, fire, assign roles)
- ✅ Menu management (add/edit/delete items)
- ✅ Table management (add/remove tables)
- ✅ Financial reports and analytics
- ✅ Cash session oversight
- ✅ System settings and configuration
- ✅ Approve refunds and discounts
- ✅ View audit logs

**Dashboard Access**: `/?req=staff&action=dashboard`

**Permissions** (via `role_permissions` table):
- `users.create`, `users.read`, `users.update`, `users.delete`
- `menu.create`, `menu.read`, `menu.update`, `menu.delete`
- `orders.create`, `orders.read`, `orders.update`, `orders.delete`
- `reports.read`, `reports.export`
- `settings.update`
- `cash.manage`, `cash.view_all`
- `refunds.approve`
- `discounts.approve`
- All kitchen and waiter permissions

**Key Actions**:
```php
// Add new staff member
POST /?req=api&action=staffAddUser
{
  "username": "john_waiter",
  "full_name": "John Smith",
  "email": "john@restaurant.com",
  "role": "waiter",
  "password": "secure123"
}

// Update menu item price
POST /?req=api&action=staffUpdateMenuItem
{
  "item_id": 15,
  "price": 25000
}

// View daily sales report
GET /?req=staff&action=sales_report&date=2025-11-17
```

**Workflow Example**:
1. Login at `/?req=staff` (username: admin)
2. Navigate to dashboard
3. Add new menu category: Appetizers
4. Add menu items: Spring Rolls, Samosas
5. Assign prices and modifiers
6. Create new waiter account
7. Assign tables to waiter
8. Monitor orders throughout the day
9. Close cash session at end of day
10. View sales report

---

### 3. **Manager** 📊
**Access Level**: SINGLE RESTAURANT (Operational control)

**Responsibilities**:
- ✅ Oversee daily operations
- ✅ Manage staff schedules and shifts
- ✅ Monitor order flow and kitchen
- ✅ Handle customer complaints
- ✅ Approve discounts (up to their limit)
- ✅ View sales reports (not financial)
- ✅ Manage cash sessions
- ✅ Staff performance monitoring
- ✅ Table management

**Permissions**:
- `orders.read`, `orders.update` (not create/delete)
- `menu.read` (cannot modify)
- `users.read` (cannot modify)
- `reports.read` (limited)
- `cash.manage`, `cash.view_all`
- `discounts.approve` (up to max_discount_percent)
- `kitchen.read`, `kitchen.update`
- `tables.read`, `tables.update`

**Cannot Do**:
- ❌ Add/remove staff
- ❌ Change menu prices
- ❌ Delete orders
- ❌ Approve refunds
- ❌ Access financial reports
- ❌ Change system settings

**Key Actions**:
```php
// Approve discount
POST /?req=api&action=applyDiscount
{
  "order_id": 125,
  "discount_percent": 10,
  "reason": "Customer complaint - cold food"
}

// View shift report
GET /?req=staff&action=shift_report&shift_id=45

// Assign table to waiter
POST /?req=api&action=assignTable
{
  "table_id": 8,
  "waiter_id": 12
}
```

**Workflow Example**:
1. Clock in at start of shift
2. Check staff assignments
3. Monitor order queue on kitchen display
4. Handle customer complaint → approve 10% discount
5. Restock inventory alerts
6. Manage table assignments
7. Review shift performance
8. Clock out

---

### 4. **Waiter** 🍽️
**Access Level**: SINGLE RESTAURANT (Order taking & table service)

**Responsibilities**:
- ✅ Take customer orders (manual entry)
- ✅ Manage assigned tables
- ✅ Modify orders before kitchen starts
- ✅ Process payments
- ✅ Print bills/receipts
- ✅ Handle waiter calls
- ✅ Update order status
- ✅ View their own sales

**Permissions**:
- `orders.create`, `orders.read`, `orders.update` (own orders only)
- `menu.read`
- `tables.read`, `tables.update` (assigned tables only)
- `payments.create`, `payments.read`
- `kitchen.read`

**Cannot Do**:
- ❌ Approve discounts beyond limit
- ❌ Cancel orders without approval
- ❌ Access other waiters' data
- ❌ Modify menu
- ❌ View financial reports
- ❌ Manage cash sessions

**Key Actions**:
```php
// Create new order
POST /?req=api&action=staffAddOrder
{
  "table_id": 5,
  "items": [
    {"menu_item_id": 12, "quantity": 2},
    {"menu_item_id": 8, "quantity": 1, "modifiers": "No onions"}
  ],
  "customer_name": "John Doe",
  "notes": "Customer allergic to peanuts"
}

// Update order status
POST /?req=api&action=updateOrderStatus
{
  "order_id": 156,
  "status": "served"
}

// Process payment
POST /?req=api&action=processPayment
{
  "order_id": 156,
  "payment_method": "cash",
  "amount_paid": 50000
}

// Respond to waiter call
POST /?req=api&action=respondWaiterCall
{
  "call_id": 89,
  "waiter_id": 15
}
```

**Workflow Example**:
1. Clock in for shift
2. View assigned tables: 1, 2, 3, 4, 5
3. Customer sits at Table 3
4. Take order: 2x Burger, 1x Fries, 2x Coke
5. Add special instructions: "Extra cheese"
6. Submit order → Kitchen receives
7. Monitor order status: Preparing → Ready
8. Serve food, mark as "Served"
9. Customer requests bill
10. Print bill, receive payment
11. Process payment: Cash 50,000 RWF
12. Generate receipt
13. Mark table as available
14. Clock out at end of shift

---

### 5. **Kitchen Staff** 👨‍🍳
**Access Level**: SINGLE RESTAURANT (Kitchen operations)

**Responsibilities**:
- ✅ View incoming orders (Kitchen Display System)
- ✅ Update order preparation status
- ✅ Mark orders as ready
- ✅ Communicate delays
- ✅ View menu items and recipes
- ✅ Track order queue

**Permissions**:
- `kitchen.read`, `kitchen.update`
- `orders.read`, `orders.update` (status only)
- `menu.read`

**Cannot Do**:
- ❌ Create or delete orders
- ❌ Modify prices
- ❌ Access customer info
- ❌ Process payments
- ❌ View financial data

**Key Actions**:
```php
// Start preparing order
POST /?req=api&action=updateOrderStatus
{
  "order_id": 167,
  "status": "preparing"
}

// Mark item as ready
POST /?req=api&action=updateOrderItemStatus
{
  "order_id": 167,
  "item_id": 234,
  "status": "ready"
}

// Complete order
POST /?req=api&action=updateOrderStatus
{
  "order_id": 167,
  "status": "ready"
}

// View order queue
GET /?req=api&action=getKitchenOrders&status=pending
```

**Workflow Example**:
1. Login to Kitchen Display System
2. View pending orders (sorted by time)
3. Order #167 arrives:
   - Table 8
   - 2x Margherita Pizza
   - 1x Caesar Salad
   - Special: No tomatoes on salad
4. Start preparing → Update status to "Preparing"
5. Salad ready → Mark item as ready
6. Pizza ready → Mark item as ready
7. Complete order → Update to "Ready"
8. Waiter collects → Order served
9. Next order appears automatically

---

### 6. **Cashier** 💰
**Access Level**: SINGLE RESTAURANT (Payment & cash management)

**Responsibilities**:
- ✅ Process all payments
- ✅ Open/close cash sessions
- ✅ Handle cash, card, mobile payments
- ✅ Print receipts
- ✅ Count cash drawer
- ✅ Record cash ins/outs
- ✅ Process refunds (with approval)
- ✅ Generate shift reports

**Permissions**:
- `payments.create`, `payments.read`, `payments.update`
- `cash.manage`, `cash.view_own`
- `orders.read`, `orders.update` (payment status)
- `refunds.create` (requires approval)
- `reports.read` (cash reports only)

**Cannot Do**:
- ❌ Modify orders
- ❌ Approve refunds (needs manager/admin)
- ❌ Access other cashiers' sessions
- ❌ Delete payment records
- ❌ Modify menu or tables

**Key Actions**:
```php
// Open cash session
POST /?req=api&action=openCashSession
{
  "opening_balance": 100000,
  "expected_cash": 100000,
  "notes": "Morning shift start"
}

// Process payment
POST /?req=api&action=processPayment
{
  "order_id": 189,
  "payment_method": "cash",
  "amount_paid": 75000,
  "change_given": 0
}

// Cash in (additional money added)
POST /?req=api&action=recordCashIn
{
  "amount": 50000,
  "reason": "Bank deposit return"
}

// Cash out (money removed)
POST /?req=api&action=recordCashOut
{
  "amount": 20000,
  "reason": "Supplier payment"
}

// Request refund
POST /?req=api&action=requestRefund
{
  "order_id": 189,
  "amount": 25000,
  "reason": "Customer complaint - wrong order"
}

// Close cash session
POST /?req=api&action=closeCashSession
{
  "closing_balance": 205000,
  "actual_cash": 205000,
  "notes": "All payments recorded"
}
```

**Workflow Example**:
1. Clock in for shift
2. Open cash session with 100,000 RWF
3. Customer pays for Order #189: 75,000 RWF cash
4. Process payment, give receipt
5. Customer pays Order #190: 45,000 RWF by card
6. Process card payment
7. Customer wants refund for wrong order
8. Create refund request → Manager approves
9. Process refund: 25,000 RWF cash
10. Supplier arrives, pay 20,000 RWF (Cash Out)
11. End of shift: Count drawer = 205,000 RWF
12. Close cash session
13. Generate shift report
14. Clock out

---

### 7. **Customer** (QR Code Orders) 🧑‍🤝‍🧑
**Access Level**: PUBLIC (Limited to ordering)

**Responsibilities**:
- ✅ Scan QR code on table
- ✅ Browse menu
- ✅ Place orders
- ✅ Track order status
- ✅ Call waiter
- ✅ Request bill

**What They Can Do**:
```php
// Scan QR code
GET /?table=ABC123DEF

// View menu
GET /?req=api&action=getMenuByTable&table_id=5

// Place order
POST /?req=api&action=placeOrder
{
  "table_id": 5,
  "items": [
    {"menu_item_id": 12, "quantity": 1},
    {"menu_item_id": 8, "quantity": 2}
  ],
  "customer_name": "Alice",
  "notes": "Extra spicy"
}

// Call waiter
POST /?req=api&action=callWaiter
{
  "table_id": 5,
  "reason": "Need assistance"
}

// Track order
GET /?req=api&action=trackOrder&order_id=234

// Request bill
POST /?req=api&action=requestBill
{
  "table_id": 5
}
```

**Customer Journey**:
1. Enter restaurant
2. Sit at Table 5
3. Scan QR code on table
4. Menu loads on phone
5. Browse categories: Starters, Mains, Drinks
6. Select: 1x Burger, 1x Fries, 1x Coke
7. Add to cart, review order
8. Submit order (no payment yet)
9. Order sent to kitchen
10. Track status: Pending → Preparing → Ready
11. Waiter serves food
12. Finish meal
13. Request bill via QR menu
14. Waiter brings bill
15. Pay at cashier or via mobile money
16. Leave restaurant

---

## System Modules

### 1. **Authentication & Authorization Module**

**Files**:
- `app/controllers/staff.php` - Staff login/logout
- `app/models/Staff.php` - User authentication
- `app/core/Permission.php` - RBAC enforcement

**Features**:
- Session-based authentication
- Password hashing (bcrypt)
- Role-based permissions
- Last login tracking
- Failed login attempts

**Login Flow**:
```
1. User enters username/password
2. System validates credentials
3. Check if user is active (is_active = 1)
4. Verify restaurant subscription is active
5. Load user permissions from role_permissions
6. Create session with user_id, role, restaurant_id
7. Update last_login timestamp
8. Redirect to appropriate dashboard
```

---

### 2. **Order Management Module**

**Files**:
- `app/controllers/api.php` - Order CRUD operations
- `app/models/Order.php` - Order business logic
- `app/views/kitchen.php` - Kitchen display

**Order Lifecycle**:
```
1. PENDING     → Order created, waiting for kitchen
2. PREPARING   → Kitchen started cooking
3. READY       → Food ready for serving
4. SERVED      → Waiter delivered to table
5. PAID        → Payment completed
6. COMPLETED   → Order finalized
7. CANCELLED   → Order cancelled
```

**Key Features**:
- Real-time order updates
- Kitchen display system
- Order modification before cooking
- Split bills
- Order history
- Order tracking

---

### 3. **Menu Management Module**

**Files**:
- `app/controllers/api.php` - Menu CRUD
- `app/models/Menu.php` - Menu business logic

**Structure**:
```
Categories
  ├── Starters
  │   ├── Spring Rolls (15,000 RWF)
  │   └── Samosas (8,000 RWF)
  ├── Main Course
  │   ├── Burger (25,000 RWF)
  │   │   └── Modifiers: Extra cheese, No onions
  │   └── Pizza (35,000 RWF)
  └── Drinks
      ├── Coke (3,000 RWF)
      └── Water (1,500 RWF)
```

**Features**:
- Category management
- Item management (name, price, image)
- Modifiers and add-ons
- Item availability (in stock/out of stock)
- Pricing history
- Popular items tracking

---

### 4. **Table Management Module**

**Features**:
- Add/remove tables
- Table status (available, occupied, reserved)
- QR code generation per table
- Table assignments to waiters
- Table capacity
- Table reset after customer leaves

**Table Status Flow**:
```
AVAILABLE → OCCUPIED → NEEDS_CLEANING → AVAILABLE
           ↓
        RESERVED (for future bookings)
```

---

### 5. **Cash Management Module**

**Files**:
- `app/controllers/api.php` - Cash operations
- `app/models/CashSession.php` - Session management

**Cash Session Lifecycle**:
```
1. OPEN
   - Opening balance: 100,000 RWF
   - Expected cash: 100,000 RWF
   
2. TRANSACTIONS
   - Sales: +500,000 RWF
   - Cash in: +50,000 RWF
   - Cash out: -20,000 RWF
   - Expected: 630,000 RWF
   
3. CLOSE
   - Actual cash: 630,000 RWF
   - Variance: 0 RWF (balanced)
```

**Features**:
- Opening balance tracking
- Real-time cash tracking
- Cash in/out recording
- Variance detection
- Shift reports
- Multi-cashier support

---

### 6. **Reporting Module**

**Files**:
- `app/controllers/staff.php` - Report generation
- `app/models/Report.php` - Report queries

**Available Reports**:

**1. Sales Report**
- Daily/Weekly/Monthly sales
- Revenue by category
- Popular items
- Average order value
- Sales by payment method

**2. Staff Performance Report**
- Orders processed per waiter
- Average service time
- Tips collected
- Clock in/out records
- Shift assignments

**3. Financial Report** (Admin only)
- Total revenue
- Cost of goods sold
- Profit margins
- Tax collected
- Discounts given
- Refunds processed

**4. Kitchen Performance Report**
- Average preparation time
- Orders per hour
- Peak hours
- Item preparation times

**5. Inventory Report** (Future)
- Stock levels
- Low stock alerts
- Ingredient usage
- Waste tracking

---

### 7. **Multi-Tenancy Module**

**Files**:
- `src/restaurant.php` - Restaurant management
- `src/tenant_middleware.php` - Data isolation
- `src/model.php` - Auto-filtering queries

**How It Works**:

**Restaurant Detection**:
```php
// 1. Check subdomain
if (pizza-palace.example.com) {
    $restaurant = getBySlug('pizza-palace');
}

// 2. Check path
if (/restaurant/pizza-palace) {
    $restaurant = getBySlug('pizza-palace');
}

// 3. Check session
if ($_SESSION['restaurant_id']) {
    $restaurant = getById($_SESSION['restaurant_id']);
}
```

**Automatic Filtering**:
```php
// Model class intercepts all queries
class Model {
    public function query($sql) {
        // Automatically add restaurant_id
        if (needsTenantFilter($sql)) {
            $sql = addTenantFilter($sql, $this->restaurantId);
        }
        return $this->db->query($sql);
    }
}
```

**Subscription Enforcement**:
```php
// Check before any operation
TenantMiddleware::requireActiveSubscription();
TenantMiddleware::checkLimit('tables', 20); // Max 20 tables
TenantMiddleware::checkLimit('users', 10);  // Max 10 users
```

---

## Security & Access Control

### 1. **Permission System**

**Database Tables**:
```sql
permissions (id, name, description)
roles (id, name, description)
role_permissions (role_id, permission_id)
```

**Permission Check**:
```php
// In controller
Permission::require('orders.create');

// Check if user has permission
if (Permission::check('reports.export')) {
    // Allow export
}

// Multiple permissions (OR)
Permission::requireAny(['orders.update', 'orders.delete']);

// Multiple permissions (AND)
Permission::requireAll(['cash.manage', 'reports.read']);
```

**Available Permissions**:
- `users.*` - Staff management
- `menu.*` - Menu management
- `orders.*` - Order operations
- `kitchen.*` - Kitchen access
- `tables.*` - Table management
- `payments.*` - Payment processing
- `cash.*` - Cash session management
- `reports.*` - Report access
- `settings.*` - System configuration
- `refunds.*` - Refund operations
- `discounts.*` - Discount approval

### 2. **Data Isolation**

**Restaurant-Level Isolation**:
```php
// Every query is automatically filtered
SELECT * FROM orders 
WHERE restaurant_id = 1  // Auto-added

// Prevents cross-restaurant access
SELECT * FROM orders WHERE id = 500
// Returns nothing if order 500 belongs to restaurant 2
```

**User-Level Isolation** (Waiters):
```php
// Waiters only see their own data
SELECT * FROM orders 
WHERE restaurant_id = 1 
  AND waiter_id = 15  // Current user
```

### 3. **Input Sanitization**

```php
// All user input is sanitized
$clean = htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');

// SQL injection prevention (PDO prepared statements)
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 4. **Password Security**

```php
// Password hashing
$hash = password_hash($password, PASSWORD_BCRYPT);

// Password verification
if (password_verify($password, $hash)) {
    // Login successful
}

// Minimum requirements
- 8+ characters
- Cannot be "password", "123456", etc.
```

### 5. **Session Security**

```php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // HTTPS only

// Session timeout (2 hours)
if (time() - $_SESSION['last_activity'] > 7200) {
    session_destroy();
}
```

---

## Workflows

### Workflow 1: Restaurant Registration

```
1. Restaurant owner visits /?req=register
2. Fills registration form:
   - Restaurant name: "Pizza Palace"
   - Owner name: "John Doe"
   - Email: admin@pizzapalace.com
   - Phone: +250 788 123 456
   - Address: Kigali, Rwanda
   - Password: secure123
   - Selects plan: Premium (79K/month)
   
3. System validates:
   - Email not already used
   - Slug "pizza-palace" available
   - Valid payment method selected
   
4. System creates:
   - Restaurant record (ID: 5, slug: pizza-palace)
   - Admin user (restaurant_id: 5, role: admin)
   - Default settings
   - Sample menu categories
   - QR codes for tables
   
5. Email sent with:
   - Access URL: https://pizza-palace.example.com
   - Username: admin
   - Password: secure123
   - Getting started guide
   
6. Restaurant is ACTIVE for 30 days (trial period)
```

### Workflow 2: Daily Operations

```
MORNING (8:00 AM)
├── Admin logs in
├── Opens cash session (Opening: 50,000 RWF)
├── Reviews yesterday's sales
├── Checks staff schedules
├── Updates menu (out of stock items)
└── Assigns tables to waiters

LUNCH RUSH (12:00 PM - 2:00 PM)
├── Customers scan QR codes
├── Orders flow in
│   ├── Waiter creates Order #101 (Table 5)
│   ├── Kitchen receives order
│   ├── Kitchen prepares (status: PREPARING)
│   ├── Kitchen marks ready (status: READY)
│   ├── Waiter serves (status: SERVED)
│   └── Customer pays at cashier
├── Manager monitors:
│   ├── Order queue length
│   ├── Kitchen performance
│   └── Table turnover rate
└── Kitchen staff focus on preparing orders

AFTERNOON (3:00 PM - 5:00 PM)
├── Admin reviews lunch performance
├── Adjusts staffing for dinner
├── Replenishes supplies
└── Updates menu prices if needed

DINNER SERVICE (6:00 PM - 10:00 PM)
├── Similar to lunch
├── More waiters on duty
├── Manager handles peak load
└── Resolves customer issues

CLOSING (11:00 PM)
├── Cashier closes cash session
│   ├── Expected: 1,500,000 RWF
│   ├── Actual: 1,498,000 RWF
│   └── Variance: -2,000 RWF (acceptable)
├── Kitchen closes
├── All staff clock out
├── Admin reviews:
│   ├── Total sales: 1,450,000 RWF
│   ├── Orders: 87
│   ├── Average order: 16,667 RWF
│   └── Top items: Burger (25), Pizza (18)
└── System generates daily report
```

### Workflow 3: Order Processing

```
CUSTOMER JOURNEY:
1. Customer scans QR code → Table 8 detected
2. Menu loads on phone
3. Selects items:
   - 2x Burger (50,000 RWF)
   - 2x Fries (16,000 RWF)
   - 3x Coke (9,000 RWF)
   - Total: 75,000 RWF
4. Submits order

SYSTEM PROCESSING:
5. Order created:
   - Order #245
   - Table: 8
   - Status: PENDING
   - Restaurant: Pizza Palace
   - Time: 12:34 PM

KITCHEN:
6. Kitchen display shows Order #245
7. Chef starts cooking → Status: PREPARING
8. Chef marks items ready → Status: READY
9. Notification sent to waiter

WAITER:
10. Waiter sees "Table 8 - Order Ready"
11. Picks up order from kitchen
12. Delivers to Table 8
13. Marks order as SERVED

PAYMENT:
14. Customer requests bill
15. Waiter generates bill (75,000 RWF)
16. Customer pays at cashier
17. Cashier processes:
    - Method: Cash
    - Amount: 75,000 RWF
    - Change: 0 RWF
18. Receipt printed
19. Order status: PAID → COMPLETED

CLEANUP:
20. Table marked as NEEDS_CLEANING
21. Staff cleans table
22. Table marked as AVAILABLE
```

### Workflow 4: Staff Management

```
HIRING:
1. Admin navigates to Staff Management
2. Clicks "Add Staff"
3. Fills form:
   - Username: sarah_waiter
   - Full name: Sarah Johnson
   - Email: sarah@pizzapalace.com
   - Phone: +250 788 555 666
   - Role: Waiter
   - Password: (auto-generated)
4. Sets permissions:
   - Max discount: 5%
   - Can handle cash: No
   - Security level: Standard
5. Saves → Staff user created
6. Email sent to sarah@pizzapalace.com with credentials

FIRST DAY:
7. Sarah logs in: /?req=staff
8. System shows onboarding
9. Assigns tables: 1, 2, 3, 4, 5
10. Training period starts

DAILY WORK:
11. Sarah clocks in (8:00 AM)
12. Reviews assigned tables
13. Takes orders throughout shift
14. Processes payments
15. Clocks out (4:00 PM)
16. System generates performance report:
    - Orders: 25
    - Sales: 450,000 RWF
    - Average order: 18,000 RWF
    - Service time: 12 minutes

PERFORMANCE REVIEW:
17. Manager views Sarah's stats
18. Sarah has:
    - 95% positive feedback
    - Fast service times
    - High sales per hour
19. Admin increases Sarah's discount limit to 10%
```

---

## API Reference

### Authentication Endpoints

**Staff Login**
```http
POST /?req=staff&action=authenticate
Content-Type: application/x-www-form-urlencoded

username=admin&password=admin123

Response:
{
  "status": "OK",
  "redirect": "/?req=staff&action=dashboard"
}
```

**Super Admin Login**
```http
POST /?req=superadmin&action=login
Content-Type: application/x-www-form-urlencoded

email=superadmin@restaurant.com&password=admin123

Response:
{
  "status": "OK",
  "message": "Login successful",
  "data": {
    "email": "superadmin@restaurant.com",
    "role": "super_admin"
  }
}
```

### Order Endpoints

**Create Order**
```http
POST /?req=api&action=staffAddOrder
Content-Type: application/json

{
  "table_id": 5,
  "items": [
    {
      "menu_item_id": 12,
      "quantity": 2,
      "modifiers": "No onions"
    }
  ],
  "customer_name": "John Doe",
  "notes": "Allergic to peanuts"
}

Response:
{
  "status": "OK",
  "order_id": 245,
  "order_number": "ORD-245",
  "total": 50000
}
```

**Get Orders**
```http
GET /?req=api&action=staffGetOrders&status=pending

Response:
{
  "status": "OK",
  "data": [
    {
      "id": 245,
      "order_number": "ORD-245",
      "table_number": 5,
      "status": "pending",
      "total_amount": 50000,
      "created_at": "2025-11-17 12:34:56"
    }
  ]
}
```

**Update Order Status**
```http
POST /?req=api&action=updateOrderStatus
Content-Type: application/json

{
  "order_id": 245,
  "status": "preparing"
}

Response:
{
  "status": "OK",
  "message": "Order status updated"
}
```

### Menu Endpoints

**Get Menu**
```http
GET /?req=api&action=staffGetMenu

Response:
{
  "status": "OK",
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Main Course",
        "items": [
          {
            "id": 12,
            "name": "Burger",
            "price": 25000,
            "image": "burger.jpg",
            "available": true
          }
        ]
      }
    ]
  }
}
```

**Add Menu Item**
```http
POST /?req=api&action=staffAddMenuItem
Content-Type: application/json

{
  "category_id": 1,
  "name": "Cheeseburger",
  "description": "Beef burger with cheese",
  "price": 30000,
  "image": "cheeseburger.jpg"
}

Response:
{
  "status": "OK",
  "item_id": 25,
  "message": "Menu item added"
}
```

### Payment Endpoints

**Process Payment**
```http
POST /?req=api&action=processPayment
Content-Type: application/json

{
  "order_id": 245,
  "payment_method": "cash",
  "amount_paid": 50000,
  "change_given": 0
}

Response:
{
  "status": "OK",
  "payment_id": 789,
  "receipt_number": "REC-789"
}
```

### Cash Session Endpoints

**Open Session**
```http
POST /?req=api&action=openCashSession
Content-Type: application/json

{
  "opening_balance": 100000,
  "notes": "Morning shift"
}

Response:
{
  "status": "OK",
  "session_id": 45
}
```

**Close Session**
```http
POST /?req=api&action=closeCashSession
Content-Type: application/json

{
  "closing_balance": 500000,
  "actual_cash": 498000,
  "notes": "End of shift"
}

Response:
{
  "status": "OK",
  "variance": -2000,
  "message": "Session closed"
}
```

---

## Database Schema

### Core Tables

**restaurants** - Master tenant table
```sql
id INT PRIMARY KEY
name VARCHAR(255)
slug VARCHAR(100) UNIQUE
email VARCHAR(255) UNIQUE
subscription_plan ENUM('trial','basic','premium','enterprise')
subscription_start DATE
subscription_end DATE
is_active TINYINT(1)
max_tables INT
max_users INT
created_at TIMESTAMP
```

**staff_users** - All staff accounts
```sql
id INT PRIMARY KEY
restaurant_id INT (tenant isolation)
username VARCHAR(50) UNIQUE
password_hash VARCHAR(255)
full_name VARCHAR(100)
email VARCHAR(100)
role ENUM('admin','manager','waiter','kitchen','cashier','super_admin')
is_active TINYINT(1)
can_handle_cash TINYINT(1)
max_discount_percent DECIMAL(5,2)
last_login TIMESTAMP
```

**restaurant_tables** - Physical tables
```sql
id INT PRIMARY KEY
restaurant_id INT
table_number VARCHAR(10)
qr_code VARCHAR(255) UNIQUE
capacity INT
status ENUM('available','occupied','reserved','cleaning')
assigned_waiter_id INT
```

**menu_categories** - Menu organization
```sql
id INT PRIMARY KEY
restaurant_id INT
name VARCHAR(100)
description TEXT
display_order INT
is_active TINYINT(1)
```

**menu_items** - Individual dishes
```sql
id INT PRIMARY KEY
restaurant_id INT
category_id INT
name VARCHAR(255)
description TEXT
price DECIMAL(10,2)
image VARCHAR(255)
is_available TINYINT(1)
preparation_time INT (minutes)
```

**orders** - Customer orders
```sql
id INT PRIMARY KEY
restaurant_id INT
order_number VARCHAR(50) UNIQUE
table_id INT
waiter_id INT
customer_name VARCHAR(100)
status ENUM('pending','preparing','ready','served','paid','completed','cancelled')
subtotal DECIMAL(10,2)
tax_amount DECIMAL(10,2)
service_charge DECIMAL(10,2)
discount_amount DECIMAL(10,2)
total_amount DECIMAL(10,2)
notes TEXT
created_at TIMESTAMP
```

**order_items** - Items in each order
```sql
id INT PRIMARY KEY
restaurant_id INT
order_id INT
menu_item_id INT
quantity INT
unit_price DECIMAL(10,2)
modifiers TEXT
subtotal DECIMAL(10,2)
status ENUM('pending','preparing','ready','served')
```

**payments** - Payment records
```sql
id INT PRIMARY KEY
restaurant_id INT
order_id INT
payment_method ENUM('cash','card','mobile_money','bank_transfer')
amount DECIMAL(10,2)
received_amount DECIMAL(10,2)
change_amount DECIMAL(10,2)
transaction_reference VARCHAR(100)
processed_by_user_id INT
created_at TIMESTAMP
```

**cash_sessions** - Cashier shifts
```sql
id INT PRIMARY KEY
restaurant_id INT
cashier_id INT
opening_balance DECIMAL(10,2)
closing_balance DECIMAL(10,2)
expected_cash DECIMAL(10,2)
actual_cash DECIMAL(10,2)
variance DECIMAL(10,2)
status ENUM('open','closed')
opened_at TIMESTAMP
closed_at TIMESTAMP
```

**permissions** - System permissions
```sql
id INT PRIMARY KEY
name VARCHAR(100) UNIQUE
description TEXT
category VARCHAR(50)
```

**role_permissions** - Role-permission mapping
```sql
role_id INT
permission_id INT
PRIMARY KEY (role_id, permission_id)
```

**audit_trail** - System activity log
```sql
id INT PRIMARY KEY
restaurant_id INT
user_id INT
action VARCHAR(100)
table_name VARCHAR(100)
record_id INT
old_values TEXT (JSON)
new_values TEXT (JSON)
ip_address VARCHAR(45)
user_agent TEXT
created_at TIMESTAMP
```

---

## Deployment & Configuration

### Server Requirements
- PHP 8.1+
- MySQL 8.0+
- Apache/Nginx
- SSL certificate (HTTPS)
- 2GB RAM minimum
- 10GB storage

### Installation Steps

1. **Clone Repository**
```bash
git clone https://github.com/your-repo/restaurant-system.git
cd restaurant-system
```

2. **Configure Database**
```bash
# Edit src/config.php
define("DB_HOST", "localhost");
define("DB_NAME", "db_restaurant");
define("DB_USER", "root");
define("DB_PWD", "your_password");
```

3. **Run Migrations**
```bash
mysql -u root -p db_restaurant < database.sql
mysql -u root -p db_restaurant < rbac_security.sql
mysql -u root -p db_restaurant < multi_tenancy.sql
```

4. **Set Permissions**
```bash
chmod -R 755 app/
chmod -R 777 images/
chmod -R 777 assets/uploads/
```

5. **Configure Virtual Hosts**

**Apache** (multi-domain):
```apache
<VirtualHost *:80>
    ServerName *.example.com
    ServerAlias example.com
    DocumentRoot /var/www/restaurant
    
    <Directory /var/www/restaurant>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. **Create Super Admin**
```sql
UPDATE staff_users 
SET email='superadmin@restaurant.com', 
    role='super_admin', 
    restaurant_id=NULL 
WHERE id=1;
```

7. **Test Installation**
```
http://localhost/restaurant/?req=superadmin
Login: superadmin@restaurant.com / admin123
```

### Environment Variables
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=db_restaurant
DB_USERNAME=root
DB_PASSWORD=secure_password

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true

# Mail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
```

### Backup Strategy
```bash
# Daily database backup
mysqldump -u root -p db_restaurant > backup_$(date +%Y%m%d).sql

# Weekly full backup
tar -czf backup_$(date +%Y%m%d).tar.gz \
  /var/www/restaurant \
  /var/backups/db_restaurant_*.sql
```

### Monitoring
- Server uptime: Pingdom, UptimeRobot
- Error logging: Sentry, Rollbar
- Performance: New Relic, DataDog
- Database: Slow query log, Performance Schema

---

## Support & Maintenance

### Common Issues

**Issue: "Access denied" after login**
```
Solution: Check restaurant subscription is active
SQL: UPDATE restaurants SET is_active=1 WHERE id=1
```

**Issue: "Restaurant context not set"**
```
Solution: Initialize restaurant in autoload
Check: Restaurant::initialize() is called
```

**Issue: Orders not showing in kitchen**
```
Solution: Check restaurant_id matching
Verify: Both user and order have same restaurant_id
```

### System Updates
```bash
# Backup first!
mysqldump -u root -p db_restaurant > backup.sql

# Pull updates
git pull origin main

# Run new migrations
mysql -u root -p db_restaurant < updates/migration_v2.sql

# Clear cache
rm -rf /tmp/cache/*

# Test
curl http://localhost/restaurant/?req=api&action=health
```

### Contact Information
- **Technical Support**: support@example.com
- **Sales**: sales@example.com
- **Documentation**: https://docs.example.com
- **Status Page**: https://status.example.com

---

## Appendix

### Subscription Plans

| Feature | Trial | Basic | Premium | Enterprise |
|---------|-------|-------|---------|------------|
| Duration | 30 days | Monthly | Monthly | Custom |
| Price | FREE | 29,000 RWF | 79,000 RWF | Custom |
| Max Tables | 10 | 20 | 50 | Unlimited |
| Max Users | 5 | 10 | 20 | Unlimited |
| QR Ordering | ✅ | ✅ | ✅ | ✅ |
| Reports | Basic | Standard | Advanced | Custom |
| Support | Email | Email + Chat | Priority | Dedicated |
| API Access | ❌ | ❌ | ✅ | ✅ |
| Custom Domain | ❌ | ❌ | ✅ | ✅ |
| White Label | ❌ | ❌ | ❌ | ✅ |

### Glossary

- **Tenant**: Individual restaurant using the system
- **Multi-Tenancy**: Multiple restaurants on same platform
- **RBAC**: Role-Based Access Control
- **QR Code**: Quick Response code for table ordering
- **Cash Session**: Period when cashier is handling money
- **Order Lifecycle**: Journey from creation to completion
- **Modifier**: Customization to menu item (e.g., "No onions")
- **Variance**: Difference between expected and actual cash
- **Audit Trail**: Record of all system changes
- **Slug**: URL-friendly identifier (e.g., "pizza-palace")

---

**Document Version**: 1.0  
**Last Updated**: November 17, 2025  
**Author**: System Documentation Team  
**Status**: Complete ✅
