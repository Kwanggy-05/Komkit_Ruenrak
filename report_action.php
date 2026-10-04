<?php
/**
 * ==============================================================================
 * ไฟล์: report_action.php
 * หน้าที่: API Endpoint (AJAX POST) สำหรับรับเรื่องรายงานเนื้อหาที่ไม่เหมาะสม (Report Snippet)
 * ผลลัพธ์: JSON Response ({ "status": "success"|"error", "message": "..." })
 * ขั้นตอนการทำงาน:
 *   1. กำหนด Content-Type และตรวจสอบการเข้าสู่ระบบ
 *   2. ตรวจสอบ Request Method (POST เท่านั้น)
 *   3. ตรวจสอบความถูกต้องของเหตุผลที่เลือก (Enum Validation: spam, inappropriate, malicious_code, other)
 *   4. ตรวจสอบการรายงานซ้ำผ่าน $control->hasUserReported()
 *   5. บันทึกข้อมูลรายงานลงตาราง reports ผ่าน $control->createReport()
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once "layout/session.php";
require_once "db/connect.php";

// --- ส่วนที่ 1: ตรวจสอบการล็อกอินของผู้ใช้ ---
$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "กรุณาเข้าสู่ระบบก่อน"]);
    exit;
}

// --- ส่วนที่ 2: ตรวจสอบ Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "รูปแบบคำขอไม่ถูกต้อง"]);
    exit;
}

// --- ส่วนที่ 3: รับค่าพารามิเตอร์และตรวจสอบความถูกต้อง (Validation) ---
$snippetId = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : '';

if ($snippetId <= 0) {
    echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูล Snippet"]);
    exit;
}

// ตรวจสอบค่าเหตุผลว่าอยู่ในรายการที่ระบบกำหนดไว้หรือไม่
$allowedReasons = ['spam', 'inappropriate', 'malicious_code', 'other'];
if (!in_array($reason, $allowedReasons)) {
    echo json_encode(["status" => "error", "message" => "เหตุผลในการรายงานไม่ถูกต้อง"]);
    exit;
}

// หากเลือก "อื่นๆ" จะต้องกรอกรายละเอียดเพิ่มเติมด้วย
if ($reason === 'other' && empty($details)) {
    echo json_encode(["status" => "error", "message" => "กรุณาระบุรายละเอียดสำหรับตัวเลือก 'อื่นๆ'"]);
    exit;
}

// --- ส่วนที่ 4: ตรวจสอบว่าเคยรายงานโพสต์นี้และยังค้างตรวจสอบอยู่หรือไม่ ---
// เรียกฟังก์ชัน hasUserReported เพื่อป้องกันการส่งรายงานซ้ำซ้อน
if ($control->hasUserReported($userId, $snippetId)) {
    echo json_encode(["status" => "error", "message" => "คุณได้รายงาน Snippet นี้ไปแล้วและอยู่ระหว่างรอการตรวจสอบ"]);
    exit;
}

// --- ส่วนที่ 5: บันทึกข้อมูลรายงานใหม่ลงฐานข้อมูล ---
// เรียกฟังก์ชัน createReport ในคลาส Control
if ($control->createReport($snippetId, $userId, $reason, $details)) {
    echo json_encode(["status" => "success", "message" => "ขอบคุณสำหรับการรายงาน เราจะตรวจสอบข้อมูลโดยเร็ว"]);
} else {
    echo json_encode(["status" => "error", "message" => "ไม่สามารถส่งรายงานได้"]);
}
exit;
?>
