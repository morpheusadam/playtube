<?php 

if (!IS_LOGGED || $pt->config->user_ads != 'on' || empty($_GET['id']) || !is_numeric($_GET['id']) || !canUseFeature($pt->user->id,'who_can_user_ads')) {
	header('Location: ' . PT_Link('404'));
	exit;
}
$_GET['id'] = strip_tags($_GET['id']);
$ad_id            = PT_Secure($_GET['id']);
$ad_data          = $db->where('id',$ad_id)->where('user_id',$user->id)->getOne(T_USR_ADS);

if (empty($ad_data)) {
	header('Location: ' . PT_Link('404'));
	exit;
}
$pt->ad           = $ad_data;
$payment_currency = $pt->config->payment_currency;
$currency         = "";
if ($payment_currency == "USD") {
	$currency     = "$";
}
else if($payment_currency == "EUR"){
	$currency     = "€";
}
$pt->page_url_ = $pt->config->site_url.'/ads/edit/'.$ad_id;
$pt->audience    = @explode(',', $ad_data->audience);
$pt->audience    = (is_array($pt->audience) === true) ? $pt->audience : array();
$pt->title       = 'Edit Advertising | ' . $pt->config->title;
$pt->page        = "user_ads";

$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('edit-ads/content',array(
	'CURRENCY'   => $currency,
	'ID'         => $ad_data->id,
	'NAME'       => $ad_data->name,
	'URL'        => urldecode($ad_data->url),
	'TITLE'      => $ad_data->headline,
	'DESC'       => $ad_data->description,
	'DAY_LIMIT'  => $ad_data->day_limit,
	'LIFETIME_LIMIT'  => $ad_data->lifetime_limit
));