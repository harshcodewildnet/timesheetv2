<?php
header("X-Robots-Tag: noindex, nofollow", true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/login1.css">
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


        /*  */
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

        /*  */
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="assets/images/wnet-image.png" alt="wildnet-img">
        </div>
        <div class="login-box">
            <form id="sendotpform" action="" method="POST" novalidate>
                <h3 class="head">Forgot Passord</h3>
                <div class="form-group">
                    <label for="email" class="form-label">Enter Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter email to get password OTP" required>
                    <small class="error" style="color:red;display:none;">Please enter a valid email.</small>
                    <a id="edit-link" style="display: none;" href="#">Edit Email Id</a>
                </div>
                <!-- <div class="form-group otp-notice" style="display:none;">
                    <h4>OTP Sent to Email Id</h4>
                    <a class="edit-link" href="#">Edit Email Id</a>
                </div> -->
                <div class="form-group otp-field" style="display:none;">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input type="text" name="otp" id="otp" placeholder="Enter OTP To Reset Passord" required>
                    <small class="error" style="color:red;display:none;">Please enter a valid 6 digit otp.</small>
                </div>
                <button type="submit" class="login-btn">Request OTP</button>
                <a href="index" class="forgot-password">Back</a>

            </form>
        </div>
    </div>


    <div id="messageBox" class="alert" style="display: none;">
        <i id="alertIcon" class="fa-solid"></i>
        <span id="alertMessage"></span>
    </div>

    <footer></footer>

    <!-- <script>
        document.getElementById('sendotpform').addEventListener('submit', function (e) {
            let valid = true;

            // Email validation
            const email = document.getElementById('email');
            const emailError = email.nextElementSibling;
            const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if (!email.value.match(emailPattern)) {
                emailError.style.display = 'block';
                valid = false;
            } else {
                emailError.style.display = 'none';
            }

            // Password validation
            // const otp = document.getElementById('otp');
            // const otpError = otp.nextElementSibling;
            // if (password.value.length < 6) {
            //     passwordError.style.display = 'block';
            //     valid = false;
            // } else {
            //     passwordError.style.display = 'none';
            // }

            if (!valid) {
                e.preventDefault(); // Stop form submission if validation fails
            }
        });
    </script> -->


    <script>
        document.getElementById('sendotpform').addEventListener('submit', function (e) {
            e.preventDefault();

            const emailInput = document.getElementById('email');
            const emailError = emailInput.nextElementSibling;
            const otpInput = document.getElementById('otp');
            const otpError = otpInput.nextElementSibling;
            const otpField = document.querySelector('.otp-field');
            const editLink = document.getElementById('edit-link');
            const button = document.querySelector('.login-btn');

            const email = emailInput.value.trim();
            const otp = otpInput.value.trim();

            // Validate email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;
            let isValid = true;

            if (!emailPattern.test(email)) {
                emailError.style.display = 'block';
                isValid = false;
            } else {
                emailError.style.display = 'none';
            }

            // If OTP is visible, validate OTP too
            if (otpField.style.display !== 'none') {
                if (!/^\d{6}$/.test(otp)) {
                    otpError.style.display = 'block';
                    isValid = false;
                } else {
                    otpError.style.display = 'none';
                }
            }

            editLink.addEventListener('click', function () {
                emailInput.disabled = false;
                otpField.style.display = 'none';
                button.textContent = 'Request OTP';
                this.style.display = 'none';
            });


            if (!isValid) return;

            // Proceed based on phase
            if (otpField.style.display === 'none') {
                // STEP 1: Request OTP
                button.disabled = true;
                button.textContent = 'Please Wait';

                fetch('api/otp-handler', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: 'send',
                        email: email
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // alert(data.message);
                            otpField.style.display = 'block';
                            emailInput.disabled = true;
                            editLink.style.display = 'inline-block';
                            button.textContent = 'Verify OTP';
                        } else {
                            // alert(data.message || 'Failed to send OTP.');
                            if (data.message == 'Email not registered.') emailError.textContent = data.message;
                            emailError.style.display = 'block';
                            button.textContent = 'Request OTP';
                        }
                        showMessage(data.message, data.success, null, 8000);
                        button.disabled = false;
                    });
            } else {
                // STEP 2: Verify OTP
                button.disabled = true;
                button.textContent = 'Please Wait';

                fetch('api/otp-handler', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: 'verify',
                        email: email,
                        otp: otp
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, true, 'reset-password', 3000);
                            // alert('OTP verified. Redirecting to password reset...');
                        } else {
                            showMessage(data.message, false, null, 8000);
                            // alert(data.message || 'Invalid OTP.');
                            button.textContent = 'Verify OTP';
                        }
                        button.disabled = false;
                    });
            }
        });

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