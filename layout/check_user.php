<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db/connect.php';

$userId = getCurrentUserId();
if(!$userId){
    header("Location:login.php");
    exit;
}

if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        session_destroy();
    }
    header("Location:login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT role, account_status FROM user WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData || $userData['role'] === 'admin' || (isset($userData['account_status']) && $userData['account_status'] === 'banned')) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            session_destroy();
        }
        $redirectUrl = (isset($userData['account_status']) && $userData['account_status'] === 'banned') ? "login.php?banned=1" : "login.php";
        header("Location: " . $redirectUrl);
        exit;
    }
    
    $_SESSION['role'] = $userData['role'];
} catch (PDOException $e) {
    // Graceful fallback
}
?>