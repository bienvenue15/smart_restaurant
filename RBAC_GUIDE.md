# RBAC Implementation Complete Guide
## Smart Restaurant - Inovasiyo Ltd

### Overview
This document provides a complete guide to the Role-Based Access Control (RBAC) system implemented for fraud prevention and business security.

---

## 1. System Architecture

### Security Layers
1. **Authentication**: Username/password with bcrypt hashing
2. **Authorization**: Permission-based access control
3. **Audit Trail**: Complete logging of sensitive actions
4. **Shift Management**: Time-based access control
5. **Approval Workflow**: High-risk actions require manager approval
6. **Cash Tracking**: Full accountability for money handling

---

## 2. Database Schema

### New Tables Created
```sql
-- 1. permissions: 35 granular permissions
-- 2. role_permissions: Permission assignments to roles
-- 3. staff_shifts: Clock in/out tracking
-- 4. audit_trail: Fraud investigation logging
-- 5. payments: Payment tracking with verification
-- 6. cash_sessions: Cash register reconciliation
-- 7. order_adjustments: Discounts/refunds requiring approval
```

### Permission Categories
- **Orders**: view, create, update, cancel, refund
- **Payments**: process, refund, view, handle_cash
- **Tables**: view, manage, reset
- **Menu**: view, create, update, delete
- **Staff**: view, create, update, delete
- **Reports**: view_sales, view_detailed
- **System**: settings, approve_actions

---

## 3. Role Definitions

### Admin (35 permissions - Full Access)
- All system permissions
- Can approve all actions
- Can handle cash and verify payments
- Unlimited discount authority
- Security Level: admin

### Manager (29 permissions - Business Operations)
- Cannot change system settings
- Can approve refunds/discounts
- Can handle cash up to 10% discounts
- Can manage staff schedules
- Security Level: elevated

### Cashier (8 permissions - Payment Focused)
- Process payments
- Handle cash (5% discount limit)
- Cannot refund without approval
- View orders and tables
- Security Level: standard

### Waiter (7 permissions - Order & Tables)
- View/create/update orders
- Manage tables
- Call for assistance
- **CANNOT handle cash or payments**
- Security Level: standard

### Kitchen (3 permissions - Order Fulfillment Only)
- View and update order status
- **CANNOT access tables, payments, or menu**
- Security Level: standard

---

## 4. Permission Checking Implementation

### Method 1: Using Permission Helper Class
```php
// In any controller action
require_once 'app/core/Permission.php';

// Require specific permission (exits with JSON if unauthorized)
Permission::require('manage_orders');

// Require any of multiple permissions
Permission::requireAny(['manage_orders', 'view_orders']);

// Check permission without exiting (returns boolean)
if (Permission::check('process_payment')) {
    // Show payment button
}

// Require active shift
Permission::requireShift();
```

### Method 2: Direct Staff Model Usage
```php
$staffModel = new Staff();

// Check if staff has permission
$canRefund = $staffModel->hasPermission($staffId, 'refund_order');

// Get all staff permissions
$result = $staffModel->getStaffPermissions($staffId);
$permissions = $result['data'];

// Check if on shift
$onShift = $staffModel->isOnShift($staffId);
```

---

## 5. Audit Trail Implementation

### Logging Actions
```php
// Using Permission helper
Permission::logAudit(
    'refund_order',           // Action type
    'orders',                 // Table name
    $orderId,                 // Record ID
    $originalAmount,          // Old value
    $refundAmount,            // New value
    'Customer complaint'      // Reason
);

// Using Staff model
$staffModel->logAudit(
    $staffId,
    'cancel_order',
    'orders',
    $orderId,
    'confirmed',
    'cancelled',
    'Wrong order placed',
    false  // Requires approval
);
```

### Viewing Audit Trail
```php
// In controller
$result = $staffModel->getPendingApprovals();
$approvals = $result['data'];

// Query audit_trail directly for investigation
$query = "SELECT a.*, s.full_name, s.role 
          FROM audit_trail a
          INNER JOIN staff_users s ON a.staff_id = s.id
          WHERE a.staff_id = ? 
          OR a.action_type = 'refund_order'
          ORDER BY a.created_at DESC";
```

---

## 6. Shift Management

### Clock In/Out
```php
// Clock in
$result = $staffModel->clockIn($staffId);

// Clock out
$result = $staffModel->clockOut($staffId);

// Check if on shift
$isOnShift = $staffModel->isOnShift($staffId);

// In controller action - require active shift
Permission::requireShift();
```

