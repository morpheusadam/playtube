<?php
$comments = '';
$vl1      = (!empty($_POST['video_id']) && is_numeric($_POST['video_id']));
$vl2      = (!empty($_POST['last_id']) && is_numeric($_POST['last_id']));
$vl3      = ($vl1 && $vl2);

if ($vl3 === true) {
    $last_id  = PT_Secure($_POST['last_id']);
    $video_id = PT_Secure($_POST['video_id']);
	$pt->get_video = $get_video = PT_GetVideoByID($video_id, 1, 1,2);
    $pt->video_owner = false;
    $sort = ((!empty($_POST['sort_by']) && is_numeric($_POST['sort_by'])) ? $_POST['sort_by'] : 0);


    if (IS_LOGGED) {
    	$db->where('user_id',$user->id);
    	$db->where('id',$video_id);
    	$pt->video_owner = ($db->getValue(T_VIDEOS,'count(*)') > 0);
    }

    
    $db->where('video_id', $video_id);
    $db->where('pinned', '1','<>');

    if ($sort == 1) {

    	if (!empty($_POST['comments'])) {
    		$com_ids = explode(',', $_POST['comments']);
    		if (is_array($com_ids)) {
    			$db->where('id', $com_ids, 'NOT IN');
    		}
    	}

    	$db->orderBy('likes', 'DESC');
    	$db->orderBy('id', 'DESC');
    }

    else{
    	$db->where('id', $last_id, '<');
    	$db->orderBy('id', 'DESC');
    }
    

    $get_comments = $db->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_COMMENTS, 10);

    if (count($get_comments) > 0) {
        foreach ($get_comments as $key => $comment) {
	        $is_liked_comment     = '';
	        $is_comment_disliked  = '';
	        $pt->is_comment_owner = false;
	        $pt->is_verified      = false;
	        $user_data            = PT_UserData($comment->user_id);
	        $replies              = "";
	        $comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES);

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

	            //Get related to this reply likes
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
	                'LIKES' => $reply_likes,
	                'DIS_LIKES' => $reply_dislikes,
	                'LIKED' => $is_liked_reply,
	                'DIS_LIKED' => $is_disliked_reply
 	            ));
	        }

	        if (IS_LOGGED == true) {

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

	        if ($user_data->verified == 1) {
	        	$pt->is_verified = true;
	        }
	        $comment->text = PT_Duration($comment->text);



	        $comments     .= PT_LoadPage('watch/comments', array(
	            'ID' => $comment->id,
	            'TEXT' => PT_Markup($comment->text),
	            'TIME' => PT_Time_Elapsed_String($comment->time),
	            'USER_DATA' => $user_data,
	            'LIKES' => $comment->likes,
            	'DIS_LIKES' => $comment->dis_likes,
	            'LIKED'  => $is_liked_comment,
	            'DIS_LIKED'  => $is_comment_disliked,
	            'COMM_REPLIES' => $replies,
	            'VID_ID' => $video_id
	        ));
	    }
	    $data = array('status' => 200, 'comments' => $comments);
    } 

    else {
    	$data = array('status' => 404, 'message' => $lang->no_more_comments);
    }
}


