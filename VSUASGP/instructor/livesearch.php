<?php

header("Access-Control-Allow-Origin: https://vsuasgp.com"); // for testing only
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


include("../connection.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['input'])) {
    $program = $_POST['program'] ?? '';
    $year = $_POST['year'] ?? '';
    $section = $_POST['section'] ?? '';
    $course_code = $_POST['course_code'] ?? '';
    $user_id = $_POST['id'] ?? '';
    $input = $_POST['input'];

$sql = "SELECT DISTINCT
            u.user_id AS User_ID,
            u.last_name AS `Last Name`,
            u.first_name AS `First Name`,
            g.grades AS Grades,
            sp.program AS Programs,
            sp.year AS Year,
            sp.section AS Section,
            ic.course_code AS `Course Code`,
            ic.course_title AS `Course Title`,
            ic.semester AS Semester
        FROM users u
        JOIN studprograms sp ON u.user_id = sp.student_id 
        JOIN studenrollstatus ses ON sp.student_id = ses.student_id 
        JOIN instructor_courses ic ON ses.course_code = ic.course_code 
                                   AND ses.instructor_id = ic.instructor_id
                                   AND ic.course_code = ?
                                   AND ic.status = 'Active'
        LEFT JOIN grades g ON ses.student_id = g.student_id 
                          AND ses.course_code = g.course_code 
                          AND ses.instructor_id = g.instructor_id
        WHERE sp.program = ?
          AND sp.year = ?
          AND sp.section = ?
          AND ses.instructor_id = ?
          AND (
              u.first_name LIKE CONCAT('%', ?, '%') OR 
              u.last_name LIKE CONCAT('%', ?, '%') OR 
              u.user_id LIKE CONCAT('%', ?, '%')
          )
        ORDER BY u.last_name, u.first_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $course_code, $program, $year, $section, $user_id, $input, $input, $input);

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

				?>
                
                                    	 <tr class="border-bottom">
                                            <td class="py-3 px-4 align-middle fw-medium"><?php echo $row['User_ID'] ?></td>
                                            <td class="py-3 px-4 align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <span class="avatar-title bg-light rounded-circle text-primary fw-bold">
                                                            <?php echo substr($row['First Name'], 0, 1) . substr($row['Last Name'], 0, 1) ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo $row['Last Name'] ?>, <?php echo $row['First Name'] ?></h6>
                                                        <small class="text-muted"><?php echo $row['Programs'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 align-middle">
                                                <span class="badge bg-light text-dark border"><?php echo $row['Course Code'] ?></span>
                                            </td>
                                            <td class="py-3 px-4 align-middle"><?php echo $row['Course Title'] ?></td>
                                            <td class="py-3 px-4 align-middle">
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <?php echo $row['Semester'] ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 align-middle ">
                                            <?php echo $row['Grades'] ?>
                                            </td>
                                            <td class="action-buttons">
                                                <button class="btn btn-success change-grade-btn" 
                                                        data-student-id="<?php echo $row['User_ID']; ?>"
                                                        data-course-code="<?php echo $row['Course Code']; ?>"
                                                        data-current-grade="<?php echo $row['Grades']; ?>">
                                                    <i class="fas fa-edit"></i> Change Grade
                                                </button>
                                                
                                                <div class="grade-input-container">
                                                    <form action="submit-grades.php" method="POST" class="grade-form">
                                                        <input type="hidden" name="instructor_id" value="<?php echo $user_id ?>">
                                                        <input type="hidden" name="student_id" value="<?php echo $row['User_ID']; ?>">
                                                        <input type="hidden" name="course_code" value="<?php echo $row['Course Code']; ?>">
                                                        <input type="hidden" name="program" value="<?php echo $program; ?>">
                                                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                                                        <input type="hidden" name="section" value="<?php echo $section; ?>">
                                                
                                                        
                                                        <div class="input-group" style="width: 100px;">
                                                            <input type="text" name="grade" class="form-control" 
                                                                required>
                                                        </div>
                                                        
                                                        <button type="submit" name="submit" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-check"></i> Save
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm cancel-grade-btn">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                <?php
    }
}
$stmt->close();
}   
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>



<script>
$(document).on('click', '.change-grade-btn', function() {
    $(this).hide().next('.grade-input-container').show();
});

$(document).on('click', '.cancel-grade-btn', function() {
    $(this).closest('.grade-input-container').hide()
           .prev('.change-grade-btn').show();
});


</script>

</body>

