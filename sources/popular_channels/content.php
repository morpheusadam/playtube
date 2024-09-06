<?php 
if ($pt->config->popular_channels != 'on') {
	header("Location: " . PT_Link(''));
	exit();
}
$text = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_channels_found_for_now . '</div>';
$final2 = '';

$types = array('views','subscribers','most_active');
$type = 'views';
if (!empty($_GET['type'])) {
    $_GET['type'] = strip_tags($_GET['type']);
}
if (!empty($_GET['type']) && in_array($_GET['type'], $types)) {
	$type = $_GET['type'];
}


$sort_types = array('today','this_week','this_month','this_year','all_time');
$sort_type = 'all_time';
$pt->cat_type = 'all_time';
if (!empty($_GET['sort_type'])) {
    $_GET['sort_type'] = strip_tags($_GET['sort_type']);
}
if (!empty($_GET['sort_type']) && in_array($_GET['sort_type'], $sort_types)) {
	$sort_type = $_GET['sort_type'];
}

if ($sort_type == 'today') {
	$start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
	$pt->cat_type = 'today';
}
elseif ($sort_type == 'this_week') {
	
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
	$pt->cat_type = 'this_week';
}
elseif ($sort_type == 'this_month') {
	$start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
	$end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
	$pt->cat_type = 'this_month';
}
elseif ($sort_type == 'this_year') {
	$start = strtotime("1 January ".date('Y')." 12:00am");
	$end = strtotime("31 December ".date('Y')." 11:59pm");
	$pt->cat_type = 'this_year';
}

if ($type == 'views') {
	if ($sort_type == 'all_time') {
		$videos = $db->rawQuery('SELECT user_id, SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id ORDER BY count DESC LIMIT 20');
	}
	else{
		$videos = $db->rawQuery('SELECT u.user_id AS user_id , v.video_id, COUNT(*) AS count FROM '.T_VIEWS.' v ,'.T_VIDEOS.' u WHERE v.time >= '.$start.' AND v.time <= '.$end.' AND u.id = v.video_id AND u.user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY u.user_id ORDER BY count DESC LIMIT 20');
	}
}
elseif ($type == 'subscribers') {
	if ($sort_type == 'all_time') {
		$videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id ORDER BY count DESC LIMIT 20');
	}
	else{
		$videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE time >= '.$start.' AND time <= '.$end.' AND user_id NOT IN ('.implode(",", $pt->blocked_array).')  GROUP BY user_id ORDER BY count DESC LIMIT 20');
	}
}
elseif ($type == 'most_active') {
	$time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));

	if (date('l') == 'Friday') {
		$week_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
	}
	else{
		$week_end = strtotime('next Friday, 11:59pm', $time);
	}
	$db->where('active_expire', time(),'<=')->update(T_USERS,array('active_expire' => $week_end,
																   'active_time' => 0));
	$videos = $db->rawQuery('SELECT * FROM '.T_USERS.' WHERE active_time <> 0 AND id NOT IN ('.implode(",", $pt->blocked_array).') ORDER BY active_time DESC LIMIT 20');
}

$featured = '';
$featuredIds = [];
if ($pt->config->theme == 'default') {
	$topChannels = getTopChannelsForThisMonth();

	$featured = $topChannels['featured'];
	$featuredIds = $topChannels['featuredIds'];
}


if (!empty($videos)) {
	foreach ($videos as $key => $value) {
		if ($type == 'views') {
			$views_count = number_format($value->count);
			$views_ = $value->count;
			$subscribers = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id = '.$value->user_id.' GROUP BY user_id LIMIT 1');
			$subscribers_count = 0;
			if (isset($subscribers[0])) {
				$subscribers_count = ($subscribers[0]->count > 0) ? number_format($subscribers[0]->count) : 0;
			}
			$user = PT_UserData($value->user_id);
		}
		elseif ($type == 'subscribers') {
			$subscribers_count = number_format($value->count);
			$views_ = $value->count;
			$views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id = '.$value->user_id.' GROUP BY user_id LIMIT 1');
			$views_count = 0;
			if (isset($views[0])) {
				$views_count = ($views[0]->count > 0) ? number_format($views[0]->count) : 0;
			}
			$user = PT_UserData($value->user_id);
		}
		elseif ($type == 'most_active') {
			$subscribers = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id = '.$value->id.' GROUP BY user_id LIMIT 1');
			$subscribers_count = 0;
			if (isset($subscribers[0])) {
				$subscribers_count = ($subscribers[0]->count > 0) ? number_format($subscribers[0]->count) : 0;
			}
			$views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id = '.$value->id.' GROUP BY user_id LIMIT 1');
			$views_count = 0;
			if (isset($views[0])) {
				$views_count = ($views[0]->count > 0) ? number_format($views[0]->count) : 0;
			}
			$views_ = $value->active_time;
			$user = PT_UserData($value->id);
		}

    	
    	if (!empty($user) && !in_array($user->id, $featuredIds)) {
    		if (strlen($user->name) > 25) {
	    		$user->name = mb_substr($user->name, 0,20).'..';
	    	}
	    	$pt->userData = $user;
	    	$pt->subs = [];
	    	if ($pt->config->theme == 'default') {
    			$sb = $db->where('user_id',$user->id)->orderBy('RAND()')->get(T_SUBSCRIPTIONS,3);
	    		foreach ($sb as $key => $value) {
	    			$userData = $db->where('id',$value->subscriber_id)->getOne(T_USERS,['avatar']);
	    			if (!empty($userData)) {
	    				$userData->avatar = PT_GetMedia($userData->avatar);
	    				$pt->subs[] = $userData;
	    			}
	    		}
	    	}

    		$final2 .= PT_LoadPage('popular_channels/list', array(
			    'ID' => $user->id,
			    'USER_DATA' => $user,
			    'VIEWS' => $views_count,
			    'VIEWS_COUNT' => $views_,
			    'SUB' => $subscribers_count,
			    'ACTIVE_TIME' => (!empty($user->active_time) && $user->active_time > 0 ? secondsToTime($user->active_time) : "0 sec")
			));
    	}
    }
}




$final = (!empty($final2)) ? $final2 : $text;
$pt->type = $type;
$pt->channels_count = count($videos);
$pt->page_url_ = $pt->config->site_url.'/popular_channels?type='.$type.'&sort_type='.$sort_type;
$pt->page        = 'popular_channels';
$pt->title       = $lang->popular_channels . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('popular_channels/content', array(
    'CHANNELS' => $final,
    'featured' => $featured,
));