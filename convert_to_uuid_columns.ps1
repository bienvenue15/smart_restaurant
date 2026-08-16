# UUID Column Migration Script
# Converts all SQL queries from integer ID columns to UUID columns
# BACKUP YOUR FILES BEFORE RUNNING THIS!

Write-Host "=" * 80 -ForegroundColor Cyan
Write-Host "UUID Column Migration Script" -ForegroundColor Cyan
Write-Host "=" * 80 -ForegroundColor Cyan
Write-Host ""

# Define file patterns to process
$phpFiles = Get-ChildItem -Path "app" -Recurse -Filter "*.php"
$phpFiles += Get-ChildItem -Path "src" -Filter "*.php"

Write-Host "Found $($phpFiles.Count) PHP files to process" -ForegroundColor Yellow
Write-Host ""

# Define replacement patterns
# Format: @{Old = "pattern"; New = "replacement"; Description = "what it does"}
$replacements = @(
    # Session-based replacements
    @{
        Old = "['\""]staff_user['\"]\s*\]\s*\[\s*['\"]id['\"]\s*\]"
        New = "['staff_user']['uuid']"
        Description = "Session staff_user ID to UUID"
        Regex = $true
    },
    @{
        Old = "['\""]staff_user['\"]\s*\]\s*\[\s*['\"]restaurant_id['\"]\s*\]"
        New = "['staff_user']['restaurant_uuid']"
        Description = "Session restaurant_id to restaurant_uuid"
        Regex = $true
    },
    @{
        Old = "\$_SESSION\['staff_id'\]"
        New = "`$_SESSION['staff_uuid']"
        Description = "Session staff_id variable"
        Regex = $true
    },
    
    # SQL WHERE clauses
    @{
        Old = "WHERE\s+id\s*="
        New = "WHERE uuid ="
        Description = "WHERE id = to WHERE uuid ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+(\w+)\.id\s*="
        New = "WHERE `$1.uuid ="
        Description = "WHERE table.id = to WHERE table.uuid ="
        Regex = $true
    },
    @{
        Old = "AND\s+id\s*="
        New = "AND uuid ="
        Description = "AND id = to AND uuid ="
        Regex = $true
    },
    @{
        Old = "AND\s+(\w+)\.id\s*="
        New = "AND `$1.uuid ="
        Description = "AND table.id = to AND table.uuid ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+restaurant_id\s*="
        New = "WHERE restaurant_uuid ="
        Description = "WHERE restaurant_id ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+(\w+)\.restaurant_id\s*="
        New = "WHERE `$1.restaurant_uuid ="
        Description = "WHERE table.restaurant_id ="
        Regex = $true
    },
    @{
        Old = "AND\s+restaurant_id\s*="
        New = "AND restaurant_uuid ="
        Description = "AND restaurant_id ="
        Regex = $true
    },
    @{
        Old = "AND\s+(\w+)\.restaurant_uuid\s*="
        New = "AND `$1.restaurant_uuid ="
        Description = "AND table.restaurant_id ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+staff_id\s*="
        New = "WHERE staff_uuid ="
        Description = "WHERE staff_id ="
        Regex = $true
    },
    @{
        Old = "AND\s+staff_id\s*="
        New = "AND staff_uuid ="
        Description = "AND staff_id ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+order_id\s*="
        New = "WHERE order_uuid ="
        Description = "WHERE order_id ="
        Regex = $true
    },
    @{
        Old = "AND\s+order_id\s*="
        New = "AND order_uuid ="
        Description = "AND order_id ="
        Regex = $true
    },
    @{
        Old = "WHERE\s+table_id\s*="
        New = "WHERE table_uuid ="
        Description = "WHERE table_id ="
        Regex = $true
    },
    @{
        Old = "AND\s+table_id\s*="
        New = "AND table_uuid ="
        Description = "AND table_uuid ="
        Regex = $true
    },
    
    # JOIN clauses
    @{
        Old = "ON\s+(\w+)\.id\s*=\s*(\w+)\.(\w+_)id"
        New = "ON `$1.uuid = `$2.`$3uuid"
        Description = "JOIN ON table.id = table2.foreign_id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.restaurant_id\s*=\s*(\w+)\.id"
        New = "ON `$1.restaurant_uuid = `$2.uuid"
        Description = "JOIN ON table.restaurant_id = restaurants.id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.staff_id\s*=\s*(\w+)\.id"
        New = "ON `$1.staff_uuid = `$2.uuid"
        Description = "JOIN ON table.staff_id = staff.id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.order_id\s*=\s*(\w+)\.id"
        New = "ON `$1.order_uuid = `$2.uuid"
        Description = "JOIN ON table.order_id = orders.id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.table_id\s*=\s*(\w+)\.id"
        New = "ON `$1.table_uuid = `$2.uuid"
        Description = "JOIN ON table.table_id = tables.id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.menu_item_id\s*=\s*(\w+)\.id"
        New = "ON `$1.menu_item_uuid = `$2.uuid"
        Description = "JOIN ON table.menu_item_id = menu_items.id"
        Regex = $true
    },
    @{
        Old = "ON\s+(\w+)\.category_id\s*=\s*(\w+)\.id"
        New = "ON `$1.category_uuid = `$2.uuid"
        Description = "JOIN ON table.category_id = categories.id"
        Regex = $true
    },
    
    # SELECT column lists
    @{
        Old = "SELECT\s+id,"
        New = "SELECT uuid,"
        Description = "SELECT id, to SELECT uuid,"
        Regex = $true
    },
    @{
        Old = "SELECT\s+(\w+)\.id,"
        New = "SELECT `$1.uuid,"
        Description = "SELECT table.id, to SELECT table.uuid,"
        Regex = $true
    },
    @{
        Old = "SELECT\s+(\w+)\.id\s+as\s+"
        New = "SELECT `$1.uuid as "
        Description = "SELECT table.id as to SELECT table.uuid as"
        Regex = $true
    },
    @{
        Old = ",\s*restaurant_id,"
        New = ", restaurant_uuid,"
        Description = ", restaurant_id, to , restaurant_uuid,"
        Regex = $true
    },
    @{
        Old = ",\s*staff_id,"
        New = ", staff_uuid,"
        Description = ", staff_id, to , staff_uuid,"
        Regex = $true
    },
    @{
        Old = ",\s*order_id,"
        New = ", order_uuid,"
        Description = ", order_id, to , order_uuid,"
        Regex = $true
    },
    @{
        Old = ",\s*table_id,"
        New = ", table_uuid,"
        Description = ", table_id, to , table_uuid,"
        Regex = $true
    },
    
    # UPDATE SET clauses
    @{
        Old = "SET\s+restaurant_id\s*="
        New = "SET restaurant_uuid ="
        Description = "UPDATE SET restaurant_id ="
        Regex = $true
    },
    @{
        Old = "SET\s+staff_id\s*="
        New = "SET staff_uuid ="
        Description = "UPDATE SET staff_id ="
        Regex = $true
    },
    @{
        Old = "SET\s+order_id\s*="
        New = "SET order_uuid ="
        Description = "UPDATE SET order_id ="
        Regex = $true
    },
    @{
        Old = "SET\s+table_id\s*="
        New = "SET table_uuid ="
        Description = "UPDATE SET table_id ="
        Regex = $true
    },
    @{
        Old = "SET\s+assigned_to\s*="
        New = "SET assigned_to_uuid ="
        Description = "UPDATE SET assigned_to ="
        Regex = $true
    },
    @{
        Old = "SET\s+approved_by\s*="
        New = "SET approved_by_uuid ="
        Description = "UPDATE SET approved_by ="
        Regex = $true
    },
    
    # Array keys in PHP
    @{
        Old = "\['id'\]"
        New = "['uuid']"
        Description = "Array access ['id'] to ['uuid']"
        Regex = $false
    },
    @{
        Old = "\['restaurant_id'\]"
        New = "['restaurant_uuid']"
        Description = "Array access ['restaurant_id']"
        Regex = $false
    },
    @{
        Old = "\['staff_id'\]"
        New = "['staff_uuid']"
        Description = "Array access ['staff_id']"
        Regex = $false
    },
    @{
        Old = "\['order_id'\]"
        New = "['order_uuid']"
        Description = "Array access ['order_id']"
        Regex = $false
    },
    @{
        Old = "\['table_id'\]"
        New = "['table_uuid']"
        Description = "Array access ['table_id']"
        Regex = $false
    },
    @{
        Old = "\['menu_item_id'\]"
        New = "['menu_item_uuid']"
        Description = "Array access ['menu_item_id']"
        Regex = $false
    },
    @{
        Old = "\['category_id'\]"
        New = "['category_uuid']"
        Description = "Array access ['category_id']"
        Regex = $false
    }
)

