# Smart Restaurant - Production Deployment PowerShell Script
# For Windows servers running IIS or local testing before upload

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  SMART RESTAURANT DEPLOYMENT CHECK" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$dbName = "inovasiy_smartresto"
$dbUser = "inovasiy_admin"
$productionUrl = "https://smartresto.inovasiyo.rw"

Write-Host "Step 1: Checking local files..." -ForegroundColor Yellow

$criticalFiles = @(
    "index.php",
    "src\config.php",
    "app\controllers\staff.php",
    "app\controllers\api.php",
    "app\controllers\menu.php",
    "app\controllers\superadmin.php",
    "db_restaurant (2).sql",
    "import_database.php",
    "verify_production.php"
)

$allFilesExist = $true
foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file MISSING" -ForegroundColor Red
        $allFilesExist = $false
    }
}

Write-Host ""
Write-Host "Step 2: Checking directories..." -ForegroundColor Yellow

$directories = @(
    "app\controllers",
    "app\core",
    "app\models",
    "app\views",
    "assets\css",
    "assets\js",
    "assets\images",
    "src",
    "PHPMailer",
    "images\qrcodes"
)

foreach ($dir in $directories) {
    if (Test-Path $dir) {
        $fileCount = (Get-ChildItem $dir -File -Recurse).Count
        Write-Host "  ✓ $dir ($fileCount files)" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $dir MISSING" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Step 3: Analyzing configuration..." -ForegroundColor Yellow

if (Test-Path "src\config.php") {
    $configContent = Get-Content "src\config.php" -Raw
    
    if ($configContent -match "inovasiy_smartresto") {
        Write-Host "  ✓ Production database configured: $dbName" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Production database not found in config" -ForegroundColor Yellow
    }
    
    if ($configContent -match "smartresto\.inovasiyo\.rw") {
        Write-Host "  ✓ Production URL configured: $productionUrl" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Production URL not found in config" -ForegroundColor Yellow
    }
    
    if ($configContent -match '\$_SERVER\[.HTTP_HOST.\]') {
        Write-Host "  ✓ Auto-detection enabled (localhost → production)" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Step 4: Checking SQL file..." -ForegroundColor Yellow

if (Test-Path "db_restaurant (2).sql") {
    $sqlSize = (Get-Item "db_restaurant (2).sql").Length / 1KB
    Write-Host "  ✓ SQL file found: $([math]::Round($sqlSize, 2)) KB" -ForegroundColor Green
    
    $sqlContent = Get-Content "db_restaurant (2).sql" -Raw
    
    if ($sqlContent -match "DEFINER=") {
        Write-Host "  ✗ DEFINER clauses found (will cause cPanel errors)" -ForegroundColor Red
        Write-Host "    Run fix: Remove all DEFINER clauses" -ForegroundColor Yellow
    } else {
        Write-Host "  ✓ No DEFINER clauses (cPanel compatible)" -ForegroundColor Green
    }
    
    $tableCount = ([regex]::Matches($sqlContent, "CREATE TABLE")).Count
    $viewCount = ([regex]::Matches($sqlContent, "CREATE.*VIEW")).Count
    Write-Host "  ✓ Tables: $tableCount, Views: $viewCount" -ForegroundColor Green
}

Write-Host ""
Write-Host "Step 5: File upload preparation..." -ForegroundColor Yellow

$totalSize = (Get-ChildItem -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host "  Total project size: $([math]::Round($totalSize, 2)) MB" -ForegroundColor Cyan

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  DEPLOYMENT CHECKLIST" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

if ($allFilesExist) {
    Write-Host "✅ All critical files present" -ForegroundColor Green
} else {
    Write-Host "❌ Some files are missing!" -ForegroundColor Red
}

Write-Host ""
Write-Host "📦 UPLOAD TO PRODUCTION:" -ForegroundColor Yellow
Write-Host "   1. Upload all files to /public_html/" -ForegroundColor White
Write-Host "   2. Import: db_restaurant (2).sql" -ForegroundColor White
Write-Host "   3. Create directories:" -ForegroundColor White
Write-Host "      - mkdir sessions (chmod 777)" -ForegroundColor Gray
Write-Host "      - mkdir images/qrcodes (chmod 777)" -ForegroundColor Gray
Write-Host "   4. Visit: $productionUrl/verify_production.php" -ForegroundColor White
Write-Host "   5. Visit: $productionUrl/import_database.php" -ForegroundColor White
Write-Host "   6. Test: $productionUrl/?req=superadmin" -ForegroundColor White
Write-Host ""

Write-Host "🔒 AFTER DEPLOYMENT:" -ForegroundColor Yellow
Write-Host "   - Delete verify_production.php" -ForegroundColor White
Write-Host "   - Delete import_database.php" -ForegroundColor White
Write-Host "   - Enable SSL/HTTPS" -ForegroundColor White
Write-Host ""

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  Ready for production deployment!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Ask if user wants to create a deployment package
Write-Host "Would you like to create a ZIP package for upload? (Y/N): " -ForegroundColor Yellow -NoNewline
$response = Read-Host

if ($response -eq 'Y' -or $response -eq 'y') {
    Write-Host ""
    Write-Host "Creating deployment package..." -ForegroundColor Yellow
    
    $zipFile = "smart_restaurant_production_$(Get-Date -Format 'yyyyMMdd_HHmmss').zip"
    
    # Exclude unnecessary files
    $exclude = @('*.git*', 'node_modules', '*.log', 'backups', 'sessions')
    
    Compress-Archive -Path * -DestinationPath $zipFile -Force
    
    Write-Host "✓ Package created: $zipFile" -ForegroundColor Green
    Write-Host "  Upload this file to your server and extract it." -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
