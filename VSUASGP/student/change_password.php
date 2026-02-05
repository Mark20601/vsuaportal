<?php
session_start();

// 1. ALL-IN-ONE SECURITY CHECK
// This checks for the login flag, the ID, and the correct User Level (1 for Student)
if (
    !isset($_SESSION['student_logged_in']) || 
    $_SESSION['student_logged_in'] !== true || 
    !isset($_SESSION['user_id']) || 
    $_SESSION['userLevelID'] != 1
) {
    // Clear session data to prevent accidental reuse
    session_unset();
    session_destroy();

    // Redirect to login - ensure path '../index.php' is correct from the student/ folder
    header("Location: ../index.php?error=" . urlencode("Please login."));
    exit(); 
}

// 2. DEPENDENCIES
// Only loaded if the user is verified
include('../connection.php');

if (isset($_POST['user_id']) && isset($_POST['password']) && isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['password'];

    // Hash the new password
    $hashed = hash('sha256', $new_password);

    // Update login table and mark unchangePass = 2
    $stmt = $conn->prepare("UPDATE login SET password = ?, unchangedPass = 2 WHERE user_id = ?");
    $stmt->bind_param("ss", $hashed, $user_id);
    if ($stmt->execute()) {
        header("Location: student-dashboard.php");
        exit();
    } else {
        http_response_code(500);
        echo "Error updating password";
    }
}
?>
