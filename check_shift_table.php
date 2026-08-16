<?php
$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');

echo "=== STAFF_SHIFTS TABLE STRUCTURE ===\n";
$stmt = $db->query("SHOW COLUMNS FROM staff_shifts");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ") - " . $row['Key'] . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$stmt = $db->query("SELECT * FROM staff_shifts LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
