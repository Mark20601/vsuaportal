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

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['save-changes'])) {
    $current_user_id = $_SESSION['user_id'];

    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $password_raw = $_POST['password'];
    $password_hashed = !empty($password_raw) ? hash('sha256', $password_raw) : null;

    $conn->begin_transaction();

    try {
        // 1. Update `users` table
        $updateUsers = "UPDATE users SET
            first_name = '$firstName',
            last_name = '$last_name'
            WHERE user_id = '$current_user_id'";
        if (!$conn->query($updateUsers)) {
            throw new Exception("Failed to update users: " . $conn->error);
        }

        // 2. Update `login` table (only password if changed)
        if (!empty($password_hashed)) {
            $updateLogin = "UPDATE login SET password = '$password_hashed'
                            WHERE user_id = '$current_user_id'";
            if (!$conn->query($updateLogin)) {
                throw new Exception("Failed to update login: " . $conn->error);
            }
        }

        // 3. Update `studprograms` table
        $updateStudProgram = "UPDATE studprograms SET
            program = '".mysqli_real_escape_string($conn, $_POST['program'])."',
            year = '".mysqli_real_escape_string($conn, $_POST['year'])."',
            section = '".mysqli_real_escape_string($conn, $_POST['section'])."'
            WHERE student_id = '$current_user_id'";
        if (!$conn->query($updateStudProgram)) {
            throw new Exception("Failed to update studprograms: " . $conn->error);
        }

        // 4. Update `studenrollstatus` table if exists
        $checkEnroll = "SELECT * FROM studenrollstatus WHERE student_id = '$current_user_id'";
        $resultEnroll = $conn->query($checkEnroll);
        if ($resultEnroll->num_rows > 0) {
            $updateEnrollStatus = "UPDATE studenrollstatus SET
                                   status = '".mysqli_real_escape_string($conn, $_POST['enroll_status'])."'
                                   WHERE student_id = '$current_user_id'";
            if (!$conn->query($updateEnrollStatus)) {
                throw new Exception("Failed to update studenrollstatus: " . $conn->error);
            }
        }

        // 5. Update `grades` table if exists
        $checkGrades = "SELECT * FROM grades WHERE student_id = '$current_user_id'";
        $resultGrades = $conn->query($checkGrades);
        if ($resultGrades->num_rows > 0) {
            // Example: only updating a field if needed, adjust according to your schema
            $updateGrades = "UPDATE grades SET
                             remarks = '".mysqli_real_escape_string($conn, $_POST['grade_remarks'])."'
                             WHERE student_id = '$current_user_id'";
            if (!$conn->query($updateGrades)) {
                throw new Exception("Failed to update grades: " . $conn->error);
            }
        }

        // Commit transaction
        $conn->commit();

        // Update session names
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $last_name;

        header("Location: student-dashboard.php?success=Profile updated successfully!");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Transaction failed: " . $e->getMessage());
    }
}
?>
