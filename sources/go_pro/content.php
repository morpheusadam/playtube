<?php 

if (!IS_LOGGED || $pt->config->go_pro != 'on') {
	header('Location: ' . PT_Link('404'));
	exit;
}

if (PT_IsUpgraded()) {
	header('Location: ' . PT_Link('upgraded'));
	exit;
}


$currency        = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';

// if ($pt->config->payment_currency == 'EUR') {
// 	$currency    = '€';
// }
$pt->unlimited_free = false;
$pt->unlimited_pro = false;
if ($pt->config->upload_system_type == '0') {
	if ($pt->config->max_upload_all_users != '0') {
		$pt->max_upload_users_ = pt_size_format($pt->config->max_upload_all_users);
		$pt->max_upload_users_pro = pt_size_format($pt->config->max_upload_all_users);
	}
	else{
		$pt->unlimited_free = true;
		$pt->unlimited_pro = true;
	}
	
}
elseif ($pt->config->upload_system_type == '1') {
	if ($pt->config->max_upload_free_users != 0) {
		$pt->max_upload_users_ = pt_size_format($pt->config->max_upload_free_users);
	}
	else{
		$pt->unlimited_free = true;
	}
	if ($pt->config->max_upload_pro_users != 0) {
		$pt->max_upload_users_pro = pt_size_format($pt->config->max_upload_pro_users);
	}
	else{
		$pt->unlimited_pro = true;
	}
}

$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = ($key == $pt->user->country_id) ? 'selected' : '';
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}


$pt->page_url_ = $pt->config->site_url.'/go_pro';
$pt->title       = $lang->go_pro . ' | ' . $pt->config->title;
$pt->page        = "go_pro";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('go_pro/content', array('CURRENCY' => $currency,'SITE_NAME' => $pt->config->name,'COUNTRIES' => $countries));



