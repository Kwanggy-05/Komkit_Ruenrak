<?php
/**
 * ==============================================================================
 * ไฟล์: login.php
 * หน้าที่: หน้าเข้าสู่ระบบสำหรับสมาชิก (User Login)
 * ส่วนประกอบในไฟล์:
 *   1. ตรวจสอบและเริ่มต้นเซสชัน (Session Initialization)
 *   2. ตรวจสอบการแจ้งเตือนกรณีบัญชีถูกแบน (?banned=1)
 *   3. ตรวจสอบจำนวนผู้ใช้งานในระบบ ($user->countUsers())
 *   4. ประมวลผลการส่งฟอร์มล็อกอิน (POST Authentication) ตรวจสอบสิทธิ์ และสถานะแบน
 *   5. ส่วนแสดงผลแบบฟอร์ม HTML (Username, Password, ลิงก์ลืมรหัสผ่าน)
 *   6. สคริปต์ JavaScript ดักจับการกดแป้น Enter เพื่อสลับช่องพิมพ์
 * ==============================================================================
 */

require_once "layout/session.php";
require_once "db/connect.php";

// --- ส่วนที่ 1: ตรวจสอบเซสชันเดิม หากมีอยู่แล้วให้เริ่มเซสชันใหม่ ---
if(getCurrentUserId()){
    startAppSession();
}

// --- ส่วนที่ 2: ตรวจสอบและแสดงข้อความแจ้งเตือนกรณีถูกแบน ---
$errorAlert = "";
if (isset($_GET['banned'])) {
    $errorAlert = "บัญชีของคุณถูกระงับการใช้งานอย่างถาวร\nหากเชื่อว่าเป็นข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ";
}

// ตรวจสอบว่าในระบบมีผู้ใช้งานแล้วหรือไม่
$hasAnyUser = $user->countUsers();
if($hasAnyUser === 0){
    $errorAlert = "กรุณาสมัครสมาชิกก่อนเข้าใช้งาน";
}

// --- ส่วนที่ 3: ประมวลผลการส่งฟอร์มเข้าสู่ระบบ (POST Request) ---
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    // เรียกฟังก์ชัน getUser จากคลาส User เพื่อตรวจรหัสผ่านและสถานะการถูกแบน
    $result=$user->getUser($username,$password);

    if($result){
        // ป้องกันไม่ให้ Admin ใช้หน้าล็อกอินของผู้ใช้ทั่วไป
        if (isset($result['role']) && $result['role'] === 'admin') {
            $errorAlert = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        } elseif (!empty($result['is_banned'])) {
            $errorAlert = "บัญชีของคุณถูกระงับการใช้งานอย่างถาวร\nหากเชื่อว่าเป็นข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ";
        } else {
            // บันทึกข้อมูลเข้า Session เมื่อล็อกอินสำเร็จ
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $result["user_id"];
            $_SESSION["userid"] = $result["user_id"];
            $_SESSION["role"] = $result["role"] ?? 'user';
            header("Location:snippet_page.php");
            exit;
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
    <title>เข้าสู่ระบบ - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/logincss.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <a href="index.php"><button class="back-btn" type="button">กลับหน้าหลัก</button></a>
        

        <div class="card">
            <div class="title-wrapper">
                <img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px>
                <h1 class="text-center">
                    เข้าสู่ระบบ
                    <span style="display:none;"><?php "Register" ?></span> </h1>
            </div>
            

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username"
                    value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo htmlspecialchars($username); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" name="password" id="password">
                    <a href="forget_password.php" class="forgot-password" style="text-decoration: none; display: block;">ลืมรหัสผ่าน?</a>
                </div>
                <?php if ($errorAlert !== ""): ?>
                    <div class="error-alert"><?php echo nl2br(htmlspecialchars($errorAlert)); ?></div>
                <?php endif; ?>

                <input type="submit" name="submit" value="เข้าสู่ระบบ">
                
            </form>
        </div>
    </div>

    <script>
        // --- ส่วนที่ 6: สคริปต์ JavaScript จัดการการทำงานของฟอร์มเข้าสู่ระบบ ---
        document.addEventListener("DOMContentLoaded", function() {
            const usernameInput = document.getElementById("username");
            const passwordInput = document.getElementById("password");

            // ดักจับการกดปุ่ม Enter ในช่องชื่อผู้ใช้ เพื่อเลื่อนเคอร์เซอร์ไปยังช่องรหัสผ่านทันที
            if (usernameInput && passwordInput) {
                usernameInput.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        passwordInput.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>