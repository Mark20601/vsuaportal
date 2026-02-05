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

if (isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $program = $_POST['program'];
    $year = $_POST['year'];
    $section = $_POST['section'];

    // Prepare the insert query
    $stmt = $conn->prepare("INSERT INTO studprograms (student_id, program, year, section) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_id, $program, $year, $section); // "ssss" means 4 string parameters

    // Execute the query
    if ($stmt->execute()) {
        header("Location: student-dashboard.php");
        exit();
    } else {
        // Handle error here (optional)
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}
?>
