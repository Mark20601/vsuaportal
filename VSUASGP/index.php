<?php
session_start();
require_once 'vendor/autoload.php';
include("connection.php");

// Google Client Setup
$client = new Google_Client();
$client->setClientId('91227571241-q7j0lqc2do8hlub2l7plnfnmfoajrbkc.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://www.vsuasgp.com/VSUASGP/index.php');
$client->addScope("email");
$client->addScope("profile");

// Default
$email = '';
$name = '';
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    $google_oauth = new Google_Service_Oauth2($client);
    $info = $google_oauth->userinfo->get();

    $email = $info->email;
    $name_parts = explode(" ", $info->name);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';

    /* ===============================================================
       1. FIRST → CHECK IF USER EXISTS IN users TABLE
       =============================================================== */
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();

    if (!$userData) {
        // Not registered anywhere
        header("Location: index.php?error=Invalid Access!");
        exit();
    }

    $userLevel = $userData['userLevelID']; // 1 = student, 2 = instructor
    $status    = $userData['status'];      // Pending, Accepted, Denied

    /* ===============================================================
       2. IF STUDENT → CHECK registering_users TABLE
       =============================================================== */
    if ($userLevel == 1) {

        // Check registering_users
        $stmt = $conn->prepare("SELECT status FROM registering_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $reg = $stmt->get_result()->fetch_assoc();

        if (!$reg) {
            header("Location: index.php?error=Please complete registration/payment");
            exit();
        }

        if (strtolower($reg['status']) === 'pending') {
            header("Location: index.php?error=Your account is waiting for approval");
            exit();
        }

        if (strtolower($reg['status']) !== 'approved') {
            header("Location: index.php?error=Please complete registration/payment");
            exit();
        }
    }

    /* ===============================================================
       3. INSTRUCTOR → SKIP registering_users CHECK
       =============================================================== */
    else if ($userLevel == 2) {

        if ($status !== 'Accepted') {
            header("Location: index.php?error_warn=Your account waiting for approval");
            exit();
        }
    }

    /* ===============================================================
       4. CHECK LOGIN TABLE
       =============================================================== */
    $stmt = $conn->prepare("
        SELECT l.user_id, u.first_name, u.last_name, u.status, u.userLevelID
        FROM login l 
        JOIN users u ON l.user_id = u.user_id
        WHERE u.email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $loginData = $stmt->get_result()->fetch_assoc();

    if (!$loginData) {
        header("Location: index.php?error=Login record not found");
        exit();
    }

    if ($loginData['status'] !== 'Accepted') {
        header("Location: index.php?error_warn=Your account waiting for approval");
        exit();
    }

   /* ===============================================================
       5. SET SESSION AND REDIRECT BASED ON ROLE
       =============================================================== */
    $_SESSION['first_name']  = $loginData['first_name'];
    $_SESSION['last_name']   = $loginData['last_name'];
    $_SESSION['email']       = $email;
    $_SESSION['user_id']     = $loginData['user_id'];
    $_SESSION['userLevelID'] = $loginData['userLevelID'];
    $_SESSION['status']      = $loginData['status'];
    $_SESSION['picture']     = $info->picture ?? '';

    if ($userLevel == 1) {
        date_default_timezone_set('Asia/Manila'); // PHP will use PH time
         $_SESSION['userLevelID'] = 1;
         $_SESSION['student_logged_in'] = true;
        $stmt = $conn->prepare("
            UPDATE login 
            SET last_activity = ?, is_logged_in = 1
            WHERE user_id = ?
        ");
        
        $now = date('Y-m-d H:i:s'); // PH time
        $stmt->bind_param("ss", $now, $_SESSION['user_id']);
        $stmt->execute();


        header("Location: student/student-dashboard.php");
    } else if ($userLevel == 2) {
        $_SESSION['userLevelID'] = 2;
        $_SESSION['instructor_logged_in'] = true;
        date_default_timezone_set('Asia/Manila'); // PHP will use PH time
        
        $stmt = $conn->prepare("
            UPDATE login 
            SET last_activity = ?, is_logged_in = 1
            WHERE user_id = ?
        ");
        
        $now = date('Y-m-d H:i:s'); // PH time
        $stmt->bind_param("ss", $now, $_SESSION['user_id']);
        $stmt->execute();




        header("Location: instructor/dashboard.php");
    } else {
        header("Location: index.php?error=Invalid user level");
    }

    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign In</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="/VSUASGP/fontawesome6.0.0/css/all.min.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css"  href="/VSUASGP/bootstrap/css/bootstrap.min.css">
    <link  href="/VSUASGP/mdb/css/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css"  href="/VSUASGP/boxicons-2.1.4/css/boxicons.min.css">  
      <link href="/VSUASGP/fontawesome6.0.0/css/all.min.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css" href="/VSUASGP/bootstrap/css/bootstrap.min.css">
    <link href="/VSUASGP/mdb/css/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css" href="/VSUASGP/boxicons-2.1.4/css/boxicons.min.css">
    <link href="/VSUASGP/index.css" rel="stylesheet"/>
    <link rel="icon" href="/VSUASGP/pictures/vsu-icon.ico" type="image/x-icon">

    </head>
<body style="
    background: 
     linear-gradient(rgba(8, 16, 12, 0.9), rgba(12, 24, 18, 0.92)),
    url('pictures/vsuaGate.png');
background-size: cover;
background-position: center;
background-repeat: no-repeat;
background-attachment: fixed;
min-height: 100vh;">
  

  <div class="signin-container">
    <div class="signin-card">
      <form class="signin-form" action="login.php" method="POST">
        <h4 class="text-center">Welcome to VSUA Student Grading Portal</h4>
        
        <div class="phase-notice">
            <div class="phase-header">
                <span class="phase-tag">Phase 1</span>
                <h5 class="phase-title">v1.001b</h5>
            </div>
            
            <a href="choose_roles/choose_roles.php" class="feedback-link">
                New user? Sign up here.
            </a>
        </div>
        <?php if (isset($_GET['pending'])): ?>
          <div class="alert alert-notice">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Notice</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['pending']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['error_warn'])): ?>
          <div class="alert alert-warning">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Notice</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['error_warn']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['errorAttempts'])): ?>
          <div class="alert alert-error">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Error</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['errorAttempts']) ?></div>
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
              <div class="alert-title">Pending: </div>
              <div class="alert-message"><?= htmlspecialchars($_GET['success']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error">
            <div class="alert-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-content">
              <div class="alert-title">Error</div>
              <div class="alert-message"><?= htmlspecialchars($_GET['error']) ?></div>
            </div>
            <div class="alert-close">&times;</div>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label for="user_id">User ID</label>
          <input type="text" id="user_id" name="user_id" class="form-control" placeholder="Enter your user ID" required />
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-input-container">
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required />
            <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
          </div>
        </div>
        <button type="submit" class="btn btn-outline-dark" >Login</button>
        
        <div class="divider">or</div>
        
        <a href="<?php echo $client->createAuthUrl() ?>" class="google-btn btn btn-outline-info">
          <img src="pictures/web_light_rd_na@2x.png" alt="Google logo">
          Sign in with Google
        </a>
        <div class="developers-section">
            <div class="divider">Developers</div>
            <div class="developers-names" style="font-size: 90%;">
                <span>M. F. Sabinator</span> • 
                <span>H. F. Villafuerte</span> • 
                <span>P. V. Royo</span> • 
                <span>D. J. Barangan</span> • 
                <span>C. J. Orfinada</span> • 
                <span>C. J. Cuenca</span> • 
                <span>G. H. Conge</span>
                </div>
            </div>
      </form>
    </div>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Alert close functionality
      document.querySelectorAll('.alert-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
          const alert = this.closest('.alert');
          alert.style.animation = 'alertEnter 0.3s reverse forwards';
          setTimeout(() => alert.remove(), 300);
        });
      });
      
      // Auto-dismiss alerts after 5 seconds
      setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
          alert.style.animation = 'alertEnter 0.3s reverse forwards';
          setTimeout(() => alert.remove(), 300);
        });
      }, 5000);
    });
  </script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const togglePassword = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');
  
  togglePassword.addEventListener('click', function() {
    // Toggle the type attribute
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    
    // Toggle the eye icon
    this.classList.toggle('fa-eye-slash');
    this.classList.toggle('fa-eye');
  });
});
</script>
</body>
</html>