# ============================================
# Smart Restaurant - Production Deployment
# PowerShell Script for Windows
# ============================================

Write-Host "Starting Production Deployment..." -ForegroundColor Green
Write-Host "Target: https://smartresto.inovasiyo.rw" -ForegroundColor Cyan
Write-Host ""

# Step 1: Verify files
Write-Host "Verifying files..." -ForegroundColor Yellow
if (-Not (Test-Path "src\config.php")) {
    Write-Host "Error: src\config.php not found" -ForegroundColor Red
    exit 1
}

if (-Not (Test-Path "db_restaurant_complete.sql")) {
    Write-Host "Error: db_restaurant_complete.sql not found" -ForegroundColor Red
    exit 1
}

Write-Host "All required files present" -ForegroundColor Green
Write-Host ""

# Step 2: Configuration Check
Write-Host "Configuration Status:" -ForegroundColor Yellow
php -r "require 'src/config.php'; echo 'BASE_URL: ' . BASE_URL . PHP_EOL; echo 'MAIL_HOST: ' . MAIL_SMTP_HOST . PHP_EOL; echo 'MAIL_FROM: ' . MAIL_FROM_ADDRESS . PHP_EOL; echo 'MAIL_PORT: ' . MAIL_SMTP_PORT . PHP_EOL;"
Write-Host ""

# Step 3: Pre-Deployment Checklist
Write-Host "Pre-Deployment Checklist:" -ForegroundColor Cyan
Write-Host "   [ ] Backup existing database" -ForegroundColor White
Write-Host "   [ ] Backup existing files" -ForegroundColor White
Write-Host "   [ ] Verify SSL certificate is active" -ForegroundColor White
Write-Host "   [ ] Update database credentials in src\config.php" -ForegroundColor White
Write-Host "   [ ] Test email from production server" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to continue or Ctrl+C to abort"

# Step 4: Database Setup Instructions
Write-Host ""
Write-Host "Database Setup Instructions:" -ForegroundColor Yellow
Write-Host "   1. Login to cPanel or phpMyAdmin" -ForegroundColor White
Write-Host "   2. Create database: db_restaurant" -ForegroundColor White
Write-Host "   3. Create database user with privileges" -ForegroundColor White
Write-Host "   4. Import db_restaurant_complete.sql" -ForegroundColor White
Write-Host "   5. Note down: DB_USER and DB_PWD" -ForegroundColor White
Write-Host ""

# Step 5: File Upload Instructions
Write-Host "File Upload Instructions:" -ForegroundColor Yellow
Write-Host "   Option 1 - FTP/SFTP:" -ForegroundColor Cyan
Write-Host "      - Use FileZilla or WinSCP" -ForegroundColor White
Write-Host "      - Upload to: /public_html/" -ForegroundColor White
Write-Host "      - Maintain folder structure" -ForegroundColor White
Write-Host ""
Write-Host "   Option 2 - cPanel File Manager:" -ForegroundColor Cyan
Write-Host "      - Login to cPanel" -ForegroundColor White
Write-Host "      - Open File Manager" -ForegroundColor White
Write-Host "      - Navigate to public_html" -ForegroundColor White
Write-Host "      - Upload ZIP and extract" -ForegroundColor White
Write-Host ""

# Step 6: Configuration Update
Write-Host "Update Production Configuration:" -ForegroundColor Yellow
Write-Host "   Edit: src/config.php (lines 5-11)" -ForegroundColor White
Write-Host "   " -ForegroundColor White
Write-Host "   define(`"DB_HOST`", `"localhost`");" -ForegroundColor Gray
Write-Host "   define(`"DB_NAME`", `"db_restaurant`");" -ForegroundColor Gray
Write-Host "   define(`"DB_USER`", `"YOUR_DB_USER`");  ⬅ UPDATE THIS" -ForegroundColor Yellow
Write-Host "   define(`"DB_PWD`", `"YOUR_DB_PASSWORD`");  ⬅ UPDATE THIS" -ForegroundColor Yellow
Write-Host ""

# Step 7: File Permissions (if using SSH)
Write-Host "File Permissions (via SSH/Terminal):" -ForegroundColor Yellow
Write-Host "   chmod 755 /public_html/" -ForegroundColor Gray
Write-Host "   chmod 644 src/config.php" -ForegroundColor Gray
Write-Host "   chmod 777 images/qrcodes/ -R" -ForegroundColor Gray
Write-Host "   chmod 777 assets/images/menu/ -R" -ForegroundColor Gray
Write-Host ""

