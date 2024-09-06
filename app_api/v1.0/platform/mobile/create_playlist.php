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

else if (empty($_POST['name'])) {

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

	$user_id     = $user->id;
	$name      = (!empty($_POST['name'])) ? PT_ShortText(PT_Secure($_POST['name']), 30) : "";
    $desc      = (!empty($_POST['desc'])) ? PT_ShortText(PT_Secure($_POST['desc']), 500) : "";
    $privacy   = (isset($_POST['pr']) && is_numeric($_POST['pr']) && $_POST['pr'] > -1 && $_POST['pr'] < 2) ? PT_Secure($_POST['pr']) : 1;  

    if (!empty($name)) {
    	 $uid = PT_GenerateKey(15, 15);
         $data_insert      = array(
            'list_id'     => $uid,
            'user_id'     => $user_id,
            'name'        => $name,
            'description' => $desc,
            'privacy'     => $privacy,
            'time'        => time()
        );
        $insert           = $db->insert(T_LISTS, $data_insert);
        if ($insert) {

        	$response_data    = array(
		        'api_status'  => '200',
		        'api_version' => $api_version,
		        'playlist_id' => $insert,
		        'playlist_uid' => $uid
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
}