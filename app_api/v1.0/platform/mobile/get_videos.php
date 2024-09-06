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

$table                = T_VIDEOS;
$response_data        = array(
    'api_status'      => '200',
    'api_version'     => $api_version,
    'data'            => array(
    	'featured'    => array(),
    	'top'         => array(),
    	'latest'      => array(),
    	'fav'      => array(),
    	'live'      => array(),
    )
);

$get_params           = array(
	'featured_offset' => null,
	'top_offset'      => null,
	'latest_offset'   => null,
	'fav_offset'   => null,
	'live_offset'   => null,
	'limit'           => null
);

foreach ($get_params as $key => $value) {
	if (!empty($_GET[$key]) && is_numeric($_GET[$key])) {
		$get_params[$key] = $_GET[$key];
	}	
}




# Home Page Featured Videos
if (!empty($get_params['featured_offset'])) {
	$db->where('id', $get_params['featured_offset'],'<');
}

$db->where('featured', '1')->orderBy('RAND()');
$featured = array();
$limit    = ((!empty($get_params['limit'])) ? $get_params['limit'] : 10);
$featured = $db->get($table,$limit,array('video_id','user_id'));

if (empty($featured)) {
	if (!empty($get_params['featured_offset'])) {
		$db->where('id', $get_params['featured_offset'],'<');
	}
    $featured = $db->orderBy('id', 'DESC')->get(T_VIDEOS,$limit,array('video_id','user_id'));
}


foreach ($featured as $video) {
	$video = PT_GetVideoByID($video->video_id);
	if (!empty($video)) {
		$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
		$response_data['data']['featured'][] = $video;
	}
}

#Home Page Top Videos
if (!empty($get_params['top_offset'])) {
	$db->where('id', $get_params['top_offset'],'<');
}

$limit = ((!empty($get_params['limit'])) ? $get_params['limit'] : 6);
$top   = $db->orderby('views', 'DESC')->get(T_VIDEOS, $limit,array('video_id','user_id'));

foreach ($top as $video) {
	$video = PT_GetVideoByID($video->video_id);
	if (!empty($video)) {
		$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
		$response_data['data']['top'][] = $video;
	}
}


#Home Page Latest Videos
if (!empty($get_params['latest_offset'])) {
	$db->where('id', $get_params['latest_offset'],'<');
}

$limit  = ((!empty($get_params['limit'])) ? $get_params['limit'] : 10);
$latest = $db->orderby('id', 'DESC')->get(T_VIDEOS, $limit,array('video_id','user_id'));

foreach ($latest as $video) {
	$video = PT_GetVideoByID($video->video_id);
	if (!empty($video)) {
		$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
		$response_data['data']['latest'][] = $video;
	}
}

if (IS_LOGGED && !empty($pt->user->fav_category)) {
	$limit  = ((!empty($get_params['limit'])) ? $get_params['limit'] : 10);
	if (!empty($get_params['fav_offset'])) {
		$db->where('id', $get_params['fav_offset'],'<');
	}
	$db->where("category_id",$pt->user->fav_category,"IN");
	$db->where('privacy', 0);
	$db->orderBy('id', 'DESC');
	$pt->cat_videos = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_VIDEOS, $limit);
	foreach ($pt->cat_videos as $key => $video) {
		$video = PT_GetVideoByID($video->video_id);
		if (!empty($video)) {
			$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
			$response_data['data']['fav'][] = $video;
		}
	}
}
if ($pt->config->live_video == 1) {
	if (!empty($get_params['live_offset'])) {
		$db->where('id', $get_params['live_offset'],'<');
	}
    $live_data = $db->where('privacy', 0)->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('live_time',0,'>')->orderBy('id', 'DESC')->get(T_VIDEOS, $limit);
    if (!empty($live_data)) {
    	foreach ($live_data as $key => $video) {
			$video = PT_GetVideoByID($video->video_id);
			if (!empty($video)) {
				$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
				$response_data['data']['live'][] = $video;
			}
		}
    }
}