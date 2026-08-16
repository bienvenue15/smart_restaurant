<?php
// Check database tables
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
    $stmt = $pdo->query('SHOW TABLES');
    echo "Tables in db_restaurant:\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "  - " . $row[0] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
