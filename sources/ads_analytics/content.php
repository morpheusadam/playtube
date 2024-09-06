<?php 
if (!IS_LOGGED || empty($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] < 1 ) {
	header('Location: ' . PT_Link('404'));
	exit;
}
$_GET['id'] = strip_tags($_GET['id']);
$ad_id = PT_Secure($_GET['id']);
$user_ad = $db->where('id',$_GET['id'])->getOne(T_USR_ADS);
if ($user_ad->user_id != $pt->user->id) {
	header('Location: ' . PT_Link('404'));
	exit;
}


$types = array('today','this_week','this_month','this_year');
$type = 'today';

if (!empty($_GET['type']) && in_array($_GET['type'], $types)) {
	$_GET['type'] = strip_tags($_GET['type']);
	$type = $_GET['type'];
}

if ($type == 'today') {
	$start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

	$array = array('00' => 0 ,'01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0);
	$click_array = $array;
	$date_type = 'H';
	$pt->cat_type = 'today';
    $pt->chart_title = $lang->today;
    $pt->chart_text = date("l");
}
elseif ($type == 'this_week') {
	
	$time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));
	if (date('l') == 'Saturday') {
		$start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	}
	else{
		$start = strtotime('last saturday, 12:00am', $time);
	}

	if (date('l') == 'Friday') {
		$end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
	}
	else{
		$end = strtotime('next Friday, 11:59pm', $time);
	}
	
	$array = array('Saturday' => 0 , 'Sunday' => 0 , 'Monday' => 0 , 'Tuesday' => 0 , 'Wednesday' => 0 , 'Thursday' => 0 , 'Friday' => 0);
	$click_array = $array;
	$date_type = 'l';
	$pt->cat_type = 'this_week';
    $pt->chart_title = $lang->this_week;
    $pt->chart_text = date('y/M/d',$start)." To ".date('y/M/d',$end);
}
elseif ($type == 'this_month') {
	$start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
	$end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
	if (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 31) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0 ,'30' => 0 ,'31' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 30) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0 ,'30' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 29) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 28) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0);
	}
	$click_array = $array;
	$pt->month_days = count($array);
	$date_type = 'd';
	$pt->cat_type = 'this_month';
    $pt->chart_title = $lang->this_month;
    $pt->chart_text = date("M");
}
elseif ($type == 'this_year') {
	$start = strtotime("1 January ".date('Y')." 12:00am");
	$end = strtotime("31 December ".date('Y')." 11:59pm");
	$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0);
	$click_array = $array;
	$date_type = 'm';
	$pt->cat_type = 'this_year';
    $pt->chart_title = $lang->this_year;
    $pt->chart_text = date("Y");
}
if ($user_ad->type == 1) {
	$ads_result = $db->where('time',$start,'>=')->where('time',$end,'<=')->where('ad_id',$ad_id)->where('type','click')->get(T_ADS_TRANS);
	$text = $lang->clicks;
}
else{
	$ads_result = $db->where('time',$start,'>=')->where('time',$end,'<=')->where('ad_id',$ad_id)->where('type','view')->get(T_ADS_TRANS);
	$text = $lang->views;
}

$ads_spents = $db->where('time',$start,'>=')->where('time',$end,'<=')->where('ad_id',$ad_id)->where('type','spent')->get(T_ADS_TRANS);
if (!empty($ads_result)) {
	foreach ($ads_result as $key => $ad) {
		if ($ad->time >= $start && $ad->time <= $end) {
			$day = date($date_type,$ad->time);
			if (in_array($day, array_keys($click_array))) {
				$click_array[$day] += 1; 
			}
		}
	}
}
if (!empty($ads_spents)) {
	foreach ($ads_spents as $key => $ad) {
		if ($ad->time >= $start && $ad->time <= $end) {
			$day = date($date_type,$ad->time);
			if (in_array($day, array_keys($click_array))) {
				$array[$day] += $ad->amount; 
			}
		}
	}
}
$currency        = '$';

if ($pt->config->payment_currency == 'EUR') {
	$currency    = '€';
}

$pt->array = implode(', ', $array);
$pt->click_array = implode(', ', $click_array);
$pt->page_url_ = $pt->config->site_url.'/ads/analytics/'.$_GET['id'];
$pt->title       = $lang->ads_analytics . ' | ' . $pt->config->title;
$pt->page        = "ads_analytics";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('ads_analytics/content',array(
	'CURRENCY'   => $currency,
	'ID' => $ad_id,
	'TOTAL_SPENT' => number_format($user_ad->spent,2),
	'TOTAL_RESULT' => $user_ad->results,
	'TEXT_RESULT' => $text
));