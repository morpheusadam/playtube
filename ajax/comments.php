<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if ($_GET['first'] == 'get_comment' && !empty($_POST['id'])) {
	$data['status'] = 400;
	$id = PT_Secure($_POST['id']);
	$comment = $db->where('id',$id)->getOne(T_COMMENTS);
	if (!empty($comment)) {
		$duration_search = '/\[d\](.*?)\[\/d\]/i';

	    if (preg_match_all($duration_search, $comment->text, $matches)) {
	        foreach ($matches[1] as $match) {
	            $comment->text = str_replace('[d]' . $match . '[/d]', $match, $comment->text);
	        }
	    }

	    $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $comment->text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode     = urldecode($match);
                $match_decode_url = $match_decode;
                $count_url        = mb_strlen($match_decode);
                if ($count_url > 50) {
                    $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                }
                $match_url = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $comment->text = str_replace('[a]' . $match . '[/a]', strip_tags($match_url), $comment->text);
            }
        }
        $data['status'] = 200;
        $data['text'] = $comment->text;
	}
}

if ($_GET['first'] == 'update_comment' && !empty($_POST['id']) && !empty($_POST['text'])) {
	$data['status'] = 400;
	$id = PT_Secure($_POST['id']);
	$comment = $db->where('id',$id)->getOne(T_COMMENTS);
	$video = $db->where('id',$comment->video_id)->getOne(T_VIDEOS);
	if ($comment->user_id == $pt->user->id || $video->user_id == $pt->user->id) {
		$text = PT_Secure($_POST['text'],1);
		$link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	    $i          = 0;
	    preg_match_all($link_regex, $text, $matches);
	    foreach ($matches[0] as $match) {
	        $match_url = strip_tags($match);
	        $syntax    = '[a]' . urlencode($match_url) . '[/a]';
	        $text      = str_replace($match, $syntax, $text);
	    }
	    $link_regex = '/[0-9]*:[0-9]{2}/i';
	    $i          = 0;
	    preg_match_all($link_regex, $text, $matches);
	    
	    foreach ($matches[0] as $match) {
	        $syntax    = '[d]' . $match . '[/d]';
	        $text      = str_replace($match, $syntax, $text);
	    }
	    $db->where('id',$id)->update(T_COMMENTS,array('text' => $text));
	    $new_text = PT_Duration($text);
	    $new_text = PT_Markup($new_text);
	    $data['status'] = 200;
	    $data['text'] = $new_text;
	}
}
if ($_GET['first'] == 'open_modal') {
	$data['status'] = 400;
	$comments = '<div class="text-center no-comments-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>'.$pt->all_lang->no_comments_found.'</div>';
	if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
		$id =  PT_Secure($_POST['id']);
		$pt->get_video = $get_video = PT_GetVideoByID($id, 1, 1,2);
		if (!empty($get_video)) {
			$db->where('video_id', $get_video->id);
			$db->where('pinned', '1','<>')->where('user_id',$pt->blocked_array , 'NOT IN');
			$db->orderBy('id', 'DESC');
			$pt->config->comments_default_num = 5;
			$comments_limit     = $pt->config->comments_default_num;

			if (!empty($_GET['cl']) || !empty($_GET['rl'])) {
			    $comments_limit = null;
			}

			$get_video_comments = $db->get(T_COMMENTS,$comments_limit);
			$pt->video_owner = (IS_LOGGED && $get_video->user_id == $user->id);
			if (!empty($get_video_comments)) {
				$data['status'] = 200;
			    $comments = '';
			    foreach ($get_video_comments as $key => $comment) {
			        $comment->text = PT_Duration($comment->text);
			        $is_liked_comment = 0;
			        $pt->is_comment_owner = false;
			        $replies              = "";
			        $pt->pin              = false;
			        $comment_replies      = $db->where('comment_id', $comment->id)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_COMM_REPLIES);
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
			            $reply->text = PT_Duration($reply->text);

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
			        if (!empty($get_video->stream_name) && $comment->time <= $get_video->live_time) {
			            $video_time = GetVideoTime($get_video->time,$comment->time);
			            $current_time = '<span class="time pointer" onclick="go_to_duration('.$video_time['current_time'].')"><a href="javascript:void(0)">'.$video_time['time'].'</a> </span>';
			        }
			        else{
			            $current_time = PT_Time_Elapsed_String($comment->time);
			        }

			        $comments     .= PT_LoadPage('watch/comments', array(
			            'ID' => $comment->id,
			            'TEXT' => PT_Markup($comment->text),
			            'TIME' => $current_time,
			            'USER_DATA' => $comment_user_data,
			            'LIKES' => $comment->likes,
			            'DIS_LIKES' => $comment->dis_likes,
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
			
			$data['html'] = $comments;
			$data['pinned_comments'] = $pinned_comments;
		}
		
			
	}
}