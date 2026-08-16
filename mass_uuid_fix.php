<?php
// Mass UUID column updater

$filesToFix = [
    'c:/xampp/htdocs/restaurant/app/controllers/api.php',
    'c:/xampp/htdocs/restaurant/app/models/Staff.php'
];

$replacements = [
    // WHERE clauses
    "WHERE restaurant_id =" => "WHERE restaurant_uuid =",
    "WHERE table_id =" => "WHERE table_uuid =",
    "WHERE staff_id =" => "WHERE staff_uuid =",
    "WHERE order_id =" => "WHERE order_uuid =",
    "WHERE menu_item_id =" => "WHERE menu_item_uuid =",
    "WHERE category_id =" => "WHERE category_uuid =",
    "WHERE waiter_id =" => "WHERE waiter_uuid =",
    "WHERE assigned_to =" => "WHERE assigned_to_uuid =",
    
    // AND clauses
    " AND restaurant_id =" => " AND restaurant_uuid =",
    " AND table_id =" => " AND table_uuid =",
    " AND staff_id =" => " AND staff_uuid =",
    " AND order_id =" => " AND order_uuid =",
    
    // SELECT id FROM (for cash_sessions and other tables)
    "SELECT id FROM cash_sessions" => "SELECT uuid FROM cash_sessions",
    "SELECT id FROM restaurant_tables" => "SELECT uuid FROM restaurant_tables",
    "SELECT id, staff_id FROM cash_sessions" => "SELECT uuid, staff_uuid FROM cash_sessions",
    
    // Common SELECT patterns
    "WHERE id =" => "WHERE uuid =", // This is risky but needed for many queries
];

foreach ($filesToFix as $file) {
    echo "Processing: $file\n";
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        if ($count > 0) {
            echo "  Replaced '$search' → '$replace' ($count times)\n";
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "  ✓ File updated\n\n";
    } else {
        echo "  - No changes needed\n\n";
    }
}

echo "Mass replacement complete!\n";
?>
