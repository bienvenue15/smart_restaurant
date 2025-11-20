# Super Admin System - Workflow & Process Guide

## Visual System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     RESTAURANT MANAGEMENT SYSTEM                 │
│                     Multi-Tenant Architecture                    │
└─────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
            ┌───────▼────────┐      ┌────────▼────────┐
            │  SUPER ADMIN   │      │  RESTAURANT     │
            │    PORTAL      │      │  STAFF PORTAL   │
            └───────┬────────┘      └────────┬────────┘
                    │                        │
        ┌───────────┼────────────┐          │
        │           │            │          │
    ┌───▼───┐  ┌───▼───┐  ┌────▼────┐     │
    │Create │  │ Edit  │  │ Monitor │     │
    │ Rest. │  │ Rest. │  │ System  │     │
    └───────┘  └───────┘  └─────────┘     │
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
              ┌─────▼──────┐      ┌───────▼────────┐     ┌──────▼──────┐
              │   ADMIN    │      │    MANAGER     │     │   WAITER    │
              │  (Owner)   │      │                │     │             │
              └─────┬──────┘      └───────┬────────┘     └──────┬──────┘
                    │                     │                     │
         ┌──────────┼──────────┐         │              ┌──────┼──────┐
         │          │          │         │              │             │
    ┌────▼───┐ ┌───▼────┐ ┌──▼───┐  ┌──▼────┐    ┌────▼────┐  ┌────▼────┐
    │  Menu  │ │ Staff  │ │Report│  │Approve│    │  Take   │  │ Process │
    │  Mgmt  │ │  Mgmt  │ │      │  │Orders │    │ Orders  │  │ Payment │
    └────────┘ └────────┘ └──────┘  └───────┘    └─────────┘  └─────────┘
```

## User Access Flow

### Super Admin Login Flow
```
START
  │
  ├─► Navigate to /?req=superadmin
  │
  ├─► Enter Credentials
  │     ├─ Email: superadmin@restaurant.com
  │     └─ Password: (from database)
  │
  ├─► System Validates
  │     ├─ Check staff_users table
  │     ├─ Verify role = 'super_admin'
  │     └─ Validate password hash
  │
  ├─► Create Session
  │     ├─ Set $_SESSION['user_id']
  │     ├─ Set $_SESSION['role'] = 'super_admin'
  │     └─ Set $_SESSION['email']
  │
  └─► Redirect to Dashboard
        └─ /?req=superadmin&action=dashboard
```

### Restaurant Staff Login Flow
```
START
  │
  ├─► Navigate to /?req=staff&action=login
  │
  ├─► Enter Credentials
  │     ├─ Email: user@restaurant.com
  │     └─ Password: (staff password)
  │
  ├─► System Validates
  │     ├─ Find user in staff_users
  │     ├─ Verify restaurant_id exists
  │     ├─ Check is_active = 1
  │     └─ Validate password hash
  │
  ├─► Load Restaurant Context
  │     ├─ Get restaurant details
  │     ├─ Check subscription status
  │     └─ Verify subscription not expired
  │
  ├─► Create Session
  │     ├─ Set $_SESSION['user_id']
  │     ├─ Set $_SESSION['restaurant_id']
  │     ├─ Set $_SESSION['role'] (admin/manager/waiter/etc)
  │     └─ Set restaurant context
  │
  └─► Redirect to Dashboard
        └─ /?req=staff&action=dashboard
