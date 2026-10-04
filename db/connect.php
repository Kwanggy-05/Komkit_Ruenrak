<?php
/**
 * ==============================================================================
 * ไฟล์: db/connect.php
 * หน้าที่: จัดการการเชื่อมต่อฐานข้อมูล MySQL (PDO) และสร้าง Object สำหรับเรียกใช้งาน
 * ส่วนประกอบในไฟล์:
 *   1. การตรวจสอบความปลอดภัย (Block PATH_INFO)
 *   2. การตั้งค่าและสร้างการเชื่อมต่อฐานข้อมูล (PDO Connection)
 *   3. การโหลดไฟล์คลาสโมเดลและสร้างอินสแตนซ์ ($control, $user)
 * ==============================================================================
 */

// --- ส่วนที่ 1: ตรวจสอบความปลอดภัย ป้องกันการเข้าถึงผ่าน PATH_INFO (เช่น /page.php/Admin) ---
if (!empty($_SERVER['PATH_INFO']) || !empty($_SERVER['ORIG_PATH_INFO'])) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "The requested URL was not found on this server.";
    exit;
}

// --- ส่วนที่ 2: ตั้งค่าพารามิเตอร์และสร้างการเชื่อมต่อฐานข้อมูลด้วย PDO ---
$host="localhost";
$username="snippetbox";
$password="code7836";
$db="snippetboxdb";
$dsn="mysql:host=$host;dbname=$db;charset=utf8";
try{
    $pdo=new PDO($dsn, $username, $password);
}catch(PDOException $e){
    echo $e->getMessage();
}

// --- ส่วนที่ 3: โหลดคลาสจัดการข้อมูลและสร้าง Object ตัวแปรสำหรับเรียกใช้งานในระบบ ---
require_once "db/control.php";
require_once "db/user.php";

// ตัวแปร Object จัดการ Snippet, ภาษา, แท็ก, รายงาน, คำอุทธรณ์ ฯลฯ
$control = new Control($pdo);

// ตัวแปร Object จัดการข้อมูลผู้ใช้งาน (สมาชิก, สิทธิ์, การแบน, รหัสผ่าน)
$user = new User($pdo);
?>