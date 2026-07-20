<?php
require_once "layout/session.php";
require_once "db/connect.php";

$error = "";
$firstname = "";
$lastname = "";
$username = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $firstname = trim($_POST["firstname"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if($firstname === "" || $lastname === "" || $username === "" || $password === ""){
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif($password !== $confirmPassword) {
        $error = "รหัสผ่านกับยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        $result = $user->insertUser($firstname, $lastname, $username, $password);
        if($result){
            header("Location:login.php");
            exit;
        } else {
            $error = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style/registercss.css">
</head>
<body>
    <div class="container">
        <a href="Home.php"><button class="back-btn" type="button">Back</button></a>
        

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
                <?php if($error !== ""): ?>
                    <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First name</label>
                        <input type="text" name="firstname"
                        value="<?php echo htmlspecialchars($firstname); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last name</label>
                        <input type="text" name="lastname"
                        value="<?php echo htmlspecialchars($lastname); ?>"
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