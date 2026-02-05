<?php
session_start();
include("connection.php");
// Redirect if not from Google sign-in or not in 'Registering' state
if (!isset($_SESSION['new_user_email']) || $_SESSION['status'] !== 'Registering') {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['new_user_email'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="pictures/vsu-icon.ico" type="image/x-icon">
    <!-- Add this to your HTML <head> -->
    <link href="bootstrap5.3.3/css/bootstrap.min.css" rel="stylesheet">


         <script src="jquery-3.7.1.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7fafc;
        }
        .registration-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .registration-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }
        #confirmPassword:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
            outline: none;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .password-input-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #718096;
        }
        .id-format-hint, .password-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
            display: none;
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .form-checkbox input {
            margin-right: 10px;
        }
        .text-link {
            color: #4299e1;
            text-decoration: none;
        }
        .text-link:hover {
            text-decoration: underline;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #4299e1;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background-color: #3182ce;
        }
        /* Modal styles */
        #termsModal {
            transition: opacity 0.3s ease;
        }
        /* Alert styles */
        .alert {
            position: relative;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }
        /* Mobile-specific adjustments */
@media (max-width: 640px) {
    .registration-container {
        padding: 10px;
        align-items: flex-start; /* Better for short screens */
    }

    .registration-card {
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
        font-size: 1.3rem;
        margin-bottom: 20px;
    }

    .form-group input, 
    .form-group select, 
    .submit-btn {
        padding: 14px 12px; /* Larger tap targets */
        font-size: 16px; /* Better mobile readability */
    }

    .form-row {
        flex-direction: column; /* Stack on mobile */
        gap: 10px;
    }

    /* Modal adjustments */
    #termsModal > div {
        width: 90%;
        margin: 20px auto;
        max-height: 70vh;
    }

    /* Hide non-critical hints on mobile */
    .id-format-hint, .password-hint {
        font-size: 11px;
    }
}
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-card">
            <div class="header">
                <h1>Create Your Account</h1>
            </div>

            <form id="registrationForm" action="submit_registration.php" method="POST">
                <div class="form-container">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div id="step1" class="form-step step-active">
                        <div class="form-group">
                        <label for="role">I am registering as a</label>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Instructor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id" id="idLabel">User ID</label>
                        <input type="text" id="id" name="id" placeholder="Enter your ID" required disabled>
                        <p id="studentIdHint" class="id-format-hint">Format: aXX-XX-XXXXX (Only input the numbers.)</p>
                        <p id="instructorIdHint" class="id-format-hint">Format: VA-XXXXX (Only input the numbers.)</p>
                    </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close">&times;</button>
                            </div>
                        <?php endif; ?>
                       
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="first_name"  value="<?php echo htmlspecialchars($first_name); ?>" required>
                                <div class="exceed" id="exceed-firstName"></div>
                            </div>
                                                    <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                <div class="exceed" id="exceed-lastName"></div>
                            </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-input-container">
                                <input type="password" id="password" name="password"  required>
                                <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                            </div>
                            <p class="password-hint" id="hint">Password must be at least 8 characters with numbers and special characters</p>
                            <div id="feedback1"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <div class="password-input-container">
                                <input type="password" id="confirmPassword" name="confirm_password"  required>
                                <i class="fas fa-eye-slash toggle-password" id="toggleConfirmPassword"></i>
                                
                            </div>
                            <div id="feedback2"></div>
                        </div>

                        <?php if (isset($_GET['mismatched'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> <?php echo htmlspecialchars($_GET['mismatched']); ?>
                                <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close">&times;</button>
                            </div>
                        <?php endif; ?>

                        <div class="form-checkbox">
                           
                            <label for="terms">
                                <input type="checkbox" id="agree" style="cursor: pointer;">
                                I agree to the 
                                <a href="#" onclick="openModal()" class="text-link underline">Terms and Conditions</a>
                            </label>
                        </div>
                        
                        <div class="text-center text-sm text-gray-600 mb-4">
                            Already have an account? <a href="index.php" class="text-link">Sign in</a>
                        </div>

                        <button type="submit" id="submitBtn" name="submit" class="submit-btn">Create Account</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Terms & Agreement Modal -->
    <div id="termsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full relative">
            <h2 class="text-2xl font-bold mb-4">Terms & Conditions / Data Privacy Agreement</h2>
            <div class="max-h-60 overflow-y-auto text-gray-700 text-sm">
                <p><strong>Data Privacy Notice:</strong> By registering, you agree to allow us to collect, store, and process your personal data (e.g., student ID, email, name) for the purpose of academic record integration, system access control, and performance analysis.</p>

                <p class="mt-2">Your data will be handled in accordance with the Data Privacy Act of 2025 and will only be accessible to authorized school personnel. You have the right to access, modify, and request deletion of your data upon request.</p>

                <p class="mt-2">You also agree not to share your login credentials with anyone and to abide by the school's acceptable use policies regarding system access.</p>

                <p class="mt-2">If you have any questions, contact the system administrator at <a href="mailto:support@vsu.edu.ph" class="text-blue-600 underline">support@vsu.edu.ph</a>.</p>
            </div>
            <div class="mt-6 text-right">
                <button onclick="closeModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
            const confirmPassword = document.querySelector('#confirmPassword');
            
            if (togglePassword && password) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                    this.classList.toggle('fa-eye');
                });
            }
            
            if (toggleConfirmPassword && confirmPassword) {
                toggleConfirmPassword.addEventListener('click', function() {
                    const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPassword.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                    this.classList.toggle('fa-eye');
                });
            }
            
            // Auto-dismiss alert after 5 seconds
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });

        function updateIdField() {
            const roleSelect = document.getElementById('role');
            const idInput = document.getElementById('id');
            const idLabel = document.getElementById('idLabel');
            const studentHint = document.getElementById('studentIdHint');
            const instructorHint = document.getElementById('instructorIdHint');
            
             // Reset all hints
                studentHint.style.display = 'none';
                instructorHint.style.display = 'none';

                // Clear input value every time role changes
                idInput.value = '';



            
            if (roleSelect.value === 'student') {
                idLabel.textContent = 'Student ID';
                idInput.placeholder = 'aXX-XX-XXXXX(Only input the numbers.)';
                studentHint.style.display = 'block';
                
                // Add input mask for student ID format (aXX-XX-XXXXX)
                idInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = 'a' + value;
                        if (value.length > 3) {
                            value = value.slice(0, 3) + '-' + value.slice(3);
                        }
                        if (value.length > 6) {
                            value = value.slice(0, 6) + '-' + value.slice(6);
                        }
                        // Limit to aXX-XX-XXXXX format (11 characters total)
                        value = value.slice(0, 12);
                    }
                    e.target.value = value;
                });
            } if (roleSelect.value === 'faculty') {
                idLabel.textContent = 'Instructor ID';
                idInput.placeholder = 'VA-XXXXX (Only input the numbers.)';
                instructorHint.style.display = 'block';
                
                // Add input mask for instructor ID format (aXXX)
                idInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = 'VA' + value;
                    }if (value.length > 2) {
                            value = value.slice(0, 2) + '-' + value.slice(2);
                    }if (value.length > 8) {
                            value = value.slice(0, 8) 
                    }
                    e.target.value = value;
                });
            } else {
                idLabel.textContent = 'User ID';
                idInput.placeholder = 'Enter your ID';
                idInput.parentNode.replaceChild(newInput, idInput);
                idInput = newInput;

            }
        }

        // Add event listener for role change
        document.getElementById('role').addEventListener('change', updateIdField);

        // Initialize the field based on any pre-selected role
        document.addEventListener('DOMContentLoaded', function() {
            updateIdField();
        });

        function openModal() {
            document.getElementById("termsModal").classList.remove("hidden");
        }

        function closeModal() {
            document.getElementById("termsModal").classList.add("hidden");
        }
    </script>
     <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript">


