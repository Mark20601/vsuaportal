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
include('../update_activity.php');
// Assign session variables to local variables for easier use in HTML
$firstName = htmlspecialchars($_SESSION['first_name'] ?? '');
$lastName = htmlspecialchars($_SESSION['last_name'] ?? '');
$status = htmlspecialchars($_SESSION['status'] ?? '');
$user_id = htmlspecialchars($_SESSION['user_id'] ?? '');
$picture = htmlspecialchars($_SESSION['picture'] ?? '');
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

// Get URL parameters
$program = $_GET['program'] ?? '';
$year = $_GET['year'] ?? '';
$section = $_GET['section'] ?? '';
$course_code = $_GET['course_code'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Student Grades - Instructor Portal</title>
    <link href="bootstrap/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="bootstrap/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../pictures/vsu-icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/student.css">

  
</head>
<body>
    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" onclick="closeNav()"></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar" id="mySidenav">
            <div class="sidebar-header p-4">
                <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-chalkboard-teacher me-2 fs-4" style="color: var(--corn-yellow);"></i>
                    <h4 class="mb-0" style="color: white;">Instructor Portal</h4>
                </div>
                <div class="profile-container d-flex align-items-center">
                    <div class="profile-pic-container me-3">
                        <?php if (!empty($_SESSION['picture'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['picture']); ?>" 
                                 alt="Profile Picture" 
                                 class="profile-pic">
                        <?php else: ?>
                            <div class="profile-initials">
                                <?php echo strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-white"><?php echo $firstName . ' ' . $lastName; ?></h6>
                        <small class="text-light"><?php echo ucfirst($status); ?> Instructor</small>
                        <small class="text-warning d-block mt-1 fw-medium">
                            <?php 
                            $departmentQuery = "
                                SELECT department 
                                FROM dept_table  
                                WHERE instructor_id = '$user_id'
                                LIMIT 1
                            ";
                            
                            $departmentResult = mysqli_query($conn, $departmentQuery);
                            
                            $department = "";
                            if ($departmentResult && mysqli_num_rows($departmentResult) > 0) {
                                $row = mysqli_fetch_assoc($departmentResult);
                                $department = $row['department'];
                                echo $department;
                            }
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            <ul class="nav flex-column px-3 py-4">
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" href="dashboard.php">
                        <i class="fas fa-home me-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" href="profile.php">
                        <i class="fas fa-user me-3"></i>
                        <span>My Account</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" href="courses.php">
                        <i class="fas fa-book me-3"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" 
                       data-bs-toggle="collapse" 
                       href="#studentsCollapse" 
                       role="button" 
                       aria-expanded="false" 
                       aria-controls="studentsCollapse">
                        <i class="fas fa-users me-3"></i>
                        <span>Students</span>
                        <i class="fas fa-chevron-down ms-2 small"></i>
                    </a>
                    <div class="collapse ps-4" id="studentsCollapse">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" href="pending.php">
                                    <i class="fas fa-clock me-3"></i>
                                    <span>Pending</span>
                                    <span class="badge bg-warning ms-auto">
                                        <?php
                                            $countPendingQuery = mysqli_query($conn, "SELECT COUNT(status) AS Pending FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id' ");
                                            if (mysqli_num_rows($countPendingQuery)) {
                                                $row = mysqli_fetch_assoc($countPendingQuery);
                                                echo $row['Pending'];
                                            }
                                        ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2" href="accepted.php">
                                    <i class="fas fa-check-circle me-3"></i>
                                    <span>Accepted</span>
                                    <span class="badge bg-success ms-auto">
                                        <?php
                                            $countApprovedQuery = mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) AS Accepted 
                                                                            FROM studenrollstatus 
                                                                            WHERE status = 'approved' 
                                                                            AND instructor_id = '$user_id' ");
                                            if (mysqli_num_rows($countApprovedQuery)) {
                                                $row = mysqli_fetch_assoc($countApprovedQuery);
                                                echo $row['Accepted'];
                                            }
                                        ?>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php
                // Get current URL parameters for active state
                $currentProgram = $_GET['program'] ?? '';
                $currentYear = $_GET['year'] ?? '';
                $currentSection = $_GET['section'] ?? '';
                $currentCourseCode = $_GET['course_code'] ?? '';

                // Get all active courses taught by the instructor
                $course_query = mysqli_query($conn, "
                    SELECT ic.course_code
                    FROM instructor_courses ic
                    JOIN studenrollstatus se ON ic.course_code = se.course_code AND ic.instructor_id = se.instructor_id
                    WHERE ic.instructor_id = '$user_id'
                      AND ic.status = 'Active'
                      AND se.status = 'Approved'
                    GROUP BY ic.course_code
                    HAVING COUNT(se.student_id) >= 1
                    ORDER BY ic.course_code
                ");

                $courseCounter = 0;
                if (mysqli_num_rows($course_query) > 0) {
                    while ($course = mysqli_fetch_assoc($course_query)) {
                        $courseCode = $course['course_code'];
                        $uniqueId = "collapseCourse" . $courseCounter;
                        $isCourseActive = false;

                        // Get groups for this course
                        $groups = [];
                        $group_query = mysqli_query($conn, "
                            SELECT DISTINCT sp.program_abbr, sp.program, sp.year, sp.section
                            FROM studprograms sp
                            JOIN studenrollstatus s ON sp.student_id = s.student_id
                            WHERE s.instructor_id = '$user_id'
                              AND s.course_code = '$courseCode'
                              AND s.status = 'Approved'
                            ORDER BY sp.program, sp.year, sp.section
                        ");
                        
                        while ($group = mysqli_fetch_assoc($group_query)) {
                            $groups[] = $group;
                            if (
                                $courseCode == $currentCourseCode &&
                                $group['program'] == $currentProgram &&
                                $group['year'] == $currentYear &&
                                $group['section'] == $currentSection
                            ) {
                                $isCourseActive = true;
                            }
                        }
                ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?php echo $isCourseActive ? 'active' : ''; ?>"
                       data-bs-toggle="collapse"
                       href="#<?php echo $uniqueId; ?>"
                       role="button"
                       aria-expanded="<?php echo $isCourseActive ? 'true' : 'false'; ?>"
                       aria-controls="<?php echo $uniqueId; ?>">
                        <i class="fas fa-book me-3"></i>
                        <span><?php echo $courseCode; ?></span>
                        <i class="fas fa-chevron-down ms-2 small"></i>
                    </a>
                    <div class="collapse ps-4 <?php if ($isCourseActive) echo 'show'; ?>" id="<?php echo $uniqueId; ?>">
                        <ul class="nav flex-column">
                            <?php foreach ($groups as $group): ?>
                                <?php
                                $isActive = (
                                    $courseCode == $currentCourseCode &&
                                    $group['program'] == $currentProgram &&
                                    $group['year'] == $currentYear &&
                                    $group['section'] == $currentSection
                                );
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?php echo $isActive ? 'active' : ''; ?>"
                                       href="student.php?program=<?php echo urlencode($group['program']); ?>&year=<?php echo urlencode($group['year']); ?>&section=<?php echo urlencode($group['section']); ?>&course_code=<?php echo urlencode($courseCode); ?>">
                                        <i class="fas fa-users me-3"></i>
                                        <span class="text-start"><?php echo $group['program_abbr'] . " " . $group['year']  . $group['section']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
                <?php
                        $courseCounter++;
                    }
                }
                ?>
                <li class="nav-item mt-4 pt-3 border-top">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 text-warning" data-bs-toggle="modal" style="cursor: pointer; hover: red;" data-bs-target="#logoutModal">
                        <i class="fas fa-sign-out-alt me-3"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div id="main-content" class="flex-grow-1">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 animate-fade-in">
                <div class="d-flex align-items-center gap-3">
                    <span id="menu" onclick="openNav()">&#9776;</span>
                    <img src="../pictures/Logo-A-removebg-preview.png" alt="Logo" style="width: 200px; height: auto;">
                </div>
                <div class="text-end">
                    <h1 class="h3 mb-1" style="color: var(--dark-green);">Hello, <?php echo $firstName?></h1>
                    <span class="badge bg-light text-dark border py-1 px-3">
                        <i class="fas fa-calendar-alt me-1"></i>2025-2026
                    </span>
                </div>
            </div>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in">
                <div>
                    <h4 class="mb-0 fw-bold" style="color: var(--dark-green);">
                        <?php echo $program ?> <?php echo $year ?> <?php echo $section ?>
                    </h4>
                    <p class="text-muted small mb-0"><?php echo $course_code ?> - Student Grades Management</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="card border-0 shadow-sm mb-4 animate-fade-in">
                <div class="card-body p-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="searchBox" class="form-control search-box border-start-0 ps-0" 
                               placeholder="Search student by name or ID..." 
                               onkeyup="liveSearch()">
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="table-container animate-fade-in">

                <div class="p-4">
                    <?php 
                    $query = "SELECT DISTINCT
                                u.user_id AS User_ID,
                                u.last_name AS 'Last Name',
                                u.first_name AS 'First Name',
                                g.grades AS Grades,
                                sp.program AS Programs,
                                sp.year AS Year,
                                sp.section AS Section,
                                ic.course_code AS 'Course Code',
                                ic.course_title AS 'Course Title',
                                ic.semester AS Semester
                              FROM users u
                              JOIN studprograms sp ON u.user_id = sp.student_id 
                              JOIN studenrollstatus ses ON sp.student_id = ses.student_id 
                              JOIN instructor_courses ic ON ses.course_code = ic.course_code 
                                                        AND ses.instructor_id = ic.instructor_id
                                                        AND ic.course_code = '$course_code'
                                                        AND ic.status = 'Active'
                              LEFT JOIN grades g ON ses.student_id = g.student_id 
                                                AND ses.course_code = g.course_code 
                                                AND ses.instructor_id = g.instructor_id
                              WHERE sp.program = '$program'
                                AND sp.year = '$year'
                                AND sp.section = '$section'
                                AND ses.instructor_id = '$user_id'
                                AND ses.status = 'Approved'
                              ORDER BY u.last_name, u.first_name";

                    $selectQuery = mysqli_query($conn, $query);

                    if(mysqli_num_rows($selectQuery) > 0): 
                    ?>
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <!-- Title Section -->
                        <div class="mb-3 mb-md-0">
                            <h5 class="mb-1 fw-semibold text-dark">
                                <i class="fas fa-graduation-cap me-2 text-success"></i>
                                Student Grades
                            </h5>
                        </div>
                        
                        <!-- Action Buttons - Side by Side -->
                        <div class="d-flex flex-wrap gap-3">
                            <!-- Export Button -->
                            <form action="export_grades_excel.php" method="POST" class="mb-0">
                                <input type="hidden" name="program" value="<?php echo htmlspecialchars($program); ?>">
                                <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                                <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                                <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course_code); ?>">
                                <input type="hidden" name="instructor_id" value="<?php echo htmlspecialchars($user_id); ?>">
                                
                                <button type="submit" class="btn btn-success d-flex align-items-center position-relative">
                                    <i class="fas fa-file-excel fa-lg me-2"></i>
                                    <span>Export Records to Excel <i class="fas fa-upload fa-xs" 
                                   style="top: 5px; right: 5px; background: #ffffff; color: #198754; padding: 2px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1);"></i></span>
                                 
                                    
                                </button>
                            </form>
                            
                            <!-- Import Button -->
                            <button class="btn btn-primary d-flex align-items-center position-relative" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-file-excel fa-lg me-2"></i>
                                <span>Import Grades from Excel <i class="fas fa-download fa-xs" 
                                       style="top: 5px; right: 5px; background: #ffffff; color: #0d6efd; padding: 2px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1);"></i></span>
                                
                            </button>
                        </div>
                    </div>
                </div>
                        
                <form id="bulkGradeForm" action="submit-bulk-grades.php" method="POST">
                    <input type="hidden" name="instructor_id" value="<?php echo $user_id ?>">
                    <input type="hidden" name="program" value="<?php echo $program; ?>">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="section" value="<?php echo $section; ?>">
                    <input type="hidden" name="course_code" value="<?php echo $course_code; ?>">

                   <div id="bulkActionContainer" class="mb-3 text-end" style="display: none;">
                        <button type="submit" name="submit_bulk" class="btn btn-success" disabled>
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="dataTable">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4">#</th>
                                        <th class="py-3 px-4"><input type="checkbox" id="selectAll"> Select All</th>
                                        <th class="py-3 px-4">Student ID</th>
                                        <th class="py-3 px-4">Name</th>
                                        <th class="py-3 px-4">Course Code</th>
                                        <th class="py-3 px-4">Course Title</th>
                                        <th class="py-3 px-4">Semester</th>
                                        <th class="py-3 px-4">Grades</th>
                                    </tr>
                                </thead>
                                <tbody id="dataTableBody">
                                    <?php $i = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($selectQuery)): ?>
                                    <tr>
                                        
                                        <td class="text-center fw-bold"><?php echo $i++; ?></td>
                                        <td class="py-3 px-4 align-middle">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" name="selected_students[]" class="row-checkbox" value="<?php echo $row['User_ID']; ?>">
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 align-middle fw-bold"><?php echo $row['User_ID'] ?></td>
                                        <td class="py-3 px-4 align-middle">
                                            <div class="fw-bold"><?php echo $row['Last Name'] ?>, <?php echo $row['First Name'] ?></div>
                                            <small class="text-muted"><?php echo $row['Programs'] ?> <?php echo $row['Year'] ?><?php echo $row['Section'] ?></small>
                                        </td>
                                        <td class="py-3 px-4 align-middle">
                                            <span class="badge bg-light text-dark border"><?php echo $row['Course Code'] ?></span>
                                            <input type="hidden" name="course_codes[<?php echo $row['User_ID']; ?>]" value="<?php echo $row['Course Code']; ?>">
                                        </td>
                                        <td class="py-3 px-4 align-middle"><?php echo $row['Course Title'] ?></td>
                                        <td class="py-3 px-4 align-middle"><span class="badge badge-info-custom"><?php echo $row['Semester'] ?></span></td>
                                                        <td class="py-3 px-4 align-middle">
                                        <?php
                                        $grade = $row['Grades'];
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
                                        ?>
                                        <div class="d-inline-flex align-items-center">
                                        <span class="badge bg-<?php echo $badgeColor; ?> grade-badge">
                                            <?php echo $displayGrade; ?>
                                        </span>

                                        <div class="bulk-grade-input ms-2" style="display:none;">
                                            <input type="text" 
                                                name="grades[<?php echo $row['User_ID']; ?>]" 
                                                class="form-control form-control-sm" 
                                             
                                                style="width: 90px;">
                                        </div>
                                    </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</form>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-user-graduate text-muted fs-1 mb-3 opacity-25"></i>
                            <h5 class="text-muted mb-2">No students in this section</h5>
                            <p class="text-muted small">No students are enrolled in this course section</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
               <!-- Import Grades Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-import me-2"></i>Import Grades (Excel)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="card border-warning border-3 shadow-sm">
    <div class="card-header bg-warning text-dark d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <h6 class="mb-0 fw-bold">File Upload Requirements</h6>
    </div>
    <div class="card-body">
        <p class="mb-0 fw-bold">
           <span class="text-warning">⚠️ IMPORTANT:</span> Upload an Excel file containing exactly three columns: 
            <span class="text-primary">Student ID</span> (format: <code class="bg-light px-2 py-1 rounded">AXX-XX-XXXXX</code>),
            <span class="text-primary">Full Name</span> (format: <code class="bg-light px-2 py-1 rounded">Juan Dela Cruz</code>),  and 
            <span class="text-primary">Grade</span> (use preview to edit decimal grades if Excel won't read .00 format). 
            <span class="text-warning">Ensure your file has NO additional columns or text-based grades.</span>
        </p>
    </div>
</div>
            <div class="modal-body">


                <!-- File Upload Section -->
                <div class="card border-primary mb-4">
                    
                    <div class="card-header bg-primary bg-opacity-10">
                            
                        <label class="form-label fw-bold mb-0">
                            <i class="fas fa-file-excel me-2 text-success"></i>Upload Excel File
                        </label>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="file" id="importFile" class="form-control" accept=".xlsx">
                            <div class="form-text">
                                <i class="fas fa-file me-1"></i> Only .xlsx files are accepted
                            </div>
                        </div>
                        
                        
                        <!-- Sample Table Preview -->
                        <div class="mt-3">
                            
                            <p class="mb-2 small fw-semibold">Your Excel file should look like this:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="bg-primary bg-opacity-10">Student ID</th>
                                            <th class="bg-primary bg-opacity-10">Full Name</th>
                                            <th class="bg-primary bg-opacity-10">Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>AXX-XX-XXXX1</code></td>
                                            <td><code>Juan Dela Cruz</code></td>
                                            <td><code>2.75</code></td>
                                        </tr>
                                        <tr>
                                            <td><code>AXX-XX-XXXX2</code></td>
                                            <td><code>Maria La Del Barrio</code></td>
                                            <td><code>3.00</code></td>
                                        </tr>
                                        <tr>
                                            <td><code>AXX-XX-XXXX3</code></td>
                                            <td><code>Marimar Perez</code></td>
                                            <td><code>1.25</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Button -->
                <div class="text-center mb-4">
                    <button id="previewBtn" class="btn btn-success btn-lg px-5">
                        <i class="fas fa-eye me-2"></i> Preview Import Data
                    </button>
                </div>
                
                <!-- PREVIEW TABLE -->
                <div id="previewSection" style="display:none;">
                    <div class="card border-success">
                        <div class="card-header bg-success bg-opacity-10">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-clipboard-check me-2"></i>Preview & Edit Before Submitting
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="previewTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name (From System)</th>
                                            <th>Course</th>
                                            <th>Existing Grade</th>
                                            <th>Imported Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <button id="submitImportBtn" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i> Submit Imported Grades
                                </button>
                                <div class="form-text mt-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Grades will be updated for matched students only
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer">
                <small class="text-muted">Only files with Student ID and Grade columns will be processed</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                
            </div>
        </div>
    </div>
</div>

                <!-- Footer -->
    <footer class="container-fluid py-4 mt-5">
        <div class="row align-items-center">
            <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                
            </div>
            <div class="col-md-4 text-center mb-3 mb-md-0">
                <small class="developer-credits text-light">
                    <span class="credits-label d-block mb-2">Developed by:</span>
                    <div class="developer-links">
                        <a href="https://www.facebook.com/markflorence.sabinator.9" target="_blank">Sabinator, Mark Florence</a>
                        <a href="https://www.facebook.com/patriciovier.royo.7" target="_blank">Royo, Patricio Vier</a>
                        <a href="https://www.facebook.com/hnnahhqt" target="_blank">Villafuerte, Hannah Florence</a>
                        <a href="https://www.facebook.com/graczielle.hans.conge.2025" target="_blank">Conge, Graczielle Hans</a>
                        <a href="https://www.facebook.com/christianjimesponilla" target="_blank">Cuenca, Christian Jim</a>
                        <a href="https://www.facebook.com/lovikiee" target="_blank">Orfinada, Christine Joy</a>
                        <a href="https://www.facebook.com/Dennisbarangan01" target="_blank">Barangan, Dennis Jr.</a>
                    </div>
                    <small class="text-light">&copy; 2025 Visayas State University-Alangalang. All rights reserved.</small>
                </small>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=vsualangalang.dit.projects@vsu.edu.ph" target="_blank" class="contact-btn">
                    <i class="fas fa-envelope me-1"></i> Contact Us
                </a>
            </div>
        </div>
    </footer>

    <!-- Modals -->
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
    <!-- Password Change Modal (Hidden by default, shown via PHP if needed) -->
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

        <!-- Loading Modal -->
        <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content d-flex justify-content-center align-items-center" style="height: 200px;">
                    <div class="text-center">
                        <div class="spinner-border" style="width: 3rem; height: 3rem; color: var(--light-green);" role="status"></div>
                        <p class="mt-3 mb-0 fs-5" style="color: var(--dark-green);">Saving grade, please wait...</p>
                    </div>
                </div>
            </div>
        </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- JavaScript for Download Template -->
  <!-- Add this in your <head> section -->

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
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulkGradeForm');
    const bulkActionBtnContainer = document.getElementById('bulkActionContainer');
    const submitBtn = bulkForm.querySelector('button[name="submit_bulk"]');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    function validateBulkForm() {
        let anyChecked = false;
        let allCheckedHaveGrades = true;

        rowCheckboxes.forEach(cb => {
            if (cb.checked) {
                anyChecked = true;
                const row = cb.closest('tr');
                const gradeInput = row.querySelector('input[name^="grades"]');
                
                // If a row is selected but the grade is empty
                if (gradeInput.value.trim() === "") {
                    allCheckedHaveGrades = false;
                }
            }
        });

        // Show/Hide the container based on selection
        bulkActionBtnContainer.style.display = anyChecked ? 'block' : 'none';
        
        // Disable/Enable the button based on grade completion
        submitBtn.disabled = !(anyChecked && allCheckedHaveGrades);
    }

    // Event listener for Checkboxes
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('tr');
            const inputContainer = row.querySelector('.bulk-grade-input');
            
            // Toggle input visibility
            inputContainer.style.display = this.checked ? 'block' : 'none';
            
            validateBulkForm();
        });
    });

    // Event listener for Grade Inputs (using input event for real-time check)
    document.querySelectorAll('input[name^="grades"]').forEach(input => {
        input.addEventListener('input', validateBulkForm);
    });

    // "Select All" logic update
    document.getElementById('selectAll').addEventListener('change', function() {
        rowCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            const row = cb.closest('tr');
            row.querySelector('.bulk-grade-input').style.display = this.checked ? 'block' : 'none';
        });
        validateBulkForm();
    });
});
    document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulkGradeForm');
    
    // Initialize the Bootstrap Modal object
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            // 1. Show the loading modal
            loadingModal.show();

            // 2. The form will now proceed to submit-bulk-grades.php
            // The modal will stay visible until the page redirects 
            // after the PHP script finishes sending emails.
        });
    }
    
    // Also handle individual "Save" buttons if you have them in the table
    const individualForms = document.querySelectorAll('.grade-form');
    individualForms.forEach(form => {
        form.addEventListener('submit', function() {
            loadingModal.show();
        });
    });
});
   document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('importFile');
    const previewBtn = document.getElementById('previewBtn');
    
    // Initial state
    previewBtn.disabled = true;
    previewBtn.style.cursor = 'not-allowed';
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const file = this.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            
            // Check if file is .xlsx or .xls
            if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                previewBtn.disabled = false;
                previewBtn.style.cursor = 'pointer';
                previewBtn.classList.remove('btn-secondary');
                previewBtn.classList.add('btn-success');
                
                // Optional: Add visual feedback for valid file
                fileInput.classList.remove('is-invalid');
                fileInput.classList.add('is-valid');
                
                // Optional: Show success message
                console.log(`Valid Excel file selected: ${fileName}`);
            } else {
                previewBtn.disabled = true;
                previewBtn.style.cursor = 'not-allowed';
                previewBtn.classList.remove('btn-success');
                previewBtn.classList.add('btn-secondary');
                
                // Optional: Add visual feedback for invalid file
                fileInput.classList.remove('is-valid');
                fileInput.classList.add('is-invalid');
                
                // Optional: Show error message
                console.error(`Invalid file type: ${fileExtension}. Please select .xlsx or .xls file.`);
                alert('Please select an Excel file (.xlsx or .xls format only).');
            }
        } else {
            previewBtn.disabled = true;
            previewBtn.style.cursor = 'not-allowed';
            previewBtn.classList.remove('btn-success');
            previewBtn.classList.add('btn-secondary');
            
            // Reset validation classes
            fileInput.classList.remove('is-valid', 'is-invalid');
        }
    });
    
    // Also check on page load if there's already a file (for form resubmission)
    if (fileInput.files && fileInput.files.length > 0) {
        fileInput.dispatchEvent(new Event('change'));
    }
});
$(document).ready(function() {
    // Preview Button Handler
    $('#previewBtn').on('click', function() {
        let file = document.getElementById("importFile").files[0];
        if (!file) {
            showErrorModal("Please select an Excel file first.");
            return;
        }

        // Show loading state
        $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        $(this).prop('disabled', true);

        let formData = new FormData();
        formData.append("file", file);
        formData.append("program", "<?php echo $program; ?>");
        formData.append("year", "<?php echo $year; ?>");
        formData.append("section", "<?php echo $section; ?>");
        formData.append("course_code", "<?php echo $course_code; ?>");
        formData.append("instructor_id", "<?php echo $user_id; ?>");

        // Fetch preview data
        $.ajax({
            url: 'import-preview.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Check if response is valid JSON
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    showErrorModal("Invalid server response format");
                    return;
                }
                
                if (data.error) {
                    showErrorModal(data.error);
                    return;
                }

                // Show preview section
                $('#previewSection').show();
                let tbody = $("#previewTable tbody");
                tbody.empty();

                // Add student data to table
                data.rows.forEach(row => {
                    let tr = $(`
                        <tr>
                            <td>${row.student_id || ''}</td>
                            <td>${row.name || ''}</td>
                            <td>${row.course || ''}</td>
                            <td>${row.current_grade || 'N/A'}</td>
                            <td>
                                <input type="hidden" name="student_id[]" value="${row.student_id || ''}">
                                <input type="text" 
                                       class="form-control form-control-sm imported-grade" 
                                       name="grade[]" 
                                       value="${row.new_grade || ''}">
                            </td>
                        </tr>
                    `);
                    tbody.append(tr);
                });
                
                // Scroll to preview section
                $('html, body').animate({
                    scrollTop: $('#previewSection').offset().top - 100
                }, 500);
            },
            error: function(xhr, status, error) {
                showErrorModal("Error loading preview: " + error);
            },
            complete: function() {
                $('#previewBtn').html('<i class="fas fa-eye me-2"></i>Preview');
                $('#previewBtn').prop('disabled', false);
            }
        });
    });
 

    // Submit Button Handler
    $('#submitImportBtn').on('click', function(e) {
        e.preventDefault();
        
        // Validate there are grades to submit
        if ($('.imported-grade').length === 0) {
            showErrorModal("No grades to submit. Please preview the file first.");
            return;
        }

        // Validate all grades have values
        let emptyGrades = [];
        $('.imported-grade').each(function() {
            if (!$(this).val().trim()) {
                let studentId = $(this).closest('tr').find('td:first').text();
                emptyGrades.push(studentId);
            }
        });

        if (emptyGrades.length > 0) {
            showErrorModal(`Please enter grades for student(s): ${emptyGrades.join(', ')}`);
            return;
        }

        // Prepare form data
        let formData = new FormData();
        formData.append('program', "<?php echo $program; ?>");
        formData.append('year', "<?php echo $year; ?>");
        formData.append('section', "<?php echo $section; ?>");
        formData.append('course_code', "<?php echo $course_code; ?>");
        formData.append('instructor_id', "<?php echo $user_id; ?>");
        
        // Collect student data
        $('input[name="student_id[]"]').each(function(index) {
            formData.append('student_id[]', $(this).val());
        });
        
        $('input[name="grade[]"]').each(function(index) {
            formData.append('grade[]', $(this).val());
        });
        
        // Show loading state
        let submitBtn = $(this);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        submitBtn.prop('disabled', true);
        
        // Submit via AJAX
        $.ajax({
            url: 'import-submit.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Parse response
                let result;
                try {
                    result = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    showResultModal({
                        success: false,
                        message: "Invalid server response"
                    });
                    return;
                }
                
                showResultModal(result);
            },
            error: function(xhr, status, error) {
                showResultModal({
                    success: false,
                    message: 'Server error: ' + error
                });
            },
            complete: function() {
                submitBtn.html('<i class="fas fa-save me-2"></i>Submit Imported Grades');
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Helper function to show error modal
    function showErrorModal(message) {
        showResultModal({
            success: false,
            message: message
        });
    }
    
    // Function to show result modal
    function showResultModal(response) {
        // Close import modal first
        $('#importModal').modal('hide');
        
        // Create modal content
        let modalContent = '';
        
        if (response.success) {
            modalContent = `
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Success!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="mb-3">Grades Imported Successfully</h4>
                        <p>${response.message || 'Grades have been successfully imported.'}</p>
                        
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Email notifications have been sent to students.
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                                Continue
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-redo me-1"></i> Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            modalContent = `
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                        <h4 class="mb-3">Import Failed</h4>
                        <p class="text-danger">${response.message || 'An unknown error occurred.'}</p>
                        
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Please check your data and try again.
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                                Try Again
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create and show modal
        let modalId = 'resultModal';
        
        // Remove existing modal if any
        if ($('#' + modalId).length) {
            $('#' + modalId).remove();
        }
        
        let modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        ${modalContent}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Show the modal
        let resultModal = new bootstrap.Modal(document.getElementById(modalId));
        resultModal.show();
        
        // Clean up on hide
        $('#' + modalId).on('hidden.bs.modal', function() {
            $(this).remove();
            // Reload page on success
            if (response.success) {
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        });
    }
});
</script>


    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Sidebar functions for mobile
        function openNav() {
            document.getElementById("mySidenav").classList.add("open");
            document.querySelector('.sidebar-overlay').style.display = 'block';
        }

        function closeNav() {
            document.getElementById("mySidenav").classList.remove("open");
            document.querySelector('.sidebar-overlay').style.display = 'none';
        }



        // Close sidebar when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeNav();
            }
        });

        // Live search functionality
        function liveSearch() {
            const input = document.getElementById("searchBox").value.toLowerCase();
            const rows = document.querySelectorAll("#dataTableBody tr");

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }

       document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActionBtn = document.getElementById('bulkActionContainer');

    function toggleBulkInputs() {
        let anyChecked = false;
        rowCheckboxes.forEach(cb => {
            const row = cb.closest('tr');
            const inputContainer = row.querySelector('.bulk-grade-input');
            if (cb.checked) {
                inputContainer.style.display = 'block';
                anyChecked = true;
            } else {
                inputContainer.style.display = 'none';
            }
        });
        bulkActionBtn.style.display = anyChecked ? 'block' : 'none';
    }

    selectAll.addEventListener('change', function() {
        rowCheckboxes.forEach(cb => cb.checked = this.checked);
        toggleBulkInputs();
    });

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkInputs);
    });

            // Form submission loading modal
            const forms = document.querySelectorAll(".grade-form");
            forms.forEach((form) => {
                form.addEventListener("submit", function() {
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                    loadingModal.show();
                });
            });
        });

        // Auto-dismiss alerts (if any were added to this page)
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
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
</body>

</html>