# Staff Roles Feature Verification

This document outlines all features available to each staff role and their verification status.

## Role Permissions Summary

### Admin Role (Restaurant Owner)
**Full Access to:**
- ✅ Menu Management (Create/Edit/Delete categories & items)
- ✅ Tables Management (Create/Edit/Delete tables, generate QR codes)
- ✅ Staff Management (Add/Edit/Delete staff, manage roles)
- ✅ Orders Management (View all orders, details, status updates)
- ✅ Reports & Analytics (Revenue, daily stats, top items)
- ✅ Restaurant Settings (Profile, subscription, QR regeneration)
- ✅ Dashboard Stats (Revenue, orders, active tables, pending orders)
- ✅ Clock In/Out
- ✅ View Orders
- ✅ View Tables
- ✅ View Menu
- ✅ Process Payments (if has cash permission)

**Access Level:** Full restaurant control

---

### Manager Role
**Access to:**
- ✅ Menu Management (Create/Edit/Delete categories & items)
- ✅ Tables Management (Create/Edit/Delete tables, generate QR codes)
- ✅ Orders Management (View all orders, details, status updates)
- ✅ Reports & Analytics (Revenue, daily stats, top items)
- ✅ Dashboard Stats
- ✅ Clock In/Out
- ✅ View Orders
- ✅ Create Orders
- ✅ Modify Orders
- ✅ View Tables
- ✅ Manage Tables
- ✅ View Menu
- ✅ Approve Actions (refunds, discounts)
- ✅ Process Payments

**Cannot Access:**
- ❌ Staff Management (Add/Remove staff)
- ❌ Restaurant Settings (Profile changes limited)

**Access Level:** Operational control (no staff management)

---

### Waiter Role
**Access to:**
- ✅ View Orders
- ✅ Create Orders
- ✅ Modify Orders (before kitchen starts)
- ✅ View Tables
- ✅ Manage Tables
- ✅ Reset Tables
- ✅ Reserve Tables
- ✅ View Menu
- ✅ Toggle Menu Item Availability
- ✅ Waiter Calls (Create/Assign/Complete)
- ✅ Dashboard (limited view)
- ✅ Clock In/Out

**Cannot Access:**
- ❌ Menu Management (Edit menu, prices)
- ❌ Tables Management (Create/Delete tables)
- ❌ Staff Management
- ❌ Orders Management (Full admin view)
- ❌ Reports & Analytics
- ❌ Restaurant Settings
- ❌ Process Payments (Limited - no refunds)

**Access Level:** Front-of-house operations

---

### Cashier Role
**Access to:**
- ✅ View Orders
- ✅ View All Orders
- ✅ Process Payments (Cash, Card, Mobile Money)
- ✅ Open/Close Cash Register
- ✅ View Payment History
- ✅ View Tables
- ✅ Reset Tables
- ✅ View Menu
- ✅ Dashboard (limited view)
- ✅ Clock In/Out

**Cannot Access:**
- ❌ Create Orders
- ❌ Modify Orders
- ❌ Menu Management
- ❌ Tables Management (Create/Delete)
- ❌ Staff Management
- ❌ Reports & Analytics (Financial details)
- ❌ Restaurant Settings
- ❌ Void Payments (Requires approval)
- ❌ Process Refunds (Requires approval)

**Access Level:** Payment processing focused

---

### Kitchen Role
**Access to:**
- ✅ View Orders (Kitchen display)
- ✅ Update Order Status (Preparing → Ready)
- ✅ Mark Items as Completed
- ✅ View Order Priorities
- ✅ Dashboard (kitchen view)
- ✅ Clock In/Out

**Cannot Access:**
- ❌ Create Orders
- ❌ Modify Orders
- ❌ Process Payments
- ❌ Manage Tables
- ❌ Menu Management
- ❌ Staff Management
- ❌ Reports & Analytics
- ❌ Restaurant Settings

**Access Level:** Kitchen operations only

---

## System Settings Enforcement

All roles are subject to system-wide settings:

1. **Session Timeout**: Auto-logout after configured inactivity
2. **Shift Requirement**: If enabled, must clock in before actions
3. **Business Hours**: Orders may be restricted outside business hours
4. **Max Pending Orders**: Limit on concurrent pending orders
5. **Minimum Order Amount**: Validation on order creation
6. **Table Limits**: Maximum tables per restaurant
7. **Table Auto-Release**: Automatic table release after timeout

---

## Feature Verification Checklist

### Admin Features
- [x] Menu Management Page (`?req=staff&action=menu`)
- [x] Tables Management Page (`?req=staff&action=tables`)
- [x] Staff Management Page (`?req=staff&action=staff_manage`)
- [x] Orders Management Page (`?req=staff&action=orders_manage`)
- [x] Reports Page (`?req=staff&action=reports`)
- [x] Settings Page (`?req=staff&action=settings`)
- [x] All API endpoints protected with `isAdmin()` check
- [x] QR Code generation and regeneration

### Manager Features
- [x] Menu Management (no staff management)
- [x] Tables Management
- [x] Orders Management
- [x] Reports
- [x] Approve Actions

### Waiter Features
- [x] Create Orders (via API)
- [x] View Orders (dashboard)
- [x] Manage Tables
- [x] View Menu
- [x] Waiter Calls

### Cashier Features
- [x] View Orders
- [x] Process Payments
- [x] Cash Management
- [x] Payment History

### Kitchen Features
- [x] Kitchen Display (orders)
- [x] Update Order Status
- [x] View Active Orders

---

## Testing Instructions

1. **Login as each role** via `?req=staff&action=login`
2. **Verify sidebar navigation** shows correct menu items
3. **Test each feature** for the role
4. **Verify permissions** - unauthorized actions should redirect or show error
5. **Check system settings enforcement** - test limits, timeouts, etc.

---

## Access Control Implementation

All admin features are protected by:
```php
$this->requireAdmin(); // Checks if role is 'admin' or 'manager'
```

API endpoints check:
```php
if (!$this->isAdmin()) {
    echo json_encode(['status' => 'FAIL', 'message' => 'Access denied']);
    exit;
}
```

Permissions are checked via:
```php
Permission::check('permission_code');
Permission::require('permission_code');
```

---

## Notes

- All roles can access the dashboard
- Role is stored in `$_SESSION['staff_role']`
- Restaurant ID is stored in `$_SESSION['staff_user']['restaurant_id']`
- System settings are enforced globally
- Shift requirement can be enabled/disabled in system settings

