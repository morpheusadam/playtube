<?php
if (IS_LOGGED == true) {
	header("Location: " . PT_Link(''));
	exit();
}

if (empty($_GET['code'])) {
   header("Location: " . PT_Link(''));
   exit();
}
$_GET['code'] = strip_tags($_GET['code']);
$code = PT_Secure($_GET['code']);
$db->where('email_code', $code);
$user_id = $db->getValue(T_USERS, 'id');
$error_code = false;
if (empty($user_id)) {
	$error_code = true;
}
$color1 = '609b41';
$color2 = '8ad363';
$errors   = array();
$erros_final = '';
$success = '';
if (!empty($_POST) && $error_code == false) {
    if (empty($_POST['password']) || empty($_POST['re-password'])) {
        $errors = $error_icon . $lang->please_check_details;
    } else {
    	$password        = PT_Secure($_POST['password']);
        $c_password      = PT_Secure($_POST['re-password']);
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        if ($password != $c_password) {
            $errors[] = $lang->password_not_match;
        } else if (strlen($password) < 4 || strlen($password) > 32) {
            $errors[] = $lang->password_is_short;
        }
        if (empty($errors)) {
        	$email_code = sha1(time() + rand(111,999));
        	$db->where('id', $user_id);
        	$update_data = array('password' => $password_hashed, 'email_code' => $email_code);
        	$update = $db->update(T_USERS, $update_data);
        	if ($update) {
        		$session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
	            $insert_data         = array(
	                'user_id' => $user_id,
	                'session_id' => $session_id,
	                'time' => time()
	            );
	            $insert              = $db->insert(T_SESSIONS, $insert_data);
	            $_SESSION['user_id'] = $session_id;
	            setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60));
        		header("Location: " . PT_Link(''));
                exit();
        	}
        }
    }
}
$pt->page        = 'login';
$pt->title       = $lang->change_password . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;

if (!empty($errors)) {
    foreach ($errors as $key => $error) {
        $erros_final .= $error_icon . $error . "<br>";
    }
}

$page = 'content';
if ($error_code == true) {
	$page = 'invalid';
}
$pt->content     = PT_LoadPage('auth/reset-password/' . $page, array(
    'COLOR1' => $color1,
    'COLOR2' => $color2,
    'ERRORS' => $erros_final,
    'SUCCESS' => $success,
    'CODE' => $code
));