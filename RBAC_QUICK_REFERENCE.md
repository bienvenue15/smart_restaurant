# RBAC Quick Reference Card
## Smart Restaurant - Inovasiyo Ltd

---

## 🚀 Quick Start

### 1. Protect a Controller Action
```php
require_once 'app/core/Permission.php';

public function myAction() {
    // Check permission (exits if denied)
    Permission::require('permission_code');
    
    // Your code here
}
```

### 2. Check Permission for UI
```php
<?php if (Permission::check('manage_orders')): ?>
    <button>Update Order</button>
<?php endif; ?>
```

### 3. Log Audit Trail
```php
Permission::logAudit(
    'action_type',    // e.g., 'refund_order'
    'table_name',     // e.g., 'orders'
    $recordId,        // e.g., 123
    $oldValue,        // e.g., 150.00
    $newValue,        // e.g., 0.00
    $reason          // e.g., 'Customer complaint'
);
```

---

## 📋 Permission Codes

### Orders
- `view_orders` - View order list
- `create_orders` - Create new orders
- `update_orders` - Change order status
- `cancel_orders` - Cancel orders
- `refund_orders` - Process refunds (HIGH RISK)

### Payments (HIGH RISK)
- `process_payment` - Accept payment
- `refund_payment` - Refund payment (requires approval)
- `view_payment` - View payment history
- `handle_cash` - Handle physical cash
- `verify_payment` - Verify payment (manager)

### Tables
- `view_tables` - View table status
- `manage_tables` - Update table status
- `reset_table` - Reset table to available

### Menu
- `view_menu` - View menu items
- `create_menu` - Add menu items
- `update_menu` - Edit menu items
- `delete_menu` - Delete menu items

### Staff
- `view_staff` - View staff list
- `create_staff` - Add staff members
- `update_staff` - Edit staff info
- `delete_staff` - Remove staff

### Reports
- `view_reports` - View basic reports
- `view_sales` - View sales reports
- `view_detailed_reports` - View detailed analytics

### System (CRITICAL)
- `system_settings` - Change system config
- `approve_actions` - Approve high-risk actions

---

## 👥 Role Permissions Summary

| Permission | Admin | Manager | Cashier | Waiter | Kitchen |
|-----------|-------|---------|---------|--------|---------|
| View Orders | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create Orders | ✅ | ✅ | ❌ | ✅ | ❌ |
| Update Orders | ✅ | ✅ | ✅ | ✅ | ✅ |
| Cancel Orders | ✅ | ✅ | ❌ | ❌ | ❌ |
| Refund Orders | ✅ | ✅ | ❌ | ❌ | ❌ |
| Process Payment | ✅ | ✅ | ✅ | ❌ | ❌ |
| Handle Cash | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Tables | ✅ | ✅ | ❌ | ✅ | ❌ |
| Reset Table | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manage Menu | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve Actions | ✅ | ✅ | ❌ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 🔒 Common Patterns

### Pattern 1: Simple Permission Check
```php
public function viewOrders() {
    Permission::require('view_orders');
    
    $orders = $this->orderModel->getAllOrders();
    // ... render view
}
```

### Pattern 2: Multiple Permission Options
```php
public function viewReports() {
    // Needs either permission
    Permission::requireAny(['view_reports', 'view_sales']);
    
    // ... show reports
}
```

### Pattern 3: Require Shift + Permission
```php
public function processCash() {
    Permission::requireShift();  // Must be clocked in
    Permission::require('handle_cash');
    
    // ... handle cash
}
```

### Pattern 4: Check Without Exiting
```php
public function dashboard() {
    $canManageStaff = Permission::check('manage_staff');
    $canViewReports = Permission::check('view_reports');
    
    // Pass to view for conditional rendering
    $this->render('dashboard', [
        'can_manage_staff' => $canManageStaff,
        'can_view_reports' => $canViewReports
    ]);
}
```

