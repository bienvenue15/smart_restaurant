<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');
$stmt = $pdo->query('DESCRIBE staff_users');
echo "Columns in staff_users:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '  - ' . $row['Field'] . ' (' . $row['Type'] . ')' . "\n";
}
