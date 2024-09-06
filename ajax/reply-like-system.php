<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if (!empty($_GET['first']) && !empty($_POST['id'])) {
    $id                    = PT_Secure($_POST['id']);
    $reply_data = $db->where('id', $id)->getOne(T_COMM_REPLIES);
    if (!empty($reply_data)) {
        if ($_GET['first'] == 'like' || $_GET['first'] == 'up') {
            $db->where('user_id', $user->id);
            $db->where('reply_id', $id);
            $db->where('type', 1);
            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('reply_id', $id);
                $db->where('type', 1);
                $delete = $db->delete(T_COMMENTS_LIKES);
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_like'
                );
            }

            else {

                $db->where('user_id', $user->id);
                $db->where('reply_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_COMMENTS_LIKES);

                $insert_data = array(
                    'user_id' => $user->id,
                    'reply_id' => $id,
                    'time' => time(),
                    'type' => 1
                );

                $insert      = $db->insert(T_COMMENTS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'type' => 'added_like'
                    );
                }
            }
        }

        elseif ($_GET['first'] == 'dislike' || $_GET['first'] == 'down') {
            $db->where('user_id', $user->id);
            $db->where('reply_id', $id);
            $db->where('type', 2);
            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('reply_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_COMMENTS_LIKES);
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_dislike',
                    'code' => 0,
                );
            }

            else {
                
                $db->where('user_id', $user->id);
                $db->where('reply_id', $id);
                $db->where('type', 1);
                $delete = $db->delete(T_COMMENTS_LIKES);

                $insert_data = array(
                    'user_id' => $user->id,
                    'reply_id' => $id,
                    'time' => time(),
                    'type' => 2
                );

                $insert      = $db->insert(T_COMMENTS_LIKES, $insert_data);
                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'type' => 'added_dislike',
                        'code' => 1
                    );
                }
            }
        }

        if (in_array($data['type'], array('added_like','added_dislike'))) {
            if ($reply_data->user_id != $user->id) {
                $type       = ($data['type'] == 'added_like') ? 'liked_ur_comment' : 'disliked_ur_comment';
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $reply_data->user_id,
                    'type' => $type,
                    'url' => ('@'.$pt->user->username),
                    'time' => time()
                );

                if (!empty($reply_data->video_id)) {
                    $video_data = $db->where('id',$reply_data->video_id)->getOne(T_VIDEOS);
                    $uniq_id           = $video_data->video_id;
                    $notif_data['url'] = "watch/$uniq_id&rl=$id";
                }
                
                else if(!empty($reply_data->post_id)){
                    $post_data = $db->where('id',$reply_data->post_id)->getOne(T_POSTS);
                    $uniq_id           = $post_data->id;
                    $notif_data['url'] = "articles/read/$uniq_id&rl=$id";
                }

                pt_notify($notif_data);
            }
        }

        $db->where('reply_id', $id);
        $db->where('type', 1);
        $data['up']    = $db->getValue(T_COMMENTS_LIKES, "count(*)");

        $db->where('reply_id', $id);
        $db->where('type', 2);
        $data ['down'] = $db->getValue(T_COMMENTS_LIKES, "count(*)");
    }
}
?>