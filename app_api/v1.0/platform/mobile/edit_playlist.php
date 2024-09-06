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
		$name      = (!empty($_POST['name'])) ? PT_ShortText(PT_Secure($_POST['name']), 30) : "";
	    $desc      = (!empty($_POST['desc'])) ? PT_ShortText(PT_Secure($_POST['desc']), 500) : "";
	    $privacy   = (isset($_POST['pr']) && is_numeric($_POST['pr']) && $_POST['pr'] > -1 && $_POST['pr'] < 2) ? PT_Secure($_POST['pr']) : 1;  

	    $update_data = array();
	    if (!empty($name)) {
	    	$update_data['name'] = $name;
	    }
	    if (!empty($desc)) {
	    	$update_data['description'] = $desc;
	    }
	    $update_data['privacy'] = $privacy;

	    $update           = $db->where('list_id',$list_data->list_id)->update(T_LISTS, $update_data);
	    $response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'edit_playlist',
		    'message'      => 'The playlist edited'
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