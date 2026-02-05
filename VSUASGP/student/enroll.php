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

if (isset($_POST['courseCode']) && isset($_POST['enrollmentKey']) && isset($_POST['identification'])) {
    $user = $_SESSION['user_id'];
    $courseCode = trim($_POST['courseCode']);
    $key = trim($_POST['enrollmentKey']);
    $identification = trim($_POST['identification']);

    if (empty($courseCode)) {
        header("Location: student-dashboard.php?warning=Please Enter Course Code!");
        exit();
    } elseif (empty($key)) {
        header("Location: student-dashboard.php?warning=Please Enter Enrollment Key!");
        exit();
    } elseif (empty($identification)) {
        header("Location: student-dashboard.php?warning=Please Enter Identification!");
        exit();
    }

    // Check if the course key exists
    $stmt = $conn->prepare("SELECT instructor_id FROM coursekeys WHERE course_code = ? AND enrollment_key = ? AND unique_identification = ?");
    $stmt->bind_param("sss", $courseCode, $key, $identification);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $instructor = $row['instructor_id'];

            // Check for pending request
            $check = $conn->prepare("SELECT * FROM studenrollstatus 
WHERE student_id = ? 
  AND instructor_id = ? 
  AND course_code = ? 
  AND status = 'pending'");
            $check->bind_param("sss", $user, $instructor, $courseCode);
            $check->execute();
            $checkResult = $check->get_result();

            if ($checkResult->num_rows > 0) {
                header("Location: student-dashboard.php?already_pending=Your request is already pending!");
                exit();
            }

            // Check for approved request
            $checkApp = $conn->prepare("SELECT * FROM studenrollstatus 
WHERE student_id = ? 
  AND instructor_id = ? 
  AND course_code = ? 
  AND (status = 'approved' OR status = 'Archive')");
            $checkApp->bind_param("sss", $user, $instructor, $courseCode);
            $checkApp->execute();
            $checkAppResult = $checkApp->get_result();

            if ($checkAppResult->num_rows > 0) {
                header("Location: student-dashboard.php?approved=Your Request is already Approved!");
                exit();
            }

            // Insert pending request
            $insert = $conn->prepare("INSERT INTO studenrollstatus (student_id, course_code ,instructor_id) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $user, $courseCode, $instructor);
            $insert->execute();

            header("Location: student-dashboard.php?pending=Pending Request!");
            exit();
        }
    } else {
        header("Location: student-dashboard.php?not_exist=Subject does not exist!");
        exit();
    }
}
?>
