<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if (!empty($_GET['first']) && !empty($_POST['id'])) {
    $id           = PT_Secure($_POST['id']);
    $comment_data = $db->where('id', $id)->getOne(T_COMMENTS);
    if (!empty($comment_data)) {
        if ($_GET['first'] == 'like' || $_GET['first'] == 'up') {

            $db->where('user_id', $user->id);
            $db->where('comment_id', $id);
            $db->where('type', 1);
            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

            if ($check_for_like > 0) {

                $db->where('user_id', $user->id);
                $db->where('comment_id', $id);
                $db->where('type', 1);

                $delete = $db->delete(T_COMMENTS_LIKES);
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_like'
                );

                $ud = array(
                    'likes' => ($comment_data->likes -=1 )
                );

                $db->where('id', $id)->update(T_COMMENTS,$ud);
            }

            else {

                $db->where('user_id', $user->id);
                $db->where('comment_id', $id);
                $db->where('type', 2);

                if ($db->getValue(T_COMMENTS_LIKES,'count(*)') > 0) {
                    $db->where('user_id', $user->id);
                    $db->where('comment_id', $id);
                    $db->where('type', 2);
                    $delete = $db->delete(T_COMMENTS_LIKES);

                    $ud = array(
                        'dis_likes' => ($comment_data->dis_likes -=1 )
                    );

                    $db->where('id', $id)->update(T_COMMENTS,$ud);
                }
                

                

                $insert_data = array(
                    'user_id' => $user->id,
                    'comment_id' => $id,
                    'time' => time(),
                    'type' => 1
                );

                $insert      = $db->insert(T_COMMENTS_LIKES, $insert_data);

                if ($insert) {
                    $data = array(
                        'status' => 200,
                        'type' => 'added_like'
                    );

                    #PHP trigger on insert likes
                    $ud = array(
                        'likes' => ($comment_data->likes += 1)
                    );

                    $db->where('id', $id)->update(T_COMMENTS,$ud);
                }
            }
        }

        elseif ($_GET['first'] == 'dislike' || $_GET['first'] == 'down') {
            $db->where('user_id', $user->id);
            $db->where('comment_id', $id);
            $db->where('type', 2);
            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

            if ($check_for_like > 0) {
                $db->where('user_id', $user->id);
                $db->where('comment_id', $id);
                $db->where('type', 2);
                $delete = $db->delete(T_COMMENTS_LIKES);
                $data   = array(
                    'status' => 200,
                    'type' => 'deleted_dislike',
                    'code' => 0,
                );

                #PHP trigger on delete dis likes
                $ud = array(
                    'dis_likes' => ($comment_data->dis_likes -= 1)
                );

                $db->where('id', $id)->update(T_COMMENTS,$ud);
            }

            else {
                
                $db->where('user_id', $user->id);
                $db->where('comment_id', $id);
                $db->where('type', 1);

                if ($db->getValue(T_COMMENTS_LIKES,'count(*)') > 0) {
                    $db->where('user_id', $user->id);
                    $db->where('comment_id', $id);
                    $db->where('type', 1);

                    $delete = $db->delete(T_COMMENTS_LIKES);

                    $ud = array(
                        'likes' => ($comment_data->likes -= 1)
                    );
                    $db->where('id', $id)->update(T_COMMENTS,$ud);
                }
                
                $insert_data = array(
                    'user_id' => $user->id,
                    'comment_id' => $id,
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

                    $ud = array(
                        'dis_likes' => ($comment_data->dis_likes += 1)
                    );

                    $db->where('id', $id)->update(T_COMMENTS,$ud);
                }
            }
        }

        if (in_array($data['type'], array('added_like','added_dislike'))) {
            if ($comment_data->user_id != $user->id) {
                $type       = ($data['type'] == 'added_like') ? 'liked_ur_comment' : 'disliked_ur_comment';   
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $comment_data->user_id,
                    'type' => $type,
                    'url' => ('@'.$pt->user->username),
                    'time' => time()
                );

                
                if (!empty($comment_data->video_id)) {
                    $video_data = $db->where('id',$comment_data->video_id)->getOne(T_VIDEOS);
                    $uniq_id           = $video_data->video_id;
                    $notif_data['url'] = "watch/$uniq_id&cl=$id";
                }
                
                else if(!empty($comment_data->post_id)){
                    $post_data = $db->where('id',$comment_data->post_id)->getOne(T_POSTS);
                    $uniq_id           = $post_data->id;
                    $notif_data['url'] = "articles/read/$uniq_id&cl=$id";
                }
                
                else if(!empty($comment_data->activity_id)){
                    $post_data = $db->where('id',$comment_data->activity_id)->getOne(T_ACTIVITES);
                    $uniq_id           = $post_data->id;
                    $notif_data['url'] = "post/".PT_URLSlug($post_data->text,$post_data->id);
                }

                pt_notify($notif_data);
            }
        }

        $comment_data  = $db->where('id', $id)->getOne(T_COMMENTS);
        $data['up']    = $comment_data->likes;
        $data ['down'] = $comment_data->dis_likes;
    }
}
?>