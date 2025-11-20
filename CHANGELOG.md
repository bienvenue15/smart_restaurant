# Smart Restaurant System - Changelog

## Latest Update - Order Management & Demo Mode

### ✅ Completed Features

#### 1. Order Model Enhancements
**File:** `app/models/Order.php`

- ✅ **canCancelOrder($orderId)** - Checks if order can be cancelled within 1-minute window
  - Validates order status (must be 'pending')
  - Calculates seconds elapsed since order creation
  - Returns cancellation eligibility with time remaining
  
- ✅ **cancelOrder($orderId)** - Cancels an order if within time window
  - Enforces 1-minute cancellation policy
  - Updates order status to 'cancelled'
  - Returns success/failure response
  
- ✅ **getTableOrderHistory($tableId, $limit)** - Retrieves order history for table
  - Includes all order statuses (pending, completed, cancelled)
  - Adds cancellation metadata for pending orders
  - Returns time elapsed and remaining cancellation time

#### 2. Menu Controller - Demo Mode Support
**File:** `app/controllers/menu.php`

- ✅ **Demo Mode Access** - URL: `?req=menu&demo=1&table=T001`
  - Bypasses QR code validation for testing
  - Accepts table parameter (T001-T005)
  - Sets demo flag in session
  - Maintains same functionality as production mode
  
- ✅ **Production Mode** - URL: `?req=menu&qr=QR-T001-uuid`
  - Requires valid QR code
  - Enforces table locking
  - Validates session security
  - Shows error page for invalid access

- ✅ **Session Management**
  - Stores: table_id, table_number, qr_code, demo_mode, session_start
  - Prevents table hopping
  - Validates occupied tables

#### 3. API Controller - Session Security
**File:** `app/controllers/api.php`

- ✅ **Session Validation** - All order/waiter endpoints now check:
  - Session exists (except in demo mode)
  - Table ID matches session
  - Prevents unauthorized access
  
- ✅ **New Endpoints:**
  - `cancel_order` - POST with order_id
  - `get_order_history` - GET for current table
  
- ✅ **Enhanced Endpoints:**
  - `create_order` - Added session/table validation
  - `call_waiter` - Added session/table validation

#### 4. Frontend - Order History & Cancellation
**File:** `assets/js/app.js`

- ✅ **loadOrderHistory()** - Fetches order history from API
- ✅ **displayOrderHistory(orders)** - Renders order cards with:
  - Order number and status with color coding
  - Item count and total amount
  - Relative time display (e.g., "5 minutes ago")
  - Cancellation button with countdown timer
  
- ✅ **startCancellationCountdown(orderId, seconds)** - Live countdown
  - Updates every second
  - Disables button when time expires
  - Visual feedback with seconds remaining
  
- ✅ **cancelOrder(orderId)** - API call to cancel order
  - Confirmation dialog
  - Success notification
  - Auto-refresh order history
  
- ✅ **startOrderHistoryRefresh()** - Auto-refresh every 30 seconds

#### 5. UI Enhancements
**File:** `assets/css/style.css`

- ✅ **Order History Styles:**
  - Color-coded status badges (pending, confirmed, preparing, ready, served, completed, cancelled)
  - Hover effects on order cards
  - Countdown timer styling
  - Cancel button with red gradient
  - Responsive layout
  
- ✅ **Visual Hierarchy:**
  - Order history section in menu content area
  - Proper spacing and typography
  - Icon integration for better UX

**File:** `app/views/menu.php`
- ✅ Added `<div id="orderHistory"></div>` container

### 🔧 Technical Details

#### Database Tables Used:
- `restaurant_tables` - Table information and status
- `orders` - Order records with timestamps
- `order_items` - Individual items in orders
- `menu_items` - Menu catalog
- `menu_categories` - Category organization

#### Security Features:
- ✅ Session-based authentication
- ✅ Table ID validation on all order operations
- ✅ XSS protection with htmlspecialchars()
- ✅ SQL injection prevention with prepared statements
- ✅ Input sanitization on all user inputs

#### Time-Based Features:
- ✅ 1-minute cancellation window using MySQL TIMESTAMPDIFF()
- ✅ Real-time countdown timer in JavaScript
- ✅ Relative time display for order history
- ✅ Auto-refresh every 30 seconds

### 📋 Usage Instructions

#### For Testing (Demo Mode):
1. Open `http://localhost/restaurant/demo.html`
2. Click any table link (T001-T005)
3. Browse menu and add items to cart
4. Place an order
5. See order appear in history with cancel button
6. Cancel within 60 seconds if needed
7. Watch countdown timer update

**Demo URLs:**
- Table T001: `http://localhost/restaurant/?req=menu&demo=1&table=T001`
- Table T002: `http://localhost/restaurant/?req=menu&demo=1&table=T002`
- (etc.)

#### For Production (QR Code Mode):
1. Generate QR codes: `php generate_qrcodes.php`
2. Print QR codes for each table
3. Customers scan QR code with phone
4. Access menu via unique QR URL
5. Session locked to scanned table
6. Cannot access other tables without resetting

