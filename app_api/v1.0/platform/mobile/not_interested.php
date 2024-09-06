<?php
$types = array('add','delete','fetch');
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
    if ($_POST['type'] == 'add') {
        if (!empty($_POST['video_id']) && is_numeric($_POST['video_id'])) {
            $video_id = PT_Secure($_POST['video_id']);
            $video = PT_GetVideoByID($video_id,0,1,2);
            if (!empty($video)) {
                $info = $db->where('user_id',$pt->user->id)->where('video_id',$video->id)->getOne(T_NOT_INTERESTED);
                if (!empty($info)) {
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '7',
                            'error_text' => 'video already added'
                        )
                    );
                }
                else{
                    $db->insert(T_NOT_INTERESTED,array('user_id' => $pt->user->id,
                                                       'video_id' => $video->id,
                                                       'time' => time()));
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'success',
                        'message'    => 'video added to not interested list'
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '6',
                        'error_text' => 'video not found'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '5',
                    'error_text' => 'video_id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'delete') {
        if (!empty($_POST['video_id']) && is_numeric($_POST['video_id'])) {
            $video_id = PT_Secure($_POST['video_id']);
            $db->where('user_id',$pt->user->id)->where('video_id',$video_id)->delete(T_NOT_INTERESTED);
            $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'success',
                'message'    => 'video deleted from not interested list'
            );
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '5',
                    'error_text' => 'video_id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'fetch') {
        $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
        $offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;
        if (!empty($offset)) {
            $db->where('id',PT_Secure($offset),'<');
        }
        $list = $db->where('user_id',$pt->user->id)->orderBy('id','DESC')->get(T_NOT_INTERESTED,$limit);
        foreach ($list as $key => $value) {
            $video_data = PT_GetVideoByID($value->video_id,0,1,2);
            $video_data->owner            = array_intersect_key(
                ToArray($video_data->owner), 
                array_flip($user_public_data)
            );
            $list[$key]->video = $video_data;
        }
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'success',
            'data'    => $list
        );
    }
}