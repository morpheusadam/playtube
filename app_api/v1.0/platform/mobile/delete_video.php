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

else if (empty($_GET['id']) || !is_numeric($_GET['id'])){

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

	$id      = PT_Secure($_GET['id']);
	$video   = $db->where('id', $id)->getOne(T_VIDEOS,array('id','user_id','size'));

	$request = (!empty($video) && (PT_IsAdmin() || ($video->user_id == $user->id)));


	if ($request === true) {
		$delete   = PT_DeleteVideo($id);
		
		if (!empty($video->size)) {
			$size = $video->size;
			$db->update(T_USERS,array('uploads' => ($user->uploads - $size)));
		}

		else{
			$db->update(T_USERS,array('imports' => ($user->imports - 1)));
		}

		$response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'deleted',
		    'message'      => 'Your video was successfully deleted'
		);
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
