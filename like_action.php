<?php
/**
 * ==============================================================================
 * ไฟล์: like_action.php
 * หน้าที่: API Endpoint (AJAX POST) สำหรับกดถูกใจ / ยกเลิกการถูกใจ Snippet (Like System)
 * ผลลัพธ์: JSON Response ({ "status": "success", "liked": true|false, "like_count": int } หรือ error)
 * ขั้นตอนการทำงาน:
 *   1. ตรวจสอบสถานะการเข้าสู่ระบบ
 *   2. ตรวจสอบ Request Method (POST เท่านั้น)
 *   3. ตรวจสอบสิทธิ์การเข้าถึง Snippet และสถานะการถูกระงับ
 *   4. เรียกฟังก์ชัน $control->toggleLike() เพื่ออัปเดตยอดไลก์ในตาราง snippet_like
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

// --- ส่วนที่ 2: ตรวจสอบ Request Method และรับค่ารหัส Snippet ---
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'รหัส Snippet ไม่ถูกต้อง']);
        exit;
    }

    // --- ส่วนที่ 3: ตรวจสอบสิทธิ์การเข้าถึงโพสต์และสถานะการระงับ ---
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ Snippet หรือไม่มีสิทธิ์เข้าถึง']);
        exit;
    }

    if((int)($snippet['is_suspended'] ?? 0) === 1){
        echo json_encode(['status' => 'error', 'message' => 'โพสต์นี้ถูกระงับ ไม่สามารถกด Like ได้']);
        exit;
    }

    // --- ส่วนที่ 4: ประมวลผลการกดไลก์/ยกเลิกไลก์ ผ่านฟังก์ชัน toggleLike ---
    $result = $control->toggleLike($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