$(document).ready(function(){
    $("#firstName, #lastName").on('input', function(){
        var maxLength = 50;
        var input = $(this).val();
        var inputId = $(this).attr('id');  // 'firstName' or 'lastName'
        var messageDiv = $("#exceed-" + inputId);

        if(input.length > maxLength){
            // Trim the extra characters
            $(this).val(input.substring(0, maxLength));
            messageDiv.html("<small class='text-danger'>First Name and Last Name must not exceed 50 characters.</small>");
        } else {
            messageDiv.html("");
        }
    });
});

$(document).ready(function(){
    $("#role").on("change", function(){
        const selectedRole = $(this).val();

        $.ajax({
            url: 'checkRole.php',
            method: 'POST',
            dataType: 'json',
            data: { role: selectedRole },
            success: function(response){
                if (response.validRole) {
                    $("#id").prop("disabled", false);
                } else {
                    $("#id").prop("disabled", true).val('');
                }
            }
        });
    });
});
$(document).ready(function () {
    // Disable confirm password field by default
    $("#confirmPassword").prop("disabled", true);

    $("#password, #confirmPassword").on('input', function (e) {
        var pwd1 = $("#password").val();
        var pwd2 = $("#confirmPassword").val();

        $.ajax({
            url: "checkpassword.php",
            method: "POST",
            data: {
                password1: pwd1,
                password2: pwd2
            },
            dataType: "json",
            success: function (response) {
                $("#feedback1").html(response.feedback1);
                $("#feedback2").html(response.feedback2);
                $("#hint").hide();

                // Determine which field is being typed in
                if (e.target.id === "password") {
                    $("#feedback1").show();
                    $("#feedback2").hide();
                } else if (e.target.id === "confirmPassword") {
                    $("#feedback2").show();
                    $("#feedback1").hide();
                }

                // Enable or disable confirm password field based on password strength
                if (response.feedback1.includes("Strong Password")) {
                    $("#confirmPassword").prop("disabled", false);
                } else {
                    $("#confirmPassword").prop("disabled", true);
                    $("#confirmPassword").val(""); // clear confirm password if password becomes weak again
                    $("#feedback2").html("");
                }
            }
        });
    });
});



