<?php
session_start();
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit; // stop execution, no JSON output
}

date_default_timezone_set('Asia/Manila'); // PH time
$now = date('Y-m-d H:i:s');

// If this is a logout request, mark user offline
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $stmt = $conn->prepare("UPDATE login SET is_logged_in = 0 WHERE user_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    exit; // stop execution
}

// Otherwise, update last_activity and mark user online
$stmt = $conn->prepare("
    UPDATE login 
    SET last_activity = ?, is_logged_in = 1
    WHERE user_id = ?
");
$stmt->bind_param("ss", $now, $_SESSION['user_id']);
$stmt->execute();

// No output sent back, safe for security
