# Super Admin System - Quick Reference Card

## Super Admin Access
**URL:** `http://localhost/restaurant/?req=superadmin`
**Login:** superadmin@restaurant.com

## Main Dashboard Actions
| Action | URL | Description |
|--------|-----|-------------|
| **Dashboard** | `?req=superadmin&action=dashboard` | System overview |
| **Add Restaurant** | `?req=superadmin&action=create_restaurant` | Create new restaurant |
| **Edit Restaurant** | `?req=superadmin&action=edit_restaurant&id={id}` | Modify restaurant |
| **View Stats** | `?req=superadmin&action=get_restaurant&id={id}` | Detailed analytics |

## Restaurant Staff Access
**URL:** `http://localhost/restaurant/?req=staff&action=login`
**Access:** Each restaurant staff uses their own credentials

## User Roles Summary

### 🔐 Super Admin (System)
- ✅ Manage ALL restaurants
- ✅ View system-wide statistics
- ✅ Create/edit/delete restaurants
- ✅ Manage subscriptions
- ✅ Access audit logs
- ❌ Cannot directly manage individual restaurant operations

### 👔 Restaurant Admin (Restaurant Owner)
- ✅ Full control of THEIR restaurant
- ✅ Manage menu items
- ✅ Add/remove staff users
- ✅ View reports and analytics
- ✅ Manage tables and orders
- ❌ Cannot access other restaurants
- ❌ Cannot change subscription pricing

### 📊 Manager
- ✅ Daily operations management
- ✅ Approve waiter requests
- ✅ View all orders and reports
- ✅ Manage cash sessions
- ❌ Cannot add/remove staff
- ❌ Cannot change subscription

### 🍽️ Waiter
- ✅ Take orders
- ✅ Assign to tables
- ✅ Process basic payments
- ❌ Cannot edit menu
- ❌ Cannot view financial reports

### 👨‍🍳 Kitchen Staff
- ✅ View incoming orders
- ✅ Update order status
- ✅ Mark items ready
- ❌ Cannot take new orders
- ❌ Cannot view financial data

### 💰 Cashier
- ✅ Process payments
- ✅ Manage cash sessions
- ✅ Handle refunds
- ❌ Cannot edit menu
- ❌ Cannot manage users

## Subscription Plans Quick View

| Plan | Price | Tables | Users | Duration |
|------|-------|--------|-------|----------|
| **Trial** | Free | 10 | 5 | 30 days |
| **Basic** | 29,000 RWF/mo | 20 | 10 | Monthly |
| **Premium** | 79,000 RWF/mo | 50 | 20 | Monthly |
| **Enterprise** | Custom | ∞ | ∞ | Custom |

## Common Tasks

### Add New Restaurant (Super Admin)
1. Click "Add Restaurant" button
2. Enter: Name, Email, Slug
3. Choose subscription plan
4. Set limits (tables, users)
5. Click "Create Restaurant"
6. **Default admin created automatically**

### Edit Restaurant (Super Admin)
1. Find restaurant in table
2. Click "Edit" icon (pencil)
3. Modify fields
4. Click "Update Restaurant"

### Suspend Restaurant (Super Admin)
1. Click "Edit" on restaurant
2. Uncheck "Active Status"
3. Click "Update"
**Result:** Restaurant immediately loses access

### Extend Subscription (Super Admin)
1. Click "Edit" on restaurant
2. Change "Subscription End Date"
3. Or change plan to upgrade/downgrade
4. Click "Update Restaurant"

### Add Staff User (Restaurant Admin)
1. Login to staff portal
2. Go to Staff Management
3. Click "Add User"
4. Enter: Name, Email, Role
5. Click "Create User"
6. Send credentials to staff member

### Reset Staff Password (Restaurant Admin)
1. Go to Staff Management
2. Find user
3. Click "Reset Password"
4. New temporary password generated
5. Send to user

## Database Quick Reference

### Main Tables
- `restaurants` - All restaurant tenants
- `staff_users` - Super admin + all restaurant staff
- `restaurant_tables` - Tables (per restaurant)
- `menu_categories` - Menu categories (per restaurant)
- `menu_items` - Menu items (per restaurant)
- `orders` - Order records (per restaurant)
- `payments` - Payment history (per restaurant)

### Find Super Admin User
```sql
SELECT * FROM staff_users WHERE role = 'super_admin';
```

### Find Restaurant Users
```sql
SELECT * FROM staff_users WHERE restaurant_id = 1;
```

### Check Active Subscriptions
```sql
SELECT name, subscription_plan, subscription_end, is_active 
FROM restaurants 
WHERE subscription_end < DATE_ADD(NOW(), INTERVAL 7 DAY);
```

## Status Indicators

### Restaurant Status Badges
- 🟢 **Active** - Fully operational
- 🔴 **Inactive** - Suspended or expired

### Subscription Plan Badges
- 🟡 **Trial** - Free trial period
- 🔵 **Basic** - Standard features
- 🟦 **Premium** - Advanced features
- 🟣 **Enterprise** - Custom solutions

