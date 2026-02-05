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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';


function sendEmail($to, $subject, $htmlBody, $altBody = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a23-1-00574@vsu.edu.ph';
        $mail->Password   = 'wyto tlwx tenx dcoo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('vsualangalang.dit.projects@vsu.edu.ph', 'VSU-Alangalang IT Department');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

if (isset($_POST['approve'])) {
    $user = $_POST['instructor_id'];
    $id = $_POST['id'];
    $courseCode = $_POST['course_code'];
    $prog = $_POST['program'];
    $year = $_POST['year'];
    $sec = $_POST['section'];
    $sem = $_POST['semester'];

    // Check if program already exists
    $stmt = $conn->prepare("SELECT * FROM program_student WHERE program = ? AND year = ? AND section = ? AND course_code = ? AND instructor_id = ?");
    $stmt->bind_param("sssss", $prog, $year, $sec, $courseCode, $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmtInsert = $conn->prepare("INSERT INTO grades (course_code, student_id, instructor_id) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("sss", $courseCode, $id, $user);
        $stmtInsert->execute();

        $stmtUpdate = $conn->prepare("UPDATE studenrollstatus SET status = 'approved', semester = '$sem' WHERE student_id = ? AND instructor_id = ? AND course_code = ?");
        $stmtUpdate->bind_param("sss", $id, $user, $courseCode);
        $stmtUpdate->execute();
    } else {
        $stmtProg = $conn->prepare("INSERT INTO program_student (program, year, semester, section, course_code, instructor_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtProg->bind_param("ssssss", $prog, $year, $sem, $sec, $courseCode, $user);
        $stmtProg->execute();

        $stmtInsert = $conn->prepare("INSERT INTO grades (course_code, student_id, instructor_id) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("sss", $courseCode, $id, $user);
        $stmtInsert->execute();

        $stmtUpdate = $conn->prepare(
            "UPDATE studenrollstatus 
             SET status = 'approved', semester = ?
             WHERE student_id = ? AND instructor_id = ? AND course_code = ?"
        );
        $stmtUpdate->bind_param("ssss", $sem, $id, $user, $courseCode);

        $stmtUpdate->execute();
    }

    // Send email
    $stmtStudent = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $stmtStudent->bind_param("s", $id);
    $stmtStudent->execute();
    $res = $stmtStudent->get_result();
    if ($student = $res->fetch_assoc()) {
        $email = $student['email'];
        $name = $student['first_name'];
        $subject = "Enrollment Approved";
        $htmlBody = "<p>Hi $name,</p><p>Your enrollment in <strong>$courseCode</strong> has been <b>approved</b> by your instructor.</p>";
        sendEmail($email, $subject, $htmlBody);
    }

    header("Location: pending.php?approve= Student $id for course $courseCode is Approved!");
    exit();
}

else if (isset($_POST['deny'])) {
    $user = $_POST['instructor_id'];
    $id = $_POST['id'];
    $courseCode = $_POST['course_code'];
    $prog = $_POST['program'];
    $year = $_POST['year'];
    $sec = $_POST['section'];
    $sem = $_POST['semester'];

    $stmt = $conn->prepare("DELETE FROM studenrollstatus WHERE instructor_id = ? AND student_id = ? AND course_code = ? AND status = 'pending'");
    $stmt->bind_param("sss", $user, $id, $courseCode);
    $stmt->execute();

    // Send denial email
    $stmtStudent = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $stmtStudent->bind_param("s", $id);
    $stmtStudent->execute();
    $res = $stmtStudent->get_result();
    if ($student = $res->fetch_assoc()) {
        $email = $student['email'];
        $name = $student['first_name'];
        $subject = "Enrollment Denied";
        $htmlBody = "<p>Hi $name,</p><p>Your enrollment request for <strong>$courseCode</strong> has been <b>denied</b> by your instructor.</p>";
        sendEmail($email, $subject, $htmlBody);
    }

    header("Location: pending.php?deleted=Student $id Denied!");
    exit();
}

// Add this after your existing approval/denial code
else if (isset($_POST['bulkApprove'])) {
    if (!empty($_POST['student_ids'])) {
        $instructor_id = $_POST['instructor_id'];
        $successCount = 0;
        
        foreach ($_POST['student_ids'] as $student_course) {
            list($student_id, $course_code) = explode('|', $student_course);
            
            // Get student and course details
            $stmt = $conn->prepare("SELECT 
                    studprograms.program, studprograms.year, studprograms.section, 
                    instructor_courses.semester
                FROM studprograms
                INNER JOIN instructor_courses ON instructor_courses.course_code = ?
                WHERE studprograms.student_id = ?");
            $stmt->bind_param("ss", $course_code, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $prog = $row['program'];
                $year = $row['year'];
                $sec = $row['section'];
                $sem = $row['semester'];
                
                // Check if program already exists
                $stmtCheck = $conn->prepare("SELECT * FROM program_student WHERE program = ? AND year = ? AND section = ? AND course_code = ? AND instructor_id = ?");
                $stmtCheck->bind_param("sssss", $prog, $year, $sec, $course_code, $instructor_id);
                $stmtCheck->execute();
                $checkResult = $stmtCheck->get_result();

                if ($checkResult->num_rows > 0) {
                    // Insert grade record
                    $stmtGrade = $conn->prepare("INSERT INTO grades (course_code, student_id, instructor_id) VALUES (?, ?, ?)");
                    $stmtGrade->bind_param("sss", $course_code, $student_id, $instructor_id);
                    $stmtGrade->execute();
                } else {
                    // Insert program record
                    $stmtProg = $conn->prepare("INSERT INTO program_student (program, year, semester, section, course_code, instructor_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtProg->bind_param("ssssss", $prog, $year, $sem, $sec, $course_code, $instructor_id);
                    $stmtProg->execute();

                    // Insert grade record
                    $stmtGrade = $conn->prepare("INSERT INTO grades (course_code, student_id, instructor_id) VALUES (?, ?, ?)");
                    $stmtGrade->bind_param("sss", $course_code, $student_id, $instructor_id);
                    $stmtGrade->execute();
                }

                // Update enrollment status
                $stmtUpdate = $conn->prepare("UPDATE studenrollstatus SET status = 'approved', semester = ? WHERE student_id = ? AND instructor_id = ? AND course_code = ?");
                $stmtUpdate->bind_param("ssss", $sem, $student_id, $instructor_id, $course_code);
                $stmtUpdate->execute();
                
                // Send email notification
                $stmtStudent = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
                $stmtStudent->bind_param("s", $student_id);
                $stmtStudent->execute();
                $res = $stmtStudent->get_result();
                if ($student = $res->fetch_assoc()) {
                    $email = $student['email'];
                    $name = $student['first_name'];
                    $subject = "Enrollment Approved";
                    $htmlBody = "<p>Hi $name,</p><p>Your enrollment in <strong>$course_code</strong> has been <b>approved</b> by your instructor.</p>";
                    sendEmail($email, $subject, $htmlBody);
                }
                
                $successCount++;
            }
        }
        
        header("Location: pending.php?approve=Selected student's successfully Approved! $successCount students approved.");
        exit();
    } else {
        header("Location: pending.php?error=No students selected for approval.");
        exit();
    }
}

else if (isset($_POST['bulkDeny'])) {
    if (!empty($_POST['student_ids'])) {
        $instructor_id = $_POST['instructor_id'];
        $successCount = 0;
        
        foreach ($_POST['student_ids'] as $student_course) {
            list($student_id, $course_code) = explode('|', $student_course);
            
            // Delete enrollment request
            $stmt = $conn->prepare("DELETE FROM studenrollstatus WHERE instructor_id = ? AND student_id = ? AND course_code = ? AND status = 'pending'");
            $stmt->bind_param("sss", $instructor_id, $student_id, $course_code);
            $stmt->execute();
            
            // Send denial email
            $stmtStudent = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
            $stmtStudent->bind_param("s", $student_id);
            $stmtStudent->execute();
            $res = $stmtStudent->get_result();
            if ($student = $res->fetch_assoc()) {
                $email = $student['email'];
                $name = $student['first_name'];
                $subject = "Enrollment Denied";
                $htmlBody = "<p>Hi $name,</p><p>Your enrollment request for <strong>$course_code</strong> has been <b>denied</b> by your instructor.</p>";
                sendEmail($email, $subject, $htmlBody);
            }
            
            $successCount++;
        }
        
        header("Location: pending.php?deleted=Selected student's successfully denied! $successCount requests denied.");
        exit();
    } else {
        header("Location: pending.php?error=No students selected for denial.");
        exit();
    }
}

?>
