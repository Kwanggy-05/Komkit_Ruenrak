<?php
/**
 * ==============================================================================
 * ไฟล์: register.php
 * หน้าที่: หน้าลงทะเบียนสมัครสมาชิกใหม่ (User Registration)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดการเชื่อมต่อฐานข้อมูลและเซสชัน
 *   2. ประมวลผลการส่งฟอร์มสมัครสมาชิก (POST Request)
 *   3. ตรวจสอบความถูกต้องของข้อมูล (Validation: ห้ามเว้นว่าง, Username 3-20 ตัวอักษร, รหัสผ่านอย่างน้อย 6 ตัวอักษร, รหัสผ่านตรงกัน)
 *   4. เรียกฟังก์ชัน $user->insertUser() เพื่อบันทึกข้อมูลและแฮชรหัสผ่าน
 *   5. ส่วนแสดงผลแบบฟอร์มสมัครสมาชิก HTML (ชื่อ, นามสกุล, Username, Password, คำถามความปลอดภัย)
 * ==============================================================================
 */

require_once "layout/session.php";
require_once "db/connect.php";

$error = "";
$firstname = "";
$lastname = "";
$username = "";
$answer = "";

// --- ส่วนที่ 1: ประมวลผลเมื่อมีการส่งฟอร์มสมัครสมาชิก (POST Method) ---
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $firstname = trim($_POST["firstname"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $question = "ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?";
    $answer = trim($_POST["answer"] ?? "");

    // ตรวจสอบความครบถ้วนของข้อมูล
    if($firstname === "" || $lastname === "" || $username === "" || $password === "" || $answer === ""){
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } elseif(mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        $error = "ชื่อผู้ใช้ต้องมีความยาวระหว่าง 3 ถึง 20 ตัวอักษร";
    } elseif(mb_strlen($password) < 6) {
        $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } elseif($password !== $confirmPassword) {
        // ตรวจสอบว่ารหัสผ่านและการยืนยันรหัสผ่านตรงกันหรือไม่
        $error = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        // เรียกฟังก์ชัน insertUser จากคลาส User เพื่อบันทึกข้อมูลและ Hash ด้วย Bcrypt
        $result = $user->insertUser($firstname, $lastname, $username, $password, $question, $answer);
        if($result){
            // สมัครสำเร็จ ให้พาไปยังหน้า login.php
            header("Location:login.php");
            exit;
        } else {
            $error = "ชื่อผู้ใช้นี้มีผู้ใช้งานแล้ว";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/registercss.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <a href="index.php"><button class="back-btn" type="button">กลับหน้าหลัก</button></a>
        

        <div class="card">
            <h1 class="text-center">
                <img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px>
                <span>สมัครสมาชิก</span>
                <span style="display:none;"><?php "Register" ?></span> </h1>

            <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                <?php if($error !== ""): ?>
                    <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">ชื่อจริง <span style="color: red; margin-left: 5px;">*</span></label>
                        <input type="text" id="firstname" name="firstname"
                        value="<?php echo htmlspecialchars($firstname); ?>"
                        required>
                    </div>

                    <div class="form-group">
                        <label for="lastname">นามสกุล <span style="color: red; margin-left: 5px;">*</span></label>
                        <input type="text" id="lastname" name="lastname"
                        value="<?php echo htmlspecialchars($lastname); ?>"
                        required>
                    </div>
                </div>

                <div class="form-group-center">
                    <label for="username">ชื่อผู้ใช้ <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="text" id="username" name="username"
                    minlength="3" maxlength="20" placeholder="ความยาว 3-20 ตัวอักษร"
                    value="<?php echo htmlspecialchars($username); ?>"
                    required>
                </div>
                
                <div class="form-group-center">
                    <label for="password">รหัสผ่าน <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="password" id="password" name="password" minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร" required>
                </div>

                <div class="form-group-center">
                    <label for="confirm_password">ยืนยันรหัสผ่าน <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
                </div>

                <div class="form-group-center">
                    <label for="question">คำถามความปลอดภัย</label>
                    <input type="text" id="question" value="ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?" disabled style="background-color: #f1f3f5; cursor: not-allowed; opacity: 0.85;">
                </div>

                <div class="form-group-center">
                    <label for="answer">คำตอบความปลอดภัย <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="text" name="answer" id="answer" placeholder="คำตอบของคุณ" value="<?php echo htmlspecialchars($answer); ?>" autocomplete="off" required>
                </div>

                <div class="submit-group">
                    <input type="submit" name="submit" value="สมัครสมาชิก">
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>