<?php
if (IS_LOGGED == false) {
	header("Location: " . PT_Link('login'));
	exit();
}

$today_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
$today_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

$types = array('likes','dislikes','comments','views');
$rand = rand(0,3);


if ($types[$rand] == 'likes') {
	$result = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$today_start.' AND `time` <= '.$today_end.' AND type = 1 AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id GROUP BY video_id ORDER BY count DESC LIMIT 1');
	$title = $lang->the_most_liked;
	$type = $lang->likes;
}
elseif ($types[$rand] == 'dislikes') {
	$result = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$today_start.' AND `time` <= '.$today_end.'  AND type = 2 AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id GROUP BY video_id ORDER BY count DESC LIMIT 1');
	$title = $lang->the_most_disliked;
	$type = $lang->dislikes;
}
elseif ($types[$rand] == 'comments') {
	$result = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE `time` >= '.$today_start.' AND `time` <= '.$today_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id GROUP BY video_id ORDER BY count DESC LIMIT 1');
	$title = $lang->the_most_commented;
	$type = $lang->comments;
}
elseif ($types[$rand] == 'views') {
	$result = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_VIEWS.' v WHERE `time` >= '.$today_start.' AND `time` <= '.$today_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = v.video_id) = video_id GROUP BY video_id ORDER BY count DESC LIMIT 1');
	$title = $lang->the_most_viewed;
	$type = $lang->views;
}

if (!empty($result[0]->video_id)) {
	$video = PT_GetVideoByID($result[0]->video_id, 0, 1,2);
}

$most_section = '';
if (!empty($result) && !empty($video)) {
	$file_name = 'most_section';
	if ($video->is_short == 1) {
		$file_name = 'most_short_section';
	}
	$most_section = PT_LoadPage("dashboard/$file_name",array('TITLE' => $title,
                                               'VIDEO_TITLE' => $video->title,
								            'URL' => $video->url,
								            'LIST_ID' => 1,
								            'VID_ID' => $video->id,
								            'ID' => $video->video_id,
								            'THUMBNAIL' => $video->thumbnail,
								            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
								            'VIEWS' => $video->views,
								            'LIKES' => $video->likes,
								            'DISLIKES' => $video->dislikes,
								            'COUNT' => $result[0]->count,
								            'TYPE' => $type));
}

$last_month_start = strtotime("first day of previous month 12:00am");
$last_month_end = strtotime("last day of previous month 11:59pm");
$this_month_start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
$this_month_end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");

$user = $pt->user;

$last_month_info = json_decode($user->last_month,true);

if (empty($pt->user->last_month) || $this_month_start > $last_month_info['update_time']) {

	$last_month = array();

	$last_month_likes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$last_month_start.' AND `time` <= '.$last_month_end.' AND type = 1  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id');
	$last_month_dislikes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$last_month_start.' AND `time` <= '.$last_month_end.' AND type = 2  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id');
	$last_month_views = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_VIEWS.' v WHERE `time` >= '.$last_month_start.' AND `time` <= '.$last_month_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = v.video_id) = video_id');
	$last_month_comments = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE `time` >= '.$last_month_start.' AND `time` <= '.$last_month_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id');

	$last_month['likes'] = $last_month_likes[0]->count;
	$last_month['dislikes'] = $last_month_dislikes[0]->count;
	$last_month['views'] = $last_month_views[0]->count;
	$last_month['comments'] = $last_month_comments[0]->count;
	$last_month['update_time'] = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");

	$db->where('id',$pt->user->id)->update(T_USERS,array('last_month' => json_encode($last_month)));
	$user = $db->where('id',$pt->user->id)->getOne(T_USERS);
}


$last_month_info = json_decode($user->last_month,true);

$this_month_likes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$this_month_start.' AND `time` <= '.$this_month_end.' AND type = 1  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id');

$this_month_dislikes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE `time` >= '.$this_month_start.' AND `time` <= '.$this_month_end.' AND type = 2  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id');

$this_month_views = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_VIEWS.' v WHERE `time` >= '.$this_month_start.' AND `time` <= '.$this_month_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = v.video_id) = video_id');

