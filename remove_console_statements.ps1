# Remove Console Statements Script
# This script removes all console.log, console.error, console.warn, console.info, console.debug statements

Write-Host "Removing console statements from JavaScript files..." -ForegroundColor Yellow
Write-Host ""

$files = @(
    "assets\js\app.js",
    "assets\js\customer-menu.js",
    "assets\js\dynamic-language-switcher.js",
    "app\views\staff\admin\menu.php",
    "app\views\staff\admin\order_tracking.php",
    "app\views\staff\admin\orders.php",
    "app\views\staff\reports.php",
    "app\views\staff\liabilities.php",
    "app\views\staff\approvals.php",
    "app\views\staff\dashboard.php",
    "app\views\staff\_sidebar.php",
    "app\views\staff\includes\announcement_banner.php",
    "app\views\superadmin\announcements.php",
    "app\views\superadmin\dashboard.php",
    "app\views\home.php",
    "app\views\menu.php",
    "dynamic-language-demo.php"
)

$totalRemoved = 0

foreach ($file in $files) {
    $filePath = Join-Path $PSScriptRoot $file
    
    if (-Not (Test-Path $filePath)) {
        Write-Host "  Skipping $file (not found)" -ForegroundColor Yellow
        continue
    }
    
    Write-Host "  Processing $file..." -ForegroundColor Cyan
    
    $content = Get-Content $filePath -Raw
    $originalContent = $content
    
    # Remove console.log statements (handles multi-line)
    $content = $content -replace "console\.log\([^)]*\);?`r?`n?", ""
    $content = $content -replace "console\.error\([^)]*\);?`r?`n?", ""
    $content = $content -replace "console\.warn\([^)]*\);?`r?`n?", ""
    $content = $content -replace "console\.info\([^)]*\);?`r?`n?", ""
    $content = $content -replace "console\.debug\([^)]*\);?`r?`n?", ""
    
    # Special case: audio.play().catch(e => console.log(...))
    $content = $content -replace "\.catch\(e\s*=>\s*console\.log\([^)]*\)\)", ".catch(() => {})"
    $content = $content -replace "\.catch\(\([^)]*\)\s*=>\s*console\.log\([^)]*\)\)", ".catch(() => {})"
    
    # Count changes
    $changed = $originalContent -ne $content
    
    if ($changed) {
        # Backup original
        $backupPath = "$filePath.backup"
        Copy-Item $filePath $backupPath -Force
        
        # Save cleaned content
        Set-Content -Path $filePath -Value $content -NoNewline
        
        $removedCount = ([regex]::Matches($originalContent, "console\.(log|error|warn|info|debug)")).Count
        $totalRemoved += $removedCount
        
        Write-Host "    Removed $removedCount console statement(s)" -ForegroundColor Green
    } else {
        Write-Host "    No console statements found" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Cleanup Complete!" -ForegroundColor Green
Write-Host "Total console statements removed: $totalRemoved" -ForegroundColor White
Write-Host ""
Write-Host "Backups created with .backup extension" -ForegroundColor Yellow
Write-Host "Review changes and delete backups if satisfied" -ForegroundColor Yellow
Write-Host "================================================" -ForegroundColor Cyan