```

## Data Flow: Creating a Restaurant

```
Super Admin Dashboard
        │
        ├─► Click "Add Restaurant"
        │
        ▼
    Restaurant Form (GET)
        │
        ├─► Fill Details:
        │     ├─ Name: "New Pizza Place"
        │     ├─ Email: "contact@newpizza.com"
        │     ├─ Slug: "new-pizza-place"
        │     ├─ Plan: "basic"
        │     ├─ Max Tables: 20
        │     └─ Max Users: 10
        │
        ├─► Click "Create Restaurant"
        │
        ▼
    Submit Form (POST)
        │
        ├─► JavaScript sends JSON:
        │     {
        │       "name": "New Pizza Place",
        │       "email": "contact@newpizza.com",
        │       "slug": "new-pizza-place",
        │       "subscription_plan": "basic",
        │       "max_tables": 20,
        │       "max_users": 10,
        │       "is_active": 1
        │     }
        │
        ▼
    Server Processing (superadmin.php)
        │
        ├─► Validate Required Fields
        │     ├─ Name ✓
        │     ├─ Email ✓
        │     └─ Slug ✓
        │
        ├─► Set Auto Values
        │     ├─ subscription_start = today
        │     ├─ subscription_end = +1 year
        │     └─ country = "Rwanda"
        │
        ├─► Insert into Database
        │     INSERT INTO restaurants (...)
        │     VALUES (...)
        │     ↓
        │     Get new restaurant_id = 5
        │
        ├─► Create Default Admin
        │     ├─ Generate random password
        │     ├─ Hash password
        │     └─ INSERT INTO staff_users:
        │           - restaurant_id = 5
        │           - role = 'admin'
        │           - email = "contact@newpizza.com"
        │
        └─► Return Success
              {
                "status": "OK",
                "message": "Restaurant created successfully",
                "restaurant_id": 5,
                "slug": "new-pizza-place"
              }
              │
              ▼
    Frontend Receives Response
        │
        ├─► Show Success Message
        │
        └─► Redirect to Dashboard (after 1.5s)
              └─ Shows new restaurant in list
```

## Multi-Tenancy: How Data Isolation Works

### Request Flow with Tenant Filtering

```
User Logged In
(restaurant_id = 3)
        │
        ├─► Request: Get All Orders
        │     URL: /?req=api&action=get_orders
        │
        ▼
Middleware Intercepts
        │
        ├─► Check Session
        │     ├─ $_SESSION['restaurant_id'] = 3
        │     └─ $_SESSION['role'] = 'admin'
        │
        ├─► Verify Subscription
        │     ├─ Get restaurant record
        │     ├─ Check is_active = 1 ✓
        │     └─ Check subscription_end > today ✓
        │
        ▼
Model Query (Model.php)
        │
        ├─► Auto-add restaurant_id filter
        │     Original Query:
        │       SELECT * FROM orders WHERE status = 'pending'
        │     
        │     Modified Query:
        │       SELECT * FROM orders 
        │       WHERE status = 'pending' 
        │       AND restaurant_id = 3  ← AUTOMATIC!
        │
        ▼
Database Execution
        │
        ├─► Returns only orders for restaurant_id = 3
        │     Result: 15 orders
        │
        └─► Other restaurants' data NOT visible
              Restaurant 1: 45 orders (hidden)
              Restaurant 2: 23 orders (hidden)
              Restaurant 3: 15 orders (returned) ✓
              Restaurant 4: 38 orders (hidden)
```

### Cross-Tenant Protection

```
Scenario: User tries to access another restaurant's data

User Session:
  restaurant_id = 3
  role = 'admin'

Malicious Request:
  /?req=api&action=get_order&id=999

Database Check:
  SELECT * FROM orders 
  WHERE id = 999
  AND restaurant_id = 3  ← Enforced by middleware
  
Result:
  Order 999 belongs to restaurant_id = 1
  
  WHERE restaurant_id = 3  ← No match!
  
  Returns: NULL (Access Denied)

✅ Data isolation maintained!
```

## Subscription Management Flow

### Check Subscription Status

```
Every Request
      │
      ├─► Middleware: checkSubscription()
      │
      ▼
Get Restaurant Record
      │
      ├─► Query:
      │     SELECT * FROM restaurants 
      │     WHERE id = {restaurant_id}
      │
      ▼
