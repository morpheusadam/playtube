<?php
if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}
$currency         = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';
$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = ($key == $pt->user->country_id) ? 'selected' : '';
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}

$pt->page        = 'wallet';
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->page_url_ = $pt->config->site_url.'/wallet';
$pt->title = $pt->config->name . ' | ' . $pt->config->title;
$pt->content  = PT_LoadPage('wallet/content',array(
	'CURRENCY'   => $currency,
    'COUNTRIES' => $countries));