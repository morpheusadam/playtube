<?php
if (!IS_LOGGED) {
	header('Location: ' . PT_Link('404'));
	exit;
}

$video_id               = (!empty($_GET['video_id'])) ? PT_Secure($_GET['video_id']) : '';
$get_video              = $db->where('video_id',$video_id)->getOne(T_VIDEOS);

if (!empty($get_video)) {
	$_GET['video_id'] = strip_tags($_GET['video_id']);
	$pt->page           = 'get-video-comments';
	$pt->title          = $lang->home . ' | ' . $pt->config->title;
	$pt->description    = $pt->config->description;
	$pt->keyword        = $pt->config->keyword;
	$pt->count_comments = $db->where('video_id', $get_video->id)->getValue(T_COMMENTS, 'count(*)');
	$comments           = '';
	$db->where('video_id', $get_video->id);
	$db->where('pinned', '1','<>');
	$db->orderBy('id', 'DESC');
	$pt->config->comments_default_num = 5;
	$comments_limit     = $pt->config->comments_default_num;
	$pt->video_owner    = ($user->id == $get_video->user_id) ? true : false;

	if (!empty($_GET['cl']) || !empty($_GET['rl'])) {
		if (!empty($_GET['cl'])) {
			$_GET['cl'] = strip_tags($_GET['cl']);
		}
		if (!empty($_GET['rl'])) {
			$_GET['rl'] = strip_tags($_GET['rl']);
		}
	    $comments_limit = null;
	}

	$get_video_comments = $db->get(T_COMMENTS,$comments_limit);
	if (!empty($get_video_comments)) {
	    $comments = '';
	    foreach ($get_video_comments as $key => $comment) {
	        $is_liked_comment = 0;
	        $pt->is_comment_owner = false;      
	        $replies              = "";
	        $pt->pin              = false;
	        $comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES);
	        $is_liked_comment     = '';
	        $is_comment_disliked  = '';
	        $comment_user_data    = PT_UserData($comment->user_id);
	        $pt->is_verified      = ($comment_user_data->verified == 1) ? true : false;
	        foreach ($comment_replies as $reply) {
	            $pt->is_reply_owner = false;
	            $pt->is_ro_verified = false;
	            $reply_user_data    = PT_UserData($reply->user_id);
	            $is_liked_reply     = '';
	            $is_disliked_reply  = '';
	            if (IS_LOGGED == true) {
	                $is_reply_owner = $db->where('id', $reply->id)->where('user_id', $user->id)->getValue(T_COMM_REPLIES, 'count(*)');
	                if ($is_reply_owner || $pt->video_owner) {
	                    $pt->is_reply_owner = true;
	                }

	                //Check is this reply  voted by logged-in user
	                $db->where('reply_id', $reply->id);
	                $db->where('user_id', $user->id);
	                $db->where('type', 1);
	                $is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	                $db->where('reply_id', $reply->id);
	                $db->where('user_id', $user->id);
	                $db->where('type', 2);
	                $is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
	            }
	            
	            if ($reply_user_data->verified == 1) {
	                $pt->is_ro_verified = true;
	            }

	            //Get related to reply likes
	            $db->where('reply_id', $reply->id);
	            $db->where('type', 1);
	            $reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            $db->where('reply_id', $reply->id);
	            $db->where('type', 2);
	            $reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            $replies    .= PT_LoadPage('watch/replies', array(
	                'ID' => $reply->id,
	                'TEXT' => PT_Markup($reply->text),
	                'TIME' => PT_Time_Elapsed_String($reply->time),
	                'USER_DATA' => $reply_user_data,
	                'COMM_ID' => $comment->id,
	                'LIKES'  => $reply_likes,
	                'DIS_LIKES' => $reply_dislikes,
	                'LIKED' => $is_liked_reply,
	                'DIS_LIKED' => $is_disliked_reply,
	            ));
	        }

	        if (IS_LOGGED == true) {
	            $is_liked_comment = $db->where('comment_id', $comment->id)->where('user_id', $user->id)->getValue(T_COMMENTS_LIKES, 'count(*)');

	            //Check is comment voted by logged-in user
	            $db->where('comment_id', $comment->id);
	            $db->where('user_id', $user->id);
	            $db->where('type', 1);
	            $is_liked_comment   = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	            $db->where('comment_id', $comment->id);
	            $db->where('user_id', $user->id);
	            $db->where('type', 2);
	            $is_comment_disliked = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	            if ($user->id == $comment->user_id || $pt->video_owner) {
	                $pt->is_comment_owner = true;
	            }
	        }

	        $comments     .= PT_LoadPage('watch/comments', array(
	            'ID' => $comment->id,
	            'TEXT' => PT_Markup($comment->text),
	            'TIME' => PT_Time_Elapsed_String($comment->time),
	            'USER_DATA' => $comment_user_data,
	            'LIKES' => $db->where('comment_id', $comment->id)->where('type', 1)->getValue(T_COMMENTS_LIKES, 'count(*)'),
	            'DIS_LIKES' => $db->where('comment_id', $comment->id)->where('type', 2)->getValue(T_COMMENTS_LIKES, 'count(*)'),
	            'LIKED' => $is_liked_comment,
	            'DIS_LIKED' => $is_comment_disliked,
	            'COMM_REPLIES' => $replies,
	            'VID_ID' => $get_video->id
	        )); 
	    }
	}


	$db->where('video_id', $get_video->id);
	$db->where('pinned', '1');
	$pinned_comments     = "";
	$pinned_comm_data    = $db->getOne(T_COMMENTS);

	if (!empty($pinned_comm_data)) {
	    $pinned_comments = pt_comm_object_data($pinned_comm_data,true);
	}

	echo PT_LoadPage("hybird_view/content",array(
		'CONTENT'  => PT_LoadPage("watch/video-comments",array(
			'COUNT_COMMENTS' => $pt->count_comments,
	        'COMMENTS' => $comments,
	        'PINNED_COMMENTS' => $pinned_comments,
		)),
		'EXTRA_JS' => PT_LoadPage("extra-js/content"),
		'IS_LOGGED' => (IS_LOGGED == true) ? 'data-logged="true"' : '',
		'VIDEO_ID' => $get_video->id
	));
	exit();
}
else{
	echo PT_LoadPage("hybird_view/content",array(
		'CONTENT'  => PT_LoadPage("404/content"),
		'EXTRA_JS' => ''
	));
	exit();
}
