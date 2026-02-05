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

// 1. Move the helper function to the top (outside the loop)
function maskName($name) {
    if (empty($name)) return "";
    $length = mb_strlen($name);
    if ($length <= 2) {
        return str_repeat('*', $length);
    }
    return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$program       = $_POST['program'] ?? '';
$year          = $_POST['year'] ?? '';
$section       = $_POST['section'] ?? '';
$course_code   = $_POST['course_code'] ?? '';
$instructor_id = $_POST['instructor_id'] ?? '';
$student_ids   = $_POST['student_id'] ?? [];
$grades        = $_POST['grade'] ?? [];

if (empty($program) || empty($course_code) || empty($instructor_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // 2. Get instructor info
    $instructorQuery = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $instructorQuery->bind_param("s", $instructor_id);
    $instructorQuery->execute();
    $instructor = $instructorQuery->get_result()->fetch_assoc();

    // Mask the instructor name once here to save processing power
    $maskedFirstName = maskName($instructor['first_name'] ?? '');
    $maskedLastName  = maskName($instructor['last_name'] ?? '');

    // 3. Get course details
    $courseQuery = $conn->prepare("SELECT course_title, semester FROM instructor_courses WHERE instructor_id = ? AND course_code = ?");
    $courseQuery->bind_param("ss", $instructor_id, $course_code);
    $courseQuery->execute();
    $courseData = $courseQuery->get_result()->fetch_assoc();
    
    $course_title = $courseData['course_title'] ?? '';
    $semester     = $courseData['semester'] ?? '';

    // 4. Setup PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'a23-1-00574@vsu.edu.ph';
    $mail->Password   = 'wyto tlwx tenx dcoo'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('vsualangalang.dit.projects@vsu.edu.ph', 'VSU-Alangalang Student Grading Portal');

    $successCount = 0;
    $emailSentCount = 0;
    $failedStudents = [];

    $updateStmt = $conn->prepare("UPDATE grades SET grades = ? WHERE student_id = ? AND instructor_id = ? AND course_code = ?");
    $studentStmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name FROM users u WHERE u.user_id = ?");

    foreach ($student_ids as $index => $student_id) {
        if (!isset($grades[$index]) || empty($grades[$index])) continue;

        $new_grade = $grades[$index];
        $updateStmt->bind_param("ssss", $new_grade, $student_id, $instructor_id, $course_code);

        if ($updateStmt->execute()) {
            $successCount++;
            
            $studentStmt->bind_param("s", $student_id);
            $studentStmt->execute();
            $student = $studentStmt->get_result()->fetch_assoc();
            
            if ($student && !empty($student['email'])) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($student['email'], $student['first_name'] . ' ' . $student['last_name']);
                    
                    $emailBody = "
                        <h3>Grade Submission Notification</h3>
                        <p>Dear {$student['first_name']} {$student['last_name']},</p>
                        <p>Your instructor has submitted/updated a grade for:</p>
                        <p>
                            <strong>Course:</strong> {$course_code} - {$course_title}<br>
                            <strong>Instructor:</strong> {$maskedFirstName} {$maskedLastName}<br>
                            <strong>Program:</strong> {$program} {$year}{$section}<br>
                            <strong>Semester:</strong> {$semester}
                        </p>
                        <p>Please check your portal for details.</p>
                    ";

                    $mail->isHTML(true);
                    $mail->Subject = "Grade Notification - {$course_code}";
                    $mail->Body    = $emailBody;
                    $mail->AltBody = strip_tags($emailBody);
                    
                    $mail->send();
                    $emailSentCount++;
                } catch (Exception $e) {
                    error_log("Email failed for {$student['email']}: " . $mail->ErrorInfo);
                }
            }
        } else {
            $failedStudents[] = $student_id;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Updated $successCount grades. $emailSentCount emails sent.",
        'stats' => [
            'total' => count($student_ids),
            'updated' => $successCount,
            'emails' => $emailSentCount,
            'failed' => count($failedStudents)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "System Error: " . $e->getMessage()]);
}