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

else if (empty($_POST['id']) || !is_numeric($_POST['id'])){

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
	);
}
else if (empty($_POST['text'])){

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter (text)'
        )
	);
}

else{

	$id      = PT_Secure($_POST['id']);
	$user_id = $user->id;
	$video   = $db->where('id', $id)->getOne(T_VIDEOS,array('id','user_id','size'));
    $report  = $db->where('video_id', $id)->where('user_id', $user_id)->getValue(T_REPORTS, 'count(*)');
	$request = (!empty($video) && ($video->user_id != $user->id));


	if ($request === true) {
		if ($report > 0) {
			$db->where('video_id', $id);
			$db->where('user_id', $user_id);
			$db->delete(T_REPORTS);
			$response_data     = array(
			    'api_status'   => '200',
			    'api_version'  => $api_version,
			    'success_type' => 'report_deleted',
			    'message'      => 'The video report was deleted'
			);
		} else {
			$text    = PT_Secure($_POST['text']);
			$re_data = array(
				'user_id' => $user_id,
				'video_id' => $id,
				'type' => 'video',
				'time' => time(),
				'text' => $text,
			);
			if ($db->insert(T_REPORTS,$re_data)) {
				$response_data     = array(
				    'api_status'   => '200',
				    'api_version'  => $api_version,
				    'success_type' => 'report_added',
				    'message'      => 'The video was reported'
				);
			}
		}
	}

	else{
		$response_data       = array(
	        'api_status'     => '404',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '3',
	            'error_text' => 'Video does not exist'
	        )
	    );
	}
}
