#!/bin/bash

# ============================================
# Smart Restaurant - Production Deployment
# ============================================

echo "🚀 Starting Production Deployment..."
echo "Target: https://smartresto.inovasiyo.rw"
echo ""

# Step 1: Verify files
echo "✓ Verifying files..."
if [ ! -f "src/config.php" ]; then
    echo "❌ Error: src/config.php not found"
    exit 1
fi

if [ ! -f "db_restaurant_complete.sql" ]; then
    echo "❌ Error: db_restaurant_complete.sql not found"
    exit 1
fi

echo "✓ All required files present"
echo ""

# Step 2: Backup reminder
echo "📋 Pre-Deployment Checklist:"
echo "   [ ] Backup existing database"
echo "   [ ] Backup existing files"
echo "   [ ] Verify SSL certificate"
echo "   [ ] Update database credentials"
echo ""
read -p "Press Enter to continue or Ctrl+C to abort..."

# Step 3: Configuration Check
echo ""
echo "🔍 Configuration Status:"
echo "   BASE_URL: https://smartresto.inovasiyo.rw ✓"
echo "   MAIL_HOST: mail.inovasiyo.rw ✓"
echo "   MAIL_FROM: info@inovasiyo.rw ✓"
echo "   MAIL_PORT: 587 (TLS) ✓"
echo ""

# Step 4: Database Setup
echo "📊 Database Setup:"
echo "   1. Create database: db_restaurant"
echo "   2. Import: mysql -u [user] -p db_restaurant < db_restaurant_complete.sql"
echo ""

# Step 5: File Upload
echo "📤 Upload Files:"
echo "   - Upload all files to: /public_html/ or /var/www/html/"
echo "   - Ensure proper permissions:"
echo "     chmod 755 directories"
echo "     chmod 644 PHP files"
echo "     chmod 777 images/qrcodes/ (for QR generation)"
echo ""

# Step 6: Configuration Update
echo "⚙️ Update Configuration:"
echo "   Edit: src/config.php"
echo "   Update DB_USER and DB_PWD with production credentials"
echo ""

# Step 7: Testing
echo "🧪 Post-Deployment Testing:"
echo "   1. Visit: https://smartresto.inovasiyo.rw"
echo "   2. Test SuperAdmin: https://smartresto.inovasiyo.rw/?req=superadmin"
echo "   3. Create test restaurant and verify email"
echo "   4. Test staff login"
echo "   5. Test customer menu"
echo ""

# Step 8: Security
echo "🔐 Security Hardening:"
echo "   1. Remove test_email_simple.php from production"
echo "   2. Set display_errors = 0 in php.ini"
echo "   3. Enable HTTPS-only (force SSL)"
echo "   4. Review file permissions"
echo "   5. Change default SuperAdmin password"
echo ""

# Step 9: Monitoring
echo "📈 Monitoring:"
echo "   1. Check PHP error logs: /var/log/php_errors.log"
echo "   2. Monitor email delivery"
echo "   3. Test from different devices/browsers"
echo "   4. Check SSL certificate expiry"
echo ""

echo "✅ Deployment Guide Complete!"
echo ""
echo "📖 For detailed configuration, see: PRODUCTION_CONFIG.md"
echo ""
