<?php
header('Content-Type: application/json; charset=utf-8');
require_once "layout/session.php";
require_once "db/connect.php";

$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "กรุณาเข้าสู่ระบบก่อน"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "รูปแบบคำขอไม่ถูกต้อง"]);
    exit;
}

$snippetId = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : '';

if ($snippetId <= 0) {
    echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูล Snippet"]);
    exit;
}

// Validate Enum Values
$allowedReasons = ['spam', 'inappropriate', 'malicious_code', 'other'];
if (!in_array($reason, $allowedReasons)) {
    echo json_encode(["status" => "error", "message" => "เหตุผลในการรายงานไม่ถูกต้อง"]);
    exit;
}

if ($reason === 'other' && empty($details)) {
    echo json_encode(["status" => "error", "message" => "กรุณาระบุรายละเอียดสำหรับตัวเลือก 'อื่นๆ'"]);
    exit;
}

// Check duplicate report
if ($control->hasUserReported($userId, $snippetId)) {
    echo json_encode(["status" => "error", "message" => "คุณได้รายงาน Snippet นี้ไปแล้วและอยู่ระหว่างรอการตรวจสอบ"]);
    exit;
}

// Insert report
if ($control->createReport($snippetId, $userId, $reason, $details)) {
    echo json_encode(["status" => "success", "message" => "ขอบคุณสำหรับการรายงาน เราจะตรวจสอบข้อมูลโดยเร็ว"]);
} else {
    echo json_encode(["status" => "error", "message" => "ไม่สามารถส่งรายงานได้"]);
}
exit;
?>
