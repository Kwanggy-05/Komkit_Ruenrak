<?php
chdir(dirname(__DIR__));
require_once "layout/session.php";
require_once "db/connect.php";

if (getCurrentUserId()) {
    startAppSession();
}

$errorAlert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    
    // Authenticate user
    $result = $user->getUser($username, $password);
    
    if ($result) {
        // Check if user has admin role
        if ($result['role'] === 'admin') {
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $result["user_id"];
            $_SESSION["userid"] = $result["user_id"];
            $_SESSION["role"] = "admin";
            header("Location: manage_languages.php");
            exit;
        } else {
            $errorAlert = "บัญชีนี้ไม่มีสิทธิ์ของผู้ดูแลระบบ (เฉพาะผู้ดูแลระบบเท่านั้น)";
        }
    } else {
        $errorAlert = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ - Snippet Box</title>
    <link rel="stylesheet" href="../style/logincss.css">
    <style>
        /* Small style tweaks for Admin Login visual distinction */
        .card {
            border-top: 5px solid #007BFF; /* Theme primary color indicator */
        }
        .admin-tag {
            font-size: 0.8rem;
            background-color: #007BFF;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../Home.php"><button class="back-btn" type="button">ย้อนกลับ</button></a>
        
        <div class="card">
            <div class="title-wrapper">
                <img src="../logo/Logo_snippetBox.png" alt="Logo" width="32px" height="32px">
                <h1 class="text-center" style="margin-bottom: 0;">
                    เข้าสู่ระบบผู้ดูแลระบบ
                </h1>
                <!-- <div class="text-center">
                    <span class="admin-tag">Administrator Only</span>
                </div> -->
            </div>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" required
                           value="<?php if ($_SERVER["REQUEST_METHOD"] == "POST") echo htmlspecialchars($username); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <?php if ($errorAlert !== ""): ?>
                    <div class="error-alert">
                        <?php echo htmlspecialchars($errorAlert); ?>
                    </div>
                <?php endif; ?>

                <input type="submit" name="submit" value="เข้าสู่ระบบ Admin Panel">
            </form>
        </div>
    </div>
</body>
</html>
