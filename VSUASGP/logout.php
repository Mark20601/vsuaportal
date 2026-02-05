<?php
include('connection.php');
session_start();

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        UPDATE login 
        SET is_logged_in = 0
        WHERE user_id = ?
    ");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
}

session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