$totalChanges = 0
$filesModified = 0

# Process each file
foreach ($file in $phpFiles) {
    $content = Get-Content -Path $file.FullName -Raw
    $originalContent = $content
    $fileChanges = 0
    
    foreach ($replacement in $replacements) {
        if ($replacement.Regex) {
            $matches = [regex]::Matches($content, $replacement.Old)
            if ($matches.Count -gt 0) {
                $content = $content -replace $replacement.Old, $replacement.New
                $fileChanges += $matches.Count
            }
        } else {
            $count = ($content.ToCharArray() | Where-Object {$_ -eq $replacement.Old[0]}).Count
            $content = $content.Replace($replacement.Old, $replacement.New)
            if ($content -ne $originalContent) {
                $fileChanges++
            }
        }
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        $filesModified++
        $totalChanges += $fileChanges
        Write-Host "[MODIFIED] $($file.FullName) - $fileChanges changes" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=" * 80 -ForegroundColor Cyan
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "Files Modified: $filesModified" -ForegroundColor Yellow
Write-Host "Total Changes: $totalChanges" -ForegroundColor Yellow
Write-Host "=" * 80 -ForegroundColor Cyan
Write-Host ""
Write-Host "IMPORTANT: Review the changes and test thoroughly!" -ForegroundColor Red
