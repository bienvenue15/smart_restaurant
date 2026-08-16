<?php
require_once __DIR__ . '/init_session.php';
$db = new PDO('mysql:host=localhost;dbname=db_restaurant', 'root', '');

// Get current session info
echo "=== SESSION INFO ===\n";
echo "staff_uuid: " . ($_SESSION['staff_uuid'] ?? 'NOT SET') . "\n";
echo "staff_user restaurant_uuid: " . ($_SESSION['staff_user']['restaurant_uuid'] ?? 'NOT SET') . "\n\n";

$staffUuid = $_SESSION['staff_uuid'] ?? null;

if (!$staffUuid) {
    die("No staff UUID in session\n");
}

echo "=== GETTING STAFF INFO ===\n";
$query = "SELECT id, restaurant_uuid FROM staff_users WHERE uuid = ?";
$stmt = $db->prepare($query);
$stmt->execute([$staffUuid]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($staff);

if (!$staff) {
    die("Staff not found!\n");
}

echo "\n=== GETTING RESTAURANT INFO ===\n";
$query = "SELECT id FROM restaurants WHERE uuid = ?";
$stmt = $db->prepare($query);
$stmt->execute([$staff['restaurant_uuid']]);
$restaurantId = $stmt->fetchColumn();
echo "Restaurant integer ID: " . $restaurantId . "\n";

if (!$restaurantId) {
    die("Restaurant not found!\n");
}

echo "\n=== ATTEMPTING CLOCK IN ===\n";
$query = "INSERT INTO staff_shifts (staff_id, restaurant_id, clock_in, status) 
          VALUES (?, ?, NOW(), 'active')";
$stmt = $db->prepare($query);
try {
    $stmt->execute([$staff['id'], $restaurantId]);
    echo "SUCCESS! Shift ID: " . $db->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
