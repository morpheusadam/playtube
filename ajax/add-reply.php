<?php
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}


if ($first == 'video') {

	$request   = array();
	$request[] = (empty($_POST['video_id']) || !is_numeric($_POST['video_id']));
	$request[] = (empty($_POST['text'])  || empty($_POST['id']));
	$request[] = (!is_numeric($_POST['id']));

	if (!in_array(true, $request)) {

		$text     = PT_Secure($_POST['text'],1);
	    $video_id = PT_Secure($_POST['video_id']);
	    $comm_id  = PT_Secure($_POST['id']);
	    $reply_id = (!empty($_POST['reply']) && is_numeric($_POST['reply'])) ? $_POST['reply'] : 0;
	    $video_data = $db->where('id', $video_id)->getOne(T_VIDEOS);
	    $comm_data  = $db->where('id', $comm_id)->getOne(T_COMMENTS);
	    if (!empty($video_data) && !empty($comm_data)) {
	        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	        $i          = 0;
	        preg_match_all($link_regex, $text, $matches);
	        foreach ($matches[0] as $match) {
	            $match_url = strip_tags($match);
	            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
	            $text      = str_replace($match, $syntax, $text);
	        }
	        if (empty($video_data->facebook) && empty($video_data->vimeo) && empty($video_data->daily) && empty($video_data->youtube) && empty($video_data->twitch) && empty($video_data->ok)) {
	            $link_regex = '/[0-9]*:[0-9]{2}/i';
	            $i          = 0;
	            preg_match_all($link_regex, $text, $matches);
	            
	            foreach ($matches[0] as $match) {
	                $syntax    = '[d]' . $match . '[/d]';
	                $text      = str_replace($match, $syntax, $text);
	            }
	        }

	        $insert_data     = array(
	            'user_id'    => $user->id,
	            'comment_id' => $comm_id,
	            'video_id'   => $video_id,
	            'text' => $text,
	            'time' => time()
	        );

	        $insert_reply = $db->insert(T_COMM_REPLIES, $insert_data);
	        if ($insert_reply) {
	            $get_reply = $db->where('id', $insert_reply)->getOne(T_COMM_REPLIES);
	            $pt->is_reply_owner = true;
	            $pt->is_ro_verified = ($user->verified == 1) ? true : false;
	            $get_reply->text = PT_Duration($get_reply->text);
	            $reply     = PT_LoadPage('watch/replies', array(
	                'ID' => $get_reply->id,
	                'TEXT' => PT_Markup($get_reply->text),
	                'TIME' => PT_Time_Elapsed_String($get_reply->time),
	                'USER_DATA' => PT_UserData($get_reply->user_id),
	                'COMM_ID' => $comm_id,
	                'LIKES' => 0,
	                'DIS_LIKES' => 0,
	                'LIKED' => '',
                	'DIS_LIKED' => ''
	            ));

	            $data        = array(
	                'status' => 200,
	                'html' => $reply
	            );

		        if (!empty($reply_id)) {
	            	$reply_data = $db->where('id',$reply_id)->getOne(T_COMM_REPLIES);
	            	if (!empty($reply_data) && $reply_data->user_id != $user->id) {
	            		$type    = 'replied_2ur_comment';
		                $uniq_id = $video_data->video_id;
		                $notif_data = array(
		                    'notifier_id' => $pt->user->id,
		                    'recipient_id' => $reply_data->user_id,
		                    'type' => $type,
		                    'url' => "watch/$uniq_id&rl=$insert_reply",
		                    'time' => time()
		                );
		                
		                pt_notify($notif_data);
	            	}
	            }
	            else if($comm_data->user_id != $user->id && empty($reply_id)){
	            	$type    = 'replied_2ur_comment';
	                $uniq_id = $video_data->video_id;
	                $notif_data = array(
	                    'notifier_id' => $pt->user->id,
	                    'recipient_id' => $comm_data->user_id,
	                    'type' => $type,
	                    'url' => "watch/$uniq_id&rl=$insert_reply",
	                    'time' => time()
	                );
	                
	                pt_notify($notif_data);
	            }
	        }
	    }
	}

    
}

