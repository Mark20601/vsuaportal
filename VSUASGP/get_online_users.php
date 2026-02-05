<?php
include('connection.php');
// Users who pinged in the last 20 seconds
$threshold = date("Y-m-d H:i:s", strtotime("-20 seconds"));

$query = "SELECT l.user_id, u.first_name, u.last_name, u.userLevelID 
          FROM login l 
          JOIN users u ON l.user_id = u.user_id 
          WHERE l.last_activity > ? 
          ORDER BY l.last_activity DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $threshold);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $role = ($row['userLevelID'] == 1) ? "Student" : "Instructor";
        echo "<tr>
                <td>{$row['user_id']}</td>
                <td>{$row['first_name']} {$row['last_name']}</td>
                <td><span style='color: #28a745;'>● Online</span></td>
                <td>$role</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No users online</td></tr>";
}
?>