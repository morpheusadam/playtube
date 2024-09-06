<?php 
if (IS_LOGGED == false) {
	header("Location: " . PT_Link('login'));
	exit();
}
if (empty($_GET['id'])) {
	header("Location: " . PT_Link(''));
	exit();
}
$_GET['id'] = strip_tags($_GET['id']);

$id = PT_Secure($_GET['id']);
$video = PT_GetVideoByID($id, 0, 1);

if (empty($video) || !$video->is_owner) {
	header("Location: " . PT_Link(''));
	exit();
}

$id = $video->id;




$types = array('today','this_week','this_month','this_year');
$type = 'today';

if (!empty($_GET['type']) && in_array($_GET['type'], $types)) {
	$type = $_GET['type'];
}
else{
	header("Location: " . PT_Link(''));
	exit();
}

if ($type == 'today') {

	$array = array('00' => 0 ,'01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0);
	$day_likes_array = $array;
	$day_dislikes_array = $array;
	$day_views_array = $array;
	$day_comments_array = $array;

	$today_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$today_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

	$today_likes = $db->where('type',1)->where('time',$today_start,'>=')->where('time',$today_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$today_dislikes = $db->where('type',2)->where('time',$today_start,'>=')->where('time',$today_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$today_views = $db->where('time',$today_start,'>=')->where('time',$today_end,'<=')->where('video_id',$id)->get(T_VIEWS);
	$today_comments = $db->where('time',$today_start,'>=')->where('time',$today_end,'<=')->where('video_id',$id)->get(T_COMMENTS);

	foreach ($today_likes as $key => $value) {
		$hour = date('H',$value->time);
		if (in_array($hour, array_keys($day_likes_array))) {
			$day_likes_array[$hour] += 1; 
		}
	}
	foreach ($today_dislikes as $key => $value) {
		$hour = date('H',$value->time);
		if (in_array($hour, array_keys($day_dislikes_array))) {
			$day_dislikes_array[$hour] += 1; 
		}
	}
	foreach ($today_views as $key => $value) {
		$hour = date('H',$value->time);
		if (in_array($hour, array_keys($day_views_array))) {
			$day_views_array[$hour] += 1; 
		}
	}
	foreach ($today_comments as $key => $value) {
		$hour = date('H',$value->time);
		if (in_array($hour, array_keys($day_comments_array))) {
			$day_comments_array[$hour] += 1; 
		}
	}
    
    $pt->cat_type = 'today';
    $pt->chart_title = $lang->today;
    $pt->chart_text = date("l");
	$pt->likes_array = implode(', ', $day_likes_array);
	$pt->dislikes_array = implode(', ', $day_dislikes_array);
	$pt->views_array = implode(', ', $day_views_array);
	$pt->comments_array = implode(', ', $day_comments_array);
}
elseif ($type == 'this_week') {
	$time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));

	if (date('l') == 'Saturday') {
		$week_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	}
	else{
		$week_start = strtotime('last saturday, 12:00am', $time);
	}

	if (date('l') == 'Friday') {
		$week_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
	}
	else{
		$week_end = strtotime('next Friday, 11:59pm', $time);
	}
	
	$array = array('Saturday' => 0 , 'Sunday' => 0 , 'Monday' => 0 , 'Tuesday' => 0 , 'Wednesday' => 0 , 'Thursday' => 0 , 'Friday' => 0);

	$week_likes_array = $array;
	$week_dislikes_array = $array;
	$week_views_array = $array;
	$week_comments_array = $array;

	$week_likes = $db->where('type',1)->where('time',$week_start,'>=')->where('time',$week_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$week_dislikes = $db->where('type',2)->where('time',$week_start,'>=')->where('time',$week_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$week_views = $db->where('time',$week_start,'>=')->where('time',$week_end,'<=')->where('video_id',$id)->get(T_VIEWS);
	$week_comments = $db->where('time',$week_start,'>=')->where('time',$week_end,'<=')->where('video_id',$id)->get(T_COMMENTS);

	foreach ($week_likes as $key => $value) {
		$day_week = date('l',$value->time);
		if (in_array($day_week, array_keys($week_likes_array))) {
			$week_likes_array[$day_week] += 1; 
		}
	}
	foreach ($week_dislikes as $key => $value) {
		$day_week = date('l',$value->time);
		if (in_array($day_week, array_keys($week_dislikes_array))) {
			$week_dislikes_array[$day_week] += 1; 
		}
	}
	foreach ($week_views as $key => $value) {
		$day_week = date('l',$value->time);
		if (in_array($day_week, array_keys($week_views_array))) {
			$week_views_array[$day_week] += 1; 
		}
	}
	foreach ($week_comments as $key => $value) {
		$day_week = date('l',$value->time);
		if (in_array($day_week, array_keys($week_comments_array))) {
			$week_comments_array[$day_week] += 1; 
		}
	}

	$pt->cat_type = 'this_week';
    $pt->chart_title = $lang->this_week;
    $pt->chart_text = date('y/M/d',$week_start)." To ".date('y/M/d',$week_end);
	$pt->likes_array = implode(', ', $week_likes_array);
	$pt->dislikes_array = implode(', ', $week_dislikes_array);
	$pt->views_array = implode(', ', $week_views_array);
	$pt->comments_array = implode(', ', $week_comments_array);

}
elseif ($type == 'this_month') {
	$this_month_start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
	$this_month_end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
	$array = array_fill(1, cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')),0);
	if (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 31) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0 ,'30' => 0 ,'31' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 30) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0 ,'30' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 29) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0 ,'29' => 0);
	}elseif (cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) == 28) {
		$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0,'24' => 0 ,'25' => 0 ,'26' => 0 ,'27' => 0 ,'28' => 0);
	}

	$pt->month_days = count($array);


	$month_likes_array = $array;
	$month_dislikes_array = $array;
	$month_views_array = $array;
	$month_comments_array = $array;

	$month_likes = $db->where('type',1)->where('time',$this_month_start,'>=')->where('time',$this_month_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$month_dislikes = $db->where('type',2)->where('time',$this_month_start,'>=')->where('time',$this_month_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$month_views = $db->where('time',$this_month_start,'>=')->where('time',$this_month_end,'<=')->where('video_id',$id)->get(T_VIEWS);
	$month_comments = $db->where('time',$this_month_start,'>=')->where('time',$this_month_end,'<=')->where('video_id',$id)->get(T_COMMENTS);


	foreach ($month_likes as $key => $value) {
		$day = date('d',$value->time);
		if (in_array($day, array_keys($month_likes_array))) {
			$month_likes_array[$day] += 1; 
		}
	}
	foreach ($month_dislikes as $key => $value) {
		$day = date('d',$value->time);
		if (in_array($day, array_keys($month_dislikes_array))) {
			$month_dislikes_array[$day] += 1; 
		}
	}
	foreach ($month_views as $key => $value) {
		$day = date('d',$value->time);
		if (in_array($day, array_keys($month_views_array))) {
			$month_views_array[$day] += 1; 
		}
	}
	foreach ($month_comments as $key => $value) {
		$day = date('d',$value->time);
		if (in_array($day, array_keys($month_comments_array))) {
			$month_comments_array[$day] += 1; 
		}
	}

	$pt->cat_type = 'this_month';
    $pt->chart_title = $lang->this_month;
    $pt->chart_text = date("M");
	$pt->likes_array = implode(', ', $month_likes_array);
	$pt->dislikes_array = implode(', ', $month_dislikes_array);
	$pt->views_array = implode(', ', $month_views_array);
	$pt->comments_array = implode(', ', $month_comments_array);
	
}
elseif ($type == 'this_year') {
	$this_year_start = strtotime("1 January ".date('Y')." 12:00am");
	$this_year_end = strtotime("31 December ".date('Y')." 11:59pm");
	$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0);

	$year_likes_array = $array;
	$year_dislikes_array = $array;
	$year_views_array = $array;
	$year_comments_array = $array;

	$year_likes = $db->where('type',1)->where('time',$this_year_start,'>=')->where('time',$this_year_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$year_dislikes = $db->where('type',2)->where('time',$this_year_start,'>=')->where('time',$this_year_end,'<=')->where('video_id',$id)->get(T_DIS_LIKES);
	$year_views = $db->where('time',$this_year_start,'>=')->where('time',$this_year_end,'<=')->where('video_id',$id)->get(T_VIEWS);
	$year_comments = $db->where('time',$this_year_start,'>=')->where('time',$this_year_end,'<=')->where('video_id',$id)->get(T_COMMENTS);

	foreach ($year_likes as $key => $value) {
		$day = date('m',$value->time);
		if (in_array($day, array_keys($year_likes_array))) {
			$year_likes_array[$day] += 1; 
		}
	}
	foreach ($year_dislikes as $key => $value) {
		$day = date('m',$value->time);
		if (in_array($day, array_keys($year_dislikes_array))) {
			$year_dislikes_array[$day] += 1; 
		}
	}
	foreach ($year_views as $key => $value) {
		$day = date('m',$value->time);
		if (in_array($day, array_keys($year_views_array))) {
			$year_views_array[$day] += 1; 
		}
	}
	foreach ($year_comments as $key => $value) {
		$day = date('m',$value->time);
		if (in_array($day, array_keys($year_comments_array))) {
			$year_comments_array[$day] += 1; 
		}
	}

	$pt->cat_type = 'this_year';
    $pt->chart_title = $lang->this_year;
    $pt->chart_text = date("Y");
	$pt->likes_array = implode(', ', $year_likes_array);
	$pt->dislikes_array = implode(', ', $year_dislikes_array);
	$pt->views_array = implode(', ', $year_views_array);
	$pt->comments_array = implode(', ', $year_comments_array);
}
$comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' WHERE '.$id.' = video_id');

$pt->page_url_ = $pt->config->site_url.'/view_analytics/'.$_GET['id']."?type=".$type;
$pt->page = 'view_analytics';
$pt->title = $lang->view_analytics . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content     = PT_LoadPage("view_analytics/content", array(
            'TITLE' => $video->title,
            'URL' => $video->url,
            'LIST_ID' => 1,
            'VID_ID' => $video->id,
            'ID' => $video->video_id,
            'THUMBNAIL' => $video->thumbnail,
            'VID_NUMBER' => ($video->video_id == $id) ? "<i class='fa fa-circle'></i>" : 1,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'VIEWS' => number_format($video->views),
            'LIKES' => number_format($video->likes),
            'DISLIKES' => number_format($video->dislikes),
            'COMMENTS' => number_format($comments_count[0]->count)
        ));