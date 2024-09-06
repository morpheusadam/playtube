<?php
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
else if ((empty($_POST['video_id']) || !is_numeric($_POST['video_id'])) || empty($_POST['time'])) {
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
	$video_id    = PT_Secure($_POST['video_id']);
	$time        = PT_Secure($_POST['time']);
	$video = PT_GetVideoByID($video_id,0,1,2);
	if (!empty($video)) {
		$info = $db->where('video_id',$video_id)->where('user_id',$user->id)->getOne(T_VIDEO_TIME);
		if (!empty($info)) {
			$db->where('video_id',$video_id)->where('user_id',$user->id)->update(T_VIDEO_TIME,array('time' => $time));
		}
		else{
			$db->insert(T_VIDEO_TIME, array('video_id' => $video_id,
		                                    'user_id' => $user->id,
		                                    'time' => $time));
		}
		$response_data     = array(
	        'api_status'   => '200',
	        'api_version'  => $api_version,
	        'success_type' => 'success',
	        'success'    => 'success'
	    );
	}
	else{
		$response_data       = array(
	        'api_status'     => '400',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '3',
	            'error_text' => 'video not found'
	        )
	    );
	}
		



}