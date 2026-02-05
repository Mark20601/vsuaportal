<?php
$input = $_POST['input'] ?? '';

$response = ['exceed' => false];

if (strlen($input) > 30) {
    $response['exceed'] = true;
}

echo json_encode($response);
?>