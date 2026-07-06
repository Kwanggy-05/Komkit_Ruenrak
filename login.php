<?php
require_once "layout/session.php";
require_once "db/connect.php";

$hasAnyUser = $user->countUsers();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $username = $_POST["username"];
    $password = $_POST["password"];
    $new_password =md5($password.$username);
    $result=$user->getUser($username,$new_password);

    if($result){
        $_SESSION["username"] = $username;
        $_SESSION["user_id"] = $result["user_id"];
        $_SESSION["userid"] = $result["user_id"];
        header("Location:snippet_page.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
            width: 550px;
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
            border-radius: 10px;
            width: 100%;
            padding: 40px 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .title-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .title-wrapper svg {
            width: 32px;
            height: 32px;
        }

        h1.text-center {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            font-style: italic;
            color: #000;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            width: 300px;
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

        .forgot-password {
            text-align: right;
            font-size: 12px;
            color: #888;
            margin-top: 6px;
            margin-right: 5px;
            cursor: pointer;
        }

        input[type="submit"] {
            background-color: #1f1f1f;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 35px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
        }

        /* ตกแต่งกล่องแจ้งเตือน Error */
        .error-alert {
            background-color: #fee2e2; /* พื้นหลังสีแดงอ่อน */
            color: #991b1b; /* ตัวหนังสือสีแดงเข้ม */
            border: 1px solid #f87171; /* ขอบสีแดง */
            border-radius: 8px; /* ขอบมนให้เข้ากับช่องกรอกข้อมูล */
            padding: 10px 15px;
            margin-bottom: 20px; /* ระยะห่างด้านล่างก่อนถึงช่องกรอกข้อมูล */
            font-size: 14px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="Home.php"><button class="back-btn" type="button">Back</button></a>
        

        <div class="card">
            <div class="title-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    <path d="M7 10L5 12L7 14"></path>
                    <path d="M17 10L19 12L17 14"></path>
                    <line x1="13" y1="9" x2="11" y2="15"></line>
                </svg>
                <h1 class="text-center">
                    Login
                    <span style="display:none;"><?php "Register" ?></span> </h1>
            </div>
            

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username"
                    value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo $_POST["username"]; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password">
                    <div class="forgot-password">Forget password?</div>
                </div>
                <?php
            if($hasAnyUser === 0){
                echo '<div class="error-alert">โปรดสมัครสมาชิก</div>';
            } elseif($_SERVER["REQUEST_METHOD"]=="POST" && !$result){
                echo '<div class="error-alert">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>';
            }
            ?>

                <input type="submit" name="submit" value="Sign in">
                
            </form>
        </div>
    </div>
</body>
</html>