if ($first == 'article') {

	$request   = array();
	$request[] = (empty($_POST['post_id']) || !is_numeric($_POST['post_id']));
	$request[] = (empty($_POST['text']) || empty($_POST['id']));
	$request[] = (!is_numeric($_POST['id']));

	if (!in_array(true, $request)) {

		$text     = PT_Secure($_POST['text'],1);
	    $post_id  = PT_Secure($_POST['post_id']);
	    $comm_id  = PT_Secure($_POST['id']);
	    $reply_id = (!empty($_POST['reply']) && is_numeric($_POST['reply'])) ? $_POST['reply'] : 0;
	    $post_data  = $db->where('id', $post_id)->getOne(T_POSTS);
	    $comm_data  = $db->where('id', $comm_id)->getOne(T_COMMENTS);
	    if (!empty($post_data) && !empty($comm_data)) {
	        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	        $i          = 0;
	        preg_match_all($link_regex, $text, $matches);
	        foreach ($matches[0] as $match) {
	            $match_url = strip_tags($match);
	            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
	            $text      = str_replace($match, $syntax, $text);
	        }

	        $insert_data     = array(
	            'user_id'    => $user->id,
	            'comment_id' => $comm_id,
	            'post_id'    => $post_id,
	            'text' => $text,
	            'time' => time()
	        );

	        $insert_reply = $db->insert(T_COMM_REPLIES, $insert_data);
	        if ($insert_reply) {
	            $get_reply = $db->where('id', $insert_reply)->getOne(T_COMM_REPLIES);
	            $pt->is_reply_owner = true;
	            $pt->is_ro_verified = ($user->verified == 1) ? true : false;
	            $reply     = PT_LoadPage('articles/includes/replies', array(
	                'ID' => $get_reply->id,
	                'TEXT' => PT_Markup($get_reply->text),
	                'TIME' => PT_Time_Elapsed_String($get_reply->time),
	                'USER_DATA' => PT_UserData($get_reply->user_id),
	                'COMM_ID' => $comm_id,
	                'LIKES' => 0,
	                'DIS_LIKES' => 0,
	                'LIKED' => '',
                	'DIS_LIKED' => ''
	            ));

	            $data        = array(
	                'status' => 200,
	                'html' => $reply
	            );

	            if (!empty($reply_id)) {
	            	$reply_data = $db->where('id',$reply_id)->getOne(T_COMM_REPLIES);
	            	if (!empty($reply_data) && $reply_data->user_id != $user->id) {
	            		$type    = 'replied_2ur_comment';
		                $uniq_id = $post_data->id;
		                $notif_data = array(
		                    'notifier_id' => $pt->user->id,
		                    'recipient_id' => $reply_data->user_id,
		                    'type' => $type,
		                    'url' => "articles/read/$uniq_id&rl=$insert_reply",
		                    'time' => time()
		                );
		                
		                pt_notify($notif_data);
	            	}
	            }
	            else if($comm_data->user_id != $user->id && empty($reply_id)){
	            	$type    = 'replied_2ur_comment';
	                $uniq_id = $post_data->id;
	                $notif_data = array(
	                    'notifier_id' => $pt->user->id,
	                    'recipient_id' => $comm_data->user_id,
	                    'type' => $type,
	                    'url' => "articles/read/$uniq_id&rl=$insert_reply",
	                    'time' => time()
	                );
	                
	                pt_notify($notif_data);
	            }

	            
	        }
	    }
	}

    
}

if ($first == 'activity') {

	$request   = array();
	$request[] = (empty($_POST['post_id']) || !is_numeric($_POST['post_id']));
	$request[] = (empty($_POST['text']) || empty($_POST['id']));
	$request[] = (!is_numeric($_POST['id']));

	if (!in_array(true, $request)) {

		$text     = PT_Secure($_POST['text'],1);
	    $post_id  = PT_Secure($_POST['post_id']);
	    $comm_id  = PT_Secure($_POST['id']);
	    $reply_id = (!empty($_POST['reply']) && is_numeric($_POST['reply'])) ? $_POST['reply'] : 0;
	    $post_data  = $db->where('id', $post_id)->getOne(T_ACTIVITES);
	    $comm_data  = $db->where('id', $comm_id)->getOne(T_COMMENTS);
	    if (!empty($post_data) && !empty($comm_data)) {
	        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	        $i          = 0;
	        preg_match_all($link_regex, $text, $matches);
	        foreach ($matches[0] as $match) {
	            $match_url = strip_tags($match);
	            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
	            $text      = str_replace($match, $syntax, $text);
	        }

	        $insert_data     = array(
	            'user_id'    => $user->id,
	            'comment_id' => $comm_id,
	            'post_id'    => $post_id,
	            'text' => $text,
	            'time' => time()
	        );

	        $insert_reply = $db->insert(T_COMM_REPLIES, $insert_data);
	        if ($insert_reply) {
	            $get_reply = $db->where('id', $insert_reply)->getOne(T_COMM_REPLIES);
	            $pt->is_reply_owner = true;
	            $pt->is_ro_verified = ($user->verified == 1) ? true : false;
	            $reply     = PT_LoadPage('post/replies', array(
	                'ID' => $get_reply->id,
	                'TEXT' => PT_Markup($get_reply->text),
	                'TIME' => PT_Time_Elapsed_String($get_reply->time),
	                'USER_DATA' => PT_UserData($get_reply->user_id),
	                'COMM_ID' => $comm_id,
	                'LIKES' => 0,
	                'DIS_LIKES' => 0,
	                'LIKED' => '',
                	'DIS_LIKED' => ''
	            ));

	            $data        = array(
	                'status' => 200,
	                'html' => $reply
	            );

	            if (!empty($reply_id)) {
	            	$reply_data = $db->where('id',$reply_id)->getOne(T_COMM_REPLIES);
	            	if (!empty($reply_data) && $reply_data->user_id != $user->id) {
	            		$type    = 'replied_2ur_comment';
		                $uniq_id = $post_data->id;
		                $notif_data = array(
		                    'notifier_id' => $pt->user->id,
		                    'recipient_id' => $reply_data->user_id,
		                    'type' => $type,
		                    'url' => "post/".PT_URLSlug($post_data->text,$post_data->id),
		                    'time' => time()
		                );
		                
		                pt_notify($notif_data);
	            	}
	            }
	            else if($comm_data->user_id != $user->id && empty($reply_id)){
	            	$type    = 'replied_2ur_comment';
	                $uniq_id = $post_data->id;
	                $notif_data = array(
	                    'notifier_id' => $pt->user->id,
	                    'recipient_id' => $comm_data->user_id,
	                    'type' => $type,
	                    'url' => "post/".PT_URLSlug($post_data->text,$post_data->id),
	                    'time' => time()
	                );
	                
	                pt_notify($notif_data);
	            }

	            
	        }
	    }
	}

    
}