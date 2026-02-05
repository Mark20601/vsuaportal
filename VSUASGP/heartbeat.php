<?php
session_start();
include('connection.php');

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'no-session']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE login
    SET last_activity = NOW()
    WHERE user_id = ?
");

$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();

echo json_encode(['status' => 'ok']);
