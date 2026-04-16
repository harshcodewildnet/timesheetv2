<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Unauthorized Access</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        :root {
            --main-color: #568fac;
            --main-color-light: #b3e3fb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100dvh;
            background-color: var(--main-color);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .message-box {
            height: 50dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 20px;
            /* border: 1px solid #fff; */
            padding: 10px 20px;
        }

        h1 {
            font-size: 3rem;
            border: 1px solid #fff;
            padding: 10px 20px;
            /* text-decoration: underline; */
        }

        a.home-link {
            color: #fff;
            font-size: 1.2rem;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="message-box">
        <h1>Unauthorized Access</h1>
        <a href="index" class="home-link">Go Back</a>
    </div>
</body>

</html>