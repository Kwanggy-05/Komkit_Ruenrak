<?php
/**
 * ==============================================================================
 * ไฟล์: appeal_action.php
 * หน้าที่: API Endpoint (AJAX POST) สำหรับให้เจ้าของโค้ดยื่นคำร้องอุทธรณ์ Snippet ที่ถูกระงับ
 * ผลลัพธ์: JSON Response ({ "status": "success"|"error", "message": "..." })
 * ขั้นตอนการทำงาน:
 *   1. กำหนด Content-Type และตรวจสอบการล็อกอิน
 *   2. รับและตรวจสอบข้อมูล POST (snippet_id, reason)
 *   3. ตรวจสอบความเป็นเจ้าของ Snippet และสถานะการระงับ (is_suspended == 1)
 *   4. ป้องกันการยื่นซ้ำขณะมีคำร้องค้างพิจารณา
 *   5. บันทึกคำร้องลงตาราง snippet_appeals ผ่าน $control->createSnippetAppeal()
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once "layout/session.php";
require_once "db/connect.php";

// --- ส่วนที่ 1: ตรวจสอบสิทธิ์การเข้าใช้งาน (ต้องล็อกอินก่อน) ---
$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "กรุณาเข้าสู่ระบบก่อนทำรายการ"]);
    exit;
}

// --- ส่วนที่ 2: ตรวจสอบ Request Method (ต้องเป็น POST เท่านั้น) ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "รูปแบบคำขอไม่ถูกต้อง"]);
    exit;
}

// --- ส่วนที่ 3: รับค่าและตรวจสอบความถูกต้องของข้อมูล (Validation) ---
$snippetId = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($snippetId <= 0) {
    echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลโพสต์ที่ต้องการยื่นคำร้อง"]);
    exit;
}

if ($reason === '') {
    echo json_encode(["status" => "error", "message" => "กรุณาระบุเหตุผลหรือคำชี้แจงในการขอยกเลิกการระงับ"]);
    exit;
}

// --- ส่วนที่ 4: ตรวจสอบความเป็นเจ้าของและสถานะโพสต์ว่าถูกระงับอยู่จริงหรือไม่ ---
// เรียกฟังก์ชัน getSnippetById เพื่อดึงข้อมูลโพสต์ (รวมโพสต์ที่ถูกระงับ)
$snippet = $control->getSnippetById($snippetId, $userId, true);
if (!$snippet) {
    echo json_encode(["status" => "error", "message" => "ไม่พบโพสต์ หรือคุณไม่มีสิทธิ์เข้าถึงโพสต์นี้"]);
    exit;
}

// ตรวจสอบว่าผู้ยื่นเป็นเจ้าของ Snippet ตัวจริงหรือไม่
if ((int)($snippet['user_id'] ?? 0) !== (int)$userId) {
    echo json_encode(["status" => "error", "message" => "คุณสามารถยื่นคำร้องได้เฉพาะโพสต์ของคุณเองเท่านั้น"]);
    exit;
}

// ตรวจสอบสถานะว่าถูกระงับจริงหรือไม่
if ((int)($snippet['is_suspended'] ?? 0) !== 1) {
    echo json_encode(["status" => "error", "message" => "โพสต์นี้ไม่ได้อยู่ในสถานะถูกระงับ"]);
    exit;
}

// --- ส่วนที่ 5: ตรวจสอบคำร้องที่ค้างอยู่ (ป้องกันการยื่นอุทธรณ์ซ้ำ) ---
// เรียกฟังก์ชัน getPendingAppealBySnippet ตรวจสอบคำร้องที่รอพิจารณา
$pendingAppeal = $control->getPendingAppealBySnippet($snippetId);
if ($pendingAppeal) {
    echo json_encode(["status" => "error", "message" => "คุณได้ยื่นคำร้องสำหรับโพสต์นี้ไปแล้ว และอยู่ระหว่างรอผู้ดูแลระบบพิจารณา"]);
    exit;
}

// --- ส่วนที่ 6: บันทึกข้อมูลคำร้องอุทธรณ์ลงฐานข้อมูล ---
// เรียกฟังก์ชัน createSnippetAppeal ในคลาส Control
if ($control->createSnippetAppeal($snippetId, $userId, $reason)) {
    echo json_encode([
        "status" => "success",
        "message" => "ยื่นคำร้องขอถอนการระงับสำเร็จแล้ว ผู้ดูแลระบบจะพิจารณาคำร้องของคุณโดยเร็ว"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการบันทึกคำร้อง กรุณาลองใหม่อีกครั้ง"]);
}
exit;
?>