### Preventing After-Hours Access
```php
// Before sensitive operations
if (!$staffModel->isOnShift($staffId)) {
    return ['status' => 'FAIL', 'message' => 'Must be clocked in'];
}
```

---

## 7. Approval Workflow

### Requesting Approval (High-Risk Actions)
```php
// Check if action requires approval
$requiresApproval = $staffModel->requiresApproval('refund_order');

if ($requiresApproval) {
    // Log for approval instead of executing
    $result = $staffModel->requestApproval(
        $staffId,
        'refund_order',
        'orders',
        $orderId,
        'Customer dissatisfied with meal quality'
    );
    
    return ['status' => 'PENDING', 'message' => 'Refund request sent for approval'];
}
```

### Approving/Rejecting Actions
```php
// Manager/Admin approves
$result = $staffModel->approveAction($auditId, $approverId);

// Or reject
$query = "UPDATE audit_trail 
          SET status = 'rejected', approved_by = ?, approved_at = NOW() 
          WHERE id = ?";
```

### View Pending Approvals Page
Navigate to: `/?req=staff&action=pending_approvals`

---

## 8. Payment Security

### Payment Processing
```php
// Check permission
Permission::require('process_payment');

// Record payment
$paymentData = [
    'order_id' => $orderId,
    'payment_method' => 'cash',
    'amount' => $amount,
    'received_by' => $_SESSION['staff_id'],
    'status' => 'pending'
];
$this->save('payments', $paymentData);

// Verify payment (by manager/admin)
Permission::require('verify_payment');
$query = "UPDATE payments 
          SET verified_by = ?, verified_at = NOW(), status = 'verified' 
          WHERE id = ?";
```

### Cash Session Management
```php
// Open cash register
$sessionData = [
    'staff_id' => $staffId,
    'opening_balance' => 500.00,
    'status' => 'open'
];
$this->save('cash_sessions', $sessionData);

// Close cash register
$expected = 500.00 + $totalSales;
$actual = 1250.00;
$variance = $actual - $expected;

$query = "UPDATE cash_sessions 
          SET closing_balance = ?, 
              expected_amount = ?,
              variance = ?,
              closed_at = NOW(),
              status = 'closed'
          WHERE id = ? AND status = 'open'";
```

---

## 9. Order Adjustments

### Requesting Discounts/Refunds
```php
// Check max discount allowed
$query = "SELECT max_discount_percent FROM staff_users WHERE id = ?";
$stmt = $this->db->prepare($query);
$stmt->execute([$staffId]);
$maxDiscount = $stmt->fetchColumn();

if ($discountPercent > $maxDiscount) {
    // Request approval
    $adjustmentData = [
        'order_id' => $orderId,
        'adjustment_type' => 'discount',
        'amount' => $discountAmount,
        'reason' => 'Birthday celebration',
        'requested_by' => $staffId,
        'status' => 'pending'
    ];
    $this->save('order_adjustments', $adjustmentData);
    
    // Also log in audit trail
    Permission::logAudit('request_discount', 'orders', $orderId, 
                        null, $discountAmount, 'Exceeds authority');
}
```

---

## 10. Frontend Integration

### Permission-Based UI
```php
<!-- In dashboard.php -->
<?php require_once 'app/core/Permission.php'; ?>

<!-- Show only if has permission -->
<?php if (Permission::check('manage_orders')): ?>
    <button onclick="updateOrderStatus()">Update Status</button>
<?php endif; ?>

<!-- Role-based menu items -->
<?php if (in_array($_SESSION['staff_role'], ['admin', 'manager'])): ?>
    <a href="/?req=staff&action=reports">Reports</a>
<?php endif; ?>

<!-- Cash handling button -->
<?php if (Permission::check('handle_cash')): ?>
    <button onclick="openCashRegister()">Open Register</button>
<?php endif; ?>
```

### JavaScript API Calls
```javascript
// Update order status (permission checked server-side)
function updateOrderStatus(orderId, newStatus) {
    fetch('<?php echo BASE_URL; ?>/?req=staff&action=update_order_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'FAIL') {
            if (data.required_permission) {
                alert('Permission denied: ' + data.message);
            } else {
                alert(data.message);
            }
        }
    });
}

// Clock in/out
function clockIn() {
    fetch('<?php echo BASE_URL; ?>/?req=staff&action=clock_in', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'OK') {
            location.reload();
        }
    });
}
```

---

## 11. Security Best Practices

### Preventing Fraud
1. **Always log high-risk actions**: refunds, discounts, cancellations
2. **Require approval for amounts above threshold**
3. **Verify payments with second staff member**
4. **Track cash sessions for discrepancy detection**
5. **Prevent actions outside shift hours**
6. **Store IP address and user agent in audit trail**