$this_month_comments = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE `time` >= '.$this_month_start.' AND `time` <= '.$this_month_end.'  AND (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id');


if ($last_month_info['likes'] > $this_month_likes[0]->count) {
	$pt->likes_percentage = "-".number_format((100 - (($this_month_likes[0]->count * 100) / $last_month_info['likes'])), 1);
}
else{
	if ($last_month_info['likes'] == 0 && $this_month_likes[0]->count == 0) {
		$pt->likes_percentage = (0);
	}
	else{
		$pt->likes_percentage = ($this_month_likes[0]->count > 0) ? "+".number_format((100 - (($last_month_info['likes'] * 100) / $this_month_likes[0]->count)), 1) : 100;
	}
}

if ($last_month_info['dislikes'] > $this_month_dislikes[0]->count) {
	$pt->dislikes_percentage = "-".number_format((100 - (($this_month_dislikes[0]->count * 100) / $last_month_info['dislikes'])), 1);
}
else{
	if ($last_month_info['dislikes'] == 0 && $this_month_dislikes[0]->count == 0) {
		$pt->dislikes_percentage = (0);
	}
	else{
		$pt->dislikes_percentage = ($this_month_dislikes[0]->count > 0) ? "+".number_format((100 - (($last_month_info['dislikes'] * 100) / $this_month_dislikes[0]->count)), 1) : 100;
	}
}

if ($last_month_info['views'] > $this_month_views[0]->count) {
	$pt->views_percentage = "-".number_format((100 - (($this_month_views[0]->count * 100) / $last_month_info['views'])), 1);
}
else{
	if ($last_month_info['views'] == 0 && $this_month_views[0]->count == 0) {
		$pt->views_percentage = (0);
	}
	else{
		$pt->views_percentage = ($this_month_views[0]->count > 0) ? "+".number_format((100 - (($last_month_info['views'] * 100) / $this_month_views[0]->count)), 1) : 100;
	}
}

if ($last_month_info['comments'] > $this_month_comments[0]->count) {
	$pt->comments_percentage = "-".number_format((100 - (($this_month_comments[0]->count * 100) / $last_month_info['comments'])), 1);
}
else{
	if ($last_month_info['comments'] == 0 && $this_month_comments[0]->count == 0) {
		$pt->comments_percentage = (0);
	}
	else{
		$pt->comments_percentage = ($this_month_comments[0]->count > 0) ? "+".number_format((100 - (($last_month_info['comments'] * 100) / $this_month_comments[0]->count)), 1) : 100;
	}
}




$types = array('today','this_week','this_month','this_year');
$type = 'today';

if (!empty($_GET['type']) && in_array($_GET['type'], $types)) {
	$_GET['type'] = strip_tags($_GET['type']);
	$type = $_GET['type'];
}

if ($type == 'today') {

	$array = array('00' => 0 ,'01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0 ,'13' => 0 ,'14' => 0 ,'15' => 0 ,'16' => 0 ,'17' => 0 ,'18' => 0 ,'19' => 0 ,'20' => 0 ,'21' => 0 ,'22' => 0 ,'23' => 0);
	$day_sub_array = $array;

	$today_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$today_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

	$today_sub = $db->where('user_id',$pt->user->id)->where('time',$today_start,'>=')->where('time',$today_end,'<=')->get(T_SUBSCRIPTIONS);

	foreach ($today_sub as $key => $value) {
		$hour = date('H',$value->time);
		if (in_array($hour, array_keys($day_sub_array))) {
			$day_sub_array[$hour] += 1;
		}
	}

    $pt->cat_type = 'today';
    $pt->chart_title = $lang->today;
    $pt->chart_text = date("l");
	$pt->sub_array = implode(', ', $day_sub_array);
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

	$week_sub_array = $array;

    $week_sub = $db->where('user_id',$pt->user->id)->where('time',$week_start,'>=')->where('time',$week_end,'<=')->get(T_SUBSCRIPTIONS);

	foreach ($week_sub as $key => $value) {
		$day_week = date('l',$value->time);
		if (in_array($day_week, array_keys($week_sub_array))) {
			$week_sub_array[$day_week] += 1;
		}
	}

	$pt->cat_type = 'this_week';
    $pt->chart_title = $lang->this_week;
    $pt->chart_text = date('y/M/d',$week_start)." To ".date('y/M/d',$week_end);
	$pt->sub_array = implode(', ', $week_sub_array);
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


	$month_sub_array = $array;

	$month_sub = $db->where('user_id',$pt->user->id)->where('time',$this_month_start,'>=')->where('time',$this_month_end,'<=')->get(T_SUBSCRIPTIONS);


	foreach ($month_sub as $key => $value) {
		$day = date('d',$value->time);
		if (in_array($day, array_keys($month_sub_array))) {
			$month_sub_array[$day] += 1;
		}
	}

	$pt->cat_type = 'this_month';
    $pt->chart_title = $lang->this_month;
    $pt->chart_text = date("M");
	$pt->sub_array = implode(', ', $month_sub_array);

}
elseif ($type == 'this_year') {
	$this_year_start = strtotime("1 January ".date('Y')." 12:00am");
	$this_year_end = strtotime("31 December ".date('Y')." 11:59pm");
	$array = array('01' => 0 ,'02' => 0 ,'03' => 0 ,'04' => 0 ,'05' => 0 ,'06' => 0 ,'07' => 0 ,'08' => 0 ,'09' => 0 ,'10' => 0 ,'11' => 0 ,'12' => 0);

	$year_sub_array = $array;

	$year_sub = $db->where('user_id',$pt->user->id)->where('time',$this_year_start,'>=')->where('time',$this_year_end,'<=')->get(T_SUBSCRIPTIONS);

	foreach ($year_sub as $key => $value) {
		$day = date('m',$value->time);
		if (in_array($day, array_keys($year_sub_array))) {
			$year_sub_array[$day] += 1;
		}
	}

	$pt->cat_type = 'this_year';
    $pt->chart_title = $lang->this_year;
    $pt->chart_text = date("Y");
	$pt->sub_array = implode(', ', $year_sub_array);
}


$total_comments = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id ');
$total_likes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id AND l.type = 1');
$total_dislikes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id AND l.type = 2');
$total_views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' l WHERE user_id = '.$pt->user->id);
$subscribers = number_format($db->where('user_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)"));


$pt->page_url_ = $pt->config->site_url.'/dashboard';
$pt->page = 'dashboard';
$pt->title = $lang->dashboard . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content     = PT_LoadPage("dashboard/content",array('TOTAL_COMMENTS' => number_format($total_comments[0]->count),
                                                         'TOTAL_LIKES' => number_format($total_likes[0]->count),
                                                         'TOTAL_DISLIKES' => number_format($total_dislikes[0]->count),
                                                         'TOTAL_VIEWS' => ($total_views[0]->count > 0) ? number_format($total_views[0]->count) : 0,
                                                         'TOTAL_SUB' => $subscribers,
                                                         'MOST_SECTION' => $most_section));
