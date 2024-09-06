<?php
$checked = 0;
if (!empty($_SESSION['autoplay'])) { 
    if ($_COOKIE['autoplay'] == 2) {
        $checked = 2;
    } 
}
if (!empty($_SESSION['user_id'])) {
	$db->where('session_id', PT_Secure($_SESSION['user_id']));
	$db->delete(T_SESSIONS);
}
session_destroy();
if (isset($_COOKIE['user_id'])) {
	$db->where('session_id', PT_Secure($_COOKIE['user_id']));
	$db->delete(T_SESSIONS);
    unset($_COOKIE['user_id']);
    setcookie('user_id', null, -1,'/');
} 
$_SESSION = array();
unset($_SESSION);
if ($checked == 2) {
	$_COOKIE['autoplay'] = 2;
}
header("Location: {$site_url}");
exit();