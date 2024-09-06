<?php
if (IS_LOGGED == true) {
	header("Location: " . PT_Link(''));
	exit();
}

$color1 = 'cd3e2d';
$color2 = 'f77f71';
$errors   = '';
$email = '';
$success = '';
if (!empty($_POST)) {
    if (empty($_POST['email'])) {
        $errors = $error_icon . $lang->please_check_details;
    }

    else {
        $email        = PT_Secure($_POST['email']);
        $db->where("email", $email);
        $user_id = $db->getValue(T_USERS, "id");
        if (!empty($user_id)) {
           $rest_user = PT_UserData($user_id);
           $email_code = sha1(rand(1111,9999) . rand(1111,9999) . uniqid(rand(1111,9999)));
           $update_data = array('email_code' => $email_code);
           $db->where('id', $rest_user->id);
           $update = $db->update(T_USERS, $update_data);
           $update_data['USER_DATA'] = $rest_user;
           $send_email_data = array(
           		'from_email' => $pt->config->email,
           		'from_name' => $pt->config->name,
           		'to_email' => $email,
           		'to_name' => $rest_user->name,
           		'subject' => 'Reset Password',
           		'charSet' => 'UTF-8',
           		'message_body' => PT_LoadPage('emails/reset-password', $update_data),
           		'is_html' => true
           	);
            $send_message = PT_SendMessage($send_email_data);
            if ($send_message) {
            	$success = $success_icon . $lang->email_sent;
            }
        } else {
            $errors = $error_icon . $lang->email_not_exist;
        }
    }
}
$pt->page        = 'login';
$pt->title       = $lang->reset_password . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('auth/forgot_password/content', array(
    'COLOR1' => $color1,
    'COLOR2' => $color2,
    'ERRORS' => $errors,
    'SUCCESS' => $success
));