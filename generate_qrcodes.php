<?php
/**
 * Generate QR Codes for all restaurant tables
 * Run this script once to generate QR codes
 */

require_once 'src/config.php';
require_once 'src/model.php';
require_once 'app/core/QRCodeGenerator.php';

echo "=== Restaurant QR Code Generator ===\n\n";

try {
    // Create database connection
    $db = new PDO(
        DB_TYPE.":host=".DB_HOST.";dbname=".DB_NAME,
        DB_USER,
        DB_PWD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "Database connected successfully.\n\n";
    
    // Create QR code generator
    $qrGenerator = new QRCodeGenerator();
    
    echo "Generating QR codes for all tables...\n\n";
    
    // Generate QR codes
    $result = $qrGenerator->generateAllTables($db);
    
    if ($result['status'] === 'OK') {
        echo "✓ QR Codes generated successfully!\n\n";
        
        foreach ($result['results'] as $tableResult) {
            if ($tableResult['status'] === 'OK') {
                $slug = $tableResult['restaurant_slug'] ?: 'global';
                echo "✓ {$slug} / Table {$tableResult['table']}\n";
                echo "  File: {$tableResult['filename']}\n";
                echo "  URL : {$tableResult['url']}\n\n";
            } else {
                echo "✗ Table {$tableResult['table']}: Failed\n\n";
            }
        }
        
        echo "\nQR code images saved to: images/qrcodes/<restaurant>/\n";
        echo "You can now print these tenant-specific QR codes and place them on your tables!\n";
        
    } else {
        echo "✗ Error: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Generation Complete ===\n";
?>
