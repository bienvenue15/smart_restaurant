#!/bin/bash
#
# Smart Restaurant - Production Deployment Script
# This script automates the deployment process
#

echo "=========================================="
echo "  SMART RESTAURANT DEPLOYMENT"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DB_NAME="inovasiy_smartresto"
DB_USER="inovasiy_admin"
DB_PASS="shuwadilu@1234"
WEB_ROOT="/public_html"

echo -e "${YELLOW}Step 1: Creating directories...${NC}"
mkdir -p sessions
mkdir -p images/qrcodes
mkdir -p assets/images/menu
mkdir -p logs
echo -e "${GREEN}✓ Directories created${NC}"

echo ""
echo -e "${YELLOW}Step 2: Setting permissions...${NC}"
chmod 777 sessions
chmod 777 images/qrcodes
chmod 777 assets/images/menu
chmod 755 logs
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}Step 3: Checking database connection...${NC}"
mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME; SELECT 'Connected' as status;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    echo "Please check your database credentials"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 4: Importing database...${NC}"
if [ -f "db_restaurant (2).sql" ]; then
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < "db_restaurant (2).sql" 2>&1 | grep -i error
    if [ $? -ne 0 ]; then
        echo -e "${GREEN}✓ Database imported successfully${NC}"
    else
        echo -e "${RED}✗ Database import had errors${NC}"
    fi
else
    echo -e "${RED}✗ SQL file not found${NC}"
fi

echo ""
echo -e "${YELLOW}Step 5: Verifying tables...${NC}"
TABLES=$(mysql -u $DB_USER -p$DB_PASS -D $DB_NAME -e "SHOW TABLES;" 2>/dev/null | wc -l)
echo -e "${GREEN}✓ Found $(($TABLES - 1)) tables in database${NC}"

echo ""
echo -e "${YELLOW}Step 6: Checking critical files...${NC}"
FILES=("index.php" "src/config.php" "app/controllers/staff.php" "app/controllers/api.php")
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file missing${NC}"
    fi
done

echo ""
echo "=========================================="
echo -e "${GREEN}  DEPLOYMENT COMPLETE!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit: https://smartresto.inovasiyo.rw/verify_production.php"
echo "2. Test super admin: https://smartresto.inovasiyo.rw/?req=superadmin"
echo "3. Delete verify_production.php after testing"
echo ""
echo "System is live at: https://smartresto.inovasiyo.rw"
echo ""
