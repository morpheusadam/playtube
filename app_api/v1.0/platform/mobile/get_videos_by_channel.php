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

	$limit    = (!empty($_GET['limit'])  && is_numeric($_GET['limit']))  ? $_GET['limit']  : 10;
	$offset   = (!empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : null;
	$channel  = PT_Secure($_GET['channel_id']);
	$table    = T_VIDEOS;


	if (!empty($offset)) {
		$db->where('id',$offset,'<');
	}

	

	$response_data    = array(
        'api_status'  => '200',
        'api_version' => $api_version,
        'data'        => array()
    );

    $videos  = $db->where('user_id',$channel)->orderBy('id','DESC')->get($table,$limit,array('video_id','user_id'));

    foreach ($videos as $video) {
    	$video = PT_GetVideoByID($video->video_id);
		if (!empty($video)) {
			$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
			$response_data['data'][] = $video;
		}
    }
}