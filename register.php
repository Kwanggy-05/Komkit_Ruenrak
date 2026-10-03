<?php
require_once "layout/session.php";
require_once "db/connect.php";

$error = "";
$firstname = "";
$lastname = "";
$username = "";
$answer = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $firstname = trim($_POST["firstname"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $question = "ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?";
    $answer = trim($_POST["answer"] ?? "");

    if($firstname === "" || $lastname === "" || $username === "" || $password === "" || $answer === ""){
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } elseif($password !== $confirmPassword) {
        $error = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        $result = $user->insertUser($firstname, $lastname, $username, $password, $question, $answer);
        if($result){
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
    <link rel="stylesheet" href="style/registercss.css">
</head>
<body>
    <div class="container">
        <a href="Home.php"><button class="back-btn" type="button">กลับหน้าหลัก</button></a>
        

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
                        <input type="text" name="firstname"
                        value="<?php echo htmlspecialchars($firstname); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="lastname">นามสกุล <span style="color: red; margin-left: 5px;">*</span></label>
                        <input type="text" name="lastname"
                        value="<?php echo htmlspecialchars($lastname); ?>"
                        >
                    </div>
                </div>

                <div class="form-group-center">
                    <label for="username">ชื่อผู้ใช้ <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="text" name="username"
                    value="<?php if($_SERVER["REQUEST_METHOD"]=="POST") echo $_POST["username"]; ?>"
                    >
                </div>
                
                <div class="form-group-center">
                    <label for="password">รหัสผ่าน <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="password" name="password">
                </div>

                <div class="form-group-center">
                    <label for="confirm_password">ยืนยันรหัสผ่าน <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="password" name="confirm_password">
                </div>

                <div class="form-group-center">
                    <label for="question">คำถามความปลอดภัย</label>
                    <input type="text" id="question" value="ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?" disabled style="background-color: #f1f3f5; cursor: not-allowed; opacity: 0.85;">
                </div>

                <div class="form-group-center">
                    <label for="answer">คำตอบความปลอดภัย <span style="color: red; margin-left: 5px;">*</span></label>
                    <input type="text" name="answer" id="answer" placeholder="คำตอบของคุณ" value="<?php echo htmlspecialchars($answer); ?>" required>
                </div>

                <div class="submit-group">
                    <input type="submit" name="submit" value="สมัครสมาชิก">
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>