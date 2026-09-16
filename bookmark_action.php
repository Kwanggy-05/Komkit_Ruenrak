<?php
require_once "layout/session.php";
require_once "db/connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'error', 'message' => 'Please log in first']);
    exit;
}

$user_id = getCurrentUserId();

if(!$user_id){
    echo json_encode(['status' => 'error', 'message' => 'Please log in first']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
    
    if($snippet_id <= 0){
        echo json_encode(['status' => 'error', 'message' => 'Invalid snippet ID']);
        exit;
    }
    // Check snippet access permission (do not allow bookmarking someone else's private snippet)
    $snippet = $control->getSnippetById($snippet_id, $user_id);
    if(!$snippet){
        echo json_encode(['status' => 'error', 'message' => 'Snippet not found or access denied']);
        exit;
    }

    if((int)($snippet['is_suspended'] ?? 0) === 1){
        echo json_encode(['status' => 'error', 'message' => 'โพสต์นี้ถูกระงับ ไม่สามารถบุ๊กมาร์กได้']);
        exit;
    }

    $result = $control->toggleBookmark($user_id, $snippet_id);
    echo json_encode($result);
}else{
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
