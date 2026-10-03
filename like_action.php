<?php
require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

$user_id = getCurrentUserId();

if(!$user_id){
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'รหัส Snippet ไม่ถูกต้อง']);
        exit;
    }
    // Check snippet access permission (do not allow liking someone else's private snippet)
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ Snippet หรือไม่มีสิทธิ์เข้าถึง']);
        exit;
    }

    if((int)($snippet['is_suspended'] ?? 0) === 1){
        echo json_encode(['status' => 'error', 'message' => 'โพสต์นี้ถูกระงับ ไม่สามารถกด Like ได้']);
        exit;
    }

    $result = $control->toggleLike($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
