<?php
/**
 * ==============================================================================
 * ไฟล์: forget_password.php
 * หน้าที่: หน้ารีเซ็ตรหัสผ่านกรณีลืมรหัสผ่าน (Forgot Password Reset)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดระบบเซสชันและการเชื่อมต่อฐานข้อมูล
 *   2. ประมวลผลคำขอรีเซ็ตรหัสผ่าน (POST Request)
 *   3. ตรวจสอบข้อมูลและการยืนยันรหัสผ่านใหม่
 *   4. ตรวจสอบคำตอบความปลอดภัยและเปลี่ยนรหัสผ่านผ่าน $user->resetPasswordWithSecurityAnswer()
 *   5. ส่วนแสดงผลแบบฟอร์ม HTML รีเซ็ตรหัสผ่าน
 * ==============================================================================
 */

require_once "layout/session.php";
require_once "db/connect.php";

$error = "";
$success = "";
$username = "";
$answer = "";

// --- ส่วนที่ 1: ประมวลผลการส่งฟอร์มรีเซ็ตรหัสผ่าน (POST Method) ---
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST["username"] ?? "");
    $answer = trim($_POST["answer"] ?? "");
    $new_password = $_POST["new_password"] ?? "";
    $confirm_new_password = $_POST["confirm_new_password"] ?? "";

    // ตรวจสอบความครบถ้วนของข้อมูล
    if($username === "" || $answer === "" || $new_password === "" || $confirm_new_password === ""){
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } elseif($new_password !== $confirm_new_password){
        // ตรวจสอบความตรงกันของรหัสผ่านใหม่
        $error = "รหัสผ่านใหม่และยืนยันรหัสผ่านใหม่ไม่ตรงกัน";
    } else {
        // เรียกฟังก์ชัน resetPasswordWithSecurityAnswer จากคลาส User
        $result = $user->resetPasswordWithSecurityAnswer($username, $answer, $new_password);
        if($result["success"]){
            $success = $result["message"] . " กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...";
            header("Refresh: 2; url=login.php");
        } else {
            $error = $result["message"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/logincss.css?v=<?php echo time(); ?>">
    <style>
        .success-alert {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            width: 320px;
            max-width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="login.php"><button class="back-btn" type="button">ย้อนกลับ</button></a>

        <div class="card">
            <div class="title-wrapper">
                <img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px>
                <h1 class="text-center">รีเซ็ตรหัสผ่าน</h1>
            </div>

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>

                <div class="form-group">
                    <label for="question">คำถามความปลอดภัย</label>
                    <input type="text" id="question" value="ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?" disabled style="background-color: #f1f3f5; cursor: not-allowed; opacity: 0.85;">
                </div>

                <div class="form-group">
                    <label for="answer">คำตอบความปลอดภัย</label>
                    <input type="text" name="answer" id="answer" value="<?php echo htmlspecialchars($answer); ?>" placeholder="กรุณากรอกคำตอบของคุณ" required>
                </div>

                <div class="form-group">
                    <label for="new_password">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_new_password">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" required>
                </div>

                <?php if($error !== ""): ?>
                    <div class="error-alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($success !== ""): ?>
                    <div class="success-alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <input type="submit" name="submit" value="รีเซ็ตรหัสผ่าน">
                
            </form>
        </div>
    </div>
</body>
</html>
