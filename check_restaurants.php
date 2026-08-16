<?php
$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
$stmt = $db->query('SHOW COLUMNS FROM restaurants');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
