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
include('../update_activity.php');

// Assign session variables to local variables for easier use in HTML
$firstName = htmlspecialchars($_SESSION['first_name'] ?? '');
$lastName = htmlspecialchars($_SESSION['last_name'] ?? '');
$status = htmlspecialchars($_SESSION['status'] ?? '');
$user_id = htmlspecialchars($_SESSION['user_id'] ?? '');
$userLevelID = htmlspecialchars($_SESSION['userLevelID'] ?? '');
$picture = htmlspecialchars($_SESSION['picture'] ?? '');
$email = htmlspecialchars($_SESSION['email'] ?? '');
$showProgramModal = false;

$checkProgram = $conn->prepare("SELECT * FROM studprograms WHERE student_id = ?");
$checkProgram->bind_param("s", $user_id);
$checkProgram->execute();
$result = $checkProgram->get_result();

if ($result->num_rows === 0) {
    $showProgramModal = true; // Set the flag to show the modal
}

// Assuming $user_id is the logged-in user
$stmt = $conn->prepare("SELECT unchangedPass FROM login WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$showModal = false;
if ($row && $row['unchangedPass'] == 1) {
    $showModal = true; // User must change password
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | VSU</title>
    <link href="../bootstrap5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="student-dashboard.css">
    <link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../pictures/vsu-icon.ico" type="image/x-icon">
    <script src="jquery-3.7.1.min.js"></script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JC6H6QLLB0"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-JC6H6QLLB0');
    </script>
</head>
<body>
    <script>
    var forceChangePassword = <?php echo $showModal ? 'true' : 'false'; ?>;
</script>

    <!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg topnav fixed-top">
    <div class="container-fluid">
    <a class="navbar-brand text-white fw-bold d-flex align-items-center" href="#">
        <img src="../pictures/vsu-logo-modified.png" alt="VSU Logo" class="me-2" style="height: 1.5em; width: auto;">
        VSU Student Portal
    </a>
        <button class="navbar-toggler" style="background-color: white; color: white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#enrollmentModal">
                        <i class="bi bi-plus-circle-fill me-1"></i> Enroll
                    </a>
                </li>
                <!--
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal" onclick="loadProfileData(<?php //echo $user_id; ?>)">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                    </a>
                </li>
                -->
                <li class="nav-item">
                    <a class="nav-link text-danger" data-bs-toggle="modal" style="cursor: pointer;" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </li>
                <li class="nav-item ms-3">
                    <?php if (!empty($picture)): ?>
                        <img src="<?php echo $picture; ?>" alt="Profile" class="profile-img">
                    <?php else: ?>
                        <div class="initials-avatar">
                            <?php echo strtoupper(substr($firstName, 0, 1) . strtoupper(substr($lastName, 0, 1))); ?>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>


    <!-- Main Content -->
    <div class="content container">
<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <?php
            // Fetch first_name and last_name from users table
            $userQuery = mysqli_query($conn, "SELECT first_name, last_name FROM users WHERE user_id = '$user_id'");
            if ($userQuery && mysqli_num_rows($userQuery) > 0) {
                $userRow = mysqli_fetch_assoc($userQuery);
                $firstName = $userRow['first_name'];
                $lastName = $userRow['last_name'];
            } else {
                // Default values if user not found
                $firstName = "Student";
                $lastName = "";
            }

            // Fetch program info
            $program = mysqli_query($conn, "SELECT program, year, section FROM studprograms WHERE student_id = '$user_id'");

            if(mysqli_num_rows($program) > 0){
                while($row = mysqli_fetch_assoc($program)){
                    $programs = $row['program'];
                    $year = $row['year'];
                    $section = $row['section'];
                ?>
                <h2>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</h2>
                <div style="font-size: 0.9em;
                            color: white;
                            margin-top: -0.5em;
                            letter-spacing: 0.5px;
                            padding: 0.3em 0.8em;">
                    <?php echo htmlspecialchars($programs) . ' <br> ' . htmlspecialchars($year) . htmlspecialchars($section); ?>
                </div>
                <?php
                }
            } else {
                ?>
                <h2>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</h2>
                <?php
            }
            ?>
        </div>
        <div class="col-md-4 text-end">
            <div class="bg-white text-dark p-2 rounded d-inline-block">
                <small class="text-muted">Student ID:</small>
                <strong><?php echo htmlspecialchars($user_id); ?></strong>
            </div>
        </div>
    </div>
</div>


        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Enrolled Courses</h6>
                                <h3 class="card-title mb-0">
                                    <?php $countCourse = mysqli_query($conn, "SELECT COUNT(course_code) AS EnrolledCourses
                                                                                FROM studenrollstatus
                                                                                WHERE student_id = '$user_id'
                                                                                AND (status = 'approved' OR status = 'Archive')
                                                                                GROUP BY student_id;
                                                                                ");
                                            if (mysqli_num_rows($countCourse) > 0){
                                                while ($row = mysqli_fetch_assoc($countCourse)) {
                                                    echo  $row['EnrolledCourses'];
                                                }
                                            }else{
                                                echo "0";
                                            }
                                    ?>
                                </h3>
                            </div>
                            <i class="bi bi-book-fill fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Units</h6>
    <h3 class="card-title mb-0">
    <?php
    $totalUnitQuery = "
        SELECT SUM(courses.unit) AS TotalUnit
        FROM studenrollstatus
        JOIN instructor_courses 
            ON studenrollstatus.course_code = instructor_courses.course_code
            AND studenrollstatus.instructor_id = instructor_courses.instructor_id
        JOIN courses 
            ON courses.course_code = studenrollstatus.course_code AND courses.instructor_id = studenrollstatus.instructor_id
        WHERE studenrollstatus.student_id = ?
          AND (studenrollstatus.status = 'Approved' OR studenrollstatus.status = 'Archive')
    ";

    $stmt = $conn->prepare($totalUnitQuery);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo $row['TotalUnit'] !== null ? $row['TotalUnit'] : '0';
    } else {
        echo '0';
    }
    $stmt->close();
    ?>
</h3>

                            </div>
                            <i class="bi bi-collection-fill fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (isset($_GET['pending'])): ?>
          <div class="alert alert-success">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Success! </div>
              <div class="alert-message"><?= htmlspecialchars($_GET['pending']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Success! </div>
              <div class="alert-message"><?= htmlspecialchars($_GET['success']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>
        


            <?php if (isset($_GET['approved'])): ?>
          <div class="alert alert-success">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Already Approved!</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['approved']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['already_pending'])): ?>
          <div class="alert alert-success">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Already Pending!</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['already_pending']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['warning'])): ?>
          <div class="alert alert-warning">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Error</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['warning']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>

        <!-- Courses Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Courses</h5>
                        <button class="btn btn-sm enroll-btn" data-bs-toggle="modal" data-bs-target="#enrollmentModal">
                            <i class="bi bi-plus-lg me-1"></i> Enroll in New Course
                        </button>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="first-sem-tab" data-bs-toggle="tab" data-bs-target="#first-sem" type="button" role="tab">First Semester</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="second-sem-tab" data-bs-toggle="tab" data-bs-target="#second-sem" type="button" role="tab">Second Semester</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="myTabContent">
                            <div class="tab-pane fade show active" id="first-sem" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Title</th>
                                                <th>Instructor</th>
                                                <th>Unit</th>
                                                <th>Grade</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                       <?php 
$query_first = "
    SELECT DISTINCT
        courses.course_code,
        instructor_courses.course_title,
        instructor_courses.semester,
        courses.unit,
        grades.grades,
        users.first_name AS instructor_first_name,
        users.last_name AS instructor_last_name,
        studenrollstatus.status
    FROM grades
    JOIN courses 
        ON grades.course_code = courses.course_code AND grades.instructor_id = courses.instructor_id
    JOIN instructor_courses 
        ON grades.course_code = instructor_courses.course_code
        AND grades.instructor_id = instructor_courses.instructor_id
    JOIN users 
        ON grades.instructor_id = users.user_id 
        AND users.userLevelID = '2'
    JOIN studenrollstatus 
        ON grades.course_code = studenrollstatus.course_code
        AND grades.student_id = studenrollstatus.student_id
        AND grades.instructor_id = studenrollstatus.instructor_id
    WHERE 
        grades.student_id = '$user_id'
        AND studenrollstatus.status IN ('Approved', 'Archive')
        AND studenrollstatus.semester = 'First Semester'
        AND instructor_courses.semester = 'First Semester'
";
$selectQuery_first = mysqli_query($conn, $query_first);

if (mysqli_num_rows($selectQuery_first) > 0) {
    while ($row = mysqli_fetch_assoc($selectQuery_first)) {
?>
        <tr>
            <td><?php echo $row['course_code']; ?></td>
            <td><?php echo $row['course_title']; ?></td>
            <td><?php echo $row['instructor_first_name'] . ' ' . $row['instructor_last_name']; ?></td>
            <td><?php echo $row['unit']; ?></td>
            <td>
                <?php
                $grade = $row['grades'];
                $gradeUpper = strtoupper(trim($grade));
                
                // Check for incomplete status
                $isIncomplete = in_array($gradeUpper, ['INC', 'INCOMPLETE', 'INCOMPLETE', 'I', 'NG']);
                
                // Determine badge color based on grade range
                if ($isIncomplete) {
                    $badgeColor = 'danger';
                    $displayGrade = 'INC';
                } elseif (empty($grade) || $grade == 'N/A' || $grade == '') {
                    $badgeColor = 'secondary';
                    $displayGrade = 'N/A';
                } elseif (is_numeric($grade)) {
                    $gradeValue = (float)$grade;
                    
                    if ($gradeValue >= 1.00 && $gradeValue <= 2.00) {
                        $badgeColor = 'success'; // Green
                    } elseif ($gradeValue >= 2.01 && $gradeValue <= 3.00) {
                        $badgeColor = 'warning'; // Yellow
                    } elseif ($gradeValue >= 3.01 && $gradeValue <= 5.00) {
                        $badgeColor = 'danger'; // Red
                    } else {
                        $badgeColor = 'secondary'; // Gray for other values
                    }
                    $displayGrade = number_format($gradeValue, 2);
                } else {
                    $badgeColor = 'secondary';
                    $displayGrade = htmlspecialchars($grade);
                }
                
                // Output the badge
                echo '<span class="badge bg-' . $badgeColor . '">' . $displayGrade . '</span>';
                ?>
            </td>
        </tr>
<?php
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No courses enrolled for the first semester.</td></tr>";
}
?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="second-sem" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Title</th>
                                                <th>Instructor</th>
                                                <th>Units</th>
                                                <th>Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                     <?php 
$query_second = "
    SELECT DISTINCT
        courses.course_code,
        instructor_courses.course_title,
        instructor_courses.semester,
        courses.unit,
        grades.grades,
        users.first_name AS instructor_first_name,
        users.last_name AS instructor_last_name,
        studenrollstatus.status
    FROM grades
    JOIN courses 
        ON grades.course_code = courses.course_code
    JOIN instructor_courses 
        ON grades.course_code = instructor_courses.course_code
        AND grades.instructor_id = instructor_courses.instructor_id
    JOIN users 
        ON grades.instructor_id = users.user_id 
        AND users.userLevelID = '2'
    JOIN studenrollstatus 
        ON grades.course_code = studenrollstatus.course_code
        AND grades.student_id = studenrollstatus.student_id
        AND grades.instructor_id = studenrollstatus.instructor_id
    WHERE 
        grades.student_id = '$user_id'
        AND studenrollstatus.status IN ('Approved', 'Archive')
        AND studenrollstatus.semester = 'Second Semester'
        AND instructor_courses.semester = 'Second Semester'
";
$selectQuery_second = mysqli_query($conn, $query_second);

if (mysqli_num_rows($selectQuery_second) > 0) {
    while ($row = mysqli_fetch_assoc($selectQuery_second)) {
?>
        <tr>
            <td><?php echo $row['course_code']; ?></td>
            <td><?php echo $row['course_title']; ?></td>
            <td><?php echo $row['instructor_first_name'] . ' ' . $row['instructor_last_name']; ?></td>
            <td><?php echo $row['unit']; ?></td>
            <td>
                <?php
                $grade = $row['grades'];
                $gradeUpper = strtoupper(trim($grade));
                
                // Check for incomplete status
                $isIncomplete = in_array($gradeUpper, ['INC', 'INCOMPLETE', 'INCOMPLETE', 'I', 'NG']);
                
                // Determine badge color based on grade range
                if ($isIncomplete) {
                    $badgeColor = 'danger';
                    $displayGrade = 'INC';
                } elseif (empty($grade) || $grade == 'N/A' || $grade == '') {
                    $badgeColor = 'secondary';
                    $displayGrade = 'N/A';
                } elseif (is_numeric($grade)) {
                    $gradeValue = (float)$grade;
                    
                    if ($gradeValue >= 1.00 && $gradeValue <= 2.00) {
                        $badgeColor = 'success'; // Green
                    } elseif ($gradeValue >= 2.01 && $gradeValue <= 3.00) {
                        $badgeColor = 'warning'; // Yellow
                    } elseif ($gradeValue >= 3.01 && $gradeValue <= 5.00) {
                        $badgeColor = 'danger'; // Red
                    } else {
                        $badgeColor = 'secondary'; // Gray for other values
                    }
                    $displayGrade = number_format($gradeValue, 2);
                } else {
                    $badgeColor = 'secondary';
                    $displayGrade = htmlspecialchars($grade);
                }
                
                // OUTPUT THE BADGE - THIS WAS MISSING
                echo '<span class="badge bg-' . $badgeColor . '">' . $displayGrade . '</span>';
                ?>
            </td>
        </tr>
<?php
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No courses enrolled for the second semester.</td></tr>";
}
?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <div class="row">
            <!-- First Column -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="d-flex align-items-center">
                    <img src="../pictures/vsu-logo.jpg" alt="VSUA Logo" style="height: 24px; width: auto; margin-right: 8px;">
                    VSUA Student Grading Portal
                </h5>
                <p>Your gateway to academic success and university resources.</p>
                
                <!-- Sponsored By Section -->
                <div class="sponsored-by mt-3">
                    <h6>Sponsored by:</h6>
                    <div class="sponsor-item">
                        <a href="https://www.facebook.com/profile.php?id=61576826220605" target="_blank" class="text-white">
                            Faculty of Computing-Supreme Student Council (FC-SSC)
                        </a>
                    </div>
                    <div class="sponsor-item">
                        <a href="https://www.facebook.com/vsua.bsit.studentorg" target="_blank" class="text-white">
                            Guild of Unified Information Technology Students (GUTS)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Second Column -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Development Team</h5>
                <ul class="list-unstyled developer-credits">
                    <li><a href="https://www.facebook.com/markflorence.sabinator.9" target="_blank">Mark Florence Sabinator</a></li>
                    <li><a href="https://www.facebook.com/patriciovier.royo.7" target="_blank">Patricio Vier Royo</a></li>
                    <li><a href="https://www.facebook.com/hnnahhqt" target="_blank">Hannah Florence Villafuerte</a></li>
                    <li><a href="https://www.facebook.com/graczielle.hans.conge.2025" target="_blank">Graczielle Hans Conge</a></li>
                    <li><a href="https://www.facebook.com/christianjimesponilla" target="_blank">Christian Jim Cuenca</a></li>
                    <li><a href="https://www.facebook.com/lovikiee" target="_blank">Orfinada, Christine Joy</a></li>
                    <li><a href="https://www.facebook.com/Dennisbarangan01" target="_blank">Dennis Barangan Jr.</a></li>
                </ul>
            </div>

            <!-- Third Column -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Advisers</h5>
                <ul class="list-unstyled developer-credits">
                    <li><a href="https://www.facebook.com/carlojudepabuda" target="_blank">Carlo Jude Abuda (Dept. Head)</a></li>
                    <li><a href="https://www.facebook.com/rollur88" target="_blank">Ronnie Luriaga</a></li>
                    <li><a href="https://www.facebook.com/Kentucky07" target="_blank">Kent Claire Apple Joy Pallomina</a></li>
                    <li><a href="https://www.facebook.com/annmasana" target="_blank">Hannah Lyn Masana</a></li>
                    <li><a href="https://www.facebook.com/kim.patrick.sangrano" target="_blank">Kim Patrick Sangrano</a></li>
                    <li><a href="https://www.facebook.com/antonyms.x3" target="_blank">Antonino Macabacyao</a></li>
                </ul>
            </div>

            <!-- Fourth Column -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=vsualangalang.dit.projects@vsu.edu.ph" target="_blank" class="text-white">
                            <i class="bi bi-envelope-fill me-2"></i> student.support@vsu.edu.ph
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="tel:+639928608062" class="text-white">
                            <i class="bi bi-telephone-fill me-2"></i> (053) 565 0600
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <hr class="border-light">
        <div class="text-center pt-3">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> VSUA Department of Information Technology. All rights reserved.</p>
        </div>
    </div>
</footer>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to log out of your account?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="../logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div class="modal fade" id="enrollmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll in a Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
               <?php if (isset($_GET['not_exist'])): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Error! </div>
                        <div class="alert-message"><?= htmlspecialchars($_GET['not_exist']) ?></div>
                    </div>
                    <div class="alert-close">&times;</div>
                </div>
            <?php endif; ?>
                <form id="enrollmentForm" method="POST" action="enroll.php">
                    <div class="modal-body">
                    <input type="hidden" class="form-control"  name="user_id" value="<?php echo $user_id?>" required>
                        <div class="mb-3">
                            <label for="courseCode" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="courseCode" name="courseCode" required>
                        </div>
                        <div class="mb-3">
                            <label for="identification" class="form-label">Identification</label>
                            <input type="text" class="form-control" id="identification" name="identification" required>
                            <small class="text-muted">Provided by your instructor</small>
                        </div>
                        <div class="mb-3">
                            <label for="enrollmentKey" class="form-label">Enrollment Key</label>
                            <input type="input" class="form-control" id="enrollmentKey" name="enrollmentKey" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Enroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<div id="changePasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center; font-family:system-ui, -apple-system, sans-serif;">
    <div style="background:#ffffff; padding:35px; border-radius:12px; max-width:420px; width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.15); border:2px solid #1a2c1a;">
        <h2 style="color:#1a2c1a; margin-top:0; margin-bottom:15px; font-size:24px; font-weight:600;">Change Your Password</h2>
        <p style="color:#2d4a2d; margin-bottom:25px; font-size:15px; line-height:1.5;">For security reasons, you must change your temporary password before continuing.</p>
        
        <form id="changePasswordForm" action="change_password.php" method="POST">
            <div style="margin-bottom:20px;">
                <input type="password" id="newPassword" placeholder="New Password" required 
                       style="width:100%; padding:12px 15px; border-radius:6px; border:2px solid #1a2c1a; 
                              background:#ffffff; color:#1a2c1a; font-size:15px; box-sizing:border-box;
                              outline:none; transition:all 0.3s; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                    <div style="flex:1; height:6px; background:#f0f0f0; border-radius:3px; overflow:hidden;">
                        <div id="passwordStrength" style="height:100%; width:0%; background:#e74c3c; transition:width 0.3s, background 0.3s;"></div>
                    </div>
                    <span id="strengthLevel" style="color:#1a2c1a; font-size:13px; font-weight:500; min-width:70px;"></span>
                </div>
                <div style="font-size:12px; color:#666; text-align:left;">
                    <span id="strengthText"></span>
                </div>
            </div>
            
            <div style="margin-bottom:25px;">
                <input type="password" id="confirmPassword" name="password" placeholder="Confirm Password" required 
                       style="width:100%; padding:12px 15px; border-radius:6px; border:2px solid #1a2c1a; 
                              background:#ffffff; color:#1a2c1a; font-size:15px; box-sizing:border-box;
                              outline:none; transition:border-color 0.3s;">
            </div>
            <input type="hidden" name="user_id" value="<?php echo $user_id?>">
            
            <button type="submit" name="submit"
                    style="width:100%; padding:14px; background:darkGreen; color:white; border:none; 
                           border-radius:6px; font-size:16px; font-weight:600; cursor:pointer;
                           transition:background 0.3s; border:2px solid #1a2c1a;">
                Change Password
            </button>
        </form>
    </div>
</div>

    <!-- Program Info Modal -->
    <div class="modal fade" id="programModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Complete Your Profile</h5>
                </div>
                <form method="POST" action="save_program.php">
                    <div class="modal-body">
                        <p class="mb-4">Please provide your academic information to continue:</p>
                        <div class="mb-3">
                            <label for="program" class="form-label">Program</label>
                            <select class="form-select" id="program" name="program" required>
                                <option value="">Select Program</option>
                                <option value="Bachelor of Elementary Education ">Bachelor of Elementary Education </option>
                                <option value="BSEd Major in Mathematics ">BSEd Major in Mathematics </option>
                                <option value="BSEd Major in Science">BSEd Major in Science</option>
                                <option value="BS in Information Technology">BS in Information Technology</option>
                                <option value="BS in Agriculture">BS in Agriculture</option>
                                <option value="BS in Environmental Science">BS in Environmental Science</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year Level</label>
                            <select class="form-select" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Year Level</label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="">Select Section</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name= "submit" class="btn btn-primary">Save and Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
 
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <?php if (isset($_GET['alreadyTaken'])): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Error!</div>
                        <div class="alert-message"><?= htmlspecialchars($_GET['alreadyTaken']) ?></div>
                    </div>
                    <div class="alert-close">&times;</div>
                </div>
            <?php endif; ?>
            <div class="modal-body">
                <form id="editProfileForm" method="post" action="update_profile.php" enctype="multipart/form-data">
                    
                    <input type="hidden" name="email" value="<?php echo $email;?>">
                    <div class="mb-3">
                        <label for="edit_student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="edit_student_id" name="student_id" value="<?php echo $user_id;?>" disabled>
                        
                    </div>
                    
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" value="<?php echo $firstName;?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" value="<?php echo $lastName;?>" required>
                        </div>
                    
                   


                    <div class="mb-3">
                        <label for="edit_confirm_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit_confirm_password" name="password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="feedback1"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button"  class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" id="saveChangesBtn" form="editProfileForm" name="save-changes" class="btn btn-success">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
function ping() {
    $.post("../update_activity.php");
}

let heartbeatInterval;

function startHeartbeat() {
    heartbeatInterval = setInterval(ping, 30000);
}

function stopHeartbeat() {
    clearInterval(heartbeatInterval);
}

document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
        stopHeartbeat();
    } else {
        ping();
        startHeartbeat();
    }
});

