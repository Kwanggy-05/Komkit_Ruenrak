<?php
require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// ตรวจสอบว่า user login แล้วหรือยัง
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? $_SESSION['userid']);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'snippet_id ไม่ถูกต้อง']);
        exit;
    }
    // ตรวจสอบสิทธิ์การเข้าถึง snippet (ไม่ให้ bookmark snippet ที่เป็น private ของคนอื่น)
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ snippet หรือไม่มีสิทธิ์']);
        exit;
    }

    $result = $control->toggleBookmark($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
