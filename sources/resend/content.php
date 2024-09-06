<?php 
if (IS_LOGGED == true && empty($_GET['id'])) {
    header("Location: " . PT_Link(''));
    exit();
}

if (empty($_GET['id']) || empty($_GET['u_id'])) {
	header("Location: " . PT_Link(''));
    exit();
}
$_GET['id'] = strip_tags($_GET['id']);
$_GET['u_id'] = strip_tags($_GET['u_id']);
$email_code = PT_Secure($_GET['id']);
$username = PT_Secure($_GET['u_id']);

$check_for_code = $db->where('username', $username)->where('email_code', $email_code)->getOne(T_USERS);

if (empty($check_for_code)) {
	header("Location: " . PT_Link(''));
    exit();
}
$email_code = sha1(time() + rand(111,999));
$db->where('username', $username)->update(T_USERS, array('email_code' => $email_code));
$link = $email_code . '/' . $check_for_code->email; 
$data['EMAIL_CODE'] = $link;
$data['USERNAME'] = $username;
$send_email_data = array(
	'from_email' => $pt->config->email,
	'from_name' => $pt->config->name,
	'to_email' => $check_for_code->email,
	'to_name' => $username,
	'subject' => 'Confirm your account',
	'charSet' => 'UTF-8',
	'message_body' => PT_LoadPage('emails/confirm-account', $data),
	'is_html' => true
);

$send_message = PT_SendMessage($send_email_data);
header("Location: " . PT_Link('login?resend=true'));
exit();