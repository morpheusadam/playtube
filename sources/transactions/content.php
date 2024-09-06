<?php 
if (!IS_LOGGED || ($pt->config->sell_videos_system == 'off' && $pt->config->usr_v_mon == 'off' && $pt->config->payed_subscribers == 'off') ) {
	header('Location: ' . PT_Link('404'));
	exit;
}

$currency         = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';


// if ($pt->config->payment_currency == 'EUR') {
// 	$currency    = '€';
// }
$types = array('today','this_week','this_month','this_year');
$type = 'today';

if (!empty($_GET['type']) && in_array($_GET['type'], $types)) {
	$type = $_GET['type'];
}
if (!empty($_GET['type'])) {
	$_GET['type'] = strip_tags($_GET['type']);
}

if ($type == 'today') {
	$start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

	$array = array('00' => 0 ,'01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0);
	$ads_array = $array;
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
	$ads_array = $array;
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
	$ads_array = $array;
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
	$ads_array = $array;
	$date_type = 'm';
	$pt->cat_type = 'this_year';
    $pt->chart_title = $lang->this_year;
    $pt->chart_text = date("Y");
}

$day_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
$day_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
$this_day_ads_earn = $db->rawQuery("SELECT SUM(amount) AS sum FROM ".T_ADS_TRANS." c WHERE `time` >= ".$day_start." AND `time` <= ".$day_end."  AND type = 'video' AND video_owner = ".$pt->user->id);
$this_day_video_earn = $db->rawQuery("SELECT * FROM ".T_VIDEOS_TRSNS." c WHERE `time` >= ".$day_start." AND `time` <= ".$day_end." AND `type` != 'ad' AND user_id = ".$pt->user->id);
$day_net = 0;
foreach ($this_day_video_earn as $tr) {
	if (in_array($tr->currency, $pt->config->currency_array)) {
		$currency     = !empty($pt->config->currency_symbol_array[$tr->currency]) ? $pt->config->currency_symbol_array[$tr->currency] : '$';
		$admin_currency     = $currency.$tr->admin_com;
		$day_net = $day_net + ($tr->amount - $tr->admin_com);
	}
	elseif (in_array(str_replace('_PERCENT', '', $tr->currency), $pt->config->currency_array)) {
		$main_currency = str_replace('_PERCENT', '', $tr->currency);
		$currency     = !empty($pt->config->currency_symbol_array[$main_currency]) ? $pt->config->currency_symbol_array[$main_currency] : '$';
		$admin_currency = $tr->admin_com."%";
		$day_net = $day_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	}



	// if ($tr->currency == "USD") {
	// 	$day_net = $day_net + ($tr->amount - $tr->admin_com);
	// }
	// else if($tr->currency == "EUR"){
	// 	$day_net = $day_net + ($tr->amount - $tr->admin_com);
	// }
	// elseif ($tr->currency == "EUR_PERCENT") {
	// 	$day_net = $day_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	// }
	// elseif ($tr->currency == "USD_PERCENT") {
	// 	$day_net = $day_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	// }
}
$today_earn = $this_day_ads_earn[0]->sum + $day_net ;

$month_start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
$month_end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
$this_month_ads_earn = $db->rawQuery("SELECT SUM(amount) AS sum FROM ".T_ADS_TRANS." c WHERE `time` >= ".$month_start." AND `time` <= ".$month_end."  AND type = 'video' AND video_owner = ".$pt->user->id);
$this_month_video_earn = $db->rawQuery("SELECT * FROM ".T_VIDEOS_TRSNS." c WHERE `time` >= ".$month_start." AND `time` <= ".$month_end." AND `type` != 'ad' AND user_id = ".$pt->user->id);
$month_net = 0;
foreach ($this_month_video_earn as $tr) {
	if (in_array($tr->currency, $pt->config->currency_array)) {
		$currency     = !empty($pt->config->currency_symbol_array[$tr->currency]) ? $pt->config->currency_symbol_array[$tr->currency] : '$';
		$admin_currency     = $currency.$tr->admin_com;
		$month_net = $month_net + ($tr->amount - $tr->admin_com);
	}
	elseif (in_array(str_replace('_PERCENT', '', $tr->currency), $pt->config->currency_array)) {
		$main_currency = str_replace('_PERCENT', '', $tr->currency);
		$currency     = !empty($pt->config->currency_symbol_array[$main_currency]) ? $pt->config->currency_symbol_array[$main_currency] : '$';
		$admin_currency = $tr->admin_com."%";
		$month_net = $month_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	}


	// if ($tr->currency == "USD") {
	// 	$month_net = $month_net + ($tr->amount - $tr->admin_com);
	// }
	// else if($tr->currency == "EUR"){
	// 	$month_net = $month_net + ($tr->amount - $tr->admin_com);
	// }
	// elseif ($tr->currency == "EUR_PERCENT") {
	// 	$month_net = $month_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	// }
	// elseif ($tr->currency == "USD_PERCENT") {
	// 	$month_net = $month_net + ($tr->amount - ($tr->admin_com * $tr->amount)/100);
	// }
}
$month_earn = $this_month_ads_earn[0]->sum + $month_net ;
$ads_list        = "";

$trans        = $db->where('user_id',$user->id)->where('type','ad','!=')->orderBy('id','DESC')->get(T_VIDEOS_TRSNS);
$ads_trans = $db->where('time',$start,'>=')->where('time',$end,'<=')->where('video_owner',$pt->user->id)->where('type','video')->get(T_ADS_TRANS);
$total_ads = 0;
if (!empty($ads_trans)) {
	foreach ($ads_trans as $key => $ad) {
		if ($ad->time >= $start && $ad->time <= $end) {
			$day = date($date_type,$ad->time);
			if (in_array($day, array_keys($ads_array))) {
				$ads_array[$day] += $ad->amount; 
				$total_ads += $ad->amount; 
			}
		}
	}
}



$total_earn = 0;
$subscribe_array = $array;
if (!empty($trans)) {
	foreach ($trans as $tr) {
		if ($tr->type == 'subscribe') {
			$video = array();
		}
		elseif (!empty($tr->video_id)){
			$video = PT_GetVideoByID($tr->video_id, 0, 0, 2);
		}
		

		$user_data   = PT_UserData($tr->paid_id);

		$currency         = "";
		$admin_currency         = "";
		$net = 0;
		if (in_array($tr->currency, $pt->config->currency_array)) {
			$currency     = !empty($pt->config->currency_symbol_array[$tr->currency]) ? $pt->config->currency_symbol_array[$tr->currency] : '$';
			$admin_currency     = $currency.$tr->admin_com;
			$net = $tr->amount - $tr->admin_com;
		}
		elseif (in_array(str_replace('_PERCENT', '', $tr->currency), $pt->config->currency_array)) {
			$main_currency = str_replace('_PERCENT', '', $tr->currency);
			$currency     = !empty($pt->config->currency_symbol_array[$main_currency]) ? $pt->config->currency_symbol_array[$main_currency] : '$';
			$admin_currency = $tr->admin_com."%";
			$net = $tr->amount - ($tr->admin_com * $tr->amount)/100;
		}
		// if ($tr->currency == "USD") {
		// 	$currency     = "$";
		// 	$admin_currency     = "$".$tr->admin_com;
		// 	$net = $tr->amount - $tr->admin_com;
		// }
		// else if($tr->currency == "EUR"){
		// 	$currency     = "€";
		// 	$admin_currency     = "€".$tr->admin_com;
		// 	$net = $tr->amount - $tr->admin_com;
		// }
		// elseif ($tr->currency == "EUR_PERCENT") {
		// 	$currency     = "€";
		// 	$admin_currency = $tr->admin_com."%";
		// 	$net = $tr->amount - ($tr->admin_com * $tr->amount)/100;
		// }
		// elseif ($tr->currency == "USD_PERCENT") {
		// 	$currency     = "$";
		// 	$admin_currency = $tr->admin_com."%";
		// 	$net = $tr->amount - ($tr->admin_com * $tr->amount)/100;
		// }

		if ($tr->time >= $start && $tr->time <= $end) {
			$day = date($date_type,$tr->time);
			if (in_array($day, array_keys($array))) {
				if ($tr->type == 'subscribe') {
					$subscribe_array[$day] += $net;
				}
				else{
					$array[$day] += $net;
				}
			}
		}

		$total_earn = $total_earn + (float)$net;
		if (!empty($user_data)) {
			$type = $lang->ads;
			if ($tr->type == 'subscribe') {
				$type = $lang->subscribe;
			}
			elseif (!empty($video) && $tr->type != 'subscribe') {
				$type = $lang->video_purchase;
			}
			$ads_list   .= PT_LoadPage('transactions/list',array(
				'ID' => $tr->id,
				'PAID_USER' => substr($user_data->name, 0,20),
				'PAID_URL' => $user_data->url,
				'USER_NAME' => $user_data->username,
				'VIDEO_NAME' => !empty($video) && !empty($video->title) ? substr($video->title, 0,20) : '' ,
				'VIDEO_URL' => !empty($video) && !empty($video->title) ? $video->url : '',
				'VIDEO_ID_' => !empty($video) ? PT_Slug($video->title, $video->video_id) : '',
				'AMOUNT' => $tr->amount,
				"CURRENCY" => $currency,
				"A_CURRENCY" => $admin_currency,
				"NET" => $net,
				"TIME" => PT_Time_Elapsed_String($tr->time),
				"type" => $type
			));
		}
	}
}
$total_earn = $total_earn + $total_ads;

$pt->array = implode(', ', $array);
$pt->ads_array = implode(', ', $ads_array);
$pt->subscribe_array = implode(', ', $subscribe_array);
$pt->page_url_ = $pt->config->site_url.'/transactions';
$pt->title       = $lang->earnings . ' | ' . $pt->config->title;
$pt->page        = "transactions";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->currency    = $currency;
$pt->content     = PT_LoadPage('transactions/content',array(
	'CURRENCY'   => $currency,
	'ADS_LIST'   => $ads_list,
	'TOTAL_EARN' => $total_earn,
	'TODAY_EARN' => $today_earn,
	'MONTH_EARN' => $month_earn
));