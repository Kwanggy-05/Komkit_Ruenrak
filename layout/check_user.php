<?php
/**
 * ==============================================================================
 * ไฟล์: layout/check_user.php
 * หน้าที่: ตรวจสอบความถูกต้องของการเข้าสู่ระบบของผู้ใช้ทั่วไป (User Authentication Middleware)
 * ส่วนประกอบในไฟล์:
 *   1. ดึง User ID จาก Session ปัจจุบัน (หากไม่มีให้เด้งไปหน้า login.php)
 *   2. ตรวจสอบสิทธิ์ผู้ใช้ ป้องกันไม่ให้ Admin เข้ามาใช้ Session ผู้ใช้ทั่วไป
 *   3. ตรวจสอบสถานะการถูกระงับสิทธิ์ (Ban Check) แบบเรียลไทม์จากฐานข้อมูล
 * ==============================================================================
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db/connect.php';

// --- ส่วนที่ 1: ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ---
$userId = getCurrentUserId();
if(!$userId){
    header("Location:login.php");
    exit;
}

// --- ส่วนที่ 2: ป้องกันไม่ให้แอดมินใช้งานหน้าของผู้ใช้ทั่วไป ---
if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        session_destroy();
    }
    header("Location:login.php");
    exit;
}

// --- ส่วนที่ 3: ตรวจสอบสถานะผู้ใช้และตรวจเช็กการถูกแบน (Ban Status) จากฐานข้อมูลโดยตรง ---
try {
    $stmt = $pdo->prepare("SELECT role FROM user WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isBanned = false;
    if ($userData) {
        // ตรวจสอบว่ามีรายการในตาราง user_bans ที่ unbanned_at ยังเป็น NULL หรือไม่
        $banStmt = $pdo->prepare("SELECT ban_id FROM user_bans WHERE user_id = :user_id AND unbanned_at IS NULL LIMIT 1");
        $banStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $banStmt->execute();
        $isBanned = ($banStmt->fetch() !== false);
    }

    // หากไม่พบผู้ใช้ หรือเป็นแอดมิน หรือถูกแบน ให้เคลียร์ Session แล้วเด้งกลับหน้าล็อกอิน
    if (!$userData || $userData['role'] === 'admin' || $isBanned) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            session_destroy();
        }
        $redirectUrl = $isBanned ? "login.php?banned=1" : "login.php";
        header("Location: " . $redirectUrl);
        exit;
    }
    
    $_SESSION['role'] = $userData['role'];
} catch (PDOException $e) {
    // จัดการข้อผิดพลาดฐานข้อมูล
}
?>