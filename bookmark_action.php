<?php
/**
 * ==============================================================================
 * ไฟล์: bookmark_action.php
 * หน้าที่: API Endpoint (AJAX POST) สำหรับบันทึก/ยกเลิกการบันทึก Snippet (Bookmark / ที่คั่นหน้า)
 * ผลลัพธ์: JSON Response ({ "status": "success", "bookmarked": true|false } หรือ error)
 * ขั้นตอนการทำงาน:
 *   1. ตรวจสอบการเข้าสู่ระบบ (Authentication Check)
 *   2. ตรวจสอบ Request Method (POST เท่านั้น)
 *   3. ตรวจสอบสิทธิ์การเข้าถึง Snippet (ห้าม Bookmark โค้ด private คนอื่น หรือโค้ดที่ถูกระงับ)
 *   4. เรียกฟังก์ชัน $control->toggleBookmark() เพื่อสลับสถานะในตาราง bookmark
 * ==============================================================================
 */

require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// --- ส่วนที่ 1: ตรวจสอบการล็อกอินของผู้ใช้ ---
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$user_id = getCurrentUserId();

if(!$user_id){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

// --- ส่วนที่ 2: ตรวจสอบ Request Method และรับค่าพารามิเตอร์ ---
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'รหัส Snippet ไม่ถูกต้อง']);
        exit;
    }

    // --- ส่วนที่ 3: ตรวจสอบสิทธิ์การเข้าถึงโพสต์และการระงับ ---
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ Snippet หรือไม่มีสิทธิ์เข้าถึง']);
        exit;
    }

    if((int)($snippet['is_suspended'] ?? 0) === 1){
        echo json_encode(['status' => 'error', 'message' => 'โพสต์นี้ถูกระงับ ไม่สามารถบุ๊กมาร์กได้']);
        exit;
    }

    // --- ส่วนที่ 4: สลับสถานะบุ๊กมาร์ก (บันทึก / ยกเลิกบันทึก) ผ่านฟังก์ชัน toggleBookmark ---
    $result = $control->toggleBookmark($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
