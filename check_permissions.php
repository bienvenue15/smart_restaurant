<?php
$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');

echo "=== TABLE STRUCTURE ===\n";
$stmt = $db->query("SHOW COLUMNS FROM role_permissions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== ALL DATA ===\n";
$stmt = $db->query("SELECT * FROM role_permissions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
