# Super Admin Dashboard System - Complete Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [User Roles & Responsibilities](#user-roles--responsibilities)
3. [Super Admin Features](#super-admin-features)
4. [Restaurant Owner Features](#restaurant-owner-features)
5. [Staff User Features](#staff-user-features)
6. [System Architecture](#system-architecture)
7. [Database Structure](#database-structure)
8. [API Reference](#api-reference)
9. [Security & Access Control](#security--access-control)
10. [Subscription Plans](#subscription-plans)
11. [Multi-Tenancy Implementation](#multi-tenancy-implementation)
12. [How To Use The System](#how-to-use-the-system)

---

## System Overview

This is a multi-tenant restaurant management system that allows multiple restaurants to use the same platform while keeping their data completely isolated. The system uses a shared database approach with tenant identification through `restaurant_id` columns.

### Key Features
- ✅ Multi-restaurant support (multi-tenancy)
- ✅ Role-based access control (RBAC)
- ✅ Subscription-based plans
- ✅ Comprehensive super admin dashboard
- ✅ Restaurant self-service registration
- ✅ Data isolation and security
- ✅ Resource limits per plan
- ✅ Real-time analytics and reporting

---

## User Roles & Responsibilities

### 1. Super Admin
**Who:** System administrator managing the entire platform
**Access Level:** Full system access across all restaurants
**Login:** `http://localhost/restaurant/?req=superadmin`
**Credentials:** 
- Email: `superadmin@restaurant.com`
- Password: Check database `staff_users` table

**Responsibilities:**
- ✅ Manage all restaurants (create, edit, delete, suspend)
- ✅ Monitor system-wide statistics and analytics
- ✅ Manage subscriptions and billing
- ✅ View and manage all users across restaurants
- ✅ Configure system settings
- ✅ Access audit logs and security events
- ✅ Provide support to restaurant owners
- ✅ Perform system backups and maintenance
- ✅ Manage subscription plans and pricing

**Super Admin Cannot:**
- ❌ Interfere with individual restaurant operations directly
- ❌ Access restaurant data without proper authorization logs
- ❌ Modify menu items or take orders (restaurant-specific tasks)

### 2. Restaurant Owner/Admin
**Who:** Owner or manager of a specific restaurant
**Access Level:** Full access to their restaurant only
**Login:** `http://localhost/restaurant/?req=staff&action=login`
**Role in Database:** `admin` or `manager`

**Responsibilities:**
- ✅ Manage restaurant profile and settings
- ✅ Add/edit/delete menu items and categories
- ✅ Manage staff users (waiters, kitchen staff, cashiers)
- ✅ View and manage orders
- ✅ Generate reports for their restaurant
- ✅ Manage tables and seating arrangements
- ✅ Configure restaurant-specific settings
- ✅ Handle cash management and sessions
- ✅ Monitor restaurant analytics
- ✅ Manage their subscription plan

**Restaurant Admin Cannot:**
- ❌ Access other restaurants' data
- ❌ View system-wide statistics
- ❌ Manage super admin settings
- ❌ Change subscription pricing
- ❌ Access other restaurants' financial data

### 3. Manager
**Who:** Restaurant manager with elevated permissions
**Access Level:** Most features within their restaurant
**Login:** `http://localhost/restaurant/?req=staff&action=login`
**Role in Database:** `manager`

**Responsibilities:**
- ✅ Manage daily operations
- ✅ Approve/reject waiter calls
- ✅ View all orders and reports
- ✅ Manage menu items
- ✅ Handle cash sessions
- ✅ View analytics and statistics
- ✅ Manage tables

**Manager Cannot:**
- ❌ Add or remove staff users
- ❌ Delete the restaurant
- ❌ Change subscription plans
- ❌ Access admin-level settings

### 4. Waiter
**Who:** Front-of-house staff taking orders
**Access Level:** Order management and table service
**Login:** `http://localhost/restaurant/?req=staff&action=login`
**Role in Database:** `waiter`

**Responsibilities:**
- ✅ Take customer orders
- ✅ View menu items and prices
- ✅ Assign orders to tables
- ✅ Send orders to kitchen
- ✅ Call for approvals (void, discount)
- ✅ View table status
- ✅ Process payments (basic)

**Waiter Cannot:**
- ❌ Edit menu items
- ❌ Add/remove users
- ❌ View financial reports
- ❌ Manage cash sessions
- ❌ Delete orders without approval

### 5. Kitchen Staff
**Who:** Back-of-house staff preparing food
**Access Level:** Kitchen display and order status
**Login:** `http://localhost/restaurant/?req=staff&action=login`
**Role in Database:** `kitchen`

**Responsibilities:**
- ✅ View incoming orders
- ✅ Update order status (preparing, ready)
- ✅ Mark items as completed
- ✅ View order priorities
- ✅ Communicate with waiters

**Kitchen Staff Cannot:**
- ❌ Take new orders
- ❌ Edit menu items
- ❌ View financial data
- ❌ Manage tables
- ❌ Process payments

### 6. Cashier
**Who:** Staff handling payments and cash
**Access Level:** Payment processing and cash management
**Login:** `http://localhost/restaurant/?req=staff&action=login`
**Role in Database:** `cashier`

**Responsibilities:**
- ✅ Process payments (cash, card, mobile)
- ✅ Manage cash sessions (open, close)
- ✅ Handle refunds
- ✅ Print receipts
- ✅ View payment history
- ✅ Balance cash drawer

**Cashier Cannot:**
- ❌ Edit menu items
- ❌ Manage users
- ❌ View detailed analytics
- ❌ Modify orders

---

## Super Admin Features

### Dashboard Overview
**URL:** `?req=superadmin&action=dashboard`

The super admin dashboard provides a comprehensive view of the entire platform:

#### Statistics Cards
1. **Total Restaurants** - Count of all registered restaurants
2. **Active Restaurants** - Number of currently active restaurants
3. **Total Revenue** - Combined revenue from all restaurants
4. **Total Users** - All staff users across all restaurants

#### Revenue Chart
- 7-day, 30-day, and 90-day views
- Visual representation of subscription revenue
- Trend analysis

#### Recent Activity Feed
- New restaurant registrations
- Subscription upgrades/downgrades
- Expiring subscriptions (alerts)
- System events

#### Restaurant Table
Displays all restaurants with:
- Restaurant name and logo
- Email and contact info
- Subscription plan
- Active/Inactive status
- User count
- Table count
- Order count
- Revenue
- Action buttons (View, Edit, Delete)

### Restaurant Management

#### Create New Restaurant
**URL:** `?req=superadmin&action=create_restaurant`

**Form Fields:**
- **Name** (required) - Restaurant name
- **Slug** (required) - URL-friendly identifier
- **Email** (required) - Contact email
- **Phone** - Contact number
- **Address** - Physical location
- **Subscription Plan** (required) - Trial, Basic, Premium, Enterprise
- **Subscription End Date** - Auto-calculated or custom
- **Active Status** - Enable/disable restaurant
- **Max Tables** - Resource limit
- **Max Users** - Staff limit

**Process:**
1. Fill in restaurant details
2. Select subscription plan
3. Set resource limits
4. Click "Create Restaurant"
5. System creates:
   - Restaurant record
   - Default admin user
   - Initial configuration

#### Edit Restaurant
**URL:** `?req=superadmin&action=edit_restaurant&id={restaurant_id}`

**Editable Fields:**
- All fields from create form
- Can change plan, limits, status
- Can extend subscription

**Process:**
1. Click "Edit" button on dashboard
2. Modify required fields
3. Click "Update Restaurant"
4. Changes apply immediately

#### Delete Restaurant
**Delete Options:**
- **Soft Delete** - Deactivates restaurant (data retained)
- **Hard Delete** - Permanently removes all data

**Warning:** Hard delete cannot be undone and removes:
- All restaurant data
- All staff users
- All orders and history
- All menu items
- All payments

### Subscription Management

#### Plans Available
1. **Trial** - Free, 30 days, 10 tables, 5 users
2. **Basic** - 29,000 RWF/month, 20 tables, 10 users
3. **Premium** - 79,000 RWF/month, 50 tables, 20 users
4. **Enterprise** - Custom pricing, unlimited resources

#### Extend Subscription
**URL:** `?req=superadmin&action=extend_subscription`

**Process:**
1. Select restaurant
2. Choose extension period
3. Confirm extension
4. Subscription end date updated

---

## Restaurant Owner Features

### Access Your Restaurant
**URL:** `http://localhost/restaurant/?req=staff&action=login`

**Login Process:**
1. Enter email and password
2. System identifies your restaurant
3. Redirects to restaurant dashboard

### Restaurant Dashboard
Shows restaurant-specific data:
- Today's orders and revenue
- Active tables
- Staff on duty
- Menu items
- Recent orders

### Menu Management
**URL:** `?req=menu&action=manage`

**Features:**
- Create/edit/delete categories
- Add menu items with prices
- Upload item images
- Set item availability
- Manage descriptions

### Staff Management
**URL:** `?req=staff&action=manage`

**Features:**
- Add new staff users
- Assign roles (waiter, kitchen, cashier, manager)
- Reset passwords
- Activate/deactivate accounts
- View staff activity

### Order Management
**Features:**
- View all orders (pending, preparing, completed)
- Order details and history
- Revenue tracking
- Order analytics

### Reports
**Types:**
- Daily sales reports
- Menu item performance
- Staff performance
- Payment methods breakdown
- Revenue trends

---

## System Architecture

### Technology Stack
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (ES6)
- **Charts:** Chart.js
- **Icons:** Font Awesome 6

### MVC Structure
```
app/
├── controllers/          # Business logic
│   ├── superadmin.php   # Super admin controller
│   ├── staff.php        # Staff/restaurant controller
│   ├── menu.php         # Menu management
│   └── api.php          # API endpoints
├── models/              # Data layer
│   ├── Staff.php
│   ├── Menu.php
│   └── Order.php
├── views/               # Presentation layer
│   ├── superadmin/
│   │   ├── dashboard.php
│   │   └── restaurant_form.php
│   └── staff/
│       ├── dashboard.php
│       └── login.php
src/
├── restaurant.php       # Restaurant model
├── tenant_middleware.php # Data isolation
├── controller.php       # Base controller
├── model.php           # Base model
└── config.php          # Configuration
```

### Multi-Tenancy Flow
```
Request → Middleware → Identify Restaurant → Validate Subscription → 
Apply Filters → Execute Action → Return Data
```

---

## Database Structure

### Core Tables

#### `restaurants`
Main table for restaurant tenants
```sql
- id (PK)
- name
- slug (unique)
- email
- phone
- address
- subscription_plan (trial, basic, premium, enterprise)
- subscription_start
- subscription_end
- is_active
- max_tables
- max_users
- created_at
```

#### `staff_users`
All users (super admin + restaurant staff)
```sql
- id (PK)
- restaurant_id (FK, NULL for super admin)
- name
- email (unique)
- password_hash
- role (super_admin, admin, manager, waiter, kitchen, cashier)
- is_active
- created_at
```

#### Tenant-Specific Tables
All include `restaurant_id` column for data isolation:
- `restaurant_tables` - Table management
- `menu_categories` - Menu organization
- `menu_items` - Food/drink items
- `orders` - Order records
- `order_items` - Order line items
- `payments` - Payment records
- `cash_sessions` - Cash management
- `waiter_calls` - Approval requests
- `audit_trail` - Activity logs

---

## API Reference

### Super Admin Endpoints

#### List Restaurants
```
GET ?req=superadmin&action=list_restaurants&format=json
Response: {
  "status": "OK",
  "data": [...restaurants],
  "count": 4
}
```

#### Get Restaurant
```
GET ?req=superadmin&action=get_restaurant&id=1
Response: {
  "status": "OK",
  "data": {restaurant details, stats, settings}
}
```

#### Create Restaurant
```
POST ?req=superadmin&action=create_restaurant
Body: {
  "name": "New Restaurant",
  "email": "contact@restaurant.com",
  "slug": "new-restaurant",
  "subscription_plan": "basic",
  ...
}
Response: {
  "status": "OK",
  "message": "Restaurant created successfully",
  "restaurant_id": 5
}
```

#### Update Restaurant
```
POST ?req=superadmin&action=update_restaurant
Body: {
  "id": 1,
  "name": "Updated Name",
  ...
}
Response: {
  "status": "OK",
  "message": "Restaurant updated successfully"
}
```

#### Delete Restaurant
```
POST ?req=superadmin&action=delete_restaurant
Body: {
  "id": 1,
  "hard_delete": false
}
Response: {
  "status": "OK",
  "message": "Restaurant deactivated"
}
```

---

## Security & Access Control

### Authentication
- Session-based authentication
- Password hashing with `password_hash()`
- Role verification on every request

### Data Isolation
- Automatic `restaurant_id` filtering in queries
- Middleware enforcement
- Cross-tenant access prevention

### Permission Checks
```php
// Super admin check
if ($_SESSION['role'] !== 'super_admin') {
    // Deny access
}

// Restaurant owner check
if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    // Deny access
}
```

### Subscription Validation
- Check `subscription_end` date
- Verify `is_active` status
- Enforce resource limits (tables, users)

---

## Subscription Plans

### Trial (Free)
- **Duration:** 30 days
- **Cost:** Free
- **Tables:** 10
- **Users:** 5
- **Support:** Email only

### Basic
- **Cost:** 29,000 RWF/month
- **Tables:** 20
- **Users:** 10
- **Support:** Email + Chat

### Premium
- **Cost:** 79,000 RWF/month
- **Tables:** 50
- **Users:** 20
- **Support:** Priority (Email, Chat, Phone)
- **Features:** Advanced analytics, custom reports

### Enterprise
- **Cost:** Custom
- **Tables:** Unlimited
- **Users:** Unlimited
- **Support:** Dedicated account manager
- **Features:** All premium + API access, white-label

---

## How To Use The System

### For Super Admins

#### Initial Setup
1. Access super admin login at `?req=superadmin`
2. Login with super admin credentials
3. Dashboard loads with system overview

#### Adding a New Restaurant
1. Click "Add Restaurant" button
2. Fill in restaurant details:
   - Name, email, phone
   - Choose subscription plan
   - Set resource limits
3. Click "Create Restaurant"
4. Default admin account created automatically
5. Send login credentials to restaurant owner

#### Managing Existing Restaurants
1. View all restaurants in dashboard table
2. **View** - See detailed statistics
3. **Edit** - Modify details, plan, or limits
4. **Delete** - Deactivate or permanently remove

#### Monitoring Subscriptions
1. Check "Subscription End Date" column
2. Look for expiration warnings in activity feed
3. Use "Extend Subscription" to renew

### For Restaurant Owners

#### First Time Login
1. Receive credentials from super admin
2. Go to `?req=staff&action=login`
3. Enter email and password
4. Dashboard loads with your restaurant data

#### Setting Up Your Restaurant
1. **Add Menu Items**
   - Navigate to Menu Management
   - Create categories (Appetizers, Mains, Drinks)
   - Add items with prices and descriptions

2. **Add Staff Users**
   - Go to Staff Management
   - Click "Add User"
   - Enter name, email, and assign role
   - Send credentials to staff

3. **Configure Tables**
   - Set up table numbers
   - Define seating capacity
   - Assign table zones

#### Daily Operations
1. **Morning**
   - Check dashboard for overview
   - Start cash session
   - Verify staff on duty

2. **During Service**
   - Monitor incoming orders
   - Track table status
   - Handle waiter calls/approvals

3. **End of Day**
   - Close cash session
   - Generate daily report
   - Review performance

### For Waiters
1. Login to staff portal
2. View available tables
3. Take orders by selecting items
4. Send orders to kitchen
5. Process payments when ready

### For Kitchen Staff
1. Login to staff portal
2. View incoming orders
3. Mark items as "Preparing"
4. Mark as "Ready" when done
5. Communicate with waiters

### For Cashiers
1. Login to staff portal
2. Open cash session at start
3. Process payments from waiters
4. Handle refunds if needed
5. Close session at end of day
6. Balance cash drawer

---

## Troubleshooting

### Common Issues

#### Cannot Login as Super Admin
- Check database for super admin user
- Verify role is `super_admin`
- Check password hash is correct

#### Restaurant Not Loading Data
- Verify `restaurant_id` is set correctly
- Check subscription status
- Ensure `is_active = 1`

#### Permission Denied Errors
- Verify user role in database
- Check session is active
- Ensure user belongs to correct restaurant

#### Subscription Expired
- Super admin can extend subscription
- Check `subscription_end` date
- Verify payment status

---

## Support

### For Super Admins
- System administration guide
- Database management
- Backup and restore procedures
- Performance optimization

### For Restaurant Owners
- Feature tutorials
- Best practices
- Billing and subscriptions
- Staff training resources

### Contact
- **Technical Support:** support@restaurant-system.com
- **Sales:** sales@restaurant-system.com
- **Emergency:** +250 XXX XXX XXX

---

## Future Enhancements

### Planned Features
- [ ] Mobile app for waiters
- [ ] Customer-facing menu (QR code)
- [ ] Online ordering integration
- [ ] Payment gateway integration
- [ ] Loyalty program management
- [ ] Inventory management
- [ ] Recipe costing
- [ ] Staff scheduling
- [ ] Table reservations
- [ ] Multi-language support

---

## Version History

### Version 1.0 (Current)
- Multi-tenant architecture
- Super admin dashboard
- Restaurant CRUD
- Basic subscription management
- Role-based access control
- Menu and order management

---

## License & Credits

**System:** Restaurant Management Platform
**Version:** 1.0
**Last Updated:** 2024
**Documentation:** Complete

---

*This documentation covers all aspects of the system. For specific code examples, refer to the inline comments in the source files.*
