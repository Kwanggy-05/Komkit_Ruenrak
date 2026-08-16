<?php
require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// ตรวจสอบว่า user login แล้วหรือยัง
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}

$user_id = getCurrentUserId();

if(!$user_id){
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'Invalid snippet ID']);
        exit;
    }
    // ตรวจสอบสิทธิ์การเข้าถึง snippet (ไม่ให้ like snippet ที่เป็น private ของคนอื่น)
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'Snippet not found or access denied']);
        exit;
    }

    $result = $control->toggleLike($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
