<?php 
if (!isset($_COOKIE['two_factor_method']) || ($pt->config->two_factor_setting != 'on' && $pt->config->google_authenticator != 'on' && $pt->config->authy_settings != 'on')) {
	header("Location: " . PT_Link(''));
	exit();
}

if (!isset($_COOKIE['two_factor_method'])) {
	header("Location: " . PT_Link(''));
	exit();
}
// if ($pt->config->two_factor_type == 'email') {
// 	$message = $lang->sent_two_factor_email;
// }
// elseif ($pt->config->two_factor_type == 'phone') {
// 	$message = $lang->sent_two_factor_phone;
// }
// else{
// 	$message = $lang->sent_two_factor_both;
// }
$two_factor_method = 'two_factor';
$message = $lang->sent_two_factor_email;
if (!empty($_COOKIE['two_factor_method']) && in_array($_COOKIE['two_factor_method'],array('two_factor','google','authy'))) {
	$two_factor_method = $_COOKIE['two_factor_method'];
}
if ($two_factor_method == 'authy') {
	$message = $lang->use_authy_app;
}
else if ($two_factor_method == 'google') {
	$message = $lang->use_google_authenticator_app;
}

$pt->page        = 'login';
$pt->title       = $lang->two_factor . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('auth/two_factor_login/content',array('MESSAGE' => $message,
                                                                     'ERROR' => ''));