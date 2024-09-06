<?php
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}


if (!empty($_POST['video_id']) && !empty($_POST['text'])) {
    $text = PT_Secure($_POST['text'],1);
    $id   = PT_Secure($_POST['video_id']);
    
    //$video_data = $pt->get_video = $db->where('id', $id)->getOne(T_VIDEOS);
    $video_data = $pt->get_video = PT_GetVideoByID($id, 0, 0, 2);
    $pt->is_paid = 0;
    if ($video_data->sell_video > 0) {
        if (!empty($user->id)) {
            $pt->is_paid = $db->where('video_id',$video_data->id)->where('paid_id',$user->id)->getValue(T_VIDEOS_TRSNS,"count(*)");
        }
        $pt->purchased = $db->where('video_id',$video_data->id)->getValue(T_VIDEOS_TRSNS,"count(*)");
    }
    if (!empty($video_data) && $video_data->live_chating == 'on') {
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
        $insert_data    = array(
            'user_id' => $user->id,
            'video_id' => $id,
            'text' => $text,
            'time' => time()
        );
        $insert_comment = $db->insert(T_COMMENTS, $insert_data);
        if ($insert_comment) {
            $get_comment = $db->where('id', $insert_comment)->getOne(T_COMMENTS);
            $user_data   = PT_UserData($get_comment->user_id);
            $pt->is_comment_owner = false;
            $pt->is_verified      = ($user_data->verified == 1) ? true : false;
            $pt->video_owner      = false;

            if ($user->id == $get_comment->user_id) {
                $pt->is_comment_owner = true;
            }

            if ($video_data->user_id == $user->id) {
                $pt->video_owner = true;
            }
            $get_comment->text = PT_Duration($get_comment->text);

            $comment     = PT_LoadPage('watch/comments', array(
                'ID' => $get_comment->id,
                'TEXT' => PT_Markup($get_comment->text),
                'TIME' => PT_Time_Elapsed_String($get_comment->time),
                'USER_DATA' => $user_data,
                'LIKES' => 0,
                'DIS_LIKES' => 0,
                'LIKED' => '',
                'DIS_LIKED' => '',
                'LIKED_ATTR' => '',
                'COMM_REPLIES' => '',
                'VID_ID' => $id
            ));

            $data        = array(
                'status' => 200,
                'comment' => $comment
            );

            if ($video_data->user_id != $user->id) {
                RegisterPoint($video_data->id, "comments");
                $type    = 'commented_ur_video';
                $uniq_id = $video_data->video_id;
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $video_data->user_id,
                    'type' => $type,
                    'url' => "watch/$uniq_id&cl=$insert_comment",
                    'video_id' => $id,
                    'time' => time()
                );
                
                pt_notify($notif_data);
            }
        }
    }
}

if (!empty($_POST['post_id']) && !empty($_POST['text']) && is_numeric($_POST['post_id'])) {
    $text        = PT_Secure($_POST['text'],1);
    $id          = PT_Secure($_POST['post_id']);  
    $verfiy_post = $db->where('id', $id)->getValue(T_POSTS, "count(*)");

    $request     = ($verfiy_post > 0);

    if ($request === true) {

        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;

        preg_match_all($link_regex, $text, $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
            $text      = str_replace($match, $syntax, $text);
        }

        $insert_data    = array(
            'user_id' => $user->id,
            'post_id' => $id,
            'text' => PT_ShortText($text,600),
            'time' => time()
        );

        $insert_comment           = $db->insert(T_COMMENTS, $insert_data);
        if ($insert_comment) {
            $get_comment          = $db->where('id', $insert_comment)->getOne(T_COMMENTS);
            $pt->is_comment_owner = false;
            if ($user->id == $get_comment->user_id) {
                $pt->is_comment_owner = true;
            }

            //Check is user PRO or verified
            $db->where('id', $get_comment->user_id);
            $db->where('verified',1);
            $pt->is_verified      = ($db->getValue(T_USERS, 'count(*)') > 0);


            $comment     = PT_LoadPage('articles/includes/comments', array(
                'ID'   => $get_comment->id,
                'TEXT' => PT_Markup($get_comment->text),
                'TIME' => PT_Time_Elapsed_String($get_comment->time),
                'USER_DATA' => PT_UserData($get_comment->user_id),
                'LIKES' => 0,
                'DIS_LIKES' => 0,
                'LIKED' => '',
                'DIS_LIKED' =>'',
                'POST_ID' => $id,
                'COMM_REPLIES' => ''
            ));
            
            $data        = array(
                'status' => 200,
                'comment' => $comment
            );
        }
    }
}

if (!empty($_POST['activity_id']) && !empty($_POST['text']) && is_numeric($_POST['activity_id'])) {
    $text        = PT_Secure($_POST['text'],1);
    $id          = PT_Secure($_POST['activity_id']);  
    $post = $db->where('id', $id)->getOne(T_ACTIVITES);
    $verfiy_post = $db->where('id', $id)->getValue(T_ACTIVITES, "count(*)");

    $request     = ($verfiy_post > 0);

    if ($request === true) {

        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;

        preg_match_all($link_regex, $text, $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
            $text      = str_replace($match, $syntax, $text);
        }

        $insert_data    = array(
            'user_id' => $user->id,
            'activity_id' => $id,
            'text' => PT_ShortText($text,600),
            'time' => time()
        );

        $insert_comment           = $db->insert(T_COMMENTS, $insert_data);
        if ($insert_comment) {
            $get_comment          = $db->where('id', $insert_comment)->getOne(T_COMMENTS);
            $pt->is_comment_owner = false;
            if ($user->id == $get_comment->user_id) {
                $pt->is_comment_owner = true;
            }

            //Check is user PRO or verified
            $db->where('id', $get_comment->user_id);
            $db->where('verified',1);
            $pt->is_verified      = ($db->getValue(T_USERS, 'count(*)') > 0);


            $comment     = PT_LoadPage('articles/includes/comments', array(
                'ID'   => $get_comment->id,
                'TEXT' => PT_Markup($get_comment->text),
                'TIME' => PT_Time_Elapsed_String($get_comment->time),
                'USER_DATA' => PT_UserData($get_comment->user_id),
                'LIKES' => 0,
                'DIS_LIKES' => 0,
                'LIKED' => '',
                'DIS_LIKED' =>'',
                'POST_ID' => $id,
                'COMM_REPLIES' => ''
            ));
            
            $data        = array(
                'status' => 200,
                'comment' => $comment
            );

            if ($post->user_id != $user->id) {
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $post->user_id,
                    'type' => 'commented_ur_actvity',
                    'url' => "post/".PT_URLSlug($post->text,$post->id),
                    'video_id' => $id,
                    'time' => time()
                );
                
                pt_notify($notif_data);
            }
        }
    }
}