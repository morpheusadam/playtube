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

else if ((empty($_POST['video_id']) || !is_numeric($_POST['video_id'])) || empty($_POST['text'])) {

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
	$text        = PT_Secure($_POST['text']);
	$user_id     = $user->id;
	$request     = $db->where('id', $video_id)->getValue(T_VIDEOS, "count(*)");   

    if (!empty($request)) {
        $insert_data    = array(
            'user_id'   => $user_id ,
            'video_id'  => $video_id,
            'text'      => PT_ShortText($text,600),
            'time'      => time()
        );

        $insert_comment = $db->insert(T_COMMENTS, $insert_data);
        if ($insert_comment) {

        	$response_data    = array(
		        'api_status'  => '200',
		        'api_version' => $api_version
		    );
            
        }
        else{

        	$response_data       = array(
		        'api_status'     => '500',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '4',
		            'error_text' => 'Error: an unknown error occurred. Please try again later'
		        )
		    );
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