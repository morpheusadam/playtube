<?php
$comments = '';
$vl1      = (!empty($_POST['video_id']) && is_numeric($_POST['video_id']));

if ($vl1 === true) {

    $video_id = PT_Secure($_POST['video_id']);
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
    	$db->orderBy('likes', 'DESC');
    	$db->orderBy('id', 'DESC');
    }
    else{
    	$db->orderBy('id', 'DESC');
    }
    

    $get_comments = $db->get(T_COMMENTS, 10);
    

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



	        $comments_likes   = $comment->likes;
	        $comment_dislikes = $comment->dis_likes;

	        $comments     .= PT_LoadPage('watch/comments', array(
	            'ID' => $comment->id,
	            'TEXT' => PT_Markup($comment->text),
	            'TIME' => PT_Time_Elapsed_String($comment->time),
	            'USER_DATA' => $user_data,
	            'LIKES' => $comments_likes,
	            'DIS_LIKES' => $comment_dislikes,
	            'LIKED'  => $is_liked_comment,
	            'DIS_LIKED'  => $is_comment_disliked,
	            'COMM_REPLIES' => $replies,
	            'VID_ID' => $video_id
	        ));
	    }

	    $data = array('status' => 200, 'comments' => $comments);
    } 

    else {
    	$data = array('status' => 404);
    }
}

?>