if (!empty($_POST['last_id']) && !empty($_POST['post_id']) && is_numeric($_POST['post_id'])) {
    $last_id  = PT_Secure($_POST['last_id']);
    $post_id  = PT_Secure($_POST['post_id']);
    $get_comments = $db->where('id', $last_id, '<')->where('post_id', $post_id)->orderBy('id', 'DESC')->get(T_COMMENTS, 10);
    if (count($get_comments) > 0) {
        foreach ($get_comments as $key => $comment) {
	        $is_comment_liked     = '';
	        $is_comment_disliked  = '';
	        $pt->is_comment_owner = false;
	        $pt->is_verified      = false;
	        $user_data            = PT_UserData($comment->user_id);
	        $replies              = "";
	        $comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES);

	        foreach ($comment_replies as $reply) {
	            $pt->is_reply_owner = false;
	            $pt->is_ro_verified = false;
	            $reply_user_data    = PT_UserData($reply->user_id);
	            $is_liked_reply     = '';
	            $is_disliked_reply  = '';
	            if (IS_LOGGED == true) {
	                $is_reply_owner = $db->where('id', $reply->id)->where('user_id', $user->id)->getValue(T_COMM_REPLIES, 'count(*)');
	                if ($is_reply_owner) {
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

	            //Get related to this reply likes
	            $db->where('reply_id', $reply->id);
	            $db->where('type', 1);
	            $reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            $db->where('reply_id', $reply->id);
	            $db->where('type', 2);
	            $reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
   
	            $replies    .= PT_LoadPage('articles/includes/replies', array(
	                'ID' => $reply->id,
	                'TEXT' => PT_Markup($reply->text),
	                'TIME' => PT_Time_Elapsed_String($reply->time),
	                'USER_DATA' => $reply_user_data,
	                'COMM_ID' => $comment->id,
	                'LIKES' => $reply_likes,
	                'DIS_LIKES' => $reply_dislikes,
	                'LIKED' => $is_liked_reply,
	                'DIS_LIKED' => $is_disliked_reply
 	            ));
	        }

	        if (IS_LOGGED == true){

	        	//Check is comment voted by logged-in user
				$db->where('user_id', $user->id);
			    $db->where('comment_id', $comment->id);
			    $db->where('type', 1);
			    $is_comment_liked     = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
			    

			    $db->where('user_id', $user->id);
			    $db->where('comment_id', $comment->id);
			    $db->where('type', 2);
		    	$is_comment_disliked  = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	            if ($user->id == $comment->user_id) {
	                $pt->is_comment_owner = true;
	            }
	        }

	        if ($user_data->verified == 1) {
	        	$pt->is_verified = true;
	        }

	        $comments  .= PT_LoadPage('articles/includes/comments', array(
		        'ID'   => $comment->id,
		        'TEXT' => PT_Markup($comment->text),
		        'TIME' => PT_Time_Elapsed_String($comment->time),
		        'USER_DATA' => $user_data,
		        'LIKES' => $comment->likes,
            	'DIS_LIKES' => $comment->dis_likes,
		        'LIKED' => $is_comment_liked,
		        'DIS_LIKED' => $is_comment_disliked,
		        'POST_ID' => $post_id,
		        'COMM_REPLIES' => $replies,
		    ));
	    }
	    $data = array('status' => 200, 'comments' => $comments);
    } 

    else {
    	$data = array('status' => 404, 'message' => $lang->no_more_comments);
    }
}


if (!empty($_POST['last_id']) && !empty($_POST['activity_id']) && is_numeric($_POST['activity_id'])) {
    $last_id  = PT_Secure($_POST['last_id']);
    $post_id  = PT_Secure($_POST['activity_id']);
    $get_comments = $db->where('id', $last_id, '<')->where('activity_id', $post_id)->orderBy('id', 'DESC')->get(T_COMMENTS, 10);
    if (count($get_comments) > 0) {
        foreach ($get_comments as $key => $comment) {
	        $is_comment_liked     = '';
	        $is_comment_disliked  = '';
	        $pt->is_comment_owner = false;
	        $pt->is_verified      = false;
	        $user_data            = PT_UserData($comment->user_id);
	        $replies              = "";
	        $comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES);

	        foreach ($comment_replies as $reply) {
	            $pt->is_reply_owner = false;
	            $pt->is_ro_verified = false;
	            $reply_user_data    = PT_UserData($reply->user_id);
	            $is_liked_reply     = '';
	            $is_disliked_reply  = '';
	            if (IS_LOGGED == true) {
	                $is_reply_owner = $db->where('id', $reply->id)->where('user_id', $user->id)->getValue(T_COMM_REPLIES, 'count(*)');
	                if ($is_reply_owner) {
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

	            //Get related to this reply likes
	            $db->where('reply_id', $reply->id);
	            $db->where('type', 1);
	            $reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            $db->where('reply_id', $reply->id);
	            $db->where('type', 2);
	            $reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
   
	            $replies    .= PT_LoadPage('post/replies', array(
	                'ID' => $reply->id,
	                'TEXT' => PT_Markup($reply->text),
	                'TIME' => PT_Time_Elapsed_String($reply->time),
	                'USER_DATA' => $reply_user_data,
	                'COMM_ID' => $comment->id,
	                'LIKES' => $reply_likes,
	                'DIS_LIKES' => $reply_dislikes,
	                'LIKED' => $is_liked_reply,
	                'DIS_LIKED' => $is_disliked_reply
 	            ));
	        }

	        if (IS_LOGGED == true){

	        	//Check is comment voted by logged-in user
				$db->where('user_id', $user->id);
			    $db->where('comment_id', $comment->id);
			    $db->where('type', 1);
			    $is_comment_liked     = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
			    

			    $db->where('user_id', $user->id);
			    $db->where('comment_id', $comment->id);
			    $db->where('type', 2);
		    	$is_comment_disliked  = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

	            if ($user->id == $comment->user_id) {
	                $pt->is_comment_owner = true;
	            }
	        }

	        if ($user_data->verified == 1) {
	        	$pt->is_verified = true;
	        }

	        $comments  .= PT_LoadPage('post/comments', array(
		        'ID'   => $comment->id,
		        'TEXT' => PT_Markup($comment->text),
		        'TIME' => PT_Time_Elapsed_String($comment->time),
		        'USER_DATA' => $user_data,
		        'LIKES' => $comment->likes,
            	'DIS_LIKES' => $comment->dis_likes,
		        'LIKED' => $is_comment_liked,
		        'DIS_LIKED' => $is_comment_disliked,
		        'POST_ID' => $post_id,
		        'COMM_REPLIES' => $replies,
		    ));
	    }
	    $data = array('status' => 200, 'comments' => $comments);
    } 

    else {
    	$data = array('status' => 404, 'message' => $lang->no_more_comments);
    }
}
?>