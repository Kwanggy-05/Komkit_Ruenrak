<?php
require_once "layout/session.php";
require_once "db/connect.php";

if(getCurrentUserId()){
    $currUserId = getCurrentUserId();
    $update_status_sql = "UPDATE user SET status = 'offline' WHERE user_id = :user_id";
    $update_status_stmt = $pdo->prepare($update_status_sql);
    $update_status_stmt->bindParam(":user_id", $currUserId, PDO::PARAM_INT);
    $update_status_stmt->execute();

    $_SESSION = array();
    session_destroy();
    session_start();
}

$errorAlert = "";
$hasAnyUser = $user->countUsers();
if($hasAnyUser === 0){
    $errorAlert = "โปรดสมัครสมาชิก";
}

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $username = $_POST["username"];
    $password = $_POST["password"];
    $result=$user->getUser($username,$password);

    if($result){
        $_SESSION["username"] = $username;
        $_SESSION["user_id"] = $result["user_id"];
        $_SESSION["userid"] = $result["user_id"];
        header("Location:snippet_page.php");
        exit;
    } else {
        $errorAlert = "Incorrect username or password";
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
                <!-- <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    <path d="M7 10L5 12L7 14"></path>
                    <path d="M17 10L19 12L17 14"></path>
                    <line x1="13" y1="9" x2="11" y2="15"></line>
                </svg> -->
                <img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px>
                <h1 class="text-center">
                    Login
                    <span style="display:none;"><?php "Register" ?></span> </h1>
            </div>
            

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username"
                    value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo htmlspecialchars($username); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password">
                    <a href="forget_password.php" class="forgot-password" style="text-decoration: none; display: block;">Forget password?</a>
                </div>
                <?php if ($errorAlert !== ""): ?>
                    <div class="error-alert"><?php echo htmlspecialchars($errorAlert); ?></div>
                <?php endif; ?>

                <input type="submit" name="submit" value="Sign in">
                
            </form>
        </div>
    </div>
</body>
</html>