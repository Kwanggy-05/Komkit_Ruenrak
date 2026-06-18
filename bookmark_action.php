<?php
require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// ตรวจสอบว่า user login แล้วหรือยัง
if(!isset($_SESSION['userid'])){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$user_id = (int)$_SESSION['userid'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'snippet_id ไม่ถูกต้อง']);
        exit;
    }
    
    $result = $control->toggleBookmark($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
