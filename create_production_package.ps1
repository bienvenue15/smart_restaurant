# ============================================
# Smart Restaurant - Production Package Creator
# PowerShell Script for Windows
# ============================================

Write-Host "🚀 PRODUCTION DEPLOYMENT SCRIPT" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Run production readiness check
Write-Host "📋 Step 1: Running Production Readiness Check..." -ForegroundColor Yellow
php production_check.php

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ Production check failed. Please fix errors before deploying." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "✅ Production check passed!" -ForegroundColor Green
Write-Host ""

# Step 2: Create deployment directory
Write-Host "📦 Step 2: Creating Deployment Package..." -ForegroundColor Yellow
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$deployDir = "deploy_$timestamp"
New-Item -ItemType Directory -Force -Path $deployDir | Out-Null

# Files/folders to exclude
$exclude = @(
    'backups',
    'logs',
    '*.md',
    'deploy_*.sh',
    'deploy_*.ps1',
    'production_check.php',
    'test_*.php',
    'check_qr.php',
    'diagnostic.php',
    'db_*.sql',
    '.git',
    'node_modules',
    '.env',
    $deployDir
)

# Copy files
Write-Host "   Copying core files..." -ForegroundColor Gray
$items = Get-ChildItem -Path . -Exclude $exclude

foreach ($item in $items) {
    $destination = Join-Path $deployDir $item.Name
    
    if ($item.PSIsContainer) {
        # Skip excluded directories
        if ($exclude -contains $item.Name) {
            continue
        }
        Copy-Item -Path $item.FullName -Destination $destination -Recurse -Force
    } else {
        # Skip excluded file patterns
        $skip = $false
        foreach ($pattern in $exclude) {
            if ($item.Name -like $pattern) {
                $skip = $true
                break
            }
        }
        if (-not $skip) {
            Copy-Item -Path $item.FullName -Destination $destination -Force
        }
    }
}

Write-Host "✅ Deployment package created: $deployDir" -ForegroundColor Green
Write-Host ""

# Step 3: Create ZIP archive
Write-Host "📦 Step 3: Creating ZIP Archive..." -ForegroundColor Yellow
$zipFile = "smartresto_production_$timestamp.zip"

# Remove existing zip if exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

# Create ZIP
Add-Type -Assembly System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory(
    (Resolve-Path $deployDir).Path,
    (Join-Path (Get-Location) $zipFile)
)

Write-Host "✅ Archive created: $zipFile" -ForegroundColor Green
Write-Host ""

# Step 4: Calculate size
$zipSize = (Get-Item $zipFile).Length / 1MB
Write-Host "📊 Package size: $([math]::Round($zipSize, 2)) MB" -ForegroundColor Cyan
Write-Host ""

# Step 5: Deployment instructions
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "📤 DEPLOYMENT INSTRUCTIONS" -ForegroundColor Yellow
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Upload $zipFile to server" -ForegroundColor White
Write-Host "2. Extract in /public_html/ or /var/www/html/" -ForegroundColor White
Write-Host "3. Move contents from $deployDir/ to root directory" -ForegroundColor White
Write-Host "4. Import database: db_restaurant_complete.sql" -ForegroundColor White
Write-Host "5. Update src/config.php with production credentials" -ForegroundColor White
Write-Host "6. Set permissions:" -ForegroundColor White
Write-Host "   chmod 755 directories" -ForegroundColor Gray
Write-Host "   chmod 644 PHP files" -ForegroundColor Gray
Write-Host "   chmod 777 images/qrcodes" -ForegroundColor Gray
Write-Host "   chmod 777 assets/images/menu" -ForegroundColor Gray
Write-Host "7. Uncomment HTTPS redirect in .htaccess" -ForegroundColor White
Write-Host "8. Test: https://smartresto.inovasiyo.rw" -ForegroundColor White
Write-Host ""
Write-Host "📖 Full Guide: PRODUCTION_DEPLOYMENT.md" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ Deployment package ready!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Optional: Open folder
$response = Read-Host "Open deployment folder? (y/n)"
if ($response -eq 'y') {
    Invoke-Item $deployDir
}
