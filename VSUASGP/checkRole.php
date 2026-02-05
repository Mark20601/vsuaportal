<?php
$role = $_POST['role'] ?? '';

$response = ['valid' => false];

if ($role === 'student' || $role === 'faculty') {
    $response['validRole'] = true;
}

echo json_encode($response);
?>
