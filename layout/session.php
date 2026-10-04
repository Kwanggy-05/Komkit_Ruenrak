<?php
/**
 * ==============================================================================
 * ไฟล์: layout/session.php
 * หน้าที่: จัดการ Session สำหรับการเข้าสู่ระบบ แยก Session ระหว่างฝั่งผู้ใช้ทั่วไปและ Admin
 * รายการฟังก์ชันในไฟล์:
 *   - startAppSession()   : เริ่มต้นหรือสลับ Session พร้อมกำหนดชื่อ Cookie และ Cookie Parameters
 *   - getCurrentUserId()  : ดึงรหัส User ID ของผู้ใช้ปัจจุบันที่ล็อกอินอยู่
 * ==============================================================================
 */

// --- ส่วนที่ 1: ตรวจสอบความปลอดภัย ป้องกันการเจาะผ่าน PATH_INFO ---
if (!empty($_SERVER['PATH_INFO']) || !empty($_SERVER['ORIG_PATH_INFO'])) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "The requested URL was not found on this server.";
    exit;
}

/**
 * ฟังก์ชัน: startAppSession
 * ส่วนที่: การจัดการเซสชัน (Session Initialization)
 * หน้าที่: แยก Session Cookie Name ระหว่างฝั่งผู้ใช้ทั่วไป ('snippet_box_user_session') 
 *          และฝั่งแอดมิน ('snippet_box_admin_session') เพื่อความปลอดภัยและไม่ให้ข้อมูลทับซ้อนกัน
 */
function startAppSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        session_destroy();
    }
    
    $sessionName = 'snippet_box_user_session';
    if (isset($_SERVER['SCRIPT_NAME']) && (stripos($_SERVER['SCRIPT_NAME'], '/Admin/') !== false || stripos($_SERVER['SCRIPT_NAME'], '/admin/') !== false)) {
        $sessionName = 'snippet_box_admin_session';
    }
    
    session_name($sessionName);
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// เริ่มต้นเรียกทำงาน Session ทันทีหากยังไม่มีการ start
if (session_status() === PHP_SESSION_NONE) {
    startAppSession();
}

/**
 * ฟังก์ชัน: getCurrentUserId
 * ส่วนที่: การตรวจสอบข้อมูลผู้ใช้ปัจจุบัน (Auth Helper)
 * หน้าที่: ดึง User ID ของสมาชิกที่กำลังเข้าใช้งานจาก Session
 * คืนค่า: int User ID (หากไม่ได้ล็อกอินหรือเป็นแอดมินในเส้นทางฝั่งผู้ใช้จะคืนค่า 0)
 */
function getCurrentUserId(){
    $isAdminRoute = isset($_SERVER['SCRIPT_NAME']) && (stripos($_SERVER['SCRIPT_NAME'], '/Admin/') !== false || stripos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
    
    if (!$isAdminRoute && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return 0;
    }

    if(isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])){
        return (int)$_SESSION['user_id'];
    }

    if(isset($_SESSION['userid']) && is_numeric($_SESSION['userid'])){
        return (int)$_SESSION['userid'];
    }

    return 0;
}
?>