## API Endpoints (JSON)

### Get All Restaurants
```
GET ?req=superadmin&action=list_restaurants&format=json
```

### Get Single Restaurant
```
GET ?req=superadmin&action=get_restaurant&id=1
```

### Create Restaurant (POST JSON)
```
POST ?req=superadmin&action=create_restaurant
Body: {"name": "...", "email": "...", "slug": "...", "subscription_plan": "basic"}
```

### Update Restaurant (POST JSON)
```
POST ?req=superadmin&action=update_restaurant
Body: {"id": 1, "name": "...", "subscription_plan": "premium"}
```

### Delete Restaurant (POST JSON)
```
POST ?req=superadmin&action=delete_restaurant
Body: {"id": 1, "hard_delete": false}
```

## Security Checklist

### Super Admin Security
- ✅ Strong password (min 12 chars)
- ✅ Keep credentials secure
- ✅ Regular password rotation
- ✅ Monitor audit logs
- ✅ Review active sessions

### Restaurant Security
- ✅ Unique email per restaurant
- ✅ Staff password requirements
- ✅ Regular permission reviews
- ✅ Inactive user deactivation
- ✅ Subscription monitoring

## Troubleshooting Quick Fixes

### Problem: Cannot login as super admin
**Fix:** 
```sql
UPDATE staff_users 
SET role = 'super_admin' 
WHERE email = 'superadmin@restaurant.com';
```

### Problem: Restaurant can't access system
**Fix:** Check:
1. `is_active = 1` in restaurants table
2. `subscription_end` is future date
3. Staff user `is_active = 1`

### Problem: Permission denied errors
**Fix:** Verify:
1. User has correct role
2. User belongs to correct restaurant_id
3. Session is active

### Problem: Data not showing for restaurant
**Fix:** Verify:
1. `restaurant_id` column populated
2. User's `restaurant_id` matches data
3. Subscription is active

## File Locations

### Super Admin Files
```
app/controllers/superadmin.php           # Controller
app/views/superadmin/dashboard.php       # Main dashboard
app/views/superadmin/restaurant_form.php # Create/edit form
app/views/superadmin_login.php           # Login page
```

### Restaurant Files
```
src/restaurant.php                       # Restaurant model
src/tenant_middleware.php                # Data isolation
```

### Database
```
multi_tenancy.sql                        # Migration script
```

### Documentation
```
SUPERADMIN_COMPLETE_GUIDE.md            # Full documentation
SUPERADMIN_QUICK_REFERENCE.md           # This file
```

## Emergency Procedures

### Suspend Malicious Restaurant
```sql
UPDATE restaurants SET is_active = 0 WHERE id = {id};
```

### Extend Expired Subscription
```sql
UPDATE restaurants 
SET subscription_end = DATE_ADD(NOW(), INTERVAL 30 DAY) 
WHERE id = {id};
```

### Reset Super Admin Password
```sql
-- Generate hash for new password
UPDATE staff_users 
SET password_hash = '$2y$10$...' 
WHERE email = 'superadmin@restaurant.com';
```

### Backup Database
```bash
mysqldump -u root -p restaurant_db > backup_$(date +%Y%m%d).sql
```

## Support Contacts

### Technical Issues
- Check documentation first
- Review error logs: Check browser console
- Database logs: MySQL error log

### Common Questions
**Q: How many restaurants can I add?**
A: Unlimited. The system supports infinite restaurants.

**Q: Can restaurants share data?**
A: No. Data is completely isolated per restaurant.

**Q: Can I customize plans?**
A: Yes. Edit the restaurant and set custom limits.

**Q: How do I backup data?**
A: Use MySQL dump or backup tools. Super admin has access.

**Q: Can restaurants switch plans?**
A: Yes. Edit restaurant and change subscription_plan.

## Keyboard Shortcuts (Dashboard)

- `Ctrl + F` - Search restaurants
- `Esc` - Close modals
- `Enter` - Submit forms

## Best Practices

### Daily Tasks (Super Admin)
1. ✅ Check dashboard for alerts
2. ✅ Review new registrations
3. ✅ Monitor expiring subscriptions
4. ✅ Check support tickets

### Weekly Tasks (Super Admin)
1. ✅ Review system analytics
2. ✅ Verify backups completed
3. ✅ Check inactive restaurants
4. ✅ Review audit logs

### Monthly Tasks (Super Admin)
1. ✅ Billing and invoicing
2. ✅ Performance review
3. ✅ Security audit
4. ✅ Database optimization

---

## Quick Command Reference

### Start System
```
Access: http://localhost/restaurant/
Super Admin: http://localhost/restaurant/?req=superadmin
Restaurant Staff: http://localhost/restaurant/?req=staff&action=login
```

### Check System Status
```
Dashboard shows:
- Total restaurants
- Active count
- Revenue
- User count
```

---

**Keep this guide handy for quick reference during daily operations!**

*Last Updated: 2024*
