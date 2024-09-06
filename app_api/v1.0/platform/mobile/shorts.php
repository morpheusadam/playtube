<?php
$types = array('get_user_shorts','get_shorts');
if (!IS_LOGGED) {
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
	if ($_POST['type'] == 'get_user_shorts') {
        if (!empty($_POST['profile_id']) && is_numeric($_POST['profile_id'])) {
            $response_data        = array(
                'api_status'      => '200',
                'api_version'     => $api_version,
                'data'            => array()
            );
            $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
            $offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;

            $profile_id = PT_Secure($_POST['profile_id']);
            if ($profile_id != $user->id) {
                $db->where('privacy', 0);
            }
            if (!empty($offset)) {
                $db->where('id', $offset, '<');
            }
            $videos = $db->where('user_id', $profile_id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy('id', 'DESC')->get(T_VIDEOS, $limit, 'video_id');
            if (!empty($videos)) {
                foreach ($videos as $key => $video) {
                    $video = PT_GetVideoByID($video->video_id,0,1);
                    if (!empty($video)) {
                        $video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
                        $response_data['data'][] = $video;
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
                    'error_text' => 'profile_id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'get_shorts') {
        $response_data        = array(
            'api_status'      => '200',
            'api_version'     => $api_version,
            'data'            => array()
        );
        $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
        if (!empty($_POST['offset'])) {
            $saved_ids = explode(',', $_POST['offset']);
            $db->where('id',$saved_ids,'NOT IN');
        }
        $response_data['order'] = 'views';
        $videos = $db->where('privacy', 0)->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->where('time',time() - (60 * 60),'>')->orderBy('views','DESC')->get(T_VIDEOS, $limit, 'video_id');
        if (empty($videos) || count($videos) < $limit) {
            $response_data['order'] = 'id';
            if (!empty($_POST['offset'])) {
                $saved_ids = explode(',', $_POST['offset']);
                $db->where('id',$saved_ids,'NOT IN');
            }
            $videos = $db->where('privacy', 0)->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy('id','DESC')->get(T_VIDEOS, $limit, 'video_id');
        }
        if (!empty($videos)) {
            foreach ($videos as $key => $video) {
                $video = PT_GetVideoByID($video->video_id,0,1);
                if (!empty($video)) {
                    if ($pt->config->history_system == 'on' && IS_LOGGED == true && $pt->user->pause_history == 0) {
                        $history = $db->where('video_id', $video->id)->where('user_id', $user->id)->getOne(T_HISTORY);
                        if (!empty($history)) {
                            $db->where('id', $history->id)->delete(T_HISTORY);
                        }

                        $insert_to_history = array(
                            'user_id' => $user->id,
                            'video_id' => $video->id,
                            'time' => time()
                        );
                        $insert_to_history_query = $db->insert(T_HISTORY, $insert_to_history);
                    }
                    $video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
                    $response_data['data'][] = $video;
                }
            }
        }
    }
}