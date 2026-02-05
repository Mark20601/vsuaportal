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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Instructor Portal</title>
    <link href="bootstrap/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="bootstrap/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../pictures/vsu-icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/dashboard.css">

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
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 active" href="dashboard.php">
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
                                            $countPendingQuery = mysqli_query($conn, "SELECT COUNT(status) AS Pending FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id'");
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
                                                                            AND instructor_id = '$user_id'");
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
                ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2"
                       data-bs-toggle="collapse"
                       href="#<?php echo $uniqueId; ?>"
                       role="button"
                       aria-expanded="false"
                       aria-controls="<?php echo $uniqueId; ?>">
                        <i class="fas fa-book me-3"></i>
                        <span><?php echo $courseCode; ?></span>
                        <i class="fas fa-chevron-down ms-2 small"></i>
                    </a>
                    <div class="collapse ps-4" id="<?php echo $uniqueId; ?>">
                        <ul class="nav flex-column">
                            <?php
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
                            ?>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2"
                                    href="student.php?program=<?php echo urlencode($group['program']); ?>&year=<?php echo urlencode($group['year']); ?>&section=<?php echo urlencode($group['section']); ?>&course_code=<?php echo urlencode($courseCode); ?>">
                                    <i class="fas fa-users me-3"></i>
                                    <span class="text-start"><?php echo $group['program_abbr'] . " " . $group['year']  . $group['section']; ?></span>
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
                <?php
                        $courseCounter++;
                    }
                }
                ?>
                <li class="nav-item mt-4 pt-3 border-top">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 text-warning" data-bs-toggle="modal" style="cursor: pointer;" data-bs-target="#logoutModal">
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

            <!-- Alerts -->
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['success']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['already_exist'])): ?>
            <div class="alert alert-warning alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['already_exist']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['warning']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-5 animate-fade-in">
                <div class="col-md-6 col-lg-3">
                    <a href="accepted.php" class="text-decoration-none">
                        <div class="card-hover p-4 stat-card-blue">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted mb-0">Total Students</h6>
                                <div class="card-icon bg-blue text-white">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="h3 fw-bold" style="color: #2196f3;">
                                <?php
                                $countStudentsQuery = mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) AS Accepted 
                                                                            FROM studenrollstatus 
                                                                            WHERE status = 'approved' 
                                                                            AND instructor_id = '$user_id'");
                                if (mysqli_num_rows($countStudentsQuery) > 0) {
                                    $row = mysqli_fetch_assoc($countStudentsQuery);
                                    echo $row['Accepted'];
                                }
                                ?>
                            </div>
                            <small class="text-muted">Enrolled Students</small>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <a href="courses.php" class="text-decoration-none">
                        <div class="card-hover p-4 stat-card-green">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted mb-0">Courses</h6>
                                <div class="card-icon text-white" style="background: var(--light-green);">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            </div>
                            <div class="h3 fw-bold" style="color: var(--light-green);">
                                <?php
                                $selectSubCount = mysqli_query($conn, "SELECT COUNT(course_code) AS Courses FROM instructor_courses WHERE instructor_id = '$user_id' AND status = 'Active'");
                                if (mysqli_num_rows($selectSubCount) > 0) {
                                    $row = mysqli_fetch_assoc($selectSubCount);
                                    echo $row['Courses'];
                                }
                                ?>
                            </div>
                            <small class="text-muted">Total Courses</small>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#addCourseModal" class="text-decoration-none">
                        <div class="card-hover p-4 stat-card-purple">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted mb-0">Add Course</h6>
                                <div class="card-icon text-white" style="background: #9c27b0;">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                            <div class="h3 fw-bold" style="color: #9c27b0;">
                                <i class="fas fa-plus"></i> Add New
                            </div>
                            <small class="text-muted">Click to add course</small>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <a href="pending.php" class="text-decoration-none">
                        <div class="card-hover p-4 stat-card-orange">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted mb-0">Pending</h6>
                                <div class="card-icon text-white" style="background: #ff9800;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="h3 fw-bold" style="color: #ff9800;">
                                <?php
                                $countStudentsQuery = mysqli_query($conn, "SELECT COUNT(status) AS pending, instructor_id FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id' ");
                                if (mysqli_num_rows($countStudentsQuery) > 0) {
                                    $row = mysqli_fetch_assoc($countStudentsQuery);
                                    echo $row['pending'];
                                }
                                ?>
                            </div>
                            <small class="text-muted">Applications</small>
                        </div>
                    </a>
                </div>
            </div>


            <!-- Students Section -->
            <div class="table-container animate-fade-in">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="mb-0 mb-3 mb-md-0" style="color: var(--dark-green);">Students</h4>
                        <div class="nav-tabs-custom">
                            <ul class="nav">
                                <li class="nav-item">
                                <button id="enrolledTab" class="btn btn-outline-success active" onclick="showTab('enrolled')">Enrolled</button>
                                </li>
                                <li class="nav-item">
                                     <button id="pendingTab" class="btn btn-outline-warning" onclick="showTab('pending')">
                                        Pending
                                
                                            <?php
                                            $countStudentsQuery = mysqli_query($conn, "SELECT COUNT(status) AS pending FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id' ");
                                            if (mysqli_num_rows($countStudentsQuery) > 0) {
                                                $row = mysqli_fetch_assoc($countStudentsQuery);
                                                echo $row['pending'];
                                            }
                                            ?>
                                       
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Students Table -->
                <div id="enrolledTable" class="p-4 ">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Student ID</th>
                                    <th class="text-left">Name</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <?php $i = 1; ?>
                            <tbody id="enrolledStudentsTableBody">
                                <?php 
                                $enrolledPerPage = 10;
                                $enrolledQuery = mysqli_query($conn, "SELECT
                                                        users.user_id, 
                                                        users.last_name, 
                                                        users.first_name, 
                                                        users.email,
                                                        studenrollstatus.status,
                                                        studprograms.program,
                                                        studprograms.year,
                                                        studprograms.section,
                                                        -- This merges all course codes into one string
                                                        GROUP_CONCAT(instructor_courses.course_code SEPARATOR ', ') AS enrolled_codes,
                                                        MAX(instructor_courses.semester) AS semester,
                                                        MAX(instructor_courses.course_code) AS single_course_code
                                                    FROM users
                                                    INNER JOIN studenrollstatus 
                                                        ON users.user_id = studenrollstatus.student_id
                                                    INNER JOIN instructor_courses 
                                                        ON studenrollstatus.course_code = instructor_courses.course_code
                                                    INNER JOIN studprograms 
                                                        ON users.user_id = studprograms.student_id 
                                                        AND studenrollstatus.instructor_id = instructor_courses.instructor_id
                                                    WHERE 
                                                        studenrollstatus.status = 'Approved'
                                                        AND instructor_courses.status = 'Active'
                                                        AND studenrollstatus.instructor_id = '$user_id'
                                                    GROUP BY 
                                                        users.user_id, 
                                                        users.last_name, 
                                                        users.first_name, 
                                                        users.email, 
                                                        studenrollstatus.status, 
                                                        studprograms.program, 
                                                        studprograms.year, 
                                                        studprograms.section
                                                    ORDER BY users.last_name ASC");
                                
                                $enrolledTotalRecords = mysqli_num_rows($enrolledQuery);
                                $enrolledTotalPages = ceil($enrolledTotalRecords / $enrolledPerPage);
                                
                                $enrolledPage = isset($_GET['enrolled_page']) ? max(1, min($enrolledTotalPages, intval($_GET['enrolled_page']))) : 1;
                                $enrolledOffset = ($enrolledPage - 1) * $enrolledPerPage;
                                
                                $enrolledQuery = mysqli_query($conn, "SELECT DISTINCT
                                                                            users.user_id, 
                                                                            users.last_name, 
                                                                            users.first_name, 
                                                                            users.email,
                                                                            studenrollstatus.status,
                                                                            studprograms.program,
                                                                            studprograms.year,
                                                                            studprograms.section
                                                                        FROM users
                                                                        INNER JOIN studenrollstatus 
                                                                            ON users.user_id = studenrollstatus.student_id
                                                                        INNER JOIN instructor_courses 
                                                                            ON studenrollstatus.course_code = instructor_courses.course_code
                                                                            AND studenrollstatus.instructor_id = instructor_courses.instructor_id
                                                                        INNER JOIN studprograms 
                                                                            ON users.user_id = studprograms.student_id
                                                                        WHERE 
                                                                            studenrollstatus.status = 'Approved'
                                                                            AND instructor_courses.status = 'Active'
                                                                            AND studenrollstatus.instructor_id = '$user_id'
                                                                        ORDER BY users.last_name ASC
                                LIMIT $enrolledOffset, $enrolledPerPage");
                                
                                if(mysqli_num_rows($enrolledQuery) > 0){
                                    while($rows = mysqli_fetch_assoc($enrolledQuery)){
                                ?>
                                
                                <tr>
                                    <td class="text-center fw-bold"><?php echo $i++; ?></td>
                                    <td class="text-center fw-bold"><?php echo $rows['user_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white me-3" style="width: 40px; height: 40px;">
                                                <?php echo substr($rows['first_name'], 0, 1) . substr($rows['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo $rows['last_name']; ?></div>
                                                <div class="text-muted small"><?php echo $rows['first_name']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold"><?php echo $rows['program']; ?></div>
                                        <div class="text-muted small">
                                            Year <?php echo $rows['year']; ?> - Section <?php echo $rows['section']; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success-custom rounded-pill px-3 py-1">
                                            <?php echo $rows['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-user-graduate text-muted mb-3 fs-1"></i>
                                            <p class="text-muted mb-0">No enrolled students found</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($enrolledTotalPages > 1): ?>
                    <div class="d-flex justify-content-end mt-4 px-4 pb-4">
                        <nav aria-label="Enrolled students pagination">
                            <ul class="pagination pagination-custom mb-0">
                                <?php if ($enrolledPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?enrolled_page=<?php echo $enrolledPage - 1; ?>">
                                        &laquo; Previous
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                $pagesPerSet = 3;
                                $setStart = floor(($enrolledPage - 1) / $pagesPerSet) * $pagesPerSet + 1;
                                $setEnd = min($setStart + $pagesPerSet - 1, $enrolledTotalPages);
                                
                                for ($i = $setStart; $i <= $setEnd; $i++):
                                    $isLastInSet = $i === $setEnd && $setEnd < $enrolledTotalPages;
                                    $label = $isLastInSet ? "{$i}..." : $i;
                                ?>
                                <li class="page-item <?php echo $i == $enrolledPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?enrolled_page=<?php echo $i + ($isLastInSet ? 1 : 0); ?>">
                                        <?php echo $label; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($enrolledPage < $enrolledTotalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?enrolled_page=<?php echo $enrolledPage + 1; ?>">
                                        Next &raquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Students Table -->
                <div id="pendingTable" style="display: none;" class="p-4">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-left">#</th>
                                    <th class="text-left">Student ID</th>
                                    <th class="text-left">Name</th>
                                    <th class="text-center">Course Details</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingStudentsTableBody">
                                <?php 
                                $pendingPerPage = 10;
                                $pendingQuery = mysqli_query($conn, "SELECT DISTINCT 
                                    users.user_id, 
                                    users.last_name, 
                                    users.first_name, 
                                    users.email,
                                    studenrollstatus.status,
                                    instructor_courses.course_code, 
                                    instructor_courses.course_title, 
                                    instructor_courses.semester,
                                    studprograms.program,
                                    studprograms.year,
                                    studprograms.section
                                FROM users
                                INNER JOIN studenrollstatus ON users.user_id = studenrollstatus.student_id
                                INNER JOIN instructor_courses ON studenrollstatus.course_code = instructor_courses.course_code 
                                INNER JOIN studprograms ON users.user_id = studprograms.student_id
                                    AND studenrollstatus.instructor_id = instructor_courses.instructor_id
                                WHERE studenrollstatus.status = 'Pending'
                                AND studenrollstatus.instructor_id = '$user_id'");
                                
                                $pendingTotalRecords = mysqli_num_rows($pendingQuery);
                                $pendingTotalPages = ceil($pendingTotalRecords / $pendingPerPage);
                                
                                $pendingPage = isset($_GET['pending_page']) ? max(1, min($pendingTotalPages, intval($_GET['pending_page']))) : 1;
                                $pendingOffset = ($pendingPage - 1) * $pendingPerPage;
                                
                                $pendingQuery = mysqli_query($conn, "SELECT DISTINCT 
                                    users.user_id, 
                                    users.last_name, 
                                    users.first_name, 
                                    users.email,
                                    studenrollstatus.status,
                                    instructor_courses.course_code, 
                                    instructor_courses.course_title, 
                                    instructor_courses.semester,
                                    studprograms.program,
                                    studprograms.year,
                                    studprograms.section
                                FROM users
                                INNER JOIN studenrollstatus ON users.user_id = studenrollstatus.student_id
                                INNER JOIN instructor_courses ON studenrollstatus.course_code = instructor_courses.course_code 
                                INNER JOIN studprograms ON users.user_id = studprograms.student_id
                                    AND studenrollstatus.instructor_id = instructor_courses.instructor_id
                                WHERE studenrollstatus.status = 'Pending'
                                AND studenrollstatus.instructor_id = '$user_id'
                                ORDER BY users.last_name ASC
                                LIMIT $pendingOffset, $pendingPerPage");
                                
                                if(mysqli_num_rows($pendingQuery) > 0){
                                    while($rows = mysqli_fetch_assoc($pendingQuery)){
                                ?>
                                <?php $i = 1; ?>
                                <tr>
                                    <td class="text-center fw-bold"><?php echo $i++; ?></td>
                                    <td class="fw-bold"><?php echo $rows['user_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white me-3" style="width: 36px; height: 36px;">
                                                <?php echo substr($rows['first_name'], 0, 1) . substr($rows['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold small"><?php echo $rows['last_name']; ?></div>
                                                <div class="text-muted xsmall"><?php echo $rows['first_name']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small"><?php echo $rows['course_code']; ?></div>
                                        <div class="text-muted xsmall"><?php echo $rows['course_title']; ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="small"><?php echo $rows['program']; ?></div>
                                        <div class="text-muted xsmall">Yr <?php echo $rows['year']; ?> - Sec <?php echo $rows['section']; ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning-custom py-1 px-2 rounded-pill small">
                                            <i class="fas fa-clock me-1"></i> Pending
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="action.php" class="d-flex justify-content-center gap-2 action-buttons" id="pending">
                                            <input type="hidden" name="instructor_id" value="<?php echo $user_id?>">
                                            <input type="hidden" name="id" value="<?php echo $rows['user_id'] ?>">
                                            <input type="hidden" name="semester" value="<?php echo $rows['semester'] ?>">
                                            <input type="hidden" name="year" value="<?php echo $rows['year'] ?>">
                                            <input type="hidden" name="section" value="<?php echo $rows['section'] ?>">
                                            <input type="hidden" name="program" value="<?php echo $rows['program'] ?>">
                                            <input type="hidden" name="last_name" value="<?php echo $rows['last_name'];?>">
                                            <input type="hidden" name="first_name" value="<?php echo $rows['first_name'];?>">
                                            <input type="hidden" name="course_code" value="<?php echo $rows['course_code'];?>">
                                            <input type="hidden" name="course_title" value="<?php echo $rows['course_title']?>">
                                            <input type="hidden" name="status" value="<?php echo $rows['status']?>">
                                            
                                            <button type="submit" name="deny" class="btn btn-sm btn-outline-danger px-3 deny-btn" title="Deny">
                                                <i class="fas fa-times"></i> Deny
                                            </button>
                                            <button type="submit" name="approve" class="btn btn-sm btn-success-custom px-3 approve-btn" title="Approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-user-clock text-muted mb-3 fs-1"></i>
                                            <p class="text-muted mb-0">No pending student requests</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($pendingTotalPages > 1): ?>
                    <div class="d-flex justify-content-end mt-4 px-4 pb-4">
                        <nav aria-label="Pending students pagination">
                            <ul class="pagination pagination-custom mb-0">
                                <?php if ($pendingPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pending_page=<?php echo $pendingPage - 1; ?>">
                                        &laquo; Previous
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                $pagesPerSet = 3;
                                $setStart = floor(($pendingPage - 1) / $pagesPerSet) * $pagesPerSet + 1;
                                $setEnd = min($setStart + $pagesPerSet - 1, $pendingTotalPages);
                                
                                for ($i = $setStart; $i <= $setEnd; $i++):
                                    $isLastInSet = $i === $setEnd && $setEnd < $pendingTotalPages;
                                    $label = $isLastInSet ? "{$i}..." : $i;
                                ?>
                                <li class="page-item <?php echo $i == $pendingPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pending_page=<?php echo $i + ($isLastInSet ? 1 : 0); ?>">
                                        <?php echo $label; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pendingPage < $pendingTotalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pending_page=<?php echo $pendingPage + 1; ?>">
                                        Next &raquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
                            <!-- Add this after your table or in place of it -->
            <div class="mobile-table-message" style="display: none;">
                <i class="fas fa-desktop fs-1 text-muted mb-3"></i>
                <h5 class="fw-bold text-dark-green">View on Desktop</h5>
                <p class="text-muted">For the best experience viewing student data, please access this page on a desktop or tablet device.</p>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCourseModalLabel">
                        <i class="fas fa-book me-2"></i>Add New Course
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCourseForm" action="add-sub.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $user_id?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="courseCode" name="courseCode" placeholder="CS101" required>
                                    <label for="courseCode" class="text-muted">
                                        <i class="fas fa-hashtag me-1"></i>Course Code
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="courseTitle" name="courseTitle" placeholder="Introduction to Programming" required>
                                    <label for="courseTitle" class="text-muted">
                                        <i class="fas fa-book-open me-1"></i>Course Title
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-floating">
                                    
                                        <input type="text" class="form-control" id="identification" name="identification" required>
                                        <label for="identification" class="text-muted">
                                            <i class="fas fa-fingerprint"></i>Unique Identification
                                        </label>
                                        <button class="btn btn-outline-secondary" type="button" id="generateKeyBtn">
                                            <i class="fas fa-sync-alt"></i> Generate
                                        </button>
                                  
                                    
                                    <small class="text-muted mt-1 d-block">Must be unique across all courses</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="unit" name="unit" placeholder="3" min="1" max="6" required>
                                    <label for="unit" class="text-muted">
                                        <i class="fas fa-balance-scale me-1"></i>Units
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="First Semester">First Semester</option>
                                        <option value="Second Semester">Second Semester</option>
                                    </select>
                                    <label for="semester" class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>Semester
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="enrollmentKey" name="enrollmentKey" required>
                                    <label for="enrollmentKey" class="text-muted">
                                        <i class="fas fa-key me-1"></i>Enrollment Key
                                    </label>
                                    <small class="text-muted mt-1 d-block">Students will need this to enroll</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer border-top-0 pt-4">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Close
                            </button>
                            <button type="submit" name="submit" class="btn btn-success-custom px-4">
                                <i class="fas fa-plus me-1"></i>Add Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content d-flex justify-content-center align-items-center" style="height: 200px;">
                <div class="text-center">
                    <div class="spinner-border" style="width: 3rem; height: 3rem; color: var(--light-green);" role="status"></div>
                    <p class="mt-3 mb-0 fs-5" style="color: var(--dark-green);">Please wait...</p>
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
    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>      // Sidebar functions for mobile
        function openNav() {
            document.getElementById("mySidenav").classList.add("open");
            document.querySelector('.sidebar-overlay').style.display = 'block';
        }

        function closeNav() {
            document.getElementById("mySidenav").classList.remove("open");
            document.querySelector('.sidebar-overlay').style.display = 'none';
        }

        // Toggle between enrolled and pending tables
        function showTab(tabName) {
            if (tabName === 'enrolled') {
                document.getElementById('enrolledTable').style.display = 'block';
                document.getElementById('pendingTable').style.display = 'none';
                document.getElementById('enrolledTab').classList.add('active');
                document.getElementById('pendingTab').classList.remove('active');
            } else {
                document.getElementById('enrolledTable').style.display = 'none';
                document.getElementById('pendingTable').style.display = 'block';
                document.getElementById('enrolledTab').classList.remove('active');
                document.getElementById('pendingTab').classList.add('active');
            }
        }

        // Check URL parameters on load to show correct tab
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'pending') {
                showTab('pending');
            } else {
                showTab('enrolled');
            }

           
        });

        // Close sidebar when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeNav(); // Close sidebar if open when resizing to desktop
            }
        });

        // Input length limits
        document.addEventListener('DOMContentLoaded', function () {
            const limitInputLength = (id, maxLength) => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('input', function () {
                        if (this.value.length > maxLength) {
                            this.value = this.value.slice(0, maxLength);
                        }
                    });
                }
            };

            limitInputLength('courseCode', 10);
            limitInputLength('courseTitle', 50);
            limitInputLength('enrollmentKey', 20);
            limitInputLength('identification', 20);
            limitInputLength('unit', 1);
        });

        // Generate random enrollment key
        document.getElementById('generateKeyBtn')?.addEventListener('click', function() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghijklmnopqrstuvwxyz!@#$%&';
            let result = '';
            for (let i = 0; i < 6; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('identification').value = result;
        });

// Loading modal for pending actions AND add course form
document.addEventListener("DOMContentLoaded", function () {
    // For all pending forms
    const pendingForms = document.querySelectorAll("#pending");
    pendingForms.forEach((form) => {
        form.addEventListener("submit", function () {
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
        });
    });
     const addCourseForm = document.querySelector("#addCourseForm");
    const addCourseModalElement = document.getElementById("addCourseModal");
    
    if (addCourseForm && addCourseModalElement) {
        addCourseForm.addEventListener("submit", function (e) {
            // Prevent immediate submission if you want to show loading first
            // e.preventDefault();
            
            // Hide the add course modal
            const addCourseModal = bootstrap.Modal.getInstance(addCourseModalElement);
            if (addCourseModal) {
                addCourseModal.hide();
            }
            
            // Show loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
            
            // If you used e.preventDefault(), submit the form manually
            // setTimeout(() => this.submit(), 500);
        });
    }

});
        
        // Auto-dismiss alerts
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
});</script>
</body>

<style>

</style>
</html>