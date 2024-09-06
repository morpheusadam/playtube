<?php
if (!IS_LOGGED || empty($_GET['session'])) {
    header("Location: " . $pt->config->site_url);
    exit();
}
if ($pt->config->switch_account != 'on') {
    header("Location: " . $pt->config->site_url);
    exit();
}
$session_id = PT_Secure($_GET['session']);
$pt->user_session  = PT_GetUserFromSessionID($session_id);
if (!empty($pt->user_session) && is_numeric($pt->user_session) && $pt->user_session > 0) {
    $user = PT_UserData($pt->user_session);
    if (!empty($user)) {
        session_unset();
        $_SESSION['user_id'] = '';
        session_destroy();
        $_SESSION = array();
        unset($_SESSION);
        if (isset($_COOKIE['user_id'])) {
            $_COOKIE['user_id'] = '';
            unset($_COOKIE['user_id']);
            setcookie('user_id', '', -1);
            setcookie('user_id', '', -1, '/');
        }
        $_SESSION['user_id'] = $session_id;
        setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60));
    }
}


header("Location: " . $pt->config->site_url);
exit();

