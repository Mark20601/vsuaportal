<?php
session_start();
require_once 'vendor/autoload.php';

include("connection.php");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if(isset($_POST['submit'])){
    $email = $_POST['email'] ?? '';
    $id = $_POST['id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if($password !== $confirm_password){
        header("Location: register_form.php?mismatched= Password didn't match!");
        exit();
    }

// Sanitize inputs
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$email = sanitize($email);
$id = sanitize($id);
$first_name = sanitize($first_name);
$last_name = sanitize($last_name);
$confirm_password = sanitize($confirm_password);
$role = sanitize($role);

// Set user level based on role
switch ($role) {
    case 'student':
        $userLevel = 1;
        break;
    case 'faculty':
        $userLevel = 2;
        break;
    default:
        $userLevel = 0;
        break;
}

// Check if user ID already exists
$search = $conn->prepare("SELECT user_id FROM login WHERE user_id = ?");
$search->bind_param("s", $id);
$search->execute();
$search->store_result();

if ($search->num_rows > 0) {
    $search->close();
    header("Location: register_form.php?error= User ID already exists.");
    exit();
}
$search->close();

// Password hashing (security best practice)
$hashedPassword = password_hash($confirm_password, PASSWORD_DEFAULT);

// Update users table
$update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, status = 'Pending', user_id = ?, userLevelID = ? WHERE email = ?");
if ($update) {
    $update->bind_param("sssis", $first_name, $last_name, $id, $userLevel, $email);
    $update->execute();
    $update->close();
} else {
    die("Update users failed: " . $conn->error);
}

// Update login table with hashed password
$log = $conn->prepare("UPDATE login SET password = ?, user_id = ? WHERE email = ?");
if ($log) {
    $log->bind_param("sss", $hashedPassword, $id, $email);
    $log->execute();
    $log->close();
    header("Location: index.php?success=Pending Request!");
} else {
    die("Update login failed: " . $conn->error);
}

// Clear session
session_unset();
session_destroy();
}

?>