// Start heartbeat immediately
ping();
startHeartbeat();

window.addEventListener("beforeunload", function() {
    navigator.sendBeacon("../update_activity.php?logout=1");
});


</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <script src="../bootstrap5.3.3/js/bootstrap.bundle.min.js"></script>
    <?php if ($showProgramModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var programModal = new bootstrap.Modal(document.getElementById('programModal'));
            programModal.show();
        });
    </script>
    <?php endif; ?>
</body>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add smooth transitions to alerts
        document.querySelectorAll('.alert').forEach(alert => {
            alert.classList.add('alert-enter');
        });
        
        // Alert close functionality with smooth transition
        document.querySelectorAll('.alert-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                const alert = this.closest('.alert');
                alert.classList.remove('alert-enter');
                alert.classList.add('alert-exit');
                setTimeout(() => alert.remove(), 400);
            });
        });
        
        // Auto-dismiss alerts after 5 seconds with smooth transition
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.classList.remove('alert-enter');
                alert.classList.add('alert-exit');
                setTimeout(() => alert.remove(), 400);
            });
        }, 5000);
        
        // Add hover classes to cards
        document.querySelectorAll('.card').forEach(card => {
            card.classList.add('smooth-transition', 'card-hover');
        });
        
        // Add fade-in effect to tables
        document.querySelectorAll('.table-responsive').forEach(table => {
            table.classList.add('fade-in');
        });
        
        // Add smooth transition to nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.add('smooth-transition');
        });
    });
    
    // Tab change animation
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            target.classList.add('fade-in');
        });
    });
 

