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
elseif (!empty($_POST['channel_id']) && is_numeric($_POST['channel_id']) && $_POST['channel_id'] > 0 && $_POST['channel_id'] != $pt->user->id) {
	$is_sub = $db->where('user_id',PT_Secure($_POST['channel_id']))->where('subscriber_id',$pt->user->id)->where('active',1)->getValue(T_SUBSCRIPTIONS,'COUNT(*)');
	if ($is_sub > 0) {
		$is_on = $db->where('user_id',PT_Secure($_POST['channel_id']))->where('subscriber_id',$pt->user->id)->where('notify',1)->getValue(T_SUBSCRIPTIONS,'COUNT(*)');
		if ($is_on > 0) {
			$db->where('user_id',PT_Secure($_POST['channel_id']))->where('subscriber_id',$pt->user->id)->update(T_SUBSCRIPTIONS,array('notify' => 0));
			$response_data = array(
	            'api_status' => '200',
	            'api_version' => $api_version,
	            'message' => 'off'
	        );
		}
		else{
			$db->where('user_id',PT_Secure($_POST['channel_id']))->where('subscriber_id',$pt->user->id)->update(T_SUBSCRIPTIONS,array('notify' => 1));
			$response_data = array(
	            'api_status' => '200',
	            'api_version' => $api_version,
	            'message' => 'on'
	        );
		}
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '3',
	            'error_text' => 'not subscribed'
	        )
		);
	}
}
else{
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'channel_id can not be empty'
        )
	);
}