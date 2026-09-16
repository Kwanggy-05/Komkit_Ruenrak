<?php
header('Content-Type: application/json; charset=utf-8');
require_once "layout/session.php";
require_once "db/connect.php";

$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "กรุณาเข้าสู่ระบบก่อนทำรายการ"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

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

// ดึงข้อมูลโพสต์เพื่อตรวจสอบความเป็นเจ้าของและสถานะการระงับ
$snippet = $control->getSnippetById($snippetId, $userId, true);
if (!$snippet) {
    echo json_encode(["status" => "error", "message" => "ไม่พบโพสต์ หรือคุณไม่มีสิทธิ์เข้าถึงโพสต์นี้"]);
    exit;
}

// ตรวจสอบว่าเป็นเจ้าของโพสต์จริงหรือไม่
if ((int)($snippet['user_id'] ?? 0) !== (int)$userId) {
    echo json_encode(["status" => "error", "message" => "คุณสามารถยื่นคำร้องได้เฉพาะโพสต์ของคุณเองเท่านั้น"]);
    exit;
}

// ตรวจสอบว่าโพสต์ถูกระงับอยู่จริงหรือไม่
if ((int)($snippet['is_suspended'] ?? 0) !== 1) {
    echo json_encode(["status" => "error", "message" => "โพสต์นี้ไม่ได้อยู่ในสถานะถูกระงับ"]);
    exit;
}

// ตรวจสอบว่ามีคำร้องที่ค้างรอพิจารณาอยู่แล้วหรือไม่
$pendingAppeal = $control->getPendingAppealBySnippet($snippetId);
if ($pendingAppeal) {
    echo json_encode(["status" => "error", "message" => "คุณได้ยื่นคำร้องสำหรับโพสต์นี้ไปแล้ว และอยู่ระหว่างรอผู้ดูแลระบบพิจารณา"]);
    exit;
}

// บันทึกคำร้อง
if ($control->createSnippetAppeal($snippetId, $userId, $reason)) {
    echo json_encode([
        "status" => "success",
        "message" => "ยื่นคำร้องขอถอนการระงับสำเร็จแล้ว ผู้ดูแลระบบจะพิจารณาคำร้องของคุณโดยเร็ว"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการบันทึกคำร้อง กรุณาลองใหม่อีกครั้ง"]);
}
exit;