### Pattern 5: High-Risk with Approval
```php
public function refundOrder($orderId) {
    Permission::require('refund_orders');
    
    // Get old amount
    $order = $this->orderModel->getById($orderId);
    
    // Log for approval
    Permission::logAudit(
        'refund_order',
        'orders',
        $orderId,
        $order['total_amount'],
        0,
        'Customer complaint - food quality issue'
    );
    
    // Process refund...
}
```

---

## 🎯 Testing Quick Commands

### Test Login
```bash
# Open browser
http://localhost/restaurant/?req=staff

# Try each role:
admin/admin123     → Full access
manager/admin123   → Business operations
cashier/admin123   → Payment handling
waiter1/admin123   → Orders + tables
kitchen/admin123   → Kitchen orders only
```

### Test Permissions
```sql
-- Check staff permissions
SELECT p.name, p.code FROM permissions p
INNER JOIN role_permissions rp ON p.id = rp.permission_id
WHERE rp.role = 'waiter';

-- View audit trail
SELECT * FROM audit_trail ORDER BY created_at DESC LIMIT 10;

-- Check pending approvals
SELECT * FROM audit_trail WHERE status = 'pending';
```

### Test Clock In/Out
```javascript
// In browser console (after login)
fetch('/?req=staff&action=clock_in')
    .then(r => r.json())
    .then(console.log);
```

---

## ⚠️ Security Warnings

### ❌ DON'T DO THIS
```php
// BAD: No permission check
public function deleteOrder($id) {
    $this->orderModel->delete($id);
}

// BAD: Client-side only check
if (role === 'admin') {
    // Show delete button
}
```

### ✅ DO THIS
```php
// GOOD: Server-side permission check
public function deleteOrder($id) {
    Permission::require('delete_orders');
    
    // Get old data for audit
    $order = $this->orderModel->getById($id);
    
    // Delete
    $this->orderModel->delete($id);
    
    // Log
    Permission::logAudit('delete_order', 'orders', $id, 
                        json_encode($order), null, 'Admin deleted');
}

// GOOD: Server-side + client-side
<?php if (Permission::check('delete_orders')): ?>
    <button onclick="deleteOrder(<?= $id ?>)">Delete</button>
<?php endif; ?>
```

---

## 📱 Response Formats

### Success Response
```json
{
    "status": "OK",
    "message": "Action completed successfully",
    "data": { ... }
}
```

### Permission Denied
```json
{
    "status": "FAIL",
    "message": "You do not have permission to perform this action",
    "required_permission": "process_payment"
}
```

### Not Authenticated
```json
{
    "status": "FAIL",
    "message": "Authentication required"
}
```

### Not On Shift
```json
{
    "status": "FAIL",
    "message": "You must clock in before performing this action"
}
```

### Pending Approval
```json
{
    "status": "PENDING",
    "message": "Action sent for manager approval"
}
```

---

## 🔧 Troubleshooting

### Permission Always Returns False
1. Check session: `var_dump($_SESSION['staff_id']);`
2. Check role: `SELECT role FROM staff_users WHERE id = ?`
3. Check permission exists: `SELECT * FROM permissions WHERE code = ?`
4. Check assignment: `SELECT * FROM role_permissions WHERE role = ?`

### Audit Trail Not Logging
1. Check table exists: `DESCRIBE audit_trail;`
2. Check staff_id is valid: `SELECT * FROM staff_users WHERE id = ?`
3. Check for MySQL errors: `error_log()` output

### Permission Denied When Should Work
1. Verify you're logged in: `var_dump($_SESSION);`
2. Test with admin account first
3. Check permission spelling (exact match)
4. Verify role_permissions has the mapping

---

## 📞 Support

**RBAC Test Dashboard**: http://localhost/restaurant/rbac_test.html
**Complete Guide**: /restaurant/RBAC_GUIDE.md
**Implementation Summary**: /restaurant/RBAC_IMPLEMENTATION_SUMMARY.md

---

## 🎓 Remember

1. **Always check permissions server-side**
2. **Log all high-risk actions**
3. **Require approval for critical operations**
4. **Enforce shift requirements for cash**
5. **Test with multiple roles**

---

© 2025 Inovasiyo Ltd. All rights reserved.
