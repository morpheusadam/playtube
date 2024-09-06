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

$table            = T_VIDEOS;
$response_data    = array(
    'api_status'  => '200',
    'api_version' => $api_version,
    'data'        => array()
);

$get_params  = array(
	'offset' => null,
	'limit'  => null
);

foreach ($get_params as $key => $value) {
	if (!empty($_GET[$key]) && is_numeric($_GET[$key])) {
		$get_params[$key] = $_GET[$key];
	}	
}

# Home Page Trending Videos
if (!empty($get_params['offset'])) {
	$db->where('id', $get_params['offset'], '>');
}

$limit    = ((!empty($get_params['limit'])) ? $get_params['limit'] : 4);
$db->where('time', time() - 172800, '>');
$trending = $db->orderBy('views', 'DESC')->get($table,$limit,array('video_id','user_id'));

foreach ($trending as $video) {
	$video        = PT_GetVideoByID($video->video_id);
	$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
	$response_data['data'][] = $video;
}
