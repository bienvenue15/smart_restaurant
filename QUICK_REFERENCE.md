# Multi-Tenancy Quick Reference Card

## 🚀 Quick Setup (Run Once)

```powershell
# In PowerShell from restaurant directory:
.\setup_multi_tenancy.ps1
```

OR manually:

```powershell
Get-Content multi_tenancy.sql | C:\xampp\mysql\bin\mysql.exe -u root
```

---

## 📝 Restaurant Registration

### URL:
```
http://localhost/restaurant/?req=register
```

### Process:
1. Fill restaurant info
2. Choose subscription plan
3. Create owner account
4. Get access URL + credentials

### Plans:
- **Trial**: Free, 30 days, 10 tables, 5 users
- **Basic**: 29,000 RWF/month, 20 tables, 10 users
- **Premium**: 79,000 RWF/month, 50 tables, 20 users ⭐
- **Enterprise**: Custom pricing, unlimited

---

## 🔐 Access Patterns

### Development (Path-based):
```
http://localhost/restaurant/pizza-palace/
http://localhost/restaurant/burger-house/
```

### Production (Subdomain-based):
```
https://pizza-palace.restaurant.com
https://burger-house.restaurant.com
```

---

## 👨‍💼 Super Admin Operations

### Endpoints:

**List all restaurants:**
```
GET /?req=superadmin&action=list_restaurants
```

**Create restaurant:**
```
POST /?req=superadmin&action=create_restaurant
Body: name, email, phone, plan
```

**Update restaurant:**
```
POST /?req=superadmin&action=update_restaurant
Body: id, [fields to update]
```

**Extend subscription:**
```
POST /?req=superadmin&action=extend_subscription
Body: id, months
```

**Get statistics:**
```
GET /?req=superadmin&action=restaurant_stats&id=X
```

### Create Super Admin:
```sql
UPDATE users SET email='superadmin@restaurant.com', role='super_admin' WHERE id=1;
```

---

## 🔒 Data Isolation - How It Works

### Automatic Filtering:
```php
// What you write:
$query = "SELECT * FROM orders WHERE status = 'pending'";

// What actually executes:
$query = "SELECT * FROM orders WHERE restaurant_id = 1 AND status = 'pending'";
```

### Auto-Add restaurant_id:
```php
// What you write:
$model->save('menu_items', ['name' => 'Pizza', 'price' => 5000]);

// What actually inserts:
INSERT INTO menu_items (restaurant_id, name, price) VALUES (1, 'Pizza', 5000);
```

---

## ✅ Enforcement Checklist

### Every Request:
- [x] Restaurant context initialized (`Restaurant::initialize()`)
- [x] Subscription validated
- [x] All queries filtered by restaurant_id
- [x] Resource limits checked

### Every INSERT:
- [x] restaurant_id auto-added via Model class
- [x] Plan limits enforced (tables, users)

### Every SELECT:
- [x] WHERE restaurant_id = ? automatically added
- [x] Cannot access other restaurants' data

### Every UPDATE/DELETE:
- [x] Scoped to current restaurant only
- [x] Ownership verified

---

## 🛠️ Developer Usage

### Get Current Restaurant:
```php
$restaurantId = Restaurant::getCurrentId();
$restaurant = Restaurant::getCurrent();
echo $restaurant['name'];
```

### Check Subscription:
```php
TenantMiddleware::requireActiveSubscription();
```

### Check Limits:
```php
if (TenantMiddleware::checkLimit('tables')) {
    // Can add table
}
```

### Verify Ownership:
```php
if (TenantMiddleware::verifyOwnership('orders', $orderId)) {
    // User can access this order
}
```

### Get Setting:
```php
$timeout = TenantMiddleware::getSetting('order_timeout_minutes', 30);
```

---

## 📊 Useful Queries

### List all restaurants:
```sql
SELECT * FROM restaurants WHERE is_active = 1;
```

### Restaurant statistics:
```sql
SELECT * FROM v_restaurant_stats;
```

### Today's revenue per restaurant:
```sql
SELECT r.name, SUM(o.total_amount) as revenue
FROM restaurants r
LEFT JOIN orders o ON r.id = o.restaurant_id
WHERE DATE(o.created_at) = CURDATE()
GROUP BY r.id;
```

### Expiring subscriptions (next 7 days):
```sql
SELECT name, email, subscription_end
FROM restaurants
WHERE subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
AND is_active = 1;
```

---

## 🚨 Troubleshooting

| Error | Solution |
|-------|----------|
| "Restaurant context not set" | Check URL format, verify slug exists |
| "Subscription expired" | Super admin extends subscription |
| "Table limit reached" | Upgrade plan or delete unused tables |
| "Permission denied" | Check user role and permissions |
| "Database error" | Run migration SQL script |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `multi_tenancy.sql` | Database migration |
| `src/restaurant.php` | Restaurant management class |
| `src/tenant_middleware.php` | Isolation enforcement |
| `src/model.php` | Auto-filtering base model |
| `app/controllers/register.php` | Registration controller |
| `app/controllers/superadmin.php` | Super admin API |
| `app/views/register.php` | Registration UI |

---

## 🔑 Important Constants

```php
// In src/config.php
DB_HOST: "localhost"
DB_NAME: "db_restaurant"
DB_USER: "root"
BASE_URL: "http://localhost/restaurant"
```

---

## 📞 Support

**Documentation:**
- `MULTI_TENANCY_GUIDE.md` - Complete guide
- `MULTI_TENANCY_ENFORCEMENT.md` - Technical details
- `UPDATE_FEATURES_GUIDE.md` - Feature updates

**Quick Test:**
1. Visit registration: `/?req=register`
2. Create test restaurant
3. Login with credentials
4. Verify data isolation

---

## ✨ Features Summary

✅ Complete data isolation
✅ Self-service registration
✅ Multiple subscription plans
✅ Resource limits enforcement
✅ Super admin management
✅ Automatic context detection
✅ Secure query filtering
✅ Audit trail per restaurant
✅ Custom branding support
✅ Subscription management

---

**System is now ready for production multi-restaurant deployment!** 🎉
