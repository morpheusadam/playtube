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

else if ((empty($_POST['video_id']) || empty($_POST['list_uid']))) {

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

	if (!empty($_POST['video_id']) && !empty($_POST['list_uid'])) {
	    $user_id   = PT_Secure($user->id);
	    $id        = (is_numeric($_POST['video_id'])) ? PT_Secure($_POST['video_id']) : false;
	    $list      = PT_Secure($_POST['list_uid']);
	    $data      = array('status' => 400);
	    $request   = ($id && $list);
	    $table     = T_PLAYLISTS;

	    if ($request === true) {
	        $list_name = $db->where('list_id', $list)->getValue(T_LISTS,'name');
	        if (!empty($list_name)) {
	        	if ($db->where('user_id', $user_id)->where('list_id', $list)->where('video_id', $id)->getValue($table, 'count(*)') > 0) {
		            $db->where('user_id', $user_id)->where('list_id', $list)->where('video_id', $id);
		            if($db->delete($table)){
		                $response_data['status'] = 302;
		                $response_data['message']   = 'Deleted from list';
		            }
		        }
		        else{
		            $data_insert   = array(
		                'list_id'  => $list,
		                'video_id' => $id,
		                'user_id'  => $user_id
		            );

		            $insert = $db->insert($table,$data_insert);
		            if ($insert) {
		                $response_data['status'] = 200;
		                $response_data['message']   = 'Added to playlist';
		            }
		        }
	        }
	        else{
	        	$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '2',
			            'error_text' => 'Bad Request, Invalid or missing parameter'
			        )
			    );
	        }  
	    }
	}
}