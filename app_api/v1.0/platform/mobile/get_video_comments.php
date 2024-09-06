<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+
if (empty($_GET['video_id'])) {
    $response_data = array(
        'api_status' => '400',
        'api_version' => $api_version,
        'errors' => array(
            'error_id' => '1',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
} else {

    $video_id = PT_Secure($_GET['video_id']);
    $video    = $db->where('id', $video_id)->getOne(T_VIDEOS, array(
        'id',
        'video_id',
        'active'
    ));

    $offset   = ((!empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : null);
    $limit    = $pt->config->comments_default_num;

    if (empty($video)) {

        $response_data = array(
            'api_status' => '404',
            'api_version' => $api_version,
            'errors' => array(
                'error_id' => '2',
                'error_text' => 'Video does not exist'
            )
        );
    } 

    else {

        $response_data = array(
            'api_status' => '200',
            'total' => '0',
            'api_version' => $api_version,
            'data' => array()
        );

        if (!empty($offset)) {
            $db->where('id', $offset, '<');
        }

        $video_comments         = $db->where('video_id', $video->id)->orderBy('id', 'DESC')->get(T_COMMENTS, $limit);
        $response_data['total'] = $db->where('video_id', $video->id)->getValue(T_COMMENTS, 'count(*)');

        foreach ($video_comments as $key => $comment) {
            $is_comment_owner    = 0;
            $replies             = array();
            $comment_replies     = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES, 100);
            $is_liked_comment    = 0;
            $is_comment_disliked = 0;
            $comment_user_data   = PT_UserData($comment->user_id);
            unset($comment->post_id);
            unset($comment_user_data->password);

            foreach ($comment_replies as $reply) {
                $is_reply_owner    = 0;
                $reply_user_data   = PT_UserData($reply->user_id);
                unset($reply_user_data->password);
                $is_liked_reply    = 0;
                $is_disliked_reply = 0;

                if (IS_LOGGED == true) {
                    $is_reply_owner = $db->where('id', $reply->id)->where('user_id', $user->id)->getValue(T_COMM_REPLIES, 'count(*)');
                    if ($is_reply_owner) {
                        $is_reply_owner = 1;
                    }

                    #Check is this reply  voted by logged-in user
                    $db->where('reply_id', $reply->id);
                    $db->where('user_id', $user->id);
                    $db->where('type', 1);
                    $is_liked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                    $db->where('reply_id', $reply->id);
                    $db->where('user_id', $user->id);
                    $db->where('type', 2);
                    $is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                }

                #Get related to reply likes
                $db->where('reply_id', $reply->id);
                $db->where('type', 1);
                $reply_likes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
                $db->where('reply_id', $reply->id);
                $db->where('type', 2);
                $reply_dislikes     = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
                $reply->time        = PT_Time_Elapsed_String($reply->time);
                $reply->owner       = array_intersect_key(ToArray($reply_user_data), array_flip($user_public_data));
                $reply->comment_id  = $comment->id;
                $reply->likes       = $reply_likes;
                $reply->dislikes    = $reply_dislikes;
                $reply->is_liked    = $is_liked_reply;
                $reply->is_disliked = $is_disliked_reply;
                $reply->is_owner    = $is_reply_owner;
                $replies[]          = $reply;
            }

            if (IS_LOGGED == true) {
                #Check is comment voted by logged-in user
                $db->where('comment_id', $comment->id);
                $db->where('user_id', $user->id);
                $db->where('type', 1);
                $is_liked_comment = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                $db->where('comment_id', $comment->id);
                $db->where('user_id', $user->id);
                $db->where('type', 2);
                $is_comment_disliked = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
                if ($user->id == $comment->user_id) {
                    $is_comment_owner = 1;
                }
            }

            $comment_likes           = $db->where('comment_id', $comment->id)->where('type', 1)->getValue(T_COMMENTS_LIKES, 'count(*)');
            $comment_dislikes        = $db->where('comment_id', $comment->id)->where('type', 2)->getValue(T_COMMENTS_LIKES, 'count(*)');
            $comment->likes          = $comment_likes;
            $comment->dislikes       = $comment_dislikes;
            $comment->is_liked       = $is_liked_comment;
            $comment->is_owner       = $is_comment_owner;
            $comment->is_disliked    = $is_comment_disliked;
            $comment->video_id       = $video->id;
            $comment->time           = PT_Time_Elapsed_String($comment->time);
            $comment->owner          = array_intersect_key(ToArray($comment_user_data), array_flip($user_public_data));
            $comment->replies        = $replies;
            $response_data['data'][] = $comment;
        }
    }
}