Validate Status
      │
      ├─► Check 1: is_active
      │     ├─ IF is_active = 0
      │     │   └─► DENY: "Restaurant suspended"
      │     └─ IF is_active = 1
      │         └─► Continue...
      │
      ├─► Check 2: subscription_end
      │     ├─ IF subscription_end < TODAY
      │     │   └─► DENY: "Subscription expired"
      │     └─ IF subscription_end >= TODAY
      │         └─► Continue...
      │
      └─► Check 3: Resource Limits
            ├─ Count current_tables
            ├─ IF current_tables >= max_tables
            │   └─► WARN: "Table limit reached"
            │
            ├─ Count current_users
            └─ IF current_users >= max_users
                └─► WARN: "User limit reached"
```

### Upgrade Subscription Flow

```
Restaurant Owner Requests Upgrade
        │
        ├─► Current Plan: Basic (29,000/mo)
        │   Target Plan: Premium (79,000/mo)
        │
        ▼
Super Admin Processes
        │
        ├─► Navigate to Edit Restaurant
        │
        ├─► Change subscription_plan
        │     FROM: "basic"
        │     TO: "premium"
        │
        ├─► Update Limits
        │     max_tables: 20 → 50
        │     max_users: 10 → 20
        │
        ├─► Extend subscription_end
        │     (if needed)
        │
        └─► Save Changes
              │
              ▼
        Immediate Effect
              │
              ├─► Restaurant can now:
              │     ├─ Add up to 50 tables
              │     ├─ Add up to 20 users
              │     └─ Access premium features
              │
              └─► Notification sent to owner
```

## Permission Matrix

```
┌──────────────┬────────────┬────────┬─────────┬────────┬─────────┬─────────┐
│ Permission   │ Super      │ Admin  │ Manager │ Waiter │ Kitchen │ Cashier │
│              │ Admin      │(Owner) │         │        │  Staff  │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ View All     │     ✅     │   ❌   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Restaurants  │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Create       │     ✅     │   ❌   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Restaurant   │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Edit Own     │     ✅     │   ✅   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Restaurant   │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Manage       │     ✅     │   ✅   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Staff Users  │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Edit Menu    │     ❌     │   ✅   │   ✅    │   ❌   │   ❌    │   ❌    │
│ Items        │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Take Orders  │     ❌     │   ✅   │   ✅    │   ✅   │   ❌    │   ❌    │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Update Order │     ❌     │   ✅   │   ✅    │   ❌   │   ✅    │   ❌    │
│ Status       │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Process      │     ❌     │   ✅   │   ✅    │   ✅   │   ❌    │   ✅    │
│ Payments     │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Manage Cash  │     ❌     │   ✅   │   ✅    │   ❌   │   ❌    │   ✅    │
│ Sessions     │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ View         │     ✅     │   ✅   │   ✅    │   ❌   │   ❌    │   ❌    │
│ Reports      │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ View System  │     ✅     │   ❌   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Analytics    │            │        │         │        │         │         │
├──────────────┼────────────┼────────┼─────────┼────────┼─────────┼─────────┤
│ Manage       │     ✅     │   ❌   │   ❌    │   ❌   │   ❌    │   ❌    │
│ Subscription │            │        │         │        │         │         │
└──────────────┴────────────┴────────┴─────────┴────────┴─────────┴─────────┘
```

## Typical Day: Super Admin Workflow

```
8:00 AM - Morning Check
  ├─► Login to super admin dashboard
  ├─► Review overnight alerts
  ├─► Check new restaurant registrations
  └─► Verify no critical errors

9:00 AM - Process New Restaurants
  ├─► Review pending registrations
  ├─► Approve/activate new restaurants
  ├─► Send welcome emails with credentials
  └─► Set up initial configuration

10:00 AM - Subscription Management
  ├─► Check expiring subscriptions (next 7 days)
  ├─► Send renewal reminders
  ├─► Process upgrade requests
  └─► Handle payment issues

12:00 PM - Monitoring & Support
  ├─► Review support tickets
  ├─► Respond to restaurant queries
  ├─► Check system performance
  └─► Monitor resource usage

