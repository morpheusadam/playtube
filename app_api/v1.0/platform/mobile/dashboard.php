<?php
if (!IS_LOGGED) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
	);
}
else{
	$data = array();
	$total_comments = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id ');
	$total_likes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id AND l.type = 1');
	$total_dislikes = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = l.video_id) = video_id AND l.type = 2');
	$total_views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' l WHERE user_id = '.$pt->user->id);
	$data['subscribers'] = number_format($db->where('user_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)"));


	$last_month_start = strtotime("first day of previous month 12:00am");
	$last_month_end = strtotime("last day of previous month 11:59pm");
	$this_month_start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
	$this_month_end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
	$today_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
	$today_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
	$this_year_start = strtotime("1 January ".date('Y')." 12:00am");
	$this_year_end = strtotime("31 December ".date('Y')." 11:59pm");


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



	$today_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$today_start.' AND c.time <= '.$today_end);

	$month_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$this_month_start.' AND c.time <= '.$this_month_end);

	$year_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$this_year_start.' AND c.time <= '.$this_year_end);

	$data['likes_percentage'] = $pt->likes_percentage;
	$data['dislikes_percentage'] = $pt->dislikes_percentage;
	$data['views_percentage'] = $pt->views_percentage;
	$data['comments_percentage'] = $pt->comments_percentage;
	$data['total_comments'] = $total_comments[0]->count;
	$data['total_likes'] = $total_likes[0]->count;
	$data['total_dislikes'] = $total_dislikes[0]->count;
	$data['total_views'] = $total_views[0]->count;
	$data['today_comments_count'] = $today_comments_count[0]->count;
	$data['month_comments_count'] = $month_comments_count[0]->count;
	$data['year_comments_count'] = $year_comments_count[0]->count;















	$response_data     = array(
	    'api_status'   => '200',
	    'api_version'  => $api_version,
	    'success_type' => 'success',
	    'data'    => $data,
	);
}