<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}
if (!empty($_GET['first']) && !empty($_POST['id'])) {
    $id                  = PT_Secure($_POST['id']);
    $video_data = $db->where('id', $id)->getOne(T_VIDEOS);
    if (!empty($video_data)) {
        if ($_GET['first'] == 'like') {
            $db->where('user_id', $user->id);
            $db->where('video_id', $id);
            $db->where('type', 1);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('video_id', $id);
                $db->where('type', 1);
                $delete = $db->delete(T_DIS_LIKES);
                RegisterPoint($id, "likes",'-');
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_like'
                );
            } 

            else {
                $db->where('user_id', $user->id);
                $db->where('video_id', $id);
                $db->where('type', 2);
                $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_dislike) {
                    $db->where('user_id', $user->id);
                    $db->where('video_id', $id);
                    $db->where('type', 2);
                    $delete = $db->delete(T_DIS_LIKES);
                }

                $insert_data = array(
                    'user_id' => $user->id,
                    'video_id' => $id,
                    'time' => time(),
                    'type' => 1
                );

                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    RegisterPoint($id, "likes");
                    $data = array(
                        'status' => 200,
                        'type' => 'added_like'
                    );
                }
            }
        } 

        else if ($_GET['first'] == 'dislike') {
            $db->where('user_id', $user->id);
            $db->where('video_id', $id);
            $db->where('type', 2);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('video_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_DIS_LIKES);
                RegisterPoint($id, "dislikes",'-');
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_dislike'
                );
            } 

            else {
                $db->where('user_id', $user->id);
                $db->where('video_id', $id);
                $db->where('type', 1);
                $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_dislike) {
                    $db->where('user_id', $user->id);
                    $db->where('video_id', $id);
                    $db->where('type', 1);
                    $delete = $db->delete(T_DIS_LIKES);
                }
                $insert_data = array(
                    'user_id' => $user->id,
                    'video_id' => $id,
                    'time' => time(),
                    'type' => 2
                );
                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    RegisterPoint($id, "dislikes");
                    $data = array(
                        'status' => 200,
                        'type' => 'added_dislike'
                    );

                }
            }
        }

        #Send notification to video owner
        if (in_array($data['type'], array('added_dislike','added_like'))) {
            if ($video_data->user_id != $user->id) {
                $type    = ($data['type'] == 'added_dislike') ? 'disliked_ur_video' : 'liked_ur_video';
                $uniq_id = $video_data->video_id;
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $video_data->user_id,
                    'type' => $type,
                    'url' => "watch/$uniq_id",
                    'video_id' => $id,
                    'time' => time()
                );
                
                pt_notify($notif_data);
            }
        }
    }
}


if (!empty($_GET['first']) && !empty($_POST['post_id'])) {
    $id                  = PT_Secure($_POST['post_id']);
    $is_this_valid_post  = $db->where('id', $id)->getValue(T_POSTS, 'count(*)');

    if ($is_this_valid_post > 0) {
        if ($_GET['first'] == 'up') {
            $db->where('user_id', $user->id);
            $db->where('post_id', $id);
            $db->where('type', 1);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('post_id', $id);
                $db->where('type', 1);
                $delete = $db->delete(T_DIS_LIKES);
                $data   = array(
                    'status' => 200,
                    'code' => 0
                );
            } 

            else {
            	$db->where('user_id', $user->id);
	            $db->where('post_id', $id);
	            $db->where('type', 2);
	            $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
            	if ($check_for_dislike) {
            		$db->where('user_id', $user->id);
                    $db->where('post_id', $id);
                    $db->where('type', 2);
                    $delete = $db->delete(T_DIS_LIKES);
            	}

                $insert_data = array(
                    'user_id' => $user->id,
                    'post_id' => $id,
                    'time' => time(),
                    'type' => 1
                );
                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'code' => 1
                    );
                }
            }
        } 

        else if ($_GET['first'] == 'down') {
        	$db->where('user_id', $user->id);
            $db->where('post_id', $id);
            $db->where('type', 2);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('post_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_DIS_LIKES);
                $data   = array(
                    'status' => 200,
                    'code' => 0
                );
            } 

            else {
            	$db->where('user_id', $user->id);
	            $db->where('post_id', $id);
	            $db->where('type', 1);
	            $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
            	if ($check_for_dislike) {
            		$db->where('user_id', $user->id);
                    $db->where('post_id', $id);
                    $db->where('type', 1);
                    $delete = $db->delete(T_DIS_LIKES);
            	}
                $insert_data = array(
                    'user_id' => $user->id,
                    'post_id' => $id,
                    'time' => time(),
                    'type' => 2
                );
                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'code' => 1
                    );
                }
            }
        }

        $data['up']    = $db->where('post_id', $id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
        $data ['down'] = $db->where('post_id', $id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
    }
}


if (!empty($_GET['first']) && !empty($_POST['activity_id'])) {
    $id                  = PT_Secure($_POST['activity_id']);
    $post = $db->where('id', $id)->getOne(T_ACTIVITES);
    $is_this_valid_post  = $db->where('id', $id)->getValue(T_ACTIVITES, 'count(*)');

    if ($is_this_valid_post > 0) {
        if ($_GET['first'] == 'up') {
            $db->where('user_id', $user->id);
            $db->where('activity_id', $id);
            $db->where('type', 1);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('activity_id', $id);
                $db->where('type', 1);
                $delete = $db->delete(T_DIS_LIKES);
                $data   = array(
                    'status' => 200,
                    'code' => 0
                );
            } 

            else {
                $db->where('user_id', $user->id);
                $db->where('activity_id', $id);
                $db->where('type', 2);
                $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_dislike) {
                    $db->where('user_id', $user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 2);
                    $delete = $db->delete(T_DIS_LIKES);
                }

                $insert_data = array(
                    'user_id' => $user->id,
                    'activity_id' => $id,
                    'time' => time(),
                    'type' => 1
                );
                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'code' => 1,
                        'type' => 'added_like'
                    );
                }
            }
        } 

        else if ($_GET['first'] == 'down') {
            $db->where('user_id', $user->id);
            $db->where('activity_id', $id);
            $db->where('type', 2);
            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('activity_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_DIS_LIKES);
                $data   = array(
                    'status' => 200,
                    'code' => 0
                );
            } 
            else {
                $db->where('user_id', $user->id);
                $db->where('activity_id', $id);
                $db->where('type', 1);
                $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
                if ($check_for_dislike) {
                    $db->where('user_id', $user->id);
                    $db->where('activity_id', $id);
                    $db->where('type', 1);
                    $delete = $db->delete(T_DIS_LIKES);
                }
                $insert_data = array(
                    'user_id' => $user->id,
                    'activity_id' => $id,
                    'time' => time(),
                    'type' => 2
                );
                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'code' => 1,
                        'type' => 'added_dislike'
                    );
                }
            }
        }

        $data['up']    = $db->where('activity_id', $id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
        $data ['down'] = $db->where('activity_id', $id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");

        if (in_array($data['type'], array('added_dislike','added_like'))) {
            if ($post->user_id != $user->id) {
                $type    = ($data['type'] == 'added_dislike') ? 'disliked_ur_activity' : 'liked_ur_activity';
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
?>