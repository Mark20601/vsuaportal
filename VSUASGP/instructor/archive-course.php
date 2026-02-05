<?php
session_start();

// 1. ALL-IN-ONE SECURITY CHECK
// This checks for the login flag, the ID, and the correct User Level (1 for Student)
if (
    !isset($_SESSION['instructor_logged_in']) || 
    $_SESSION['instructor_logged_in'] !== true || 
    !isset($_SESSION['user_id']) || 
    $_SESSION['userLevelID'] != 2
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

if (isset($_POST['archive'])) {
    $courseCode = $_POST['course_code'];
    $user = $_POST['user_id'];

    // Update coursekeys table
    $stmt1 = $conn->prepare("UPDATE coursekeys SET status = 'Archive' WHERE course_code = ? AND instructor_id = ?");
    $stmt1->bind_param("ss", $courseCode, $user);
    $stmt1->execute();

    // Update courses table
    $stmt2 = $conn->prepare("UPDATE instructor_courses SET status = 'Archive' WHERE course_code = ? AND instructor_id = ?");
    $stmt2->bind_param("ss", $courseCode, $user);
    $stmt2->execute();

    // Update status table (with fixed column name 'instructor_id')
    $stmt3 = $conn->prepare("UPDATE studenrollstatus SET status = 'Archive' WHERE course_code = ? AND instructor_id = ?");
    $stmt3->bind_param("ss", $courseCode, $user);
    $stmt3->execute();

    header("Location: courses.php?courseDeleted=Course $courseCode Deleted Successfully!");
    exit();
}
