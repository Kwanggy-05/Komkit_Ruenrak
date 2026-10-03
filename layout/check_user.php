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
    $stmt = $pdo->prepare("SELECT role FROM user WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isBanned = false;
    if ($userData) {
        $banStmt = $pdo->prepare("SELECT ban_id FROM user_bans WHERE user_id = :user_id AND unbanned_at IS NULL LIMIT 1");
        $banStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $banStmt->execute();
        $isBanned = ($banStmt->fetch() !== false);
    }

    if (!$userData || $userData['role'] === 'admin' || $isBanned) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            session_destroy();
        }
        $redirectUrl = $isBanned ? "login.php?banned=1" : "login.php";
        header("Location: " . $redirectUrl);
        exit;
    }
    
    $_SESSION['role'] = $userData['role'];
} catch (PDOException $e) {
    // Graceful fallback
}
?>