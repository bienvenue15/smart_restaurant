# UUID Migration - Code Update Guide

## Database Changes Completed ✅
- All critical tables now use UUID as primary key
- Integer `id` columns removed from main tables
- Foreign keys updated to use UUID columns

## Tables Using UUID Primary Keys:
1. restaurants
2. staff_users  
3. menu_categories
4. menu_items
5. restaurant_tables
6. orders
7. order_items
8. payments
9. waiter_calls
10. waiter_liabilities
11. cash_sessions
12. cash_transactions

## Column Mapping Reference:

### Old Column → New Column
```
restaurant_id → restaurant_uuid
staff_id → staff_uuid
order_id → order_uuid
table_id → table_uuid
menu_item_id → menu_item_uuid
category_id → category_uuid
waiter_id → waiter_uuid
confirmed_by → confirmed_by_uuid
served_by → served_by_uuid
paid_to → paid_to_uuid
created_by_staff → created_by_staff_uuid
received_by → received_by_uuid
acknowledged_by → acknowledged_by_uuid
completed_by → completed_by_uuid
cleared_by → cleared_by_uuid
approved_by → approved_by_uuid
session_id → session_uuid
cash_session_id → session_uuid
```

## Code Changes Required:

### 1. SQL Queries
**BEFORE:**
```php
$sql = "SELECT * FROM orders WHERE restaurant_id = ? AND id = ?";
$stmt->execute([$restaurantId, $orderId]);
```

**AFTER:**
```php
$sql = "SELECT * FROM orders WHERE restaurant_uuid = ? AND uuid = ?";
$stmt->execute([$restaurantUuid, $orderUuid]);
```

### 2. INSERT Statements
**BEFORE:**
```php
$sql = "INSERT INTO orders (restaurant_id, table_id, order_number, total_amount) 
        VALUES (?, ?, ?, ?)";
```

**AFTER:**
```php
$sql = "INSERT INTO orders (uuid, restaurant_uuid, table_uuid, order_number, total_amount) 
        VALUES (?, ?, ?, ?, ?)";
// Add UUID generation
$orderUuid = UUIDHelper::generate();
$stmt->execute([$orderUuid, $restaurantUuid, $tableUuid, $orderNumber, $total]);
```

### 3. Session Data
**BEFORE:**
```php
$_SESSION['staff_user'] = [
    'id' => $row['id'],
    'restaurant_id' => $row['restaurant_id']
];
```

**AFTER:**
```php
$_SESSION['staff_user'] = [
    'uuid' => $row['uuid'],
    'restaurant_uuid' => $row['restaurant_uuid']
];
```

### 4. JavaScript/API Calls
**BEFORE:**
```javascript
const RESTAURANT_ID = <?php echo $_SESSION['staff_user']['restaurant_id']; ?>;
fetch(`/api/orders?restaurant_id=${RESTAURANT_ID}&id=${orderId}`)
```

**AFTER:**
```javascript
const RESTAURANT_UUID = '<?php echo $_SESSION['staff_user']['restaurant_uuid']; ?>';
fetch(`/api/orders?restaurant_uuid=${RESTAURANT_UUID}&uuid=${orderUuid}`)
```

### 5. Foreign Key References
**BEFORE:**
```php
$sql = "SELECT o.*, t.table_number 
        FROM orders o 
        LEFT JOIN restaurant_tables t ON o.table_id = t.id 
        WHERE o.restaurant_id = ?";
```

**AFTER:**
```php
$sql = "SELECT o.*, t.table_number 
        FROM orders o 
        LEFT JOIN restaurant_tables t ON o.table_uuid = t.uuid 
        WHERE o.restaurant_uuid = ?";
```

## Files Requiring Updates:

### Critical Files (Must Update):
1. `/src/autoload.php` - Add UUIDHelper
2. `/app/controllers/api.php` - All API endpoints
3. `/app/controllers/staff.php` - Staff authentication
4. `/app/models/*.php` - All model files
5. `/app/views/staff/*.php` - Session references
6. `/app/views/menu.php` - QR code lookups

### JavaScript Files:
1. `/assets/js/app.js`
2. `/assets/js/customer-menu.js`
3. `/assets/js/staff-dashboard.js`

### Priority Order:
1. ✅ Database schema (DONE)
2. ⏳ Session management & authentication
3. ⏳ Core API endpoints
4. ⏳ Model classes
5. ⏳ View files
6. ⏳ JavaScript API calls

## Testing Checklist:
- [ ] Staff login works
- [ ] Menu display works  
- [ ] Order creation works
- [ ] Payment processing works
- [ ] Waiter calls work
- [ ] Reports generate correctly
- [ ] QR code scanning works
- [ ] Multi-restaurant isolation maintained

## Rollback Plan:
Backup created at: `db_backup_before_uuid_*.sql`

To rollback:
```bash
mysql -u root db_restaurant < db_backup_before_uuid_YYYYMMDD_HHMMSS.sql
```