</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('alreadyTaken')) {
      const editModal = new bootstrap.Modal(document.getElementById('editProfileModal'));
      editModal.show();
    }

    if (urlParams.has('not_exist')) {
      const enrollmentModal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
      enrollmentModal.show();
    }
  });

  // Optional: Alert close behavior
  document.addEventListener("click", function (e) {
    if (e.target.closest(".alert-close")) {
      e.target.closest(".alert").remove();
    }
  });
  
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($showModal): ?>
    // Only show modal if PHP indicates user must change password
    var modal = document.getElementById("changePasswordModal");
    modal.style.display = "flex";
    
    // Prevent closing the modal - user must change password
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Also prevent escape key from closing
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            e.preventDefault();
        }
    });
    <?php endif; ?>
    
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordStrengthBar = document.getElementById('passwordStrength');
    const strengthLevelText = document.getElementById('strengthLevel');
    const strengthText = document.getElementById('strengthText');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const submitButton = changePasswordForm.querySelector('button[type="submit"]');
    
    // Initially disable the button
    submitButton.disabled = true;
    submitButton.style.opacity = '0.6';
    submitButton.style.cursor = 'not-allowed';
    
    // Create validation message elements
    const createValidationMessages = () => {
        // Create message container for new password
        const newPasswordContainer = newPasswordInput.parentElement;
        if (!document.getElementById('newPasswordMessage')) {
            const newPasswordMessage = document.createElement('div');
            newPasswordMessage.id = 'newPasswordMessage';
            newPasswordMessage.style.fontSize = '12px';
            newPasswordMessage.style.marginTop = '5px';
            newPasswordMessage.style.minHeight = '18px';
            newPasswordContainer.appendChild(newPasswordMessage);
        }
        
        // Create message container for confirm password
        const confirmPasswordContainer = confirmPasswordInput.parentElement;
        if (!document.getElementById('confirmPasswordMessage')) {
            const confirmPasswordMessage = document.createElement('div');
            confirmPasswordMessage.id = 'confirmPasswordMessage';
            confirmPasswordMessage.style.fontSize = '12px';
            confirmPasswordMessage.style.marginTop = '5px';
            confirmPasswordMessage.style.minHeight = '18px';
            confirmPasswordContainer.appendChild(confirmPasswordMessage);
        }
    };
    
    // Initialize validation messages
    createValidationMessages();
    
    // Get message elements
    const newPasswordMessage = document.getElementById('newPasswordMessage');
    const confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
    
    // Check if passwords match and show validation message
    function checkPasswordsMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            confirmPasswordInput.style.borderColor = '#1a2c1a';
            if (confirmPasswordMessage) {
                confirmPasswordMessage.textContent = '';
                confirmPasswordMessage.style.color = '';
            }
            return false;
        }
        
        if (newPassword === confirmPassword) {
            confirmPasswordInput.style.borderColor = '#27ae60';
            if (confirmPasswordMessage) {
                confirmPasswordMessage.textContent = '✓ Passwords match';
                confirmPasswordMessage.style.color = '#27ae60';
            }
            return true;
        } else {
            confirmPasswordInput.style.borderColor = '#e74c3c';
            if (confirmPasswordMessage) {
                confirmPasswordMessage.textContent = '✗ Passwords do not match';
                confirmPasswordMessage.style.color = '#e74c3c';
            }
            return false;
        }
    }
    
    // Calculate password strength
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Character variety checks
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return Math.min(strength, 5);
    }
    
    // Update password strength indicator
    function updatePasswordStrength() {
        const password = newPasswordInput.value;
        const strength = calculatePasswordStrength(password);
        
        const percentage = (strength / 5) * 100;
        passwordStrengthBar.style.width = `${percentage}%`;
        
        let levelText = '';
        let barColor = '';
        let textColor = '';
        
        if (password.length === 0) {
            levelText = '';
            barColor = '#e74c3c';
            textColor = '#666';
            newPasswordInput.style.borderColor = '#1a2c1a';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '';
                newPasswordMessage.style.color = '';
            }
        } else if (strength <= 1) {
            levelText = 'Very Weak';
            barColor = '#e74c3c';
            textColor = '#e74c3c';
            newPasswordInput.style.borderColor = '#e74c3c';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '✗ Use at least 8 characters with variety';
                newPasswordMessage.style.color = '#e74c3c';
            }
        } else if (strength === 2) {
            levelText = 'Weak';
            barColor = '#e67e22';
            textColor = '#e67e22';
            newPasswordInput.style.borderColor = '#e67e22';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '⚠️ Add more character types';
                newPasswordMessage.style.color = '#e67e22';
            }
        } else if (strength === 3) {
            levelText = 'Fair';
            barColor = '#f1c40f';
            textColor = '#f1c40f';
            newPasswordInput.style.borderColor = '#f1c40f';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '⚠️ Add one more character type';
                newPasswordMessage.style.color = '#f1c40f';
            }
        } else if (strength === 4) {
            levelText = 'Good';
            barColor = '#2ecc71';
            textColor = '#2ecc71';
            newPasswordInput.style.borderColor = '#2ecc71';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '✓ Good password strength';
                newPasswordMessage.style.color = '#2ecc71';
            }
        } else if (strength === 5) {
            levelText = 'Strong';
            barColor = '#27ae60';
            textColor = '#27ae60';
            newPasswordInput.style.borderColor = '#27ae60';
            if (newPasswordMessage) {
                newPasswordMessage.textContent = '✓ Excellent password strength!';
                newPasswordMessage.style.color = '#27ae60';
            }
        }
        
        passwordStrengthBar.style.background = barColor;
        strengthLevelText.textContent = levelText;
        strengthLevelText.style.color = textColor;
        
        if (password.length === 0) {
            strengthText.textContent = 'Password strength indicator';
            strengthText.style.color = '#666';
        } else {
            strengthText.textContent = 'Include uppercase, numbers & symbols';
            strengthText.style.color = textColor;
        }
        
        return strength;
    }
    
    // Validate form and enable/disable button
    function validateForm() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        const strength = calculatePasswordStrength(newPassword);
        const isPasswordStrong = strength >= 4;
        const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;
        
        if (isPasswordStrong && passwordsMatch) {
            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            submitButton.style.background = '#1a2c1a';
            submitButton.style.color = 'white';
        } else {
            submitButton.disabled = true;
            submitButton.style.opacity = '0.6';
            submitButton.style.cursor = 'not-allowed';
            submitButton.style.background = '#f4d03f';
            submitButton.style.color = '#1a2c1a';
        }
    }
    
    // Event listeners
    if (newPasswordInput && confirmPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            updatePasswordStrength();
            checkPasswordsMatch();
            validateForm();
        });
        
        confirmPasswordInput.addEventListener('input', function() {
            checkPasswordsMatch();
            validateForm();
        });
        
        // Form submission handler
        changePasswordForm.addEventListener('submit', function(event) {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            const strength = calculatePasswordStrength(newPassword);
            const isPasswordStrong = strength >= 4;
            const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;
            
            if (!isPasswordStrong || !passwordsMatch) {
                event.preventDefault();
                updatePasswordStrength();
                checkPasswordsMatch();
                validateForm();
            }
            // If valid, form will submit via POST to change_password.php
        });
    }
    
    // Initial validation
    if (newPasswordInput) {
        updatePasswordStrength();
        checkPasswordsMatch();
        validateForm();
    }
});
</script>

</html>
