<?php 

if (!IS_LOGGED || $pt->config->user_ads != 'on' || !canUseFeature($pt->user->id,'who_can_user_ads')) {
	header('Location: ' . PT_Link('404'));
	exit;
}

// Get user ads related data ..
$payment_currency = $pt->config->payment_currency;
$currency         = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';
// if ($payment_currency == "USD") {
// 	$currency     = "$";
// }
// else if($payment_currency == "EUR"){
// 	$currency     = "€";
// }

$db->where('user_id',$user->id)->where('day_limit',0,'>')->where('day',date("Y-m-d"),'!=')->update(T_USR_ADS,array('day' => date("Y-m-d"),
                                                                                                                   'day_spend' => 0));

$user_ads        = $db->where('user_id',$user->id)->orderBy('id','DESC')->get(T_USR_ADS);
$ads_list        = "";

foreach ($user_ads as $ad) {
	$ads_list   .= PT_LoadPage('ads/list',array(
		'ID' => $ad->id,
		'TYPE' => ($ad->category == 'image') ? 'image' : 'video_library',
		'NAME' => $ad->name,
		'PR_METHOD' => ($ad->type == 1) ? 'Clicks' : 'Views',
		'RESULTS' => $ad->results,
		'SPENT' => number_format($ad->spent,2),
		'ACTIVE' => (($ad->status == 1) ? 'checked' : ''),
		'CURRENCY'   => $currency,
	));
}

$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = ($key == $pt->user->country_id) ? 'selected' : '';
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}

$pt->page_url_ = $pt->config->site_url.'/ads';
$pt->title       = $lang->ads . ' | ' . $pt->config->title;
$pt->page        = "user_ads";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('ads/content',array(
	'CURRENCY'   => $currency,
	'ADS_LIST'   => $ads_list,
	'COUNTRIES' => $countries
));