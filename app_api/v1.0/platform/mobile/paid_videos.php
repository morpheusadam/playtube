<?php
$types = array('movies','videos','all');
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
else if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
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
	$videos_array = array();
	$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50 ? PT_Secure($_POST['limit']) : 20);
	if (!empty($_POST['tr_id']) && is_numeric($_POST['tr_id']) && $_POST['tr_id'] > 0) {
		$tr_id = PT_Secure($_POST['tr_id']);
		if (!empty($_POST['type']) && $_POST['type'] == 'movies' && $pt->config->movies_videos == 'on') {
		    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` >= '.$pt->user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` = 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id AND `id` < '.$tr_id.' ORDER BY id DESC LIMIT '.$limit);
		}
		elseif (!empty($_POST['type']) && $_POST['type'] == 'all') {
			$get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` >= '.$pt->user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id  AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id AND `id` < '.$tr_id.' ORDER BY id DESC LIMIT '.$limit);
		}
		else{
		    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` >= '.$pt->user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` != 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id AND `id` < '.$tr_id.' ORDER BY id DESC LIMIT '.$limit);
		}
	}
	else{
		if (!empty($_POST['type']) && $_POST['type'] == 'movies' && $pt->config->movies_videos == 'on') {
		    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` = '.$user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` = 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id ORDER BY id DESC LIMIT '.$limit);
		}
		elseif (!empty($_POST['type']) && $_POST['type'] == 'all') {
			$get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` = '.$user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id ORDER BY id DESC LIMIT '.$limit);
		}
		else{
		    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` = '.$user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` != 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id ORDER BY id DESC LIMIT '.$limit);
		}
	}
		
	$get_paid_videos = array();
	if (!empty($get)) {
	    foreach ($get as $key => $video_) {
	       $fetched_video = $db->where('id', $video_->video_id)->getOne(T_VIDEOS);
	       if (!empty($fetched_video)) {
	           $fetched_video->tr_id = $video_->id;
	           $get_paid_videos[] = $fetched_video;
	       }
	    }
	}
	if (!empty($get_paid_videos)) {
	    foreach ($get_paid_videos as $key => $video) {
	        $videos_array[] = PT_GetVideoByID($video, 0, 0, 0);
	    }
	}
	$response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'videos' => $videos_array
    );
}