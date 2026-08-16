#!/bin/bash

# ============================================
# Smart Restaurant - Final Production Deploy
# ============================================

echo "🚀 PRODUCTION DEPLOYMENT SCRIPT"
echo "================================"
echo ""

# Step 1: Run production readiness check
echo "📋 Step 1: Running Production Readiness Check..."
php production_check.php

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Production check failed. Please fix errors before deploying."
    exit 1
fi

echo ""
echo "✅ Production check passed!"
echo ""

# Step 2: Create deployment package
echo "📦 Step 2: Creating Deployment Package..."
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DEPLOY_DIR="deploy_$TIMESTAMP"
mkdir -p "$DEPLOY_DIR"

# Copy files (excluding development files)
echo "   Copying core files..."
rsync -av --exclude='backups/' \
          --exclude='logs/' \
          --exclude='*.md' \
          --exclude='deploy_*.sh' \
          --exclude='deploy_*.ps1' \
          --exclude='production_check.php' \
          --exclude='test_*.php' \
          --exclude='check_qr.php' \
          --exclude='diagnostic.php' \
          --exclude='db_*.sql' \
          --exclude='.git/' \
          --exclude='node_modules/' \
          --exclude='.env' \
          --progress \
          ./ "$DEPLOY_DIR/"

echo "✅ Deployment package created: $DEPLOY_DIR"
echo ""

# Step 3: Create ZIP archive
echo "📦 Step 3: Creating ZIP Archive..."
zip -r "smartresto_production_$TIMESTAMP.zip" "$DEPLOY_DIR/" -x "*.git*" -x "*node_modules*"
echo "✅ Archive created: smartresto_production_$TIMESTAMP.zip"
echo ""

# Step 4: Deployment instructions
echo "================================================"
echo "📤 DEPLOYMENT INSTRUCTIONS"
echo "================================================"
echo ""
echo "1. Upload smartresto_production_$TIMESTAMP.zip to server"
echo "2. Extract in /public_html/ or /var/www/html/"
echo "3. Move contents from $DEPLOY_DIR/ to root directory"
echo "4. Import database: db_restaurant_complete.sql"
echo "5. Update src/config.php with production credentials"
echo "6. Set permissions:"
echo "   chmod 755 directories"
echo "   chmod 644 PHP files"
echo "   chmod 777 images/qrcodes"
echo "   chmod 777 assets/images/menu"
echo "7. Uncomment HTTPS redirect in .htaccess"
echo "8. Test: https://smartresto.inovasiyo.rw"
echo ""
echo "📖 Full Guide: PRODUCTION_DEPLOYMENT.md"
echo ""
echo "✅ Deployment package ready!"
echo "================================================"
