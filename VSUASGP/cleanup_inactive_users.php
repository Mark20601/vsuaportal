<?php
include('connection.php');
date_default_timezone_set('Asia/Manila');

$conn->query("
    UPDATE login
    SET is_logged_in = 0
    WHERE is_logged_in = 1
    AND last_activity < NOW() - INTERVAL 1 MINUTE
");

$conn->close();
exit;