$(document).ready(function () {
    // Initially disable submit button
    $('#submitBtn').prop('disabled', true);
    $('#submitBtn').css('background-color', '#ccc');
    $('#submitBtn').css('cursor', 'not-allowed');

    function checkFormAndEnableButton() {
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        const agree = $('#agree').is(':checked');

        const allFieldsFilled = firstName !== '' && lastName !== '' && password !== '' && confirmPassword !== '' && agree;
        if (allFieldsFilled) {
            $.ajax({
                url: 'checkpassword.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    password1: password,
                    password2: confirmPassword
                },
                success: function (response) {
                    $('#feedback1').html(response.feedback1);
                    $('#feedback2').html(response.feedback2);

                    if (response.validMatch) {
                        $('#submitBtn').prop('disabled', false);
                        $('#submitBtn').css('background-color', '#4299e1');
                        $('#submitBtn').css('cursor', 'pointer');
                    } else {
                        $('#submitBtn').prop('disabled', true);
                        $('#submitBtn').css('background-color', '#ccc');
                        $('#submitBtn').css('cursor', 'not-allowed');
                    }
                },
                error: function () {
                    $('#submitBtn').prop('disabled', true);
                    $('#submitBtn').css('background-color', '#ccc');
                    $('#submitBtn').css('cursor', 'not-allowed');
                }
            });
        } else {
            $('#submitBtn').prop('disabled', true);
            $('#submitBtn').css('background-color', '#ccc');
            $('#submitBtn').css('cursor', 'not-allowed');
        }
    }

    $('#firstName, #lastName, #password, #confirmPassword, #agree').on('input change', checkFormAndEnableButton);

});

</script>


</script>
</body>
</html>