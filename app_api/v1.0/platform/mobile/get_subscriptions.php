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

else if (!isset($_GET['channel']) || !in_array($_GET['channel'], array(0,1))){

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

	$table    = T_VIDEOS;
	$channel  = $_GET['channel'];
	$offset   = (!empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : null;
	$limit    = (!empty($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 5;

	if ($channel == 1) {
		if (!empty($offset)) {
			$db->where('user_id', $offset,'<');
		}

		$get      = $db->where('subscriber_id', $user->id)->orderBy('id','desc')->get(T_SUBSCRIPTIONS,$limit);

		$channels = array();
		foreach ($get as $key => $userdata) {
			$userdata = PT_UserData($userdata->user_id);	
			$userdata = array_intersect_key(ToArray($userdata), array_flip($user_public_data));

			if (!empty($userdata)) {
		    	$channels[] = $userdata;
			}		
		}

		$response_data    = array(
		    'api_status'  => '200',
		    'api_version' => $api_version,
		    'data'        => $channels
		);
	}

	else if ($channel == 0) {
		$channels      = $db->where('subscriber_id', $user->id)->orderBy('id','desc')->get(T_SUBSCRIPTIONS,null,array('user_id'));	
		$subscriptions = array();
		foreach ($channels as $channel) {
			$subscriptions[] = $channel->user_id;
		}

		$response_data    = array(
		    'api_status'  => '200',
		    'api_version' => $api_version,
		    'data'        => array()
		);	

		

		if (!empty($channels)) {
			if (!empty($offset)) {
				$db->where('id', $offset,'<');
			}

			$videos = $db->where('user_id',$subscriptions,'IN')->orderBy('id','desc')->get($table,$limit,array('video_id'));
			foreach ($videos as $video) {
				$video        = PT_GetVideoByID($video->video_id);
				//$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
				$video->owner = ToArray($video->owner);
				unset($video->owner['password']);
				$response_data['data'][] = $video;
			}
		}
	}
}
