<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Add New User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <!-- Alert Box -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" id="messageBox">
            <?php echo $_SESSION['success']; ?> Redirecting in <span id="countdown">3</span> seconds...
        </div>

        <script>
            let countdown = 3;
            const countdownElement = document.getElementById('countdown');

            const interval = setInterval(function () {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(interval);
                    window.location.href = 'manage-user';
                }
            }, 1000);
        </script>

        <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" id="messageBox">
            <?php echo $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>


    <!-- Create User Form -->
    <div class="container">
        <div class="logo">
            <img src="assets/images/wnet-image.png" alt="wildnet-img">
        </div>
        <div class="login-box">
            <div class="form-container">
                <h3 class="head">Create a New User</h3>
                <form id="addUserForm" action="api/employee" method="POST" novalidate>

                    <!-- Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">Select Department</label>
                        <select name="role" id="role" required>
                            <option value="user">Accounts</option>
                            <option value="admin">Design n Development</option>
                            <option value="admin">IT</option>
                            <option value="admin">Marketing</option>
                            <option value="admin">PPC</option>
                        </select>
                        <small class="error" style="color:red;display:none;">Please enter a valid Name.</small>
                    </div>
                    <!-- Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter Name of employee" required>
                        <small class="error" style="color:red;display:none;">Please enter a valid Name.</small>
                    </div>
                    <!-- Role -->
                    <div class="form-group">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" required>
                            <option value="user">Executive</option>
                            <option value="admin">Reporting Manager</option>
                            <option value="admin">Department Head</option>
                        </select>
                        <small class="error" style="color:red;display:none;">Please enter a valid Name.</small>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="password" class="form-label">Email Id</label>
                        <input type="email" name="email" id="email" placeholder="Enter Email" required>
                        <small class="error" style="color:red;display:none;">Please enter a valid email.</small>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password" minlength="6"
                            required>
                        <small class="error" style="color:red;display:none;">Password must be at least 6
                            characters.</small>
                    </div>

                    <!-- Retype Password -->
                    <div class="form-group">
                        <label for="retype_password" class="form-label">Retype Password</label>
                        <input type="password" name="retype_password" id="retype_password" placeholder="Retype password"
                            minlength="6" required>
                        <small class="error" style="color:red;display:none;">Passwords do not match.</small>
                    </div>

                    <!-- Role (User/Admin) -->
                    <!-- <div class="form-group">
                        <label for="role" class="form-label" id="role-label">Role</label>
                        <select name="role" id="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div> -->

                    <!-- Submit Button -->
                    <button type="submit" class="login-btn">Create User</button>
                    <a class="back-btn" href="manage-user"><i class="fa-solid fa-arrow-left"></i>Back</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->

    <!-- JavaScript for Front-end Validation -->
    <script>
        document.getElementById("addUserForm").addEventListener("submit", function (event) {
            let isValid = true;

            // Name Validation
            const nameField = document.getElementById("name");
            const nameError = nameField.nextElementSibling;
            const nameRegex = /^[a-zA-Z\s]{2,50}$/; // Letters and spaces only, 2-50 chars

            if (!nameField.value.trim() || !nameRegex.test(nameField.value)) {
                nameError.style.display = "inline";
                isValid = false;
            } else {
                nameError.style.display = "none";
            }


            // Email Validation
            const emailField = document.getElementById("email");
            const emailError = emailField.nextElementSibling;
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailField.value.trim() || !emailRegex.test(emailField.value)) {
                emailError.style.display = "inline";
                isValid = false;
            } else {
                emailError.style.display = "none";
            }

            // Password Validation
            const passwordField = document.getElementById("password");
            const passwordError = passwordField.nextElementSibling;
            if (passwordField.value.length < 6) {
                passwordError.style.display = "inline";
                isValid = false;
            } else {
                passwordError.style.display = "none";
            }

            // Retype Password Validation
            const retypePasswordField = document.getElementById("retype_password");
            const retypePasswordError = retypePasswordField.nextElementSibling;
            if (retypePasswordField.value !== passwordField.value || retypePasswordField.value.length < 6) {
                retypePasswordError.style.display = "inline";
                isValid = false;
            } else {
                retypePasswordError.style.display = "none";
            }

            // If the form is invalid, prevent submission
            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>

</body>

</html>