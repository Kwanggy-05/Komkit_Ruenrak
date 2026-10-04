<?php
/**
 * ==============================================================================
 * ไฟล์: Admin/check_admin.php
 * หน้าที่: มิดเดิลแวร์ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin Authentication Middleware)
 * ส่วนประกอบในไฟล์:
 *   1. ปรับ Working Directory ให้เป็นโฟลเดอร์หลัก เพื่อโหลดไฟล์ DB ได้อย่างถูกต้อง
 *   2. ตรวจสอบการเข้าสู่ระบบผ่านฟังก์ชัน getCurrentUserId()
 *   3. ตรวจสอบ Role จากฐานข้อมูล (ต้องมีสิทธิ์ role === 'admin' เท่านั้น)
 *   4. ดึงสถิติตัวเลขรายงานและคำอุทธรณ์ที่ค้างอยู่ ($pendingReportsCount, $pendingAppealsCount)
 * ==============================================================================
 */

// --- ส่วนที่ 1: กำหนด Working Directory กลับไปยังโฟลเดอร์ราก (Root Directory) ---
chdir(dirname(__DIR__));

require_once "layout/session.php";
require_once "db/connect.php";

// --- ส่วนที่ 2: ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ---
$userId = getCurrentUserId();
if (!$userId) {
    header("Location: login.php");
    exit;
}

// --- ส่วนที่ 3: ตรวจสอบสิทธิ์ความเป็นแอดมินจากฐานข้อมูลโดยตรง ---
try {
    $stmt = $pdo->prepare("SELECT role, firstname, lastname FROM user WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // หากไม่พบข้อมูล หรือไม่ใช่แอดมิน ให้ดีดกลับไปหน้าล็อกอินของแอดมิน
    if (!$adminUser || $adminUser['role'] !== 'admin') {
        header("Location: login.php");
        exit;
    }

    // --- ส่วนที่ 4: ดึงจำนวนรายงานและคำร้องอุทธรณ์ที่รอดำเนินการ สำหรับแสดง Badge บนแท็บเมนู ---
    $pendingReportsCount = $control->getPendingReportsCountAdmin();
    $pendingAppealsCount = $control->getPendingAppealsCountAdmin();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
