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
?>