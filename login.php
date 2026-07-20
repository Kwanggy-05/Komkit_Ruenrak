<?php
require_once "layout/session.php";
require_once "db/connect.php";

if(getCurrentUserId()){
    $_SESSION = array();
    session_destroy();
    session_start();
}

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
    <link rel="stylesheet" href="style/logincss.css">
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