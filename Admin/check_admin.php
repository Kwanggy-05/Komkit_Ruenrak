<?php
// Secure Admin Panel Check

// Set working directory to project root so database connects and libraries are loaded relative to root
chdir(dirname(__DIR__));

require_once "layout/session.php";
require_once "db/connect.php";

$userId = getCurrentUserId();
if (!$userId) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT role, firstname, lastname FROM user WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser || $adminUser['role'] !== 'admin') {
        // Redirect to admin login if not an admin
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