# Step 8: Testing Checklist
Write-Host "Post-Deployment Testing:" -ForegroundColor Yellow
Write-Host "   1. [OK] Visit: https://smartresto.inovasiyo.rw" -ForegroundColor White
Write-Host "   2. [OK] Test SuperAdmin Login:" -ForegroundColor White
Write-Host "      https://smartresto.inovasiyo.rw/?req=superadmin" -ForegroundColor Gray
Write-Host "   3. [OK] Create test restaurant:" -ForegroundColor White
Write-Host "      - Fill all required fields including TIN (9-10 digits)" -ForegroundColor Gray
Write-Host "      - Check email inbox for welcome email" -ForegroundColor Gray
Write-Host "   4. [OK] Test Staff Login:" -ForegroundColor White
Write-Host "      https://smartresto.inovasiyo.rw/staff" -ForegroundColor Gray
Write-Host "   5. [OK] Test Customer Menu:" -ForegroundColor White
Write-Host "      https://smartresto.inovasiyo.rw/?restaurant=test-restaurant" -ForegroundColor Gray
Write-Host "   6. [OK] Test QR Code Generation:" -ForegroundColor White
Write-Host "      - Generate QR codes for tables" -ForegroundColor Gray
Write-Host "      - Verify images/qrcodes/{slug}/ contains PNG files" -ForegroundColor Gray
Write-Host ""

# Step 9: Email Testing
Write-Host "Email Verification:" -ForegroundColor Yellow
Write-Host "   1. Create test restaurant" -ForegroundColor White
Write-Host "   2. Check email arrives at specified address" -ForegroundColor White
Write-Host "   3. Verify email contains:" -ForegroundColor White
Write-Host "      - Login URL (https://smartresto.inovasiyo.rw/staff)" -ForegroundColor Gray
Write-Host "      - Credentials (username, password)" -ForegroundColor Gray
Write-Host "      - Customer menu URL" -ForegroundColor Gray
Write-Host "   4. Check spam folder if not in inbox" -ForegroundColor White
Write-Host ""

# Step 10: Security Hardening
Write-Host "Security Hardening:" -ForegroundColor Red
Write-Host "   1. [WARNING] Remove test files:" -ForegroundColor White
Write-Host "      - Delete test_email_simple.php" -ForegroundColor Gray
Write-Host "      - Delete deploy_production.ps1" -ForegroundColor Gray
Write-Host "      - Delete deploy_production.sh" -ForegroundColor Gray
Write-Host "   2. [WARNING] Disable error display:" -ForegroundColor White
Write-Host "      In php.ini: display_errors = Off" -ForegroundColor Gray
Write-Host "   3. [WARNING] Force HTTPS:" -ForegroundColor White
Write-Host "      Add to .htaccess: RewriteCond %{HTTPS} off" -ForegroundColor Gray
Write-Host "   4. [WARNING] Change default passwords:" -ForegroundColor White
Write-Host "      - SuperAdmin password" -ForegroundColor Gray
Write-Host "      - Email password (if different)" -ForegroundColor Gray
Write-Host "   5. [WARNING] Review file permissions" -ForegroundColor White
Write-Host ""

# Step 11: Monitoring
Write-Host "Monitoring & Maintenance:" -ForegroundColor Yellow
Write-Host "   1. Monitor error logs:" -ForegroundColor White
Write-Host "      - PHP error log in cPanel" -ForegroundColor Gray
Write-Host "      - Application logs in logs/ folder" -ForegroundColor Gray
Write-Host "   2. Email deliverability:" -ForegroundColor White
Write-Host "      - Check SMTP logs" -ForegroundColor Gray
Write-Host "      - Monitor bounce rates" -ForegroundColor Gray
Write-Host "   3. SSL certificate:" -ForegroundColor White
Write-Host "      - Check expiry date" -ForegroundColor Gray
Write-Host "      - Setup auto-renewal" -ForegroundColor Gray
Write-Host "   4. Database backups:" -ForegroundColor White
Write-Host "      - Setup automated backups in cPanel" -ForegroundColor Gray
Write-Host "      - Test restore procedure" -ForegroundColor Gray
Write-Host ""

# Summary
Write-Host "Deployment Guide Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Documentation Files:" -ForegroundColor Cyan
Write-Host "   - PRODUCTION_CONFIG.md (detailed configuration)" -ForegroundColor White
Write-Host "   - IMPLEMENTATION_PLAN.md (feature documentation)" -ForegroundColor White
Write-Host "   - RESTAURANT_MANAGEMENT_FIXES.md (technical fixes)" -ForegroundColor White
Write-Host ""
Write-Host "Quick Links:" -ForegroundColor Cyan
Write-Host "   Production URL: https://smartresto.inovasiyo.rw" -ForegroundColor White
Write-Host "   SuperAdmin: https://smartresto.inovasiyo.rw/?req=superadmin" -ForegroundColor White
Write-Host "   Staff Login: https://smartresto.inovasiyo.rw/staff" -ForegroundColor White
Write-Host ""
Write-Host "Need help? Contact: info@inovasiyo.rw" -ForegroundColor Yellow
Write-Host ""

Read-Host "Press Enter to exit"
