<?php
session_start();

function getCurrentUserId(){
    if(isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])){
        return (int)$_SESSION['user_id'];
    }

    if(isset($_SESSION['userid']) && is_numeric($_SESSION['userid'])){
        return (int)$_SESSION['userid'];
    }

    return 0;
}
?>