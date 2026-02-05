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

if (isset($_POST['delete'])) {
    $user_id = $_POST['instructor_id'];
    $student_id = $_POST['student_id'];
    
    // This is now an array from the checkboxes: name="course_codes[]"
    $courseCodes = isset($_POST['course_codes']) ? $_POST['course_codes'] : [];
    
    $program = $_POST['program'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $semester = $_POST['semester'];

    if (!empty($courseCodes)) {
        foreach ($courseCodes as $courseCode) {
            
            // 1. Archive the student enrollment for THIS specific course in the loop
            $stmt = $conn->prepare("UPDATE studenrollstatus SET status = 'Archive' WHERE instructor_id = ? AND student_id = ? AND course_code = ?");
            $stmt->bind_param("sss", $user_id, $student_id, $courseCode);
            $stmt->execute();
            $stmt->close();

            // 2. Check if any other active students exist in the group for THIS course
            $check_sql = "
                SELECT COUNT(*) AS active_count
                FROM studenrollstatus AS ses
                JOIN studprograms AS sp ON ses.student_id = sp.student_id 
                WHERE ses.status = 'Approved'
                  AND ses.course_code = ?
                  AND ses.instructor_id = ?
                  AND sp.program = ?
                  AND sp.year = ?
                  AND sp.section = ?
            ";

            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("sssss", $courseCode, $user_id, $program, $year, $section);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            $active_count = $row['active_count'];
            $check_stmt->close();

            // 3. If no active students left for this specific course group, delete from program_student
            if ($active_count == 0) {
                $delete_sql = "
                    DELETE FROM program_student
                    WHERE program = ? AND year = ? AND section = ? AND course_code = ? AND instructor_id = ? AND semester = ?
                ";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ssssss", $program, $year, $section, $courseCode, $user_id, $semester);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        }
        
        $msg = count($courseCodes) . " courses archived for student " . $student_id;
    } else {
        $msg = "No courses selected for removal.";
    }

    $conn->close();

    header("Location: accepted.php?delete=" . urlencode($msg));
    exit();
}
?>