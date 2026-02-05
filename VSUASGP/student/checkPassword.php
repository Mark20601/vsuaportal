<?php
$password1 = $_POST['password1'] ?? '';

function validatePassword1($password1) {
    $password1 = trim($password1); // Remove spaces

    if (strlen($password1) < 8) {
        return false;
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password1)) {
        return false;
    }

    if (preg_match_all('/\d/', $password1) < 3) {
        return false;
    }

    if (preg_match_all('/[a-zA-Z]/', $password1) < 8) {
        return false;
    }

    return true;
}

$isValidPassword = validatePassword1($password1);

// Step-by-step feedback
$feedback1 = '';
if ($password1 === '') {
    $feedback1 = "<small class='text-danger'>Please enter a password.</small>";
} elseif (strlen($password1) < 8) {
    $feedback1 = "<small class='text-danger'>Password must be at least 8 characters.</small>";
} elseif (preg_match_all('/\d/', $password1) < 3) {
    $feedback1 = "<small class='text-danger'>Password must include at least 3 digits.</small>";
} elseif (!preg_match('/[^a-zA-Z0-9]/', $password1)) {
    $feedback1 = "<small class='text-danger'>Password must include at least 1 special character.</small>";
} elseif (preg_match_all('/[a-zA-Z]/', $password1) < 8) {
    $feedback1 = "<small class='text-danger'>Password must include at least 8 letters.</small>";
} else {
    $feedback1 = "<small class='text-success'>Strong Password.</small>";
}

$response = [
    'feedback1' => $feedback1,
    'validMatch' => $isValidPassword
];

echo json_encode($response);
