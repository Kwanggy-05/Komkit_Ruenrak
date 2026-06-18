<?php
require_once "layout/session.php";
require_once "db/connect.php";

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $new_password =md5($password.$username);
    $result=$user->insertUser($firstname,$lastname,$username,$password);
    header("Location:login.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,700;1,800&display=swap');

        body {
            background-color: #f4f5f6;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 600px;
        }

        .back-btn {
            background-color: #fff;
            border: 1px solid #777;
            border-radius: 8px;
            padding: 6px 20px;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 12px;
            margin-left: 20px;
        }

        .card {
            background-color: #fff;
            border: 1px solid #777;
            border-radius: 12px;
            width: 100%;
            padding: 30px 40px 40px;
            box-sizing: border-box;
        }

        h1.text-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 0;
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 800;
            font-style: italic;
            color: #000;
        }

        h1.text-center svg {
            width: 36px;
            height: 36px;
            font-style: normal;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .form-row {
            display: flex;
            justify-content: center;
            width: 100%;
            gap: 40px;
        }

        .form-row .form-group {
            display: flex;
            flex-direction: column;
            width: 220px;
        }

        .form-group-center {
            display: flex;
            flex-direction: column;
            width: 340px;
        }

        label {
            font-size: 14px;
            margin-bottom: 6px;
            margin-left: 8px;
            color: #111;
        }

        input[type="text"],
        input[type="password"] {
            border: 1px solid #777;
            border-radius: 12px;
            height: 42px;
            padding: 0 12px;
            font-size: 14px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
        }

        .submit-group {
            margin-top: 15px;
        }

        input[type="submit"] {
            background-color: #1f1f1f;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 35px;
            font-size: 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="testweb.php"><button class="back-btn" type="button">Back</button></a>
        

        <div class="card">
            <h1 class="text-center">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    <path d="M7 10L5 12L7 14"></path>
                    <path d="M17 10L19 12L17 14"></path>
                    <line x1="13" y1="9" x2="11" y2="15"></line>
                </svg>
                <span>Register</span>
                <span style="display:none;"><?php "Register" ?></span> </h1>

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First name</label>
                        <input type="text" name="firstname"
                        value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo $_POST["firstname"]; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="lastname">Laast name</label>
                        <input type="text" name="lastname"
                        value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo $_POST["lastname"]; ?>"
                        >
                    </div>
                </div>

                <div class="form-group-center">
                    <label for="username">Username</label>
                    <input type="text" name="username"
                    value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo $_POST["username"]; ?>"
                    >
                </div>
                
                <div class="form-group-center">
                    <label for="password">Password</label>
                    <input type="password" name="password">
                </div>

                <div class="form-group-center">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password">
                </div>

                <div class="submit-group">
                    <input type="submit" name="submit" value="Sign up">
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>