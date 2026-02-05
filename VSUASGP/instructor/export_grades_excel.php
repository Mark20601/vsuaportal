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


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get POST values
$program = $_POST['program'] ?? '';
$year = $_POST['year'] ?? '';
$section = $_POST['section'] ?? '';
$course_code = $_POST['course_code'] ?? '';
$instructor_id = $_POST['instructor_id'] ?? '';

$query = "
    SELECT DISTINCT
        u.user_id AS student_id,
        u.first_name,
        u.last_name,
        g.grades AS grade
    FROM users u
    JOIN studprograms sp ON u.user_id = sp.student_id
    JOIN studenrollstatus ses ON sp.student_id = ses.student_id
    LEFT JOIN grades g ON ses.student_id = g.student_id
                        AND ses.course_code = g.course_code
                        AND ses.instructor_id = g.instructor_id
    WHERE sp.program = '$program'
      AND sp.year = '$year'
      AND sp.section = '$section'
      AND ses.course_code = '$course_code'
      AND ses.instructor_id = '$instructor_id'
      AND ses.status = 'Approved'
    ORDER BY u.user_id ASC
";

$result = mysqli_query($conn, $query);

// Create Excel document
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add header
$sheet->setCellValue('A1', 'Student ID');
$sheet->setCellValue('B1' , 'Full Name');
$sheet->setCellValue('C1', 'Grade');

// Fill rows
$rowCount = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue("A{$rowCount}", $row['student_id']);
    $sheet->setCellValue("B{$rowCount}", $row['first_name'] . " " . $row['last_name']);
    $sheet->setCellValue("C{$rowCount}", $row['grade'] ?: 'N/A');
    $rowCount++;
}

// Output file
$filename = "Grades_{$course_code}_{$program}{$year}{$section}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
?>
