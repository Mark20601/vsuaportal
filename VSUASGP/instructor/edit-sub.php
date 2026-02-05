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

if (isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $course_code = $_POST['courseCode'];
    $courseTitle = $_POST['courseTitle'];
    $unique_identification = $_POST['identification'];
    $units = $_POST['unit'];
    $semester = $_POST['semester'];
    $enrollment_key = $_POST['enrollmentKey'];

    $updateQuery1 = $conn->prepare("UPDATE coursekeys SET enrollment_key = ?, unique_identification = ? WHERE instructor_id = ? AND course_code = ?  AND status = 'Active'");
    $updateQuery1->bind_param("ssss", $enrollment_key, $unique_identification, $user_id, $course_code);

    if ($updateQuery1->execute()){
        $updateQuery2 = $conn->prepare("UPDATE courses SET unit = ? WHERE instructor_id = ? AND course_code = ?");
        $updateQuery2->bind_param("sss", $units, $user_id, $course_code);

        if($updateQuery2->execute()) {
            $updateQuery3 = $conn->prepare("UPDATE instructor_courses SET course_title = ?, semester = ? WHERE course_code = ? AND instructor_id = ? AND status = 'Active'");
            $updateQuery3->bind_param("ssss", $courseTitle, $semester, $course_code, $user_id);
            
           if ($updateQuery3->execute()) {

            // Check if course_code exists for this instructor
            $checkQuery = $conn->prepare("
                SELECT 1 
                FROM studenrollstatus 
                WHERE instructor_id = ? 
                AND course_code = ?
                LIMIT 1
            ");
            $checkQuery->bind_param("ss", $user_id, $course_code);
            $checkQuery->execute();
            $checkQuery->store_result();
        
            // If exists → update semester
            if ($checkQuery->num_rows > 0) {
                $updateQuery4 = $conn->prepare("
                    UPDATE studenrollstatus 
                    SET semester = ? 
                    WHERE instructor_id = ? 
                    AND course_code = ?
                ");
                $updateQuery4->bind_param("sss", $semester, $user_id, $course_code);
                $updateQuery4->execute();
            }
        
            // Always redirect
            header("Location: courses.php?success=Course " . urlencode($course_code) . " is successfully updated!");
            exit();
        } else {
                header("Location: courses.php?error= Something Went Wrong!");
                exit();
            }
        }

       
    }

}
?>