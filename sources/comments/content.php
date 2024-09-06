<?php 
if (IS_LOGGED == false) {
	header("Location: " . PT_Link('login'));
	exit();
}
$today_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
$today_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");

$this_month_start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
$this_month_end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");

$this_year_start = strtotime("1 January ".date('Y')." 12:00am");
$this_year_end = strtotime("31 December ".date('Y')." 11:59pm");

$comments = $db->rawQuery('SELECT * FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') ORDER BY id DESC LIMIT 20');

$today_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$today_start.' AND c.time <= '.$today_end);

$month_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$this_month_start.' AND c.time <= '.$this_month_end);

$year_comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.' AND id = c.video_id) = video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).') AND c.time >= '.$this_year_start.' AND c.time <= '.$this_year_end);

$html = '';
foreach ($comments as $key => $comment) {
	$comment->text = PT_Duration($comment->text);
    $is_liked_comment = 0;
    $pt->is_comment_owner = false;      
    $replies              = "";
    $is_liked_comment     = '';
    $is_comment_disliked  = '';
    $comment_user_data    = PT_UserData($comment->user_id);
    $pt->is_verified      = ($comment_user_data->verified == 1) ? true : false;

    $db->where('comment_id', $comment->id);
    $db->where('user_id', $pt->user->id);
    $db->where('type', 1);
    $is_liked_comment   = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

    $db->where('comment_id', $comment->id);
    $db->where('user_id', $pt->user->id);
    $db->where('type', 2);
    $is_comment_disliked = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	$video = PT_GetVideoByID($comment->video_id, 0, 1,2);
	$pt->comment = $comment;
	$html .= PT_LoadPage("comments/list", array(
									            'TITLE' => $video->title,
									            'URL' => $video->url,
									            'ajax_url' => $video->ajax_url,
									            'LIST_ID' => 1,
									            'VID_ID' => $video->id,
									            'ID' => $video->video_id,
									            'THUMBNAIL' => $video->thumbnail,
									            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
									            'VIEWS' => $video->views,
									            'LIKES' => $video->likes,
									            'DISLIKES' => $video->dislikes,
									            'COMMENT' => PT_LoadPage('comments/comments', array(
													            'ID'           => $comment->id,
													            'TEXT'         => PT_Markup($comment->text),
													            'TIME'         => PT_Time_Elapsed_String($comment->time),
													            'USER_DATA'    => $comment_user_data,
													            'LIKES'        => $comment->likes,
													            'DIS_LIKES'    => $comment->dis_likes,
													            'LIKED'        => $is_liked_comment,
													            'DIS_LIKED'    => $is_comment_disliked,
													            'COMM_REPLIES' => $replies,
													            'VID_ID'       => $video->id,
													            'V_ID'         => $video->video_id
													        )),
									            'COMMENT_ID' => $comment->id
									        ));
}
$pt->comments_count = count($comments);
$pt->page_url_ = $pt->config->site_url.'/comments';
$pt->page = 'comments';
$pt->title = $lang->comments . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content     = PT_LoadPage("comments/content", array('COMMENTS'             => $html,
                                                         'TOTAL_COMMENTS_TODAY' => $today_comments_count[0]->count,
                                                         'TOTAL_COMMENTS_MONTH' => $month_comments_count[0]->count,
                                                         'TOTAL_COMMENTS_YEAR'  =>  $year_comments_count[0]->count));