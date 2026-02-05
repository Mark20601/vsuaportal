<?php
session_start();
include('connection.php');



if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['user_id']) && isset($_POST['password'])) {
    function validate($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    $user = validate($_POST['user_id']);
    $password = validate($_POST['password']);

    if (empty($user)) {
        header("Location: index.php?error=User ID is Required!");
        exit();
    } elseif (empty($password)) {
        header("Location: index.php?error=Password is Required!");
        exit();
    } else {
        $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE user_id = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $login_attempts = $result->fetch_assoc();

        if ($login_attempts && $login_attempts['locked_out_until'] > date("Y-m-d H:i:s")) {
            // User is locked out
            $remaining_time = strtotime($login_attempts['locked_out_until']) - time();
            header("Location: index.php?errorAttempts= Too many attempts. Please try again in $remaining_time seconds.");
            exit();
        }

        $stmt = $conn->prepare("SELECT l.user_id, l.password, l.email, u.userLevelID, u.first_name, u.last_name, u.status 
                                FROM login l 
                                JOIN users u ON l.user_id = u.user_id 
                                WHERE l.user_id = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            $hashed_input = hash('sha256', $password);

            if ($hashed_input === $row['password']) {
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['status'] = $row['status'];
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['userLevelID'] = $row['userLevelID'];
                $status = $row['status'];
                $userLevel = $row['userLevelID'];
                $user_id = $row['user_id'];


                if ($login_attempts) {
                    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = 0, locked_out_until = NULL WHERE user_id = ?");
                    $stmt->bind_param("s", $user);
                    $stmt->execute();
                }
                if ($status == 'Pending') {
                    header("Location: index.php?error_warn=Pending Request!");
                    exit();
                } elseif ($status == 'Registering') {
                    header("Location: index.php?register=You need to register!");
                    exit();
                } else {
                    if ($userLevel == 1) {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['userLevelID'] = 1;
                        $_SESSION['student_logged_in'] = true;
                        unset($_SESSION['instructor_logged_in']);
                        date_default_timezone_set('Asia/Manila'); // PHP will use PH time
                        
                        $stmt = $conn->prepare("
                            UPDATE login 
                            SET last_activity = ?, is_logged_in = 1
                            WHERE user_id = ?
                        ");
                        
                        $now = date('Y-m-d H:i:s'); // PH time
                        $stmt->bind_param("ss", $now, $_SESSION['user_id']);
                        $stmt->execute();



                        header("Location: student/student-dashboard.php");
                        exit();
                    } elseif ($userLevel == 2) {
                         $_SESSION['user_id'] = $user_id;
                         $_SESSION['userLevelID'] = 2;
                         $_SESSION['instructor_logged_in'] = true;
                        unset($_SESSION['student_logged_in']);
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
                        exit();
                    }
                }
            } else {
                if ($login_attempts) {
                    $new_attempts = $login_attempts['attempts'] + 1;
                    $locked_out_until = NULL;

                    if ($new_attempts >= 3) {
                        $locked_out_until = date("Y-m-d H:i:s", time() + 180); // 3 minutes from now
                    }
                    $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, locked_out_until = ? WHERE user_id = ?");
                    $stmt->bind_param("iss", $new_attempts, $locked_out_until, $user);
                    $stmt->execute();
                } else {
         
                    $stmt = $conn->prepare("INSERT INTO login_attempts (user_id, attempts, locked_out_until) VALUES (?, 1, NULL)");
                    $stmt->bind_param("s", $user);
                    $stmt->execute();
                }

                header("Location: index.php?error=Incorrect User ID or Password");
                exit();
            }
        } else {
            header("Location: index.php?error=Incorrect User ID or Password!");
            exit();
        }

        $stmt->close();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