2:00 PM - Analytics Review
  ├─► Generate weekly reports
  ├─► Analyze revenue trends
  ├─► Review restaurant growth
  └─► Identify problem areas

4:00 PM - Maintenance & Updates
  ├─► Apply system updates
  ├─► Review audit logs
  ├─► Check database health
  └─► Plan improvements

5:00 PM - End of Day
  ├─► Verify backup completed
  ├─► Review daily summary
  ├─► Schedule tomorrow's tasks
  └─► Log out
```

## Typical Day: Restaurant Owner Workflow

```
8:00 AM - Opening Procedures
  ├─► Login to staff portal
  ├─► Check dashboard overview
  ├─► Verify staff scheduled for today
  └─► Review today's reservations

9:00 AM - Preparation
  ├─► Open cash session
  ├─► Check menu item availability
  ├─► Update daily specials
  └─► Brief staff on today's goals

11:00 AM - Service Start
  ├─► Monitor incoming orders
  ├─► Approve waiter requests
  ├─► Handle customer issues
  └─► Check kitchen flow

2:00 PM - Midday Review
  ├─► Check lunch sales
  ├─► Review order times
  ├─► Adjust staffing if needed
  └─► Check inventory alerts

6:00 PM - Evening Service
  ├─► Monitor dinner rush
  ├─► Approve discount requests
  ├─► Handle payment issues
  └─► Ensure quality service

10:00 PM - Closing Procedures
  ├─► Close cash session
  ├─► Generate end-of-day report
  ├─► Review today's revenue
  ├─► Note any issues
  └─► Schedule tomorrow's prep

11:00 PM - Final Check
  ├─► Verify all orders completed
  ├─► Check all tables cleared
  ├─► Review staff performance
  └─► Log out
```

## Error Handling Flow

```
Error Occurs
      │
      ├─► Catch Exception
      │
      ▼
Determine Error Type
      │
      ├─► Database Error
      │     ├─ Log to error_log
      │     ├─ Return generic message
      │     └─ Notify super admin
      │
      ├─► Permission Error
      │     ├─ Check session
      │     ├─ Verify role
      │     └─ Return 403 Forbidden
      │
      ├─► Validation Error
      │     ├─ Identify field
      │     ├─ Return specific message
      │     └─ Show to user
      │
      └─► Subscription Error
            ├─ Check status
            ├─ Return expiry message
            └─ Suggest renewal
```

## Backup & Recovery Workflow

```
Daily Automated Backup
      │
      ├─► Schedule: 2:00 AM
      │
      ├─► Process:
      │     ├─ Stop non-critical services
      │     ├─ Dump database
      │     ├─ Compress files
      │     ├─ Upload to cloud storage
      │     └─ Verify backup integrity
      │
      └─► Result:
            ├─ Backup file: backup_YYYYMMDD.sql.gz
            ├─ Send success email
            └─ Resume services

Disaster Recovery
      │
      ├─► Identify issue
      │
      ├─► Retrieve latest backup
      │
      ├─► Restore database:
      │     mysql -u root -p restaurant_db < backup.sql
      │
      ├─► Verify data integrity
      │
      └─► Resume operations
```

---

## Key Takeaways

### 🔐 Security First
- All data isolated by restaurant_id
- Role-based permissions enforced
- Session validation on every request
- Password hashing with bcrypt

### 📊 Scalability
- Supports unlimited restaurants
- Efficient query filtering
- Resource limits per plan
- Subscription-based model

### 🎯 User Experience
- Clear role separation
- Intuitive dashboards
- Real-time updates
- Mobile-responsive design

### 🛡️ Data Protection
- Automatic tenant filtering
- Cross-tenant access prevention
- Regular backups
- Audit trail logging

---

**This workflow guide provides visual understanding of system processes and data flows.**

*Refer to SUPERADMIN_COMPLETE_GUIDE.md for detailed documentation.*
