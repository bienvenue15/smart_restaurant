<?php
/**
 * MASS UUID MIGRATION FIX FOR API.PHP
 * This script will find and replace all old column references
 */

$filePath = __DIR__ . '/app/controllers/api.php';
$content = file_get_contents($filePath);
$originalContent = $content;

// Count occurrences before
preg_match_all("/\[('|\")staff_id('|\")\]/", $content, $matches);
$staffIdCount = count($matches[0]);

preg_match_all("/\[('|\")restaurant_id('|\")\]/", $content, $matches);
$restaurantIdCount = count($matches[0]);

echo "BEFORE:\n";
echo "  staff_id references: $staffIdCount\n";
echo "  restaurant_id references: $restaurantIdCount\n\n";

// Replacements
$replacements = [
    // Session array accesses - staff_user context
    "['staff_user']['id']" => "['staff_user']['uuid']",
    '["staff_user"]["id"]' => '["staff_user"]["uuid"]',
    "['staff_user']['restaurant_id']" => "['staff_user']['restaurant_uuid']",
    '["staff_user"]["restaurant_id"]' => '["staff_user"]["restaurant_uuid"]',
    
    // Direct session accesses
    "['staff_id']" => "['staff_uuid']",
    '["staff_id"]' => '["staff_uuid"]',
    "['restaurant_id']" => "['restaurant_uuid']",
    '["restaurant_id"]' => '["restaurant_uuid"]',
    
    // Session checks
    "isset(\$_SESSION['staff_id'])" => "isset(\$_SESSION['staff_uuid'])",
    'isset($_SESSION["staff_id"])' => 'isset($_SESSION["staff_uuid"])',
    "\$_SESSION['staff_id']" => "\$_SESSION['staff_uuid']",
    '$_SESSION["staff_id"]' => '$_SESSION["staff_uuid"]',
];

$changes = [];
foreach ($replacements as $old => $new) {
    $count = 0;
    $content = str_replace($old, $new, $content, $count);
    if ($count > 0) {
        $changes[] = "$old → $new ($count occurrences)";
    }
}

// Save backup
file_put_contents($filePath . '.backup_' . date('YmdHis'), $originalContent);

// Save new content
file_put_contents($filePath, $content);

echo "CHANGES MADE:\n";
foreach ($changes as $change) {
    echo "  ✅ $change\n";
}

echo "\nAFTER:\n";
preg_match_all("/\[('|\")staff_id('|\")\]/", $content, $matches);
echo "  staff_id references remaining: " . count($matches[0]) . "\n";

preg_match_all("/\[('|\")restaurant_id('|\")\]/", $content, $matches);
echo "  restaurant_id references remaining: " . count($matches[0]) . "\n";

echo "\n✅ DONE! Backup saved to: api.php.backup_" . date('YmdHis') . "\n";
