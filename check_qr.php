<?php
require_once 'src/config.php';

// Connect to database
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $qrCode = $_GET['qr'] ?? 'QR-REST1-TBL05-e3b0c44298fc';
    
    echo "<h2>QR Code Diagnostic</h2>";
    echo "<p>Searching for QR: <strong>" . htmlspecialchars($qrCode) . "</strong></p>";
    
    // Check for exact match
    $stmt = $db->prepare("SELECT * FROM restaurant_tables WHERE qr_code = ?");
    $stmt->execute([$qrCode]);
    $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Exact Match:</h3>";
    if ($exactMatch) {
        echo "<pre>" . print_r($exactMatch, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>No exact match found!</p>";
    }
    
    // Check for partial matches
    $stmt = $db->prepare("SELECT * FROM restaurant_tables WHERE qr_code LIKE ? OR table_number LIKE ?");
    $searchPattern = '%TBL05%';
    $stmt->execute([$searchPattern, $searchPattern]);
    $partialMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Partial Matches (contains 'TBL05'):</h3>";
    if ($partialMatches) {
        echo "<pre>" . print_r($partialMatches, true) . "</pre>";
    } else {
        echo "<p>No partial matches found</p>";
    }
    
    // Show all tables
    $stmt = $db->query("SELECT id, restaurant_id, table_number, qr_code FROM restaurant_tables LIMIT 10");
    $allTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Tables (first 10):</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Restaurant ID</th><th>Table Number</th><th>QR Code</th></tr>";
    foreach ($allTables as $table) {
        echo "<tr>";
        echo "<td>" . $table['id'] . "</td>";
        echo "<td>" . $table['restaurant_id'] . "</td>";
        echo "<td>" . $table['table_number'] . "</td>";
        echo "<td>" . htmlspecialchars($table['qr_code']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
