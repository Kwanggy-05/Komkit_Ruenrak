<?php
function startAppSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        session_destroy();
    }
    
    $sessionName = 'snippet_box_user_session';
    if (isset($_SERVER['SCRIPT_NAME']) && (stripos($_SERVER['SCRIPT_NAME'], '/Admin/') !== false || stripos($_SERVER['SCRIPT_NAME'], '/admin/') !== false)) {
        $sessionName = 'snippet_box_admin_session';
    }
    
    session_name($sessionName);
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (session_status() === PHP_SESSION_NONE) {
    startAppSession();
}

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