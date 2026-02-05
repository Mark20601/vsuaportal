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
    <title>Pending Enrollment - Instructor Portal</title>
    <link href="bootstrap/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="bootstrap/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../pictures/vsu-icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/pending.css">

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
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 active" 
                       data-bs-toggle="collapse" 
                       href="#studentsCollapse" 
                       role="button" 
                       aria-expanded="false" 
                       aria-controls="studentsCollapse">
                        <i class="fas fa-users me-3"></i>
                        <span>Students</span>
                        <i class="fas fa-chevron-down ms-2 small"></i>
                    </a>
                    <div class="collapse ps-4 show" id="studentsCollapse">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 active" href="pending.php">
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
            <?php if (isset($_GET['approve'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['approve']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-warning alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['deleted']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in">
                <div>
                    <h4 class="mb-0 fw-bold" style="color: var(--dark-green);">Pending Student Enrollment</h4>
                    <p class="text-muted small mb-0">Review and manage student enrollment requests</p>
                </div>
                <div>
                    <?php
                    $countPending = mysqli_query($conn, "SELECT COUNT(status) AS Pending FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id' ");
                    $pendingCount = 0;
                    if (mysqli_num_rows($countPending)) {
                        $row = mysqli_fetch_assoc($countPending);
                        $pendingCount = $row['Pending'];
                    }
                    ?>
                    <span class="badge badge-warning-custom py-2 px-3">
                        <i class="fas fa-clock me-1"></i> <?php echo $pendingCount; ?> Pending Requests
                    </span>
                </div>
            </div>

            <!-- Pending Students Table -->
            <div class="table-container animate-fade-in">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold" style="color: var(--dark-green);">Student Requests</h5>
                        <div class="d-flex gap-2">
                            <button type="submit" form="bulkActionForm" name="bulkDeny" class="btn btn-sm btn-outline-danger px-3" id="denySelected" disabled>
                                <i class="fas fa-times me-1"></i> Deny Selected
                            </button>
                            <button type="submit" form="bulkActionForm" name="bulkApprove" class="btn btn-sm btn-success-custom px-3" id="approveSelected" disabled>
                                <i class="fas fa-check me-1"></i> Approve Selected
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <form method="POST" action="action.php" id="bulkActionForm">
                        <input type="hidden" name="instructor_id" value="<?php echo $user_id?>">
                        
                        <?php 
                        $recordQuery = mysqli_query($conn, "SELECT DISTINCT 
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
                        ORDER BY users.last_name ASC");

                        if(mysqli_num_rows($recordQuery) > 0): 
                        ?>
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
                      <div class="table-container animate-fade-in" id="dataTable">
                          
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="dataTable">
                                <thead>
                                    <tr>
                                        <th class="py-3">#</th>
                                        <th class="ps-3 py-3" width="40">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        
                                        <th class="py-3">Student ID</th>
                                        <th class="py-3">Student</th>
                                        <th class="py-3">Course</th>
                                        <th class="py-3">Program</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-3 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="dataTableBody">
                                    <?php $i = 1; ?>
                                    <?php while($rows = mysqli_fetch_assoc($recordQuery)): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $i++; ?></td>
                                        <td class="ps-3">
                                            <div class="form-check">
                                                <input class="form-check-input row-checkbox" type="checkbox" name="student_ids[]" 
                                                    value="<?php echo $rows['user_id'].'|'.$rows['course_code']; ?>"
                                                    data-student-id="<?php echo $rows['user_id']; ?>"
                                                    data-course-code="<?php echo $rows['course_code']; ?>">
                                            </div>
                                        </td>
                                        
                                        <td class="fw-bold"><?php echo $rows['user_id']; ?></td>
                                        
                                        <td>
                                            <div class="d-flex align-items-center">
                                               
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
                                        
                                        <td>
                                            <div class="small"><?php echo $rows['program']; ?></div>
                                            <div class="text-muted xsmall">Year <?php echo $rows['year']; ?> - Section <?php echo $rows['section']; ?></div>
                                        </td>
                                        
                                        <td>
                                            <span class="badge badge-warning-custom py-1 px-2 rounded-pill small">
                                                <i class="fas fa-clock me-1"></i> Pending
                                            </span>
                                        </td>
                                        
                                        <td class="pe-3">
                                            <div class="d-flex justify-content-center gap-2 action-buttons">
                                                <form method="POST" action="action.php" class="d-inline pending-form">
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
                                                     <button type="submit" name="approve" class="btn btn-sm btn-success-custom px-3 approve-btn" title="Approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="deny" class="btn btn-sm btn-outline-danger px-3 deny-btn" title="Deny">
                                                        <i class="fas fa-times"></i> Deny
                                                    </button>
                                                   
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-user-clock text-muted fs-1 mb-3 opacity-25"></i>
                                <h5 class="text-muted mb-2">No pending requests</h5>
                                <p class="text-muted small">All student enrollment requests have been processed</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
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
    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
         function liveSearch() {
            const input = document.getElementById("searchBox").value.toLowerCase();
            const rows = document.querySelectorAll("#dataTableBody tr");

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }
        // Sidebar functions for mobile
        function openNav() {
            document.getElementById("mySidenav").classList.add("open");
            document.querySelector('.sidebar-overlay').style.display = 'block';
        }

        function closeNav() {
            document.getElementById("mySidenav").classList.remove("open");
            document.querySelector('.sidebar-overlay').style.display = 'none';
        }

        // Close sidebar when clicking outside on mobile
        document.querySelector('.sidebar-overlay').addEventListener('click', function() {
            closeNav();
        });

       
        // Checkbox selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const approveBtn = document.getElementById('approveSelected');
            const denyBtn = document.getElementById('denySelected');
            
            // Function to update button states
            function updateButtonStates() {
                const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
                approveBtn.disabled = !anyChecked;
                denyBtn.disabled = !anyChecked;
                selectAll.checked = anyChecked && Array.from(checkboxes).every(checkbox => checkbox.checked);
            }
            
            // Select all checkboxes
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateButtonStates();
                });
            }
            
            // Individual checkbox change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateButtonStates);
            });
            
            // Initialize button states
            updateButtonStates();
        });

        // Loading modal for form submissions
        document.addEventListener("DOMContentLoaded", function () {
            const forms = document.querySelectorAll(".pending-form");

            forms.forEach((form) => {
                form.addEventListener("submit", function () {
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                    loadingModal.show();
                });
            });

            // Also handle bulk approve/deny buttons
            const bulkForm = document.getElementById("bulkActionForm");

            if (bulkForm) {
                bulkForm.addEventListener("submit", function () {
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                    loadingModal.show();
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

        // Responsive adjustments for action buttons
        function adjustActionButtons() {
            const actionButtons = document.querySelectorAll('.action-buttons');
            if (window.innerWidth < 768) {
                actionButtons.forEach(container => {
                    container.classList.remove('gap-2');
                    container.classList.add('gap-1');
                    const buttons = container.querySelectorAll('button');
                    buttons.forEach(btn => {
                        btn.classList.add('px-2');
                        btn.classList.remove('px-3');
                    });
                });
            } else {
                actionButtons.forEach(container => {
                    container.classList.remove('gap-1');
                    container.classList.add('gap-2');
                    const buttons = container.querySelectorAll('button');
                    buttons.forEach(btn => {
                        btn.classList.remove('px-2');
                        btn.classList.add('px-3');
                    });
                });
            }
        }

        // Adjust buttons on load and resize
        window.addEventListener('load', adjustActionButtons);
        window.addEventListener('resize', adjustActionButtons);
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

<style>

</style>
</html>
