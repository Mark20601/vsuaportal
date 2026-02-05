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
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// Helper function to mask names as per your logic
function maskName($name) {
    $length = mb_strlen($name);
    if ($length <= 2) {
        return str_repeat('*', $length);
    }
    return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
}

if (isset($_POST['submit_bulk']) && !empty($_POST['selected_students'])) {
    $instructorId = $_POST['instructor_id'];
    $program = $_POST['program'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $courseCodeParam = $_POST['course_code']; // For redirection
    $selectedStudents = $_POST['selected_students'];
    $allGrades = $_POST['grades'];
    $allCourseCodes = $_POST['course_codes'];

    // 1. Fetch instructor data once to avoid redundant queries in the loop
    $instructorQuery = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $instructorQuery->bind_param("s", $instructorId);
    $instructorQuery->execute();
    $instructorData = $instructorQuery->get_result()->fetch_assoc();
    
    $maskedFirstName = maskName($instructorData['first_name']);
    $maskedLastName  = maskName($instructorData['last_name']);

    // 2. Initialize PHPMailer once outside the loop for performance
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a23-1-00574@vsu.edu.ph';
        $mail->Password   = 'wyto tlwx tenx dcoo'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('your-email@gmail.com', 'VSU-Alangalang Student Grading Portal');
        $mail->isHTML(true);
    } catch (Exception $e) {
        error_log("Mailer Setup Error: " . $mail->ErrorInfo);
    }

    // 3. Loop through selected students
    foreach ($selectedStudents as $studentId) {
        $newGrade = trim($allGrades[$studentId]);
        $courseCode = $allCourseCodes[$studentId];

        if ($newGrade === "") continue;

        // Update Database
        $updateQuery = $conn->prepare("UPDATE grades SET grades = ? WHERE student_id = ? AND course_code = ? AND instructor_id = ?");
        $updateQuery->bind_param("ssss", $newGrade, $studentId, $courseCode, $instructorId);
        
        if ($updateQuery->execute()) {
            // Fetch student and semester details
            $studentQuery = $conn->prepare("SELECT u.email, u.first_name, u.last_name, ic.course_title, ic.semester 
                                            FROM users u 
                                            JOIN instructor_courses ic ON ic.course_code = ? 
                                            WHERE u.user_id = ? AND ic.instructor_id = ?");
            $studentQuery->bind_param("sss", $courseCode, $studentId, $instructorId);
            $studentQuery->execute();
            $studentResult = $studentQuery->get_result();
            
            if ($studentData = $studentResult->fetch_assoc()) {
                try {
                    // Clear previous recipient and set new one
                    $mail->clearAddresses();
                    $mail->addAddress($studentData['email'], $studentData['first_name'] . ' ' . $studentData['last_name']);
                    
                    $mail->Subject = 'Grade Update Notification - ' . $courseCode;
                    
                    $emailBody = "
                        <h3>Grade Update Notification</h3>
                        <p>Dear {$studentData['first_name']} {$studentData['last_name']},</p>
                        <p>Your grade for the course <strong>{$courseCode} - {$studentData['course_title']}</strong> has been updated.</p>
                        <p><strong>Instructor:</strong> {$maskedFirstName} {$maskedLastName}</p>
                        <p><strong>Program:</strong> {$program} {$year} {$section}</p>
                        <p><strong>Semester: </strong> {$studentData['semester']}</p>
                        <br>
                        <p>If you have any questions about your grade, please contact your instructor.</p>
                        <p>Best regards,<br>University Administration</p>
                    ";
                    
                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags($emailBody);

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error for Student $studentId: " . $mail->ErrorInfo);
                }
            }
        }
    }

    // 4. Redirect back
    header("Location: student.php?program=" . urlencode($program) . 
           "&year=" . urlencode($year) . 
           "&section=" . urlencode($section) . 
           "&course_code=" . urlencode($courseCodeParam));
    exit();
}
?>