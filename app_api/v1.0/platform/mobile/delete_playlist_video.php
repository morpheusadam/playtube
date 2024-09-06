<?php

if (!IS_LOGGED) {
    $response_data = array(
        'api_status' => '400',
        'api_version' => $api_version,
        'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
    );
}
else if (empty($_POST['video_id']) || !is_numeric($_POST['video_id']) || $_POST['video_id'] < 1 || empty($_POST['list_id'])){

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
	);

}
else{
	$list_id  = PT_Secure($_POST['list_id']);
    $video_id = PT_Secure($_POST['video_id']);
    $db->where('list_id',$list_id)->where('user_id',$pt->user->id)->where('video_id',$video_id)->delete(T_PLAYLISTS);
    $response_data = array(
        'api_status' => '200',
        'api_version' => $api_version,
        'message' => 'the video is deleted'
    );
}