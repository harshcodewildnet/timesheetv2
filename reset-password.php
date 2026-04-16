<?php
header("X-Robots-Tag: noindex, nofollow", true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/login1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />

    <style>
        /* from login3.css from webstock */

        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        html,
        body {
            height: 100dvh;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            /* background-color: #0f004a; */
        }

        .container {
            width: 100%;
            /* height: 60dvh; */
            /* margin: 4dvh auto 10dvh auto; */
            flex: 1;
            /* background-color: #fff; */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .container .logo {
            width: 40%;

            text-align: center;
            padding: 20px 30px;
        }

        .container .logo img {
            max-width: 280px;
            height: auto;
        }

        .container .login-box {
            background-color: #fff;
            width: 40%;
            min-height: 60%;
            padding: 40px 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .login-box h3.head {
            margin-bottom: 20px;
            text-align: start;
            font-weight: 400;
        }

        form {
            /* flex: 1; */
            height: 70%;
            width: 60%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        form .form-group {
            margin-bottom: 15px;
        }

        form .check-option {
            display: flex;
            align-items: center;
            margin: 15px auto;
            font-size: .9rem;
        }

        form .check-option input {
            scale: 1.2;
            margin-right: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: .9rem;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
        }

        form a#edit-link {
            font-size: .9rem;
            /* text-decoration: none; */
            /* color: rgb(0, 0, 155); */
        }

        /* form .otp-notice h4 {
    color: green;
    font-weight: 600;
 } */

        form .login-btn {
            display: block;
            margin: 10px auto;
            padding: 10px 10px;
            background-color: #000;
            color: #fff;
            border: none;
            width: 100%;
            cursor: pointer;
        }

        form .login-btn:disabled {
            background-color: #979797;
            cursor: not-allowed;
            opacity: 0.6;
        }

        form .login-btn:disabled:hover {
            background-color: #979797;
            /* match disabled state */
        }



        form .create-btn {
            padding: 10px;
            margin: 20px auto;
        }

        form .login-btn:hover {
            background-color: #272727;
        }


        form a.forgot-password {
            text-decoration: underline;
            font-size: .8rem;
            display: block;
            text-align: center;
            cursor: pointer;
            color: #000;

        }

        footer {
            width: 100%;
            height: 10dvh;
            background-color: #fff;
        }

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


        /* Toast Notification */
        #toast {
            visibility: hidden;
            min-width: 300px;
            /* margin-left: -125px; */
            background-color: #5eda7b;
            color: white;
            text-align: center;
            /* border-radius: 4px; */
            padding: 6px 15px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 10px;
            transform: translateX(-50%);
            font-size: .9rem;
            /* border: 1px solid #fff; */
        }

        #toast.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }

        @-webkit-keyframes fadein {
            from {
                top: 0;
                opacity: 0;
            }

            to {
                top: 10px;
                opacity: 1;
            }
        }

        @keyframes fadein {
            from {
                top: 0;
                opacity: 0;
            }

            to {
                top: 10px;
                opacity: 1;
            }
        }

        @-webkit-keyframes fadeout {
            from {
                top: 10px;
                opacity: 1;
            }

            to {
                top: 0;
                opacity: 0;
            }
        }

        @keyframes fadeout {
            from {
                top: 10px;
                opacity: 1;
            }

            to {
                top: 0;
                opacity: 0;
            }
        }


        /*  */
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="assets/images/wnet-image.png" alt="wildnet-img">
        </div>
        <div class="login-box">
            <form id="resetpasswordform" action="" method="POST" novalidate>
                <h3 class="head">Reset Passord</h3>
                <!-- <input type="hidden" name="email" id="email" value=""> -->
                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter a new password"
                            minlength="6" required>
                        <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>

                    </div>
                    <small class="error" style="color:red;display:none;">Password must be at least 6
                        characters.</small>
                </div>
                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="retype_password" class="form-label">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="retype_password" id="retype_password"
                            placeholder="Retype new password" minlength="6" required>
                        <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                    </div>
                    <small class="error" style="color:red;display:none;">Passwords do not match.</small>
                </div>
                <button type="submit" class="login-btn">Update</button>
                <a href="forgot-password" class="forgot-password">Back</a>
            </form>
        </div>
    </div>
    <footer></footer>

    <div id="messageBox" class="alert" style="display: none;">
        <i id="alertIcon" class="fa-solid"></i>
        <span id="alertMessage"></span>
    </div>


    <!-- Message Toast -->
    <div id="toast"></div>

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


        function showMessage(message, isSuccess, redirectUrl = null, delay = 2000) {
            const box = document.getElementById('messageBox');
            const icon = document.getElementById('alertIcon');
            const text = document.getElementById('alertMessage');

            text.textContent = message;

            box.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
            icon.className = isSuccess ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';

            box.style.display = 'flex';

            setTimeout(() => {
                box.style.display = 'none';

                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }, delay);

        }

    </script>





    <script>
        document.getElementById('resetpasswordform').addEventListener('submit', function (e) {
            e.preventDefault();

            let isValid = true;

            // Password Validation
            // const passwordField = document.getElementById("password");
            // const retypePasswordField = document.getElementById("retype_password");
            const passwordField = document.querySelector('input[name="password"]');
            const passwordError = passwordField.closest('.password-wrapper').nextElementSibling;
            const retypePasswordField = document.querySelector('input[name="retype_password"]');
            const retypePasswordError = retypePasswordField.closest('.password-wrapper').nextElementSibling;
            const password = passwordField.value;
            const button = document.querySelector('.login-btn');

            if (passwordField.value.length < 6) {
                passwordError.style.display = "inline";
                isValid = false;
            } else {
                passwordError.style.display = "none";
            }

            // Retype Password Validation

            if (retypePasswordField.value !== passwordField.value) {
                retypePasswordError.style.display = "inline";
                isValid = false;
            } else {
                retypePasswordError.style.display = "none";
            }

            if (!isValid) return;

            button.disabled = true;

            fetch('api/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    password: password
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Password Changed. Redirecting to Login Page ...', true, 'index', 5000);
                        // showToast(data.message, false);
                        // alert('Password Changed. Redirecting to Login Page ...');
                        console.log(data.message);
                        // window.location.href = 'index';
                    } else {
                        showToast('Password Update Failed.', false);
                        // alert(data.message || 'Password Update Failed.');
                        console.log(data.message);
                    }
                    button.disabled = false;
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