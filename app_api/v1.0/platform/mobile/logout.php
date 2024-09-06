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


if (empty($_POST['user_id']) || empty($_POST['s'])) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}

else {

    $s       = PT_Secure($_POST['s']);
    $user_id = PT_Secure($_POST['user_id']);

    # Check if user  session exists in DB
    $db->where('user_id',$user_id)->where('session_id',$s);
    $__session__  = $db->getValue(T_SESSIONS,'count(`id`)');

    if (empty($__session__)) {
        $response_data       = array(
            'api_status'     => '400',
            'api_version'    => $api_version,
            'errors'         => array(
                'error_id'   => '2',
                'error_text' => 'Error 400 - Session does not exist'
            )
        );
    }
    
    else {

        # Delete user session from DB
        $db->where('user_id',$user_id)->where('session_id',$s)->delete(T_SESSIONS);

        $response_data       = array(
	        'api_status'     => '200',
	        'api_version'    => $api_version,
	        'data'           => array(
	            'message'    => 'Successfully logged out',
	        ) 
	    );
    }
}