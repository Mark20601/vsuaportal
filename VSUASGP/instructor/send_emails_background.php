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
set_time_limit(0);
// If this script is in the root, the path to vendor is 'vendor/autoload.php'
require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log start for debugging
error_log("Background script started at " . date('Y-m-d H:i:s'));

if (empty($argv[1])) {
    error_log("Background Error: No file argument provided.");
    exit;
}

$emailFile = $argv[1];
if (!file_exists($emailFile)) {
    error_log("Background Error: JSON file not found at $emailFile");
    exit;
}

$data = json_decode(file_get_contents($emailFile), true);
if (!$data) {
    error_log("Background Error: Failed to decode JSON.");
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'a23-1-00574@vsu.edu.ph';
    $mail->Password   = 'wyto tlwx tenx dcoo'; // USE APP PASSWORD HERE
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('vsualangalang.dit.projects@vsu.edu.ph', 'VSU-Alangalang Student Grading Portal');

    foreach ($data['students'] as $student) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($student['email'], $student['first_name'] . ' ' . $student['last_name']);
            
            $mail->isHTML(true);
            $mail->Subject = "Grade Submission Notification - " . $data['course_code'];
            $mail->Body    = "<h3>Grade Submission</h3><p>Hello {$student['first_name']}, your grade for {$data['course_code']} has been updated.</p>";
            
            $mail->send();
            error_log("Email sent successfully to: " . $student['email']);
            usleep(500000); 
        } catch (Exception $e) {
            error_log("PHPMailer Error for {$student['email']}: " . $mail->ErrorInfo);
        }
    }
} catch (Exception $e) {
    error_log("Mailer Setup Error: " . $e->getMessage());
}

if (file_exists($emailFile)) unlink($emailFile);