### Code Examples
```php
// GOOD: Permission check + audit trail
Permission::require('refund_order');
Permission::logAudit('refund', 'orders', $orderId, $total, 0, 'Reason');
$this->processRefund($orderId);

// BAD: No permission check
$this->processRefund($orderId);

// GOOD: Check shift status for cash handling
Permission::requireShift();
Permission::require('handle_cash');
$this->processCashPayment($amount);

// BAD: No shift validation
Permission::require('handle_cash');
$this->processCashPayment($amount);
```

---

## 12. Testing Credentials

### Test Accounts
```
Username: admin      | Password: admin123 | Role: admin
Username: manager    | Password: admin123 | Role: manager
Username: waiter1    | Password: admin123 | Role: waiter
Username: waiter2    | Password: admin123 | Role: waiter
Username: kitchen    | Password: admin123 | Role: kitchen
Username: cashier    | Password: admin123 | Role: cashier
```

### Testing Permissions
```php
// Test as waiter (should FAIL)
// Login as waiter1, try to access payments
Permission::require('process_payment');  // Returns permission denied

// Test as admin (should PASS)
// Login as admin, access anything
Permission::require('process_payment');  // Success

// Test shift requirement
// Without clocking in, try cash handling
Permission::requireShift();  // Must clock in first
```

---

## 13. API Endpoints with RBAC

### Protected Endpoints
```
GET  /?req=staff&action=clock_in          [Requires: authenticated]
GET  /?req=staff&action=clock_out         [Requires: authenticated]
POST /?req=staff&action=update_order_status [Requires: manage_orders]
POST /?req=staff&action=assign_waiter_call  [Requires: manage_tables]
POST /?req=staff&action=reset_table         [Requires: reset_table]
GET  /?req=staff&action=pending_approvals   [Requires: approve_actions]
POST /?req=staff&action=approve_action      [Requires: approve_actions]
```

### Error Responses
```json
// Permission denied
{
    "status": "FAIL",
    "message": "You do not have permission to perform this action",
    "required_permission": "manage_orders"
}

// Not authenticated
{
    "status": "FAIL",
    "message": "Authentication required"
}

// Not on shift
{
    "status": "FAIL",
    "message": "You must clock in before performing this action"
}
```

---

## 14. Troubleshooting

### Common Issues

**Issue**: Permission always returns false
```php
// Check session data
var_dump($_SESSION['staff_id']);
var_dump($_SESSION['staff_role']);

// Verify permission exists
$query = "SELECT * FROM permissions WHERE code = ?";
```

**Issue**: Audit trail not logging
```php
// Check audit_trail table exists
DESCRIBE audit_trail;

// Check foreign key constraints
SELECT * FROM staff_users WHERE id = ?;
```

**Issue**: Approval workflow not showing
```php
// Verify requires_approval flag in permissions
SELECT * FROM permissions WHERE requires_approval = 1;

// Check pending approvals query
SELECT * FROM audit_trail WHERE status = 'pending';
```

---

## 15. Future Enhancements

### Planned Features
1. **Real-time notifications** for approval requests
2. **Anomaly detection** (unusual refund patterns, excessive discounts)
3. **Biometric authentication** for high-risk actions
4. **Geofencing** (require on-premise for cash handling)
5. **Camera integration** (photo capture during refunds)
6. **SMS alerts** for critical actions
7. **Automated reports** (daily discrepancy summaries)

### Database Optimization
```sql
-- Add indexes for performance
CREATE INDEX idx_audit_staff ON audit_trail(staff_id);
CREATE INDEX idx_audit_status ON audit_trail(status);
CREATE INDEX idx_audit_created ON audit_trail(created_at);
CREATE INDEX idx_shifts_staff_date ON staff_shifts(staff_id, shift_date);
CREATE INDEX idx_payments_order ON payments(order_id);
```

---

## 16. Compliance & Legal

### Data Retention
- Audit trail: 7 years (tax compliance)
- Payment records: 7 years
- Staff activity logs: 2 years
- Cash session records: 5 years

### GDPR Considerations
- Staff has right to view their audit history
- Anonymize records after retention period
- Secure password hashing (bcrypt)
- Log only business-necessary data

---

## Copyright
© 2025 Inovasiyo Ltd. All rights reserved.

---

## Support
For technical support or questions about RBAC implementation:
- Review this guide
- Check audit trail for permission errors
- Test with admin account first
- Verify database schema integrity
