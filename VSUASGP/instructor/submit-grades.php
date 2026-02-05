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
require '../vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    $instructorId = $_POST['instructor_id'];
    $studentId = $_POST['student_id'];
    $courseCode = $_POST['course_code'];
    $newGrade = $_POST['grade'];
    $program = $_POST['program'];
    $year = $_POST['year'];
    $section = $_POST['section'];


    // Update the grade in the database
    $updateQuery = $conn->prepare("UPDATE grades SET grades = ? 
                                  WHERE student_id = ? 
                                  AND course_code = ?
                                  AND instructor_id = ?");
    $updateQuery->bind_param("ssss", $newGrade, $studentId, $courseCode, $instructorId);
    
    if ($updateQuery->execute()) {
        // Get student and course details for email
        $studentQuery = $conn->prepare("SELECT u.email, u.first_name, u.last_name, c.course_title 
                                       FROM users u
                                       JOIN instructor_courses c ON c.course_code = ?
                                       WHERE u.user_id = ?");
        $studentQuery->bind_param("ss", $courseCode, $studentId);
        $studentQuery->execute();
        $studentResult = $studentQuery->get_result();
        $studentData = $studentResult->fetch_assoc();

        // Get instructor details
        $instructorQuery = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $instructorQuery->bind_param("s", $instructorId);
        $instructorQuery->execute();
        $instructorResult = $instructorQuery->get_result();
        $instructorData = $instructorResult->fetch_assoc();

        $semQuery = $conn->prepare("SELECT semester FROM instructor_courses WHERE instructor_id = ? AND course_code = ?");
        $semQuery->bind_param("ss", $instructorId, $courseCode);
        $semQuery->execute();
        $semResult = $semQuery->get_result();
        $semData = $semResult->fetch_assoc();


        // Send email notification
        $mail = new PHPMailer(true);
        try {

            function maskName($name) {
                $length = mb_strlen($name);
            
                // If name is too short, mask all
                if ($length <= 2) {
                    return str_repeat('*', $length);
                }
            
                return mb_substr($name, 0, 1)
                    . str_repeat('*', $length - 2)
                    . mb_substr($name, -1);
            }
            
            $maskedFirstName = maskName($instructorData['first_name']);
            $maskedLastName  = maskName($instructorData['last_name']);


            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'a23-1-00574@vsu.edu.ph'; // Your email
            $mail->Password   = 'wyto tlwx tenx dcoo'; // Your email password or app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('your-email@gmail.com', 'VSU-Alangalang Student Grading Portal');
            $mail->addAddress($studentData['email'], $studentData['first_name'] . ' ' . $studentData['last_name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Grade Update Notification - ' . $courseCode;
            
            $emailBody = "
                <h3>Grade Update Notification</h3>
                <p>Dear {$studentData['first_name']} {$studentData['last_name']},</p>
                <p>Your grade for the course <strong>{$courseCode} - {$studentData['course_title']}</strong> has been updated.</p>
                <p><strong>Instructor:</strong> {$maskedFirstName} {$maskedLastName}</p>
                <p><strong>Program:</strong> {$program} {$year} {$section}</p>
                <p><strong>Semester: </strong>" . $semData['semester'] . "</p>
                <br>
                <p>If you have any questions about your grade, please contact your instructor.</p>
                <p>Best regards,<br>University Administration</p>
            ";
            
            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags($emailBody);

            $mail->send();
            
        } catch (Exception $e) {
            // Log the error but don't prevent the grade update
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }

        
        // Redirect to student.php without query parameters
       header("Location: student.php?program=" . urlencode($program) . 
       "&year=" . urlencode($year) . 
       "&section=" . urlencode($section) . 
       "&course_code=" . urlencode($courseCode));
exit();



    } else {
        echo "Error updating grade: " . $conn->error;
    }
}
?>