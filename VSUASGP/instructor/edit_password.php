<?php
include("../connection.php");

if (isset($_POST['user_id']) && isset($_POST['password']) && isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['password'];

    // Hash the new password
    $hashed = hash('sha256', $new_password);

    // Update login table and mark unchangePass = 2
    $stmt = $conn->prepare("UPDATE login SET password = ? WHERE user_id = ?");
    $stmt->bind_param("ss", $hashed, $user_id);
    if ($stmt->execute()) {
        header("Location: profile.php?success= Password Successfully Changed!");
        exit();
    } else {
        http_response_code(500);
        echo "Error updating password";
    }
}
?>
