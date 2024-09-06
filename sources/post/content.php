<?php 
if (empty($_GET['id'])) {
	header('Location: ' .PT_Link('404'));
	exit();
}
if ($pt->config->post_system != 'on') {
    header('Location: ' .PT_Link('404'));
    exit();
}
$post_id = PT_GetIdFromURL($_GET['id']);
$_GET['id'] = strip_tags($_GET['id']);
$pt->user_post = $db->where('id',$post_id)->getOne(T_ACTIVITES);
$article = $pt->user_post;
if (empty($pt->user_post)) {
	header('Location: ' .PT_Link('404'));
	exit();
}
$post_likes     = $db->where('activity_id', $post_id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
$post_dislikes  = $db->where('activity_id', $post_id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
$liked          = '';
$disliked       = '';

if (IS_LOGGED === true) {
	$u_like     = $db->where('activity_id', $post_id)->where('user_id', $user->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
	$liked      = ($u_like > 0) ? 'active' : '';	

	$u_dislike  = $db->where('activity_id', $post_id)->where('user_id', $user->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
	$disliked   = ($u_dislike > 0) ? 'active' : '';
}
$post_comments = '';


$get_comments   = $db->where('activity_id',$post_id)->orderBy('id','desc')->get(T_COMMENTS,30);
$comments_count = $db->where('activity_id',$post_id)->getValue(T_COMMENTS, "count(*)");
$pt->count_cmt  = $comments_count;
foreach ($get_comments as $get_comment) {
	$pt->is_comment_owner  = false;
	$is_comment_liked      = '';
	$is_comment_disliked   = '';     
    $replies               = "";
    $comment_replies       = $db->where('comment_id', $get_comment->id)->get(T_COMM_REPLIES);
    $is_liked_comment      = '';
    $is_comment_disliked   = '';
    $comment_user_data     = PT_UserData($get_comment->user_id);

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

        //Get related to reply likes
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
            'COMM_ID' => $get_comment->id,
            'LIKES'  => $reply_likes,
            'DIS_LIKES' => $reply_dislikes,
            'LIKED' => $is_liked_reply,
            'DIS_LIKED' => $is_disliked_reply,
        ));
    }


    //Check is user PRO or verified
    $pt->is_verified      = ($comment_user_data->verified == 1) ? true : false;
    $comment_likes        = $get_comment->likes;
    $comment_dislikes     = $get_comment->dis_likes;;  


    if (IS_LOGGED && $user->id == $get_comment->user_id) {
        $pt->is_comment_owner = true;    	
    }

    if (IS_LOGGED === true) {
    	//Check is comment voted by logged-in user
		$db->where('user_id', $user->id);
	    $db->where('comment_id', $get_comment->id);
	    $db->where('type', 1);
	    $is_comment_liked     = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
	    

	    $db->where('user_id', $user->id);
	    $db->where('comment_id', $get_comment->id);
	    $db->where('type', 2);
    	$is_comment_disliked  = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
    }

    

	$post_comments .= PT_LoadPage('post/comments', array(
        'ID'   => $get_comment->id,
        'TEXT' => PT_Markup($get_comment->text),
        'TIME' => PT_Time_Elapsed_String($get_comment->time),
        'USER_DATA' => $comment_user_data,
        'LIKES' => $comment_likes,
        'DIS_LIKES' => $comment_dislikes,
        'LIKED' => $is_comment_liked,
        'DIS_LIKED' =>$is_comment_disliked,
        'COMM_REPLIES' => $replies,
        'POST_ID' => $article->id
    ));
}

if (empty($comments_count)) {
	$post_comments = '<div class="text-center no-comments-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>' . $lang->no_comments_found . '</div>';
}

$pt->page_url_ = $pt->config->site_url.'/post/'.PT_URLSlug($pt->user_post->text,$pt->user_post->id);
$pt->page        = 'show_activity';
$pt->title       = $pt->user_post->text . ' | ' . $pt->config->title;
$pt->description = $pt->user_post->text;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('post/read', array(
    'ID' => $article->id,
    'TITLE' => $article->text,
    'DESC' => $article->text,
    'USER_DATA' => PT_UserData($pt->user_post->user_id),
    'IMAGE' => PT_GetMedia($article->image),
    'TEXT' => PT_Decode($article->text),
    'TIME' => TranslateDate(date($pt->config->date_style,$article->time)),
    'COMMENTS' => $post_comments,
    'COMMENTS_COUNT' => $comments_count,
    'LIKES' => $post_likes,
    'DIS_LIKES' => $post_dislikes,
    'LIKED' => $liked,
    'DIS_LIKED' => $disliked
));