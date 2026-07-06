<?php
require_once __DIR__ . '/session.php';

if(!getCurrentUserId()){
    header("Location:login.php");
    exit;
}
?>