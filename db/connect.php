<?php
$host="localhost";
$username="root";
$password="";
$db="snippetboxdb";
$dsn="mysql:host=$host;dbname=$db;charset=utf8";
try{
    $pdo=new PDO($dsn, $username, $password);
}catch(PDOException $e){
    echo $e->getMessage();
}
require_once "db/control.php";
require_once "db/user.php";
$control = new Control($pdo);
$user = new User($pdo);

// Update last active time for logged-in user
if (function_exists('getCurrentUserId')) {
    $sessionUserId = getCurrentUserId();
    if ($sessionUserId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE user SET last_active = NOW() WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $sessionUserId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            // Fail silently
        }
    }
}
?>