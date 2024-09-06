<?php

$types = array('add','fetch');
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

	if ($_POST['type'] == 'add') {
		if (!empty($_POST['video_id']) && is_numeric($_POST['video_id']) && $_POST['video_id'] > 0) {
			$id        = PT_Secure($_POST['video_id']);
		    $table     = T_WLATER;

			if ($db->where('user_id', $user->id)->where('video_id', $id)->getValue($table, 'count(*)') > 0) {
	            $db->where('user_id', $user->id)->where('video_id', $id);
	            if($db->delete($table)){
	                $response_data     = array(
					    'api_status'   => '200',
					    'api_version'  => $api_version,
					    'success_type' => 'Removed from watch later',
					    'message'    => 'Removed from watch later',
					    'code'      => 0
					);
	            }
	        }
	        else{
	            $data_insert   = array(
	                'video_id' => $id,
	                'user_id'  => $user->id,
	                'time'     => time()
	            );

	            $insert = $db->insert($table,$data_insert);
	            if ($insert) {
	            	$response_data     = array(
					    'api_status'   => '200',
					    'api_version'  => $api_version,
					    'success_type' => 'Added to watch later',
					    'message'    => 'Added to watch later',
					    'code'      => 1
					);
	            }
	        }
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '3',
		            'error_text' => 'video_id can not be empty'
		        )
		    );
		}
	}

	if ($_POST['type'] == 'fetch') {
		$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
		$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;

		$response_data     = array(
	        'api_status'   => '200',
	        'api_version'  => $api_version,
	    );

		$db->where('user_id', $user->id);
		
		if ($offset) {
			#offset list by id
			$db->where('id',$offset,'>');
		}
		$play_list    = $db->get(T_WLATER, $limit);
		if (!empty($play_list)) {
			foreach ($play_list as $row) {
				$video = PT_GetVideoByID($row->video_id,0,0,2);
				if (!empty($video)) {
					$video->playlist_link = $pt->config->site_url.'/watch/'.PT_Slug($video->title, $video->video_id).'/list/wl'; 
					$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
					$row->video = $video;
				}
			}
		}
			

		$response_data['data']   = $play_list;
	}

}