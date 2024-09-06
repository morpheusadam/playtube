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

if (empty($_GET['keyword']) || mb_strlen($_GET['keyword']) < 4) {
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
	$date = "";
	if (isset($_POST['date']) && !empty($_POST['date']) && in_array($_POST['date'], array('last_hour','today','this_week','this_month','this_year'))) {
	    if ($_POST['date'] == 'last_hour') {
	        $time = time()-(60*60);
	        $date = " AND time >= ".$time." ";
	    }
	    elseif ($_POST['date'] == 'today') {
	        $time = time()-(60*60*24);
	        $date = " AND time >= ".$time." ";
	    }
	    elseif ($_POST['date'] == 'this_week') {
	        $time = time()-(60*60*24*7);
	        $date = " AND time >= ".$time." ";
	    }
	    elseif ($_POST['date'] == 'this_month') {
	        $time = time()-(60*60*24*30);
	        $date = " AND time >= ".$time." ";
	    }
	    elseif ($_POST['date'] == 'this_year') {
	        $time = time()-(60*60*24*365);
	        $date = " AND time >= ".$time." ";
	    }
	}

	$limit    = (!empty($_GET['limit'])  && is_numeric($_GET['limit']))  ? $_GET['limit']  : 10;
	$offset   = (!empty($_GET['offset']) && is_numeric($_GET['offset'])) ? $_GET['offset'] : null;
	$keyword  = PT_Secure($_GET['keyword']);
	$table    = T_VIDEOS;
	$xsql     = '';

	if (!empty($offset)) {
		$xsql = " AND `id` > '{$offset}' AND `id` <> '{$offset}' ";
	}

	//$sql      = "SELECT `video_id` FROM `$table` WHERE MATCH (`title`) AGAINST ('$keyword') {$xsql} ORDER BY id ASC LIMIT {$limit}";
	$sql      = "SELECT `video_id` FROM `$table` WHERE title LIKE '%$keyword%' AND privacy = 0 {$xsql} {$date} ORDER BY id ASC LIMIT {$limit}";
	$videos   = $db->rawQuery($sql);

	$response_data    = array(
        'api_status'  => '200',
        'api_version' => $api_version,
        'data'        => array()
    );

    foreach ($videos as $video) {
    	$video = PT_GetVideoByID($video->video_id);
		if (!empty($video)) {
			$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
			$response_data['data'][] = $video;
		}
    }
}