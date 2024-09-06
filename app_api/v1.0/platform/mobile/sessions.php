<?php

$types = array('get','delete');
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
	if ($_POST['type'] == 'get') {
		$user_sessions = PT_GetUserSessions($user->id);
		$response_data = array(
	        'api_status' => '200',
	        'api_version' => $api_version,
	        'data' => $user_sessions
	    );
	}
	if ($_POST['type'] == 'delete') {
		if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
			$id = PT_Secure($_POST['id']);
			$delete_session = $db->where('user_id',$user->id)->where('id', $id)->delete(T_SESSIONS);
			$response_data = array(
		        'api_status' => '200',
		        'api_version' => $api_version,
		        'message' => 'session successfully deleted'
		    );
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '3',
		            'error_text' => 'id can not be empty'
		        )
		    );
		}
	}
}