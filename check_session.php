<?php
// Session diagnostics
require_once __DIR__ . '/init_session.php';

header('Content-Type: application/json');

$sessionData = [
    'session_id' => session_id(),
    'staff_logged_in' => isset($_SESSION['staff_logged_in']) ? $_SESSION['staff_logged_in'] : false,
    'staff_uuid' => isset($_SESSION['staff_uuid']) ? $_SESSION['staff_uuid'] : 'NOT SET',
    'staff_username' => isset($_SESSION['staff_username']) ? $_SESSION['staff_username'] : 'NOT SET',
    'staff_role' => isset($_SESSION['staff_role']) ? $_SESSION['staff_role'] : 'NOT SET',
    'staff_user_exists' => isset($_SESSION['staff_user']),
    'all_session_keys' => array_keys($_SESSION),
    'cookie_params' => session_get_cookie_params(),
    'session_save_path' => session_save_path()
];

echo json_encode($sessionData, JSON_PRETTY_PRINT);
