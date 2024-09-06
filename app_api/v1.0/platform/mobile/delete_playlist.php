<?php 

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

else if (empty($_POST['list_id'])) {

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

	$user_id   = $user->id;
	$list_id   = PT_Secure($_POST['list_id']);

	if (!empty($_POST['list_id']) && is_numeric($_POST['list_id']) && $_POST['list_id'] > 0) {
		$list_data = $db->where('id',$list_id)->where('user_id',$user_id)->getOne(T_LISTS);
	}
	else{
		$list_data = $db->where('list_id',$list_id)->where('user_id',$user_id)->getOne(T_LISTS);
	}

	if (!empty($list_data)) {
        $db->where('id',$list_data->id)->where('user_id',$user_id)->delete(T_LISTS);
        $db->where('list_id',$list_data->list_id)->where('user_id',$user_id)->delete(T_PLAYLISTS);
        $response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'delete_playlist',
		    'message'      => 'The playlist deleted'
		);
    }
    else{
    	$response_data       = array(
	        'api_status'     => '400',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '3',
	            'error_text' => 'Playlist not found'
	        )
	    );
    }
}