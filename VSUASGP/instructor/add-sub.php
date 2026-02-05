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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST['submit'])){
    $user = $_POST['user_id'];
    $courseCode = $_POST['courseCode'];
    $courseTitle = $_POST['courseTitle'];
    $unit = $_POST['unit'];
    $sem = $_POST['semester'];
    $enrollmentKey = $_POST['enrollmentKey'];
    $identification = $_POST['identification'];

    // CHECK IF COURSE ALREADY EXISTS (instructor_courses)
    $stmt = $conn->prepare("SELECT * FROM instructor_courses WHERE course_code = ? AND instructor_id = ? AND status = 'Active'");
    $stmt->bind_param("ss", $courseCode, $user);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        header("Location: courses.php?already_exist=Course Code is Already Exist!");
        exit();
    }
    $stmt->close();

    // CHECK IF IDENTIFICATION IS ALREADY USED
    $stmt = $conn->prepare("SELECT * FROM coursekeys WHERE unique_identification = ? AND status = 'Active'");
    $stmt->bind_param("s", $identification);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        header("Location: dashboard.php?warning=Identification is already used! Please try a different one.");
        exit();
    }
    $stmt->close();

    // CHECK IF COURSE CODE + UNIT EXISTS in courses
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_code = ? AND unit = ? AND instructor_id = ?");
    $stmt->bind_param("sss", $courseCode, $unit, $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $courseExists = ($result->num_rows > 0);
    $stmt->close();

    // INSERT INTO coursekeys
    $stmt = $conn->prepare("INSERT INTO coursekeys (instructor_id, course_code, enrollment_key, unique_identification) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user, $courseCode, $enrollmentKey, $identification);
    $stmt->execute();
    $stmt->close();

    // INSERT INTO instructor_courses
    $status = "Active";
    $stmt = $conn->prepare("INSERT INTO instructor_courses (course_code, course_title, semester, instructor_id, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $courseCode, $courseTitle, $sem, $user, $status);
    $stmt->execute();
    $stmt->close();

    // ONLY INSERT INTO courses IF NOT EXISTS
    if (!$courseExists) {
        $stmt = $conn->prepare("INSERT INTO courses (course_code, unit, instructor_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $courseCode, $unit, $user);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: courses.php?success=Subject Successfully Added!");
    exit();
}
?>
