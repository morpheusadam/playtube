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

else{
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = PT_Secure($_POST['id']);
		$video = $db->where('id', $id)->where('user_id', $pt->user->id)->getOne(T_HISTORY);

		if (!empty($video)) {
			$db->where('id', $id)->delete(T_HISTORY);
			$response_data     = array(
			    'api_status'   => '200',
			    'api_version'  => $api_version,
			    'success_type' => 'delete_history',
			    'message'      => 'The video deleted from history'
			);
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
	else{
		$db->where('user_id', $pt->user->id)->delete(T_HISTORY);
		$response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'delete_history',
		    'message'      => 'your history is deleted'
		);
	}
}