**Production URLs:**
- Format: `http://localhost/restaurant/?req=menu&qr=QR-T001-uuid`
- UUID is unique per table from database

### 🔄 API Endpoints

#### New Endpoints:
```
POST /api?action=cancel_order
Body: { "order_id": 123 }
Response: { "status": "OK", "message": "Order cancelled successfully" }

GET /api?action=get_order_history&limit=10
Response: { "status": "OK", "data": [ {...orders...} ] }
```

#### Updated Endpoints (Now with Session Validation):
```
POST /api?action=create_order
Body: { "table_id": 1, "items": [...], "special_instructions": "..." }
Validation: Checks session table_id matches request table_id

POST /api?action=call_waiter
Body: { "table_id": 1, "request_type": "assistance", "message": "...", "priority": "normal" }
Validation: Checks session table_id matches request table_id
```

### 📱 User Experience Flow

1. **Customer Scans QR Code** → Opens menu page
2. **Session Created** → Locked to table, stored in $_SESSION
3. **Browse Menu** → Search, filter, view items
4. **Add to Cart** → Items stored in JavaScript cart array
5. **Place Order** → POST to API, validated against session
6. **Order Confirmation** → Modal shows order number and total
7. **Order History** → Displays in dedicated section below menu
8. **Cancellation Window** → 60-second countdown timer shown
9. **Auto-Refresh** → Order status updates every 30 seconds
10. **Call Waiter** → Independent floating button (anytime)

### 🎨 Status Color Coding

- **Pending** → Yellow/Amber (⏱️ Awaiting confirmation)
- **Confirmed** → Blue (✓ Kitchen notified)
- **Preparing** → Blue (🔥 Being cooked)
- **Ready** → Green (🔔 Ready to serve)
- **Served** → Green (🍽️ Delivered to table)
- **Completed** → Dark Green (✅ Finished & paid)
- **Cancelled** → Red (❌ Cancelled by customer)

### 🔐 Security Considerations

#### Production Checklist:
- [ ] Remove `?demo=1` parameter access in production
- [ ] Generate unique QR codes for all tables
- [ ] Print and laminate QR codes
- [ ] Enable HTTPS for secure sessions
- [ ] Configure session timeout (default: 24 minutes)
- [ ] Review error messages (no sensitive data)
- [ ] Test XSS protection with malicious input
- [ ] Monitor SQL query performance
- [ ] Set up database backups

#### Session Security:
- Session data never exposed to client
- Table ID validated on every API call
- QR codes are unique and non-guessable (UUID)
- Occupied tables block new sessions
- Demo mode clearly flagged in session

### 🐛 Known Issues & Limitations

1. **Cancellation Timer Client-Side:** Countdown uses JavaScript, could be manipulated. Server validates time on actual cancellation.
2. **No Order Modification:** Cannot edit order after placement. Must cancel and reorder.
3. **Single Table Per Session:** Switching tables requires session reset.
4. **Auto-Refresh:** 30-second interval may miss rapid status changes.
5. **No Websockets:** Real-time updates require page refresh.

### 🚀 Future Enhancements

#### Planned Features:
- [ ] Admin dashboard for staff
- [ ] Table reset tracking (who reset which table)
- [ ] Waiter assignment per table
- [ ] Push notifications for order status
- [ ] Payment integration
- [ ] Order modification (change quantity/items)
- [ ] Multiple cuisines/languages
- [ ] Dietary filter (vegan, gluten-free, etc.)
- [ ] Item ratings and reviews
- [ ] Loyalty points system

### 📊 Testing Checklist

#### Demo Mode Tests:
- [x] Access via demo.html
- [x] Switch between tables
- [x] Place order successfully
- [x] View order in history
- [x] Cancel order within 60 seconds
- [x] Countdown timer accuracy
- [x] Auto-refresh after 30 seconds
- [x] Call waiter functionality
- [x] Search menu items
- [x] Add/remove cart items

#### Production Mode Tests:
- [x] Invalid QR code shows error
- [x] Valid QR code grants access
- [x] Session locks to table
- [x] Cannot access other table's QR
- [x] Occupied table shows error
- [x] Order associates with correct table
- [x] Session persists across page reloads

#### Security Tests:
- [x] XSS attempts sanitized
- [x] SQL injection blocked (prepared statements)
- [x] Table ID tampering rejected
- [x] Session hijacking prevented
- [x] CSRF protection (same-origin)

### 📝 Code Quality

- ✅ All PHP files have no syntax errors
- ✅ JavaScript follows vanilla JS best practices
- ✅ CSS uses BEM-like naming conventions
- ✅ Consistent code formatting
- ✅ Comments for complex logic
- ✅ Error handling on all API calls
- ✅ User feedback with notifications
- ✅ Responsive design for mobile

### 📞 Support

For issues or questions:
1. Check this changelog for recent updates
2. Review error logs in browser console
3. Check PHP error logs in XAMPP
4. Verify database connection in `src/config.php`
5. Ensure QR codes exist in `/images/qrcodes/`

---

**Last Updated:** November 10, 2025  
**Version:** 1.1.0  
**Status:** ✅ Fully Functional with Demo Mode
