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
require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;

header("Content-Type: application/json");

$program = $_POST['program'];
$year = $_POST['year'];
$section = $_POST['section'];
$course_code = $_POST['course_code'];
$instructor_id = $_POST['instructor_id'];

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file']['tmp_name'];

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

// Validate column count
if (count($data[0]) !== 3) {
    echo json_encode([
        'error' => 'Excel must contain exactly 3 columns: Student ID, Grade'
    ]);
    exit;
}

$rows = [];

foreach ($data as $i => $row) {
    if ($i == 0) continue; // skip header

    $student_id = trim($row[0]);
    $rawGrade = $row[2];

        if (is_numeric($rawGrade)) {
            // Force 2 decimal places for preview
            $grade = number_format((float)$rawGrade, 2, '.', '');
        } else {
            $grade = trim($rawGrade);
        }


    // Skip empty rows
    if ($student_id === "") continue;

    // Validate if student is enrolled
    $check = mysqli_query($conn, "
        SELECT 
            u.first_name, u.last_name, g.grades AS current_grade
        FROM users u
        JOIN studprograms sp ON u.user_id = sp.student_id
        JOIN studenrollstatus ses ON sp.student_id = ses.student_id
        LEFT JOIN grades g ON g.student_id = ses.student_id 
            AND g.course_code = ses.course_code
            AND g.instructor_id = ses.instructor_id
        WHERE sp.program='$program'
          AND sp.year='$year'
          AND sp.section='$section'
          AND ses.course_code='$course_code'
          AND ses.instructor_id='$instructor_id'
          AND ses.status='Approved'
          AND sp.student_id='$student_id'
    ");

    if (mysqli_num_rows($check) == 0) {
        continue; // ignore extra rows not in class list
    }

    $stud = mysqli_fetch_assoc($check);

    $rows[] = [
        'student_id' => $student_id,
        'name' => $stud['last_name'] . ", " . $stud['first_name'],
        'course' => $course_code,
        'current_grade' => $stud['current_grade'] ?: "N/A",
        'new_grade' => $grade
    ];
}

echo json_encode(['rows' => $rows]);
exit;
