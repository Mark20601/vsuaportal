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
$email = htmlspecialchars($_SESSION['email'] ?? '');

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
    <link rel="stylesheet" href="css/profile.css">

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
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 active" href="profile.php">
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
            
            <!-- Profile Section -->
            <div class="profile-section mb-5 animate-fade-in">
                            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-fade-in mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-5"></i>
                    <div><?= htmlspecialchars($_GET['success']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
                <div class="profile-header">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar rounded-circle">
                            <?php if (!empty($picture)): ?>
                                <img src="<?php echo htmlspecialchars($picture); ?>" 
                                     alt="Profile Picture" 
                                     class="profile-avatar-img rounded-circle">
                            <?php else: ?>
                                <div class="profile-avatar-initials rounded-circle d-flex align-items-center justify-content-center">
                                    <?php echo strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ms-4 text-white">
                            <h2 class="h1 mb-1"><?php echo $firstName . ' ' . $lastName; ?></h2>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning text-dark px-3 py-2 me-2">
                                    <i class="fas fa-user-tie me-1"></i> <?php echo ucfirst($status); ?> Instructor
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-graduation-cap me-1"></i> <?php echo $department; ?>
                                </span>
                            </div>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-envelope me-1"></i> <?php echo $email; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="profile-info-card h-100">
                                <h4 class="mb-4" style="color: var(--dark-green);">
                                    <i class="fas fa-info-circle me-2"></i>Personal Information
                                </h4>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Instructor ID</h6>
                                        <p><?php echo htmlspecialchars($user_id); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Full Name</h6>
                                        <p><?php echo $firstName . ' ' . $lastName; ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Email Address</h6>
                                        <p><?php echo htmlspecialchars($email); ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($contact)): ?>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Contact Number</h6>
                                        <p><?php echo htmlspecialchars($contact); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Department</h6>
                                        <p><?php echo htmlspecialchars($department); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Role</h6>
                                        <p>
                                            <span class="badge <?php echo $status === 'active' ? 'badge-success-custom' : 'badge-warning-custom'; ?> px-3 py-1">
                                                Instructor/Faculty
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="profile-info-card h-100">
                                <h4 class="mb-4" style="color: var(--dark-green);">
                                    <i class="fas fa-chart-line me-2"></i>Account Summary
                                </h4>
                                
                                <div class="info-item">
                                    <div class="info-icon" style="background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(66, 165, 245, 0.1)); color: #2196f3;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Total Students</h6>
                                        <p class="h4 mb-0" style="color: #2196f3;">
                                            <?php
                                            $countStudentsQuery = mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) AS Accepted 
                                                                            FROM studenrollstatus 
                                                                            WHERE status = 'approved' 
                                                                            AND instructor_id = '$user_id' ");
                                            if (mysqli_num_rows($countStudentsQuery) > 0) {
                                                $row = mysqli_fetch_assoc($countStudentsQuery);
                                                echo $row['Accepted'];
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon" style="background: linear-gradient(135deg, rgba(0, 149, 79, 0.1), rgba(0, 200, 83, 0.1)); color: var(--light-green);">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Active Courses</h6>
                                        <p class="h4 mb-0" style="color: var(--light-green);">
                                            <?php
                                            $selectSubCount = mysqli_query($conn, "SELECT COUNT(course_code) AS Courses FROM instructor_courses WHERE instructor_id = '$user_id' AND status = 'Active'");
                                            if (mysqli_num_rows($selectSubCount) > 0) {
                                                $row = mysqli_fetch_assoc($selectSubCount);
                                                echo $row['Courses'];
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 167, 38, 0.1)); color: #ff9800;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="info-content">
                                        <h6>Pending Requests</h6>
                                        <p class="h4 mb-0" style="color: #ff9800;">
                                            <?php
                                            $countPendingQuery = mysqli_query($conn, "SELECT COUNT(status) AS pending FROM studenrollstatus WHERE status = 'pending' AND instructor_id = '$user_id' ");
                                            if (mysqli_num_rows($countPendingQuery) > 0) {
                                                $row = mysqli_fetch_assoc($countPendingQuery);
                                                echo $row['pending'];
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-top">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#voluntaryChangePasswordModal" class="btn btn-outline-success-custom w-100">
                                        <i class="fas fa-key me-1"></i> Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
    
<!-- Voluntary Change Password Modal (Triggered by button) -->
<div class="modal fade" id="voluntaryChangePasswordModal" tabindex="-1" aria-labelledby="voluntaryChangePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voluntaryChangePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="voluntaryChangePasswordForm" action="edit_password.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_id?>">
                    
                    <!-- New Password Field -->
                    <div class="form-floating-custom mb-4">
                        <label for="newPasswordVoluntary">
                            <i class="fas fa-lock me-1"></i> New Password
                        </label>
                        <input type="password" class="form-control" id="newPasswordVoluntary" 
                               placeholder="New Password" required>

                        <div class="password-strength-container mt-2">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="password-strength-bar">
                                    <div id="passwordStrengthVoluntary" class="password-strength-fill"></div>
                                </div>
                                <span id="strengthLevelVoluntary" class="strength-level"></span>
                            </div>
                            <small id="strengthTextVoluntary" class="text-muted"></small>
                            <div id="newPasswordMessageVoluntary" class="validation-message mt-1"></div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="form-floating-custom mb-4">
                        <label for="confirmPasswordVoluntary">
                            <i class="fas fa-lock me-1"></i> Confirm Password
                        </label>
                        <input type="password" class="form-control" id="confirmPasswordVoluntary" name="password" 
                               placeholder="Confirm Password" required>

                        <div id="confirmPasswordMessageVoluntary" class="validation-message mt-1"></div>
                    </div>
                    
                    <div class="modal-footer border-top-0 pt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="submit" class="btn btn-success-custom px-4" id="voluntaryChangePasswordBtn">
                            <i class="fas fa-save me-1"></i> Change Password
                        </button>
                    </div>
                </form>
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
// Voluntary Password Change Modal Script
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInputVoluntary = document.getElementById('newPasswordVoluntary');
    const confirmPasswordInputVoluntary = document.getElementById('confirmPasswordVoluntary');
    const passwordStrengthBarVoluntary = document.getElementById('passwordStrengthVoluntary');
    const strengthLevelTextVoluntary = document.getElementById('strengthLevelVoluntary');
    const strengthTextVoluntary = document.getElementById('strengthTextVoluntary');
    const voluntaryChangePasswordForm = document.getElementById('voluntaryChangePasswordForm');
    const voluntarySubmitButton = document.getElementById('voluntaryChangePasswordBtn');
    
    // Get message elements for voluntary modal
    const newPasswordMessageVoluntary = document.getElementById('newPasswordMessageVoluntary');
    const confirmPasswordMessageVoluntary = document.getElementById('confirmPasswordMessageVoluntary');
    
    // Initialize button state for voluntary modal
    if (voluntarySubmitButton) {
        voluntarySubmitButton.disabled = true;
        voluntarySubmitButton.style.opacity = '0.6';
        voluntarySubmitButton.style.cursor = 'not-allowed';
    }
    
    // Check if passwords match for voluntary modal
    function checkPasswordsMatchVoluntary() {
        const newPassword = newPasswordInputVoluntary.value;
        const confirmPassword = confirmPasswordInputVoluntary.value;
        
        if (confirmPassword.length === 0) {
            confirmPasswordInputVoluntary.style.borderColor = '';
            if (confirmPasswordMessageVoluntary) {
                confirmPasswordMessageVoluntary.textContent = '';
                confirmPasswordMessageVoluntary.style.color = '';
            }
            return false;
        }
        
        if (newPassword === confirmPassword) {
            confirmPasswordInputVoluntary.style.borderColor = '#27ae60';
            if (confirmPasswordMessageVoluntary) {
                confirmPasswordMessageVoluntary.textContent = '✓ Passwords match';
                confirmPasswordMessageVoluntary.style.color = '#27ae60';
            }
            return true;
        } else {
            confirmPasswordInputVoluntary.style.borderColor = '#e74c3c';
            if (confirmPasswordMessageVoluntary) {
                confirmPasswordMessageVoluntary.textContent = '✗ Passwords do not match';
                confirmPasswordMessageVoluntary.style.color = '#e74c3c';
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
    
    // Update password strength indicator for voluntary modal
    function updatePasswordStrengthVoluntary() {
        const password = newPasswordInputVoluntary.value;
        const strength = calculatePasswordStrength(password);
        
        const percentage = (strength / 5) * 100;
        if (passwordStrengthBarVoluntary) {
            passwordStrengthBarVoluntary.style.width = `${percentage}%`;
        }
        
        let levelText = '';
        let barColor = '';
        
        if (password.length === 0) {
            levelText = '';
            barColor = '#e74c3c';
            newPasswordInputVoluntary.style.borderColor = '';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '';
                newPasswordMessageVoluntary.style.color = '';
            }
        } else if (strength <= 1) {
            levelText = 'Very Weak';
            barColor = '#e74c3c';
            newPasswordInputVoluntary.style.borderColor = '#e74c3c';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '✗ Use at least 8 characters with variety';
                newPasswordMessageVoluntary.style.color = '#e74c3c';
            }
        } else if (strength === 2) {
            levelText = 'Weak';
            barColor = '#e67e22';
            newPasswordInputVoluntary.style.borderColor = '#e67e22';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '⚠️ Add more character types';
                newPasswordMessageVoluntary.style.color = '#e67e22';
            }
        } else if (strength === 3) {
            levelText = 'Fair';
            barColor = '#f1c40f';
            newPasswordInputVoluntary.style.borderColor = '#f1c40f';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '⚠️ Add one more character type';
                newPasswordMessageVoluntary.style.color = '#f1c40f';
            }
        } else if (strength === 4) {
            levelText = 'Good';
            barColor = '#2ecc71';
            newPasswordInputVoluntary.style.borderColor = '#2ecc71';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '✓ Good password strength';
                newPasswordMessageVoluntary.style.color = '#2ecc71';
            }
        } else if (strength === 5) {
            levelText = 'Strong';
            barColor = '#27ae60';
            newPasswordInputVoluntary.style.borderColor = '#27ae60';
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '✓ Excellent password strength!';
                newPasswordMessageVoluntary.style.color = '#27ae60';
            }
        }
        
        if (passwordStrengthBarVoluntary) {
            passwordStrengthBarVoluntary.style.background = barColor;
        }
        if (strengthLevelTextVoluntary) {
            strengthLevelTextVoluntary.textContent = levelText;
            strengthLevelTextVoluntary.style.color = barColor;
        }
        
        if (strengthTextVoluntary) {
            if (password.length === 0) {
                strengthTextVoluntary.textContent = 'Password strength indicator';
                strengthTextVoluntary.style.color = '#666';
            } else {
                strengthTextVoluntary.textContent = 'Include uppercase, numbers & symbols';
                strengthTextVoluntary.style.color = barColor;
            }
        }
        
        return strength;
    }
    
    // Validate voluntary form and enable/disable button
    function validateVoluntaryForm() {
        const newPassword = newPasswordInputVoluntary.value;
        const confirmPassword = confirmPasswordInputVoluntary.value;
        
        const strength = calculatePasswordStrength(newPassword);
        const isPasswordStrong = strength >= 4;
        const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;

        
        if (isPasswordStrong && passwordsMatch) {
            voluntarySubmitButton.disabled = false;
            voluntarySubmitButton.style.opacity = '1';
            voluntarySubmitButton.style.cursor = 'pointer';
        } else {
            voluntarySubmitButton.disabled = true;
            voluntarySubmitButton.style.opacity = '0.6';
            voluntarySubmitButton.style.cursor = 'not-allowed';
        }
    }
    
    // Event listeners for voluntary modal
    if (newPasswordInputVoluntary && confirmPasswordInputVoluntary) {

        
        // New password validation
        newPasswordInputVoluntary.addEventListener('input', function() {
            updatePasswordStrengthVoluntary();
            checkPasswordsMatchVoluntary();
            validateVoluntaryForm();
        });
        
        // Confirm password validation
        confirmPasswordInputVoluntary.addEventListener('input', function() {
            checkPasswordsMatchVoluntary();
            validateVoluntaryForm();
        });
        
        // Form submission handler for voluntary modal
        if (voluntaryChangePasswordForm) {
            voluntaryChangePasswordForm.addEventListener('submit', function(event) {
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = newPasswordInputVoluntary.value;
                const confirmPassword = confirmPasswordInputVoluntary.value;
                
                const strength = calculatePasswordStrength(newPassword);
                const isPasswordStrong = strength >= 4;
                const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;
                const hasCurrentPassword = currentPassword.length > 0;
                
                if (!isPasswordStrong || !passwordsMatch) {
                    event.preventDefault();
                    updatePasswordStrengthVoluntary();
                    checkPasswordsMatchVoluntary();
                    validateVoluntaryForm();
                    
                    
                }
            });
        }
    }
    
    // Initial validation for voluntary modal
    if (newPasswordInputVoluntary) {
        updatePasswordStrengthVoluntary();
        checkPasswordsMatchVoluntary();
        validateVoluntaryForm();
    }
    
    // Reset voluntary modal when closed
    const voluntaryModal = document.getElementById('voluntaryChangePasswordModal');
    if (voluntaryModal) {
        voluntaryModal.addEventListener('hidden.bs.modal', function () {
            // Reset form
            if (voluntaryChangePasswordForm) {
                voluntaryChangePasswordForm.reset();
            }
            
            // Reset validation messages
            if (newPasswordMessageVoluntary) {
                newPasswordMessageVoluntary.textContent = '';
            }
            if (confirmPasswordMessageVoluntary) {
                confirmPasswordMessageVoluntary.textContent = '';
            }
            if (passwordStrengthBarVoluntary) {
                passwordStrengthBarVoluntary.style.width = '0%';
            }
            if (strengthLevelTextVoluntary) {
                strengthLevelTextVoluntary.textContent = '';
            }
            if (strengthTextVoluntary) {
                strengthTextVoluntary.textContent = 'Password strength indicator';
                strengthTextVoluntary.style.color = '#666';
            }
            
            // Reset button state
            if (voluntarySubmitButton) {
                voluntarySubmitButton.disabled = true;
                voluntarySubmitButton.style.opacity = '0.6';
                voluntarySubmitButton.style.cursor = 'not-allowed';
            }
            
            // Reset border colors
            const inputs = voluntaryModal.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.style.borderColor = '';
            });
        });
    }
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

           // Also for edit profile form
            const editProfileForm = document.getElementById('editProfileForm');
            if (editProfileForm) {
                editProfileForm.addEventListener('submit', function() {
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                    loadingModal.show();
                });
            }

        });

        // Close sidebar when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeNav(); // Close sidebar if open when resizing to desktop
            }
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