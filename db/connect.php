<?php
// Block requests with PATH_INFO (e.g. /page.php/Admin) for security
if (!empty($_SERVER['PATH_INFO']) || !empty($_SERVER['ORIG_PATH_INFO'])) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "The requested URL was not found on this server.";
    exit;
}

$host="localhost";
$username="snippetbox";
$password="code7836";
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
?>