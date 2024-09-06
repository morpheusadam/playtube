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


  

if (empty($_GET['channel_id']) || !is_numeric($_GET['channel_id'])) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}

else{

	$channel_id   = PT_Secure($_GET['channel_id']);
	$channel_info = PT_UserData($channel_id);

	if (empty($channel_info)) {
		$response_data       = array(
	        'api_status'     => '404',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '2',
	            'error_text' => 'Channel does not exist'
	        )
	    );
	}

	else{
		//$channel_info      = array_intersect_key(ToArray($channel_info), array_flip($user_public_data));
		$channel_info      = ToArray($channel_info);
		unset($channel_info['password']);
		unset($channel_info['email_code']);

		if (empty($channel_info['about'])) {
			$channel_info['about'] = "";
		}
		if ($channel_id == $user->id) {
			$channel_info['details'] = [];
			$channel_info['details']['videos_count'] = $db->where('user_id', $channel_id)->getValue(T_VIDEOS, "count(*)");
			$channel_info['details']['subscribers_count'] = $db->where('user_id', $channel_id)->getValue(T_SUBSCRIPTIONS, "count(*)");
			$channel_info['details']['playlists_count'] = $db->where('user_id', $channel_id)->getValue(T_LISTS, "count(*)");
			$channel_info['details']['activities_count'] = $db->where('user_id', $channel_id)->getValue(T_ACTIVITES, "count(*)");
		}

		$channel_info['is_subscribed_to_channel'] = $db->where('user_id', $channel_id)->where('subscriber_id', $user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
		
		$response_data     = array(
	        'api_status'   => '200',
	        'api_version'  => $api_version,
	        'data'         => $channel_info
	    );
	}
}