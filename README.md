# 🍽️ Smart Restaurant System

A modern, QR-code based restaurant ordering system with real-time menu management, interactive ordering, and waiter call functionality.

## 📋 Features

### ✨ Core Features
- **Dynamic QR Code Menu** - Table-specific QR codes for instant menu access
- **Interactive Order Widget** - Real-time cart management with quantity controls
- **Waiter Call System** - Request assistance, orders, bills, or report issues
- **Real-time Menu Updates** - Dynamic availability and pricing
- **Contactless Experience** - Complete ordering from smartphones
- **XSS Protection** - Built-in security against cross-site scripting attacks

### 🎨 Design
- Purple/Blue gradient theme
- Fully responsive design
- FontAwesome icons
- Smooth animations and transitions
- Mobile-first approach

### 🔧 Technical Features
- Custom PHP MVC framework
- Vanilla JavaScript (no dependencies)
- RESTful API endpoints
- PDO with prepared statements
- GDPR & Rwanda Data Protection compliant

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/XAMPP/WAMP
- Modern web browser

### Setup Steps

1. **Clone/Copy the project to your web server**
   ```powershell
   # Already in c:\xampp\htdocs\restaurant
   ```

2. **Create the database**
   ```powershell
   # Start MySQL from XAMPP Control Panel
   # Then import the database
   cd c:\xampp\htdocs\restaurant
   mysql -u root -p < database.sql
   ```
   
   Or use phpMyAdmin:
   - Open http://localhost/phpmyadmin
   - Create database `db_restaurant`
   - Import `database.sql` file

3. **Configure database connection**
   
   Edit `src/config.php` if needed (default settings):
   ```php
   DB_HOST: localhost
   DB_NAME: db_restaurant
   DB_USER: root
   DB_PWD: (empty)
   ```

4. **Set correct permissions** (if on Linux/Mac)
   ```bash
   chmod -R 755 c:\xampp\htdocs\restaurant
   ```

5. **Access the application**
   ```
   Homepage: http://localhost/restaurant
   Menu Demo: http://localhost/restaurant/?req=menu&table=T001
   API Endpoint: http://localhost/restaurant/?req=api&action=get_menu
   ```

### Outgoing Email

1. Open `src/config.php`.
2. Update the mail constants (they default to `info@inovasiyo.rw`):
   ```php
   MAIL_FROM_ADDRESS
   MAIL_FROM_NAME
   MAIL_SUPPORT_ADDRESS
   MAIL_SMTP_HOST
   MAIL_SMTP_PORT
   MAIL_SMTP_USERNAME
   MAIL_SMTP_PASSWORD
   MAIL_SMTP_ENCRYPTION
   ```
3. If you are running locally without SMTP access, set `MAIL_DISABLE_DELIVERY` to `true`.
4. The application uses PHPMailer (already included) for signup confirmations and support-ticket communications.

## 📁 Project Structure

```
restaurant/
├── app/
│   ├── controllers/     # Request handlers
│   │   ├── index.php   # Home page controller
│   │   ├── menu.php    # Menu display controller
│   │   └── api.php     # API endpoints controller
│   ├── models/         # Database models
│   │   ├── Menu.php    # Menu operations
│   │   └── Order.php   # Order & table operations
│   └── views/          # HTML templates
│       ├── home.php    # Landing page
│       └── menu.php    # Interactive menu page
├── assets/
│   ├── css/
│   │   └── style.css   # Main stylesheet
│   └── js/
│       └── app.js      # JavaScript functionality
├── images/             # Image assets
├── src/                # Core framework
│   ├── autoload.php   # Autoloader
│   ├── config.php     # Configuration
│   ├── controller.php # Base controller
│   ├── model.php      # Base model
│   └── view.php       # View renderer
├── index.php          # Entry point
└── database.sql       # Database schema
```

## 🎯 Usage

### For Customers

1. **Scan QR Code** - Each table has a unique QR code
2. **Browse Menu** - View items organized by categories
3. **Add to Order** - Click items to add to cart
4. **Customize** - Adjust quantities and add special requests
5. **Place Order** - Submit order to kitchen
6. **Call Waiter** - Request assistance anytime

### For Restaurant Staff

The system includes several tables with QR codes:
- T001, T002, T003, T004, T005

Access menu for any table:
```
http://localhost/restaurant/?req=menu&table=T001
```

## 🔌 API Endpoints

### Get Menu
```
GET /?req=api&action=get_menu
Response: { status: "OK", data: [...categories with items...] }
```

### Get Table Info
```
GET /?req=api&action=get_table&table=T001
Response: { status: "OK", data: {...table info...} }
```

### Create Order
```
POST /?req=api&action=create_order
Body: {
  table_id: 1,
  items: [{id: 1, quantity: 2, price: 5.99}],
  special_instructions: "No onions"
}
Response: { status: "OK", data: {order_id, order_number, total_amount} }
```

### Call Waiter
```
POST /?req=api&action=call_waiter
Body: {
  table_id: 1,
  request_type: "assistance",
  message: "Need extra napkins",
  priority: "normal"
}
Response: { status: "OK", message: "..." }
```

### Search Menu
```
GET /?req=api&action=search_menu&q=vegan
Response: { status: "OK", data: [...matching items...] }
```

## 🛡️ Security Features

- **XSS Protection** - All inputs sanitized with `htmlspecialchars()`
- **SQL Injection Prevention** - PDO prepared statements
- **CSRF Protection** - Stateless API design
- **Input Validation** - Server-side validation on all inputs
- **Type Checking** - Strict type conversion and validation

## 🎨 Color Palette

```css
Primary Gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)
Primary Color: #6366f1 (Indigo)
Secondary Color: #8b5cf6 (Purple)
Accent Color: #a78bfa (Light Purple)
Success: #10b981 (Green)
Warning: #f59e0b (Amber)
Error: #ef4444 (Red)
```

## 📱 Responsive Breakpoints

- Desktop: 1024px and above
- Tablet: 768px - 1023px
- Mobile: Below 768px

## 🔧 Customization

### Add New Menu Items
Edit `database.sql` or insert via phpMyAdmin:
```sql
INSERT INTO menu_items (category_id, name, description, price, is_available, preparation_time) 
VALUES (2, 'New Dish', 'Description', 15.99, 1, 20);
```

### Change Colors
Edit `assets/css/style.css` CSS variables in `:root` selector

### Add New Features
- Controllers: `app/controllers/`
- Models: `app/models/`
- Views: `app/views/`

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running in XAMPP
- Verify credentials in `src/config.php`
- Ensure database `db_restaurant` exists

### 404 Error on Pages
- Check `.htaccess` exists with rewrite rules
- Enable `mod_rewrite` in Apache
- Verify BASE_URL in config

### CSS/JS Not Loading
- Check file paths in views
- Verify BASE_URL constant
- Clear browser cache

## 📞 Support

For issues or questions about the system:
- Check the code comments
- Review database schema
- Inspect browser console for JS errors
- Check Apache/PHP error logs

## 📄 License

This project is created for educational and commercial use.

## 🙏 Credits

- **FontAwesome** - Icons
- **PHP PDO** - Database abstraction
- **Custom MVC Framework** - Lightweight and fast

---

**Version:** 1.0.0  
**Last Updated:** November 10, 2025  
**PHP Version:** 7.4+  
**Database:** MySQL 5.7+
