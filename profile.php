<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';

requireRole(['rm', 'admin', 'executive', 'hod']);
// if (!isset($_SESSION['emp_id']) || empty($_SESSION['emp_id'])) {
//     header('Location: index');
//     exit;
// }
$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$employee = $empObj->getEmployeeById($emp_id);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>My Profile</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <!-- <link rel="stylesheet" href="assets/css/team.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Header Dblist-->
    <header>
        <a href="dashboard-<?= $_SESSION['emp_role'] ?>">
            <div class="logo">
                <img src="assets/images/wnet-image.png" alt="WildNet logo">
            </div>
        </a>
        <div class="search-area">
        </div>
    </header>
    <?php
    include_once "includes/sidebar-" . $employee['role'] . ".php";
    ?>
    <main>
        <div class="top-row">
            <!-- <h3 class="">Home</h3> -->
            <div class="left">
                <h3>My Profile</h3>
            </div>
            <div class="right">
            </div>
        </div>
        <div class="content-area">
            <div class="profile-container">
                <!-- <form action=""> -->
                <div class="row action-row">
                    <h3>Emp Id: <?= $employee['emp_id'] ?></h3>
                    <!-- <button class="edit-profile-btn"></button> -->
                    <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-profile-btn"></i>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" class="editable" name="first-name"
                            value="<?= explode(' ', $employee['name'])[0] ?>" placeholder="Enter First Name" required
                            disabled>
                        <small class="error" style="color:red;display:none;">Please Enter a valid first name (only
                            alphabets allowed)</small>
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" class="editable" name="last-name"
                            value="<?= (explode(' ', $employee['name'])[1]) ?? '' ?>" placeholder="Enter Last Name"
                            required disabled>
                        <small class="error" style="color:red;display:none;">Please Enter a valid last name (only
                            alphabets)</small>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" class="" name="email" value="<?= $employee['email'] ?>"
                            placeholder="Enter Email" required disabled>
                        <small class="error" style="color:red;display:none;">Please Enter a valid email</small>
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact</label>
                        <input type="text" id="contact" class="editable" name="contact"
                            value="<?= $employee['contact'] ?>" placeholder="Enter Contact No." required disabled>
                        <small class="error" style="color:red;display:none;">Enter a valid 10 digit contact no.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" class="editable" name="password"
                                value="<?= $employee['password'] ?>" placeholder="Enter Password" required disabled>
                            <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                        </div>
                        <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                            length 6
                            chars)</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm-password" class="editable" name="confirm-password"
                                value="" placeholder="Re-enter Password" required disabled>
                            <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                        </div>
                        <small class="error" style="color:red;display:none;">Passwords donot match</small>
                    </div>
                </div>
                <div class="row bottom-row">
                    <!-- <div class="form-group"> -->
                    <button class="save-profile-btn" id="save-profile-btn">Save</button>
                    <!-- </div> -->
                </div>
                <!-- </form> -->
            </div>
        </div>
    </main>
    <!-- <footer></footer> -->

    <!-- Message Toast -->
    <div id="toast">Saved Successfully!</div>

    <!-- Custom Alert -->
    <div id="custom-alert" class="alert-overlay">
        <div class="alert-box" id="alert-box">
            <p id="alert-message">Alert Message</p>
            <div class="alert-actions">
                <button id="alert-confirm-btn">Yes</button>
                <button id="alert-cancel-btn">No</button>
            </div>
        </div>
    </div>

    <?php
    // Add task entry modal
    include_once 'task-modal.php';
    // Edit Profile Photo Modal 
    include_once 'photo-modal.php';
    ?>


    <!-- Show Toast Script -->
    <script>
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;

            if (isError) {
                toast.style.backgroundColor = '#ef828c';
            } else {
                toast.style.backgroundColor = '#5eda7b';
            }

            toast.className = 'show';

            setTimeout(() => { toast.classList.remove('show') }, 3000);
        }

    </script>


    <!-- Graphs Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/graph.js"></script>

    <!-- Profile Form Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButton = document.getElementById('edit-profile-btn');
            const saveButton = document.getElementById('save-profile-btn');
            const inputs = document.querySelectorAll('.profile-container input.editable');

            let isEditing = false;
            saveButton.disabled = !isEditing;

            editButton.addEventListener('click', function () {
                isEditing = !isEditing;

                inputs.forEach(input => input.disabled = !isEditing);
                saveButton.disabled = !isEditing;
            });

            saveButton.addEventListener('click', function () {
                const firstNameField = document.getElementById('first-name');
                const lastNameField = document.getElementById('last-name');
                const contactField = document.getElementById('contact');
                // const passwordField = document.getElementById('password');
                // const confirmPasswordField = document.getElementById('confirm-password');
                const passwordField = document.querySelector('input[name="password"]');
                const confirmPasswordField = document.querySelector('input[name="confirm-password"]');


                const firstName = firstNameField.value.trim();
                const lastName = lastNameField.value.trim();
                const contact = contactField.value.trim();
                const password = passwordField.value.trim();
                const confirmPassword = confirmPasswordField.value.trim();

                let isValid = true;

                // First Name Validation: Only alphabets, at least 2 characters
                const firstNameError = firstNameField.nextElementSibling;
                if (!/^[A-Za-z]{2,}$/.test(firstName)) {
                    firstNameError.style.display = 'inline';
                    isValid = false;
                } else {
                    firstNameError.style.display = 'none';
                }

                // Last Name Validation: Only alphabets, at least 2 characters
                const lastNameError = lastNameField.nextElementSibling;
                if (!/^[A-Za-z]{2,}$/.test(lastName)) {
                    lastNameError.style.display = 'inline';
                    isValid = false;
                } else {
                    lastNameError.style.display = 'none';
                }

                // Contact Validation: Only digits, exactly 10 digits
                const contactError = contactField.nextElementSibling;
                if (!/^[0-9]{10}$/.test(contact)) {
                    contactError.style.display = 'inline';
                    isValid = false;
                } else {
                    contactError.style.display = 'none';
                }

                // Password Validation: Minimum 6 characters
                // const passwordError = passwordField.nextElementSibling;
                const passwordError = passwordField.closest('.password-wrapper').nextElementSibling;
                if (password.length < 6) {
                    passwordError.style.display = 'inline';
                    isValid = false;
                } else {
                    passwordError.style.display = 'none';
                }

                // Confirm Password Validation
                const confirmPasswordError = confirmPasswordField.closest('.password-wrapper').nextElementSibling;
                if (confirmPassword !== password) {
                    confirmPasswordError.style.display = 'inline';
                    isValid = false;
                } else {
                    confirmPasswordError.style.display = 'none';
                }

                // Stop submission if form is invalid
                if (!isValid) return;

                // Prepare data for submission
                const employeeData = {
                    first_name: firstName,
                    last_name: lastName,
                    contact: contact,
                    password: password
                };

                fetch('api/update-profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(employeeData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // alert('Profile updated successfully!');
                            showToast('Profile Updated Successfully');
                            setTimeout(() => window.location.reload(), 4000);
                        } else {
                            // alert('Error updating profile.');
                            showToast('Error updating profile', true);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // alert('Something went wrong.');
                        showToast('Something went wrong', true);
                    });
            });
        });

    </script>

    <script>

        // show/hide password toggle
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".eye-icon").forEach(icon => {
                icon.addEventListener("click", togglePasswordVisibility);
            });
        });

        function togglePasswordVisibility(e) {
            const icon = e.currentTarget;
            const passwordInput = icon.closest(".password-wrapper").querySelector("input[type='password'], input[type='text']");
            const eyeIcon = icon.querySelector("i");

            if (!passwordInput) return;

            const isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";

            eyeIcon.classList.toggle("fa-eye-slash", !isHidden);
            eyeIcon.classList.toggle("fa-eye", isHidden);
        }
    </script>

    <script src="assets/js/profile.js"></script>
    <script>
        window.onload = function () {
            document.querySelectorAll('.task-date').forEach(cell => {
                cell.textContent = formatDate(cell.textContent);
            });
        }

        // Date formatting function
        function formatDate(dateString) {
            const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
            return `${parts[2]}-${parts[1]}-${parts[0]}`; // "DD-MM-YYYY"
        }



    </script>
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/main.js"></script>


    <style>
        /* Disable text selection */
        body {
            user-select: none;
        }
    </style>

    <script>
        // Disable right-click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Disable specific key combinations
        document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === "F12") {
                e.preventDefault();
            }
            // Ctrl+Shift+I / Ctrl+Shift+J
            if (e.ctrlKey && e.shiftKey && (e.key.toUpperCase() === 'I' || e.key.toUpperCase() === 'J')) {
                e.preventDefault();
            }
            // Ctrl+U
            if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                e.preventDefault();
            }
        });

        // Disable copy
        document.addEventListener('copy', function (e) {
            e.preventDefault();
        });
    </script>



</body>

</html>
</body>

</html>