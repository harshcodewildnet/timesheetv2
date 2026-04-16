<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>User Login</title>
    <link rel="stylesheet" href="assets/css/login1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        .alert {
            position: absolute;
            top: 2%;
            right: 5%;
            padding: 12px 40px;
            margin: 20px 0;
            border: 1px solid transparent;
            border-radius: 4px;
            display: flex;
            align-items: center;
            /* font-size: 14px;
            text-align: center;
            line-height: 2rem; */
        }

        .alert i {
            font-size: 1.5rem;
            margin-right: 10px;
        }

        .alert span {
            /* display: inline-block; */
            font-size: 1rem;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="assets/images/wnet-image.png" alt="wildnet-img">
        </div>
        <div class="login-box">
            <div class="form-container">
                <h3 class="head">Login To Your Account</h3>
                <form id="loginForm" action="api/login" method="POST" novalidate>

                    <!-- Hidden Input for Role -->
                    <!-- <input type="hidden" name="role" value="user"> -->

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Enter email" required>
                        <small class="error" style="color:red;display:none;">Please enter a valid email.</small>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" placeholder="Enter Password"
                                minlength="6" required>
                            <span class="eye-icon" onclick="togglePasswordVisibility();"><i
                                    class="fa-solid fa-eye-slash"></i></span>
                        </div>
                        <small class="error password-error" style="color:red;display:none;">Password must be at least 6
                            characters.</small>
                    </div>

                    <!-- <div class="check-option">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember Me</label>
                    </div> -->

                    <button type="submit" class="login-btn">Login</button>
                    <div class="links-wrapper">
                        <!-- <a href="index" class="back-btn">Back</a> -->
                        <a href="forgot-password" class="forgot-password">Forgot Password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Alert Box -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" id="messageBox">
            <i class="fa-solid fa-circle-check"></i><span><?php echo $_SESSION['success']; ?></span>
        </div>

        <script>
            let countdown = 3;
            const interval = setInterval(function () {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        </script>

        <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" id="messageBox">
            <i class="fa-solid fa-circle-exclamation"></i><span><?php echo $_SESSION['error']; ?></span>
        </div>

        <script>
            let countdown = 3;
            const interval = setInterval(function () {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(interval);
                    // window.location.reload();
                }
            }, 1000);
        </script>

        <?php unset($_SESSION['error']); endif; ?>
    <footer></footer>

    <script>
        const password = document.getElementById('password');
        const eyeToggle = document.querySelector('.eye-icon i');

        function togglePasswordVisibility() {
            if (password.type == 'password') {
                password.type = 'text';
                eyeToggle.classList.remove('fa-eye-slash');
                eyeToggle.classList.add('fa-eye');
            } else {
                password.type = 'password';
                eyeToggle.classList.remove('fa-eye');
                eyeToggle.classList.add('fa-eye-slash');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            let valid = true;

            // Email validation
            const email = document.getElementById('email');
            const emailError = email.nextElementSibling;
            const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,}$/i;
            if (!email.value.match(emailPattern)) {
                emailError.style.display = 'block';
                valid = false;
            } else {
                emailError.style.display = 'none';
            }

            // Password validation
            const passwordError = document.querySelector('.password-error');
            if (password.value.length < 6) {
                passwordError.style.display = 'block';
                valid = false;
            } else {
                passwordError.style.display = 'none';
            }

            if (!valid) {
                e.preventDefault(); // Stop form submission if validation fails
            }
        });
    </script>

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