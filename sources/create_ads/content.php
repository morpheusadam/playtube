<?php 

if (!IS_LOGGED || $pt->config->user_ads != 'on' || !canUseFeature($pt->user->id,'who_can_user_ads')) {
	header('Location: ' . PT_Link('404'));
	exit;
}


$payment_currency = $pt->config->payment_currency;
$currency         = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';
// if ($payment_currency == "USD") {
// 	$currency     = "$";
// }
// else if($payment_currency == "EUR"){
// 	$currency     = "€";
// }
$pt->page_url_ = $pt->config->site_url.'/ads/create';
$pt->title       = 'Create Advertising | ' . $pt->config->title;
$pt->page        = "user_ads";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('create-ads/content',array(
	'CURRENCY'   => $currency
));