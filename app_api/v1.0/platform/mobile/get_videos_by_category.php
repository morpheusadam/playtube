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


if (empty($_GET['category_id']) || !in_array($_GET['category_id'], array_keys($categories))) {
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

	$response_data       = array(
        'api_status'     => '200',
        'api_version'    => $api_version,
        'data'           => array()
    );

	$offset = (!empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : null;
	$limit  = (!empty($_GET['limit'])  && is_numeric($_GET['limit']))  ? $_GET['limit']  : 20;
	$catid  = PT_Secure($_GET['category_id']);

	
	if (!empty($_GET['sub_id'])) {

         $is_found = $db->where('type',$catid)->where('lang_key',PT_Secure($_GET['sub_id']))->getValue(T_LANGS,'COUNT(*)');
        if ($is_found > 0) {

            $db->where('sub_category', PT_Secure($_GET['sub_id']));
        }
    }
    if (!empty($offset)) {
		$db->where('id', $offset, '<');
	}

	$videos = $db->where('category_id',$catid)->orderBy('id','DESC')->get(T_VIDEOS,$limit,array('video_id','user_id'));

	foreach ($videos as $video) {
		$video        = PT_GetVideoByID($video->video_id);
		$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
		$response_data['data'][] = $video;
	}
}



