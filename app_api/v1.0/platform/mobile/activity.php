<?php
$types = array('create','edit','delete','user_activity','like','dislike','create_comment','edit_comment','delete_comment','reply_comment','fetch_comments','fetch_replies');
$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;
if (!IS_LOGGED && $_POST['type'] != 'user_activity' && $_POST['type'] != 'fetch_replies' && $_POST['type'] != 'fetch_comments') {

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
	);
}
else if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else{

	if ($_POST['type'] == 'create') {

        if (empty($_POST['text']) || empty($_FILES["image"])) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => 'please check your details'
                )
            );
        }
        else if (!empty($_FILES["image"]["error"]) || !file_exists($_FILES["image"]["tmp_name"])) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '4',
                    'error_text' => 'image not valid'
                )
            );
        } 
        else{

            if (file_exists($_FILES["image"]["tmp_name"])) {
                $image = getimagesize($_FILES["image"]["tmp_name"]);
                if (!in_array($image[2], array(
                    IMAGETYPE_GIF,
                    IMAGETYPE_JPEG,
                    IMAGETYPE_PNG,
                    IMAGETYPE_BMP
                ))){
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '5',
                            'error_text' => 'image not valid'
                        )
                    );
                }
            }
            if (empty($data['message'])) {
                $file_info   = array(
                    'file' => $_FILES['image']['tmp_name'],
                    'size' => $_FILES['image']['size'],
                    'name' => $_FILES['image']['name'],
                    'type' => $_FILES['image']['type'],
                    'crop' => array(
                        'width' => 600,
                        'height' => 400
                    )
                );

                $file_upload     = PT_ShareFile($file_info);

                if (!empty($file_upload['filename'])) {
                    $post_image  = PT_Secure($file_upload['filename']);
                    $insert_data = array(
                        'image' => $post_image,
                        'text' => PT_Secure($_POST['text']),
                        'time' => time(),
                        'user_id' => $pt->user->id
                    );

                    $insert     = $db->insert(T_ACTIVITES,$insert_data);

                    $activity = $db->where('id',$insert)->getOne(T_ACTIVITES);
                    $activity->image = PT_GetMedia($activity->image);
                    $activity->time_text = PT_Time_Elapsed_String($activity->time);
                    $activity->likes     = 0;
                    $activity->dislikes  = 0;

                    $activity->is_liked = 0;
                    $activity->is_disliked = 0;
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'create activity',
                        'message'    => 'Your activity successfully added.',
                        'data'      => $activity
                    );
                }
            }
        }
    }

    if ($_POST['type'] == 'edit') {

        if (empty($_POST['text']) || empty($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] < 1) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => 'please check your details'
                )
            );
        }
        else{

            if (!empty($_FILES["image"])) {
                if (file_exists($_FILES["image"]["tmp_name"])) {
                    $image = getimagesize($_FILES["image"]["tmp_name"]);
                    if (!in_array($image[2], array(
                        IMAGETYPE_GIF,
                        IMAGETYPE_JPEG,
                        IMAGETYPE_PNG,
                        IMAGETYPE_BMP
                    ))){
                        $response_data       = array(
                            'api_status'     => '400',
                            'api_version'    => $api_version,
                            'errors'         => array(
                                'error_id'   => '4',
                                'error_text' => 'image not valid'
                            )
                        );
                    }
                }
            }

            if (empty($data['message'])) {
                $id    = PT_Secure($_POST['id']);
                $post = $db->where('id',$id)->getOne(T_ACTIVITES);
                if (!empty($post) && ($post->user_id == $pt->user->id || PT_IsAdmin())) {
                    $update_data = array(
                        'text' => PT_Secure($_POST['text'])
                    );


                    if (!empty($_FILES['image'])) {
                        $file_info   = array(
                            'file' => $_FILES['image']['tmp_name'],
                            'size' => $_FILES['image']['size'],
                            'name' => $_FILES['image']['name'],
                            'type' => $_FILES['image']['type'],
                            'crop' => array(
                                'width' => 600,
                                'height' => 400
                            )
                        );

                        $file_upload     = PT_ShareFile($file_info);
                        $update_data['image'] = $file_upload['filename'];

                        if (file_exists($post->image)) {
                            unlink($post->image);
                        }
                        
                        else if ($s3 === true) {
                            PT_DeleteFromToS3($post->image);
                        }
                    }

                    $insert     = $db->where('id',$id)->update(T_ACTIVITES,$update_data);
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'edit activity',
                        'message'    => 'Your activity successfully edited.'
                    );
                }
            }
        }
    }

    if ($_POST['type'] == 'delete') {
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $article = $db->where('id',PT_Secure($_POST['id']))->getOne(T_ACTIVITES);
            if (!empty($article) && (PT_IsAdmin() || $article->user_id == $pt->user->id)) {
                if (file_exists($article->image)) {
                    unlink($article->image);
                }
                
                else if ($pt->remoteStorage === true) {
                    PT_DeleteFromToS3($article->image);
                }
            
                $delete  = $db->where('id',PT_Secure($_POST['id']))->delete(T_ACTIVITES);
                $delete  = $db->where('activity_id',PT_Secure($_POST['id']))->delete(T_DIS_LIKES);

                //Delete related data
                $post_comments = $db->where('activity_id',PT_Secure($_POST['id']))->get(T_COMMENTS);

                foreach ($post_comments as $comment_data) {
                    $delete    = $db->where('comment_id',$comment_data->id)->delete(T_COMMENTS_LIKES);
                    $replies   = $db->where('comment_id',$comment_data->id)->get(T_COMM_REPLIES);
                    $db->where('comment_id',$comment_data->id)->delete(T_COMM_REPLIES);
                    
                    foreach ($replies as $comment_reply) {
                        $db->where('reply_id',$comment_reply->id)->delete(T_COMMENTS_LIKES);
                    }
                }

                if (!empty($post_comments)) {
                    $delete    = $db->where('activity_id',PT_Secure($_POST['id']))->delete(T_COMMENTS);   
                }
                
                if ($delete) {
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'delete activity',
                        'message'    => 'Your activity successfully deleted.'
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '4',
                        'error_text' => 'activity not found'
                    )
                );
            }
        }
    }

    if ($_POST['type'] == 'user_activity') {
        if (!empty($_POST['profile_id']) && is_numeric($_POST['profile_id']) && $_POST['profile_id'] > 0) {
            $user_id = PT_Secure($_POST['profile_id']);
            if (!empty($offset)) {
                $db->where('id', $offset, '<');
            }
            $posts = $db->where('user_id',$user_id)->orderBy('id', 'DESC')->get(T_ACTIVITES,$limit);
            if (!empty($posts)) {
                foreach ($posts as $key => $post) {
                    $posts[$key]->is_owner = false;
                    if ($post->user_id == $pt->user->id || PT_IsAdmin()) {
                        $posts[$key]->is_owner = true;
                    }
                    $posts[$key]->image = PT_GetMedia($post->image);
                    $posts[$key]->time_text = PT_Time_Elapsed_String($post->time);

                    $posts[$key]->likes     = $db->where('activity_id', $post->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
                    $posts[$key]->dislikes  = $db->where('activity_id', $post->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");

                    $posts[$key]->is_liked = 0;
                    $posts[$key]->is_disliked = 0;
                    $posts[$key]->link = PT_Link('post/' . PT_URLSlug($post->text,$post->id));
                    if (IS_LOGGED === true) {
                        $u_like     = $db->where('activity_id', $post->id)->where('user_id', $pt->user->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
                        $posts[$key]->is_liked      = ($u_like > 0) ? 1 : 0;    

                        $u_dislike  = $db->where('activity_id', $post->id)->where('user_id', $pt->user->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
                        $posts[$key]->is_disliked   = ($u_dislike > 0) ? 1 : 0;
                    }
                    $posts[$key]->comments_count = $db->where('activity_id',$post->id)->getValue(T_COMMENTS, "count(*)");
                }
            }
            $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'user_activity',
                'data'    => $posts
            );
        }
    }

    if ($_POST['type'] == 'like') {
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $id = PT_Secure($_POST['id']);
            $post = $db->where('id',PT_Secure($_POST['id']))->getOne(T_ACTIVITES);
            if (!empty($post)) {

                $db->where('user_id', $pt->user->id);
                $db->where('activity_id', $id);
                $db->where('type', 1);
                $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_like > 0) {
                    $db->where('user_id', $pt->user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 1);
                    $delete = $db->delete(T_DIS_LIKES);
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'like_activity',
                        'liked'      => 0
                    );
                } 

                else {
                    $db->where('user_id', $pt->user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 2);
                    $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                    if ($check_for_dislike) {
                        $db->where('user_id', $pt->user->id);
                        $db->where('activity_id', $id);
                        $db->where('type', 2);
                        $delete = $db->delete(T_DIS_LIKES);
                    }

                    $insert_data = array(
                        'user_id' => $pt->user->id,
                        'activity_id' => $id,
                        'time' => time(),
                        'type' => 1
                    );
                    $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                    if ($insert) {
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'like_activity',
                            'liked'      => 1
                        );
                        if ($post->user_id != $pt->user->id) {
                            $type    = 'liked_ur_activity';
                            $notif_data = array(
                                'notifier_id' => $pt->user->id,
                                'recipient_id' => $post->user_id,
                                'type' => $type,
                                'url' => "post/".PT_URLSlug($post->text,$post->id),
                                'video_id' => $id,
                                'time' => time()
                            );
                            
                            pt_notify($notif_data);
                        }
                    }
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '4',
                        'error_text' => 'activity not found'
                    )
                );
            }
        }
    }


    if ($_POST['type'] == 'dislike') {
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $id = PT_Secure($_POST['id']);
            $post = $db->where('id',PT_Secure($_POST['id']))->getOne(T_ACTIVITES);
            if (!empty($post)) {

                $db->where('user_id', $pt->user->id);
                $db->where('activity_id', $id);
                $db->where('type', 2);
                $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_like > 0) {
                    $db->where('user_id', $pt->user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 2);
                    $delete = $db->delete(T_DIS_LIKES);
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'dislike_activity',
                        'disliked'      => 0
                    );
                } 
                else {
                    $db->where('user_id', $pt->user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 1);
                    $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                    if ($check_for_dislike) {
                        $db->where('user_id', $pt->user->id);
                        $db->where('activity_id', $id);
                        $db->where('type', 1);
                        $delete = $db->delete(T_DIS_LIKES);
                    }
                    $insert_data = array(
                        'user_id' => $pt->user->id,
                        'activity_id' => $id,
                        'time' => time(),
                        'type' => 2
                    );
                    $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                    if ($insert) {
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'dislike_activity',
                            'disliked'      => 1
                        );
                        if ($post->user_id != $pt->user->id) {
                            $type    = 'disliked_ur_activity';
                            $notif_data = array(
                                'notifier_id' => $pt->user->id,
                                'recipient_id' => $post->user_id,
                                'type' => $type,
                                'url' => "post/".PT_URLSlug($post->text,$post->id),
                                'video_id' => $id,
                                'time' => time()
                            );
                            
                            pt_notify($notif_data);
                        }
                    }
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '4',
                        'error_text' => 'activity not found'
                    )
                );
            }
        }
    }

    if ($_POST['type'] == 'create_comment') {

        if (!empty($_POST['activity_id'])) {
            $id = PT_Secure($_POST['activity_id']);
            $table = T_ACTIVITES;
            $col = 'activity_id';
        }
        if (!empty($id) && is_numeric($id) && $id > 0) {
            if (!empty($_POST['text'])) {
                $text = PT_Secure($_POST['text']);
                
                $data_info = $db->where('id', $id)->getOne($table);
                if (!empty($data_info)) {
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
                        $col => $id,
                        'text' => $text,
                        'time' => time()
                    );
                    $insert_comment = $db->insert(T_COMMENTS, $insert_data);
                    if ($insert_comment) {
                        if ($col == 'commented_ur_actvity') {
                            if ($data_info->user_id != $user->id) {
                                $notif_data = array(
                                    'notifier_id' => $pt->user->id,
                                    'recipient_id' => $data_info->user_id,
                                    'type' => 'commented_ur_actvity',
                                    'url' => "post/".PT_URLSlug($data_info->text,$data_info->id),
                                    'video_id' => $id,
                                    'time' => time()
                                );
                                
                                pt_notify($notif_data);
                            }
                        }
                        
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'add comment',
                            'message'    => 'Your comment successfully added.',
                            'id'      => $insert_comment
                        );
                    }
                }
                else{
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '4',
                            'error_text' => 'actvity not found'
                        )
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '3',
                        'error_text' => 'The text should not be empty'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '2',
                    'error_text' => 'Bad Request, Invalid or missing parameter'
                )
            );
        }
    }

    if ($_POST['type'] == 'edit_comment') {

        if (!empty($_POST['comment_id'])) {
            $id           = PT_Secure($_POST['comment_id']);
            $table = T_COMMENTS;
            $col = 'comment_id';
        }
        else{
            $id           = PT_Secure($_POST['reply_id']);
            $table = T_COMM_REPLIES;
            $col = 'reply_id';
        }

        if (!empty($id) && is_numeric($id) && $id > 0) {
            if (!empty($_POST['text'])) {
                $comment_data = $db->where('id', $id)->getOne($table);

                if (!empty($comment_data)) {
                    if($comment_data->user_id == $pt->user->id){
                        $text = PT_Secure($_POST['text']);
                        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
                        $i          = 0;
                        preg_match_all($link_regex, $text, $matches);
                        foreach ($matches[0] as $match) {
                            $match_url = strip_tags($match);
                            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
                            $text      = str_replace($match, $syntax, $text);
                        }
                        
                        $db->where('id',$id)->update($table,array('text' => $text));
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'edit_comment',
                            'message'      => 'Your comment successfully edited.'
                        );
                    }
                    else{
                        $response_data       = array(
                            'api_status'     => '400',
                            'api_version'    => $api_version,
                            'errors'         => array(
                                'error_id'   => '9',
                                'error_text' => 'You can not edit the comment you are not the comment owner'
                            )
                        );
                    }
                }
                else{
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '7',
                            'error_text' => 'The comment not found'
                        )
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '3',
                        'error_text' => 'The text should not be empty'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '11',
                    'error_text' => 'The comment_id or reply_id should be numeric'
                )
            );
        }
    }

    if ($_POST['type'] == 'delete_comment') {

        if (!empty($_POST['comment_id'])) {
            $id           = PT_Secure($_POST['comment_id']);
            $table = T_COMMENTS;
            $col = 'comment_id';
        }
        else{
            $id           = PT_Secure($_POST['reply_id']);
            $table = T_COMM_REPLIES;
            $col = 'reply_id';
        }
        if (!empty($id) && is_numeric($id) && $id > 0) {
            $comment_data = $db->where('id', $id)->getOne($table);
            $is_owner     = false;

            if (!empty($comment_data)) {

                $db->where('id',$comment_data->activity_id);
                $db->where('user_id',$pt->user->id);
                $video_owner = ($db->getValue(T_ACTIVITES,'count(*)') > 0);

                if ($video_owner === true) {
                    $is_owner = true;
                }

                else if($comment_data->user_id == $pt->user->id){
                    $is_owner = true;
                }

                if ($is_owner === true) {
                    $delete_comment = $db->where('id', $id)->delete($table);

                    if ($col == 'comment_id') {
                        $delete_comments_likes   = $db->where('comment_id', $id)->delete(T_COMMENTS_LIKES);
                        $comments_replies        = $db->where('comment_id', $id)->get(T_COMM_REPLIES);
                        $delete_comments_replies = $db->where('comment_id', $id)->delete(T_COMM_REPLIES);
                        foreach ($comments_replies as $reply) {
                            $db->where('reply_id', $reply->id)->delete(T_COMMENTS_LIKES);
                        }
                    }
                    else{
                        $db->where('reply_id', $id)->delete(T_COMMENTS_LIKES);
                    }
                    
                    if ($delete_comment) {
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'delete_comment',
                            'message'      => 'Your comment successfully deleted.'
                        );
                    }
                }
                else{
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '6',
                            'error_text' => 'You can not delete the comment you are not the comment owner'
                        )
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '7',
                        'error_text' => 'The comment not found'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '11',
                    'error_text' => 'The comment_id or reply_id should be numeric'
                )
            );
        }
    }

    if ($_POST['type'] == 'reply_comment') {
        if (!empty($_POST['activity_id'])) {
            $id = PT_Secure($_POST['activity_id']);
            $table = T_ACTIVITES;
            $col = 'activity_id';
        }

        if (!empty($id) && is_numeric($id) && $id > 0) {

            if (!empty($_POST['text']) && !empty($_POST['comment_id']) && is_numeric($_POST['comment_id']) && $_POST['comment_id'] > 0) {
                $comm_id           = PT_Secure($_POST['comment_id']);
                $text           = PT_Secure($_POST['text']);


                $reply_id = (!empty($_POST['reply']) && is_numeric($_POST['reply'])) ? $_POST['reply'] : 0;
                
                $comm_data  = $db->where('id', $comm_id)->getOne(T_COMMENTS);
                $id = $comm_data->activity_id;

                $data_info = $db->where('id', $id)->getOne($table);
                if (!empty($data_info) && !empty($comm_data)) {
                    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
                    $i          = 0;
                    preg_match_all($link_regex, $text, $matches);
                    foreach ($matches[0] as $match) {
                        $match_url = strip_tags($match);
                        $syntax    = '[a]' . urlencode($match_url) . '[/a]';
                        $text      = str_replace($match, $syntax, $text);
                    }

                    $insert_data     = array(
                        'user_id'    => $pt->user->id,
                        'comment_id' => $comm_id,
                        'text' => $text,
                        'time' => time()
                    );

                    $insert_reply = $db->insert(T_COMM_REPLIES, $insert_data);
                    if ($insert_reply) {

                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'reply_comment',
                            'message'      => 'Your reply successfully added.',
                            'reply_id'     => $insert_reply
                        );

                        if (!empty($reply_id)) {
                            $reply_data = $db->where('id',$reply_id)->getOne(T_COMM_REPLIES);
                            if (!empty($reply_data) && $reply_data->user_id != $pt->user->id) {
                                $type    = 'replied_2ur_comment';
                                $notif_data = array(
                                    'notifier_id' => $pt->user->id,
                                    'recipient_id' => $reply_data->user_id,
                                    'type' => $type,
                                    'time' => time()
                                );
                                $notif_data['url'] = "post/".PT_URLSlug($data_info->text,$data_info->id);
                                
                                pt_notify($notif_data);
                            }
                        }
                        else if($comm_data->user_id != $pt->user->id && empty($reply_id)){
                            $type    = 'replied_2ur_comment';
                            $notif_data = array(
                                'notifier_id' => $pt->user->id,
                                'recipient_id' => $comm_data->user_id,
                                'type' => $type,
                                'time' => time()
                            );
                            $notif_data['url'] = "post/".PT_URLSlug($data_info->text,$data_info->id);
                            
                            pt_notify($notif_data);
                        }
                    }
                }
                else{
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '10',
                            'error_text' => 'wrong video_id or post_id or comment_id'
                        )
                    );
                }

            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '8',
                        'error_text' => 'The text should not be empty and The comment_id should be numeric'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '2',
                    'error_text' => 'Bad Request, Invalid or missing parameter'
                )
            );
        }
    }


    if ($_POST['type'] == 'fetch_comments') {

        if (!empty($_POST['activity_id'])) {
            $id = PT_Secure($_POST['activity_id']);
            $table = T_ACTIVITES;
            $col = 'activity_id';
        }
        if (!empty($id) && is_numeric($id) && $id > 0) {

            $data_info = $db->where('id', $id)->getOne($table);
            if (!empty($data_info)) {


                $db->where($col, $data_info->id);
                $db->where('pinned', '1','<>');
                if ($offset > 0) {
                    $db->where('id', $offset,'<');
                }
                $db->orderBy('id', 'DESC');
                $get_comments = $db->get(T_COMMENTS,$limit);

                foreach ($get_comments as $key => $comment) {

                    $text = $comment->text;

                    $link_search = '/\[a\](.*?)\[\/a\]/i';
                    if (preg_match_all($link_search, $text, $matches)) {
                        foreach ($matches[1] as $match) {
                            $match_decode     = urldecode($match);
                            $match_decode_url = $match_decode;
                            $match_url = $match_decode;
                            if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                                $match_url = 'http://' . $match_url;
                            }
                            $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
                        }
                    }


                    $duration_search = '/\[d\](.*?)\[\/d\]/i';

                    if (preg_match_all($duration_search, $text, $matches)) {
                        foreach ($matches[1] as $match) {
                            $time = explode(":", $match);
                            $current_time = ($time[0]*60)+$time[1];
                            $text = str_replace('[d]' . $match . '[/d]', $match, $text);
                        }
                    }

                    $comment->text = $text;


                    $comment->is_liked_comment = 0;
                    $comment->is_comment_owner = false;      
                    $replies              = "";
                    $pt->pin              = false;
                    $comment->replies_count      = $db->where('comment_id', $comment->id)->getValue(T_COMM_REPLIES,'COUNT(*)');
                    $comment->comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES,10);
                    $comment->is_liked_comment     = 0;
                    $comment->is_disliked_comment  = 0;
                    $comment->comment_user_data    = PT_UserData($comment->user_id);
                    $comment->comment_user_data->is_subscribed_to_channel = $db->where('user_id', $comment->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
                    unset($comment->comment_user_data->password);

                    foreach ($comment->comment_replies as $reply) {
                        $reply->is_reply_owner = false;
                        $reply->reply_user_data    = PT_UserData($reply->user_id);
                        $reply->reply_user_data->is_subscribed_to_channel = $db->where('user_id', $reply->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
                        unset($reply->reply_user_data->password);
                        $reply->is_liked_reply     = 0;
                        $reply->is_disliked_reply  = 0;

                        $reply->is_reply_owner = false;
                        if ($reply->user_id == $user->id  || $comment->user_id == $user->id || $data_info->user_id == $user->id) {
                            $reply->is_reply_owner = true;
                        }

                        //Check is this reply  voted by logged-in user
                        $db->where('reply_id', $reply->id);
                        $db->where('user_id', $user->id);
                        $db->where('type', 1);
                        $reply->is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

                        $db->where('reply_id', $reply->id);
                        $db->where('user_id', $user->id);
                        $db->where('type', 2);
                        $reply->is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                        

                        //Get related to reply likes
                        $db->where('reply_id', $reply->id);
                        $db->where('type', 1);
                        $reply->reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

                        $db->where('reply_id', $reply->id);
                        $db->where('type', 2);
                        $reply->reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');


                        $text = $reply->text;

                        $link_search = '/\[a\](.*?)\[\/a\]/i';
                        if (preg_match_all($link_search, $text, $matches)) {
                            foreach ($matches[1] as $match) {
                                $match_decode     = urldecode($match);
                                $match_decode_url = $match_decode;
                                $match_url = $match_decode;
                                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                                    $match_url = 'http://' . $match_url;
                                }
                                $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
                            }
                        }


                        $duration_search = '/\[d\](.*?)\[\/d\]/i';

                        if (preg_match_all($duration_search, $text, $matches)) {
                            foreach ($matches[1] as $match) {
                                $time = explode(":", $match);
                                $current_time = ($time[0]*60)+$time[1];
                                $text = str_replace('[d]' . $match . '[/d]', $match, $text);
                            }
                        }

                        $reply->text = $text;
                        $reply->text_time = PT_Time_Elapsed_String($reply->time);


                    }
                   

                    //Check is comment voted by logged-in user
                    $db->where('comment_id', $comment->id);
                    $db->where('user_id', $user->id);
                    $db->where('type', 1);
                    $comment->is_liked_comment   = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

                    $db->where('comment_id', $comment->id);
                    $db->where('user_id', $user->id);
                    $db->where('type', 2);
                    $comment->is_disliked_comment = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

                    if ($user->id == $comment->user_id || $data_info->user_id == $user->id) {
                        $comment->is_comment_owner = true;
                    }
                    $comment->text_time = PT_Time_Elapsed_String($comment->time);
                }
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'fetch_comments',
                    'data'      => $get_comments
                );



            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '2',
                        'error_text' => 'Bad Request, Invalid or missing parameter'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => 'Bad Request, Invalid or missing parameter'
                )
            );
        }

    }

    if ($_POST['type'] == 'fetch_replies') {
        if (!empty($_POST['comment_id']) && is_numeric($_POST['comment_id']) && $_POST['comment_id'] > 0) {
            $id = PT_Secure($_POST['comment_id']);
            $comment_data = $db->where('id', $id)->getOne(T_COMMENTS);

            $data_info = $db->where('id', $comment_data->activity_id)->getOne(T_ACTIVITES);

            if (!empty($comment_data)) {
                $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
                $offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;

                if ($offset > 0) {
                    $comment_replies      = $db->where('comment_id', $comment_data->id)->where('id', $offset,'>')->get(T_COMM_REPLIES,$limit);
                }
                else{
                    $comment_replies      = $db->where('comment_id', $comment_data->id)->get(T_COMM_REPLIES,$limit);
                }

                

                foreach ($comment_replies as $reply) {
                    $reply->is_reply_owner = false;
                    $reply->reply_user_data    = PT_UserData($reply->user_id);
                    unset($reply->reply_user_data->password);
                    $reply->is_liked_reply     = 0;
                    $reply->is_disliked_reply  = 0;

                    $reply->is_reply_owner = false;
                    if ($reply->user_id == $pt->user->id  || $comment_data->user_id == $pt->user->id || $data_info->user_id == $pt->user->id) {
                        $reply->is_reply_owner = true;
                    }

                    //Check is this reply  voted by logged-in user
                    $db->where('reply_id', $reply->id);
                    $db->where('user_id', $pt->user->id);
                    $db->where('type', 1);
                    $reply->is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

                    $db->where('reply_id', $reply->id);
                    $db->where('user_id', $pt->user->id);
                    $db->where('type', 2);
                    $reply->is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                    

                    //Get related to reply likes
                    $db->where('reply_id', $reply->id);
                    $db->where('type', 1);
                    $reply->reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

                    $db->where('reply_id', $reply->id);
                    $db->where('type', 2);
                    $reply->reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');


                    $text = $reply->text;

                    $link_search = '/\[a\](.*?)\[\/a\]/i';
                    if (preg_match_all($link_search, $text, $matches)) {
                        foreach ($matches[1] as $match) {
                            $match_decode     = urldecode($match);
                            $match_decode_url = $match_decode;
                            $match_url = $match_decode;
                            if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                                $match_url = 'http://' . $match_url;
                            }
                            $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
                        }
                    }


                    $duration_search = '/\[d\](.*?)\[\/d\]/i';

                    if (preg_match_all($duration_search, $text, $matches)) {
                        foreach ($matches[1] as $match) {
                            $time = explode(":", $match);
                            $current_time = ($time[0]*60)+$time[1];
                            $text = str_replace('[d]' . $match . '[/d]', $match, $text);
                        }
                    }

                    $reply->text = $text;
                    $reply->text_time = PT_Time_Elapsed_String($reply->time);


                }
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'fetch_replies',
                    'data'      => $comment_replies
                );



            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '7',
                        'error_text' => 'The comment not found'
                    )
                );
            }

        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '12',
                    'error_text' => 'comment_id should be numeric'
                )
            );
        }
    }





    
}