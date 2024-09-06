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
else if ($pt->config->usr_v_mon != 'on') {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'Monetization not available at this time'
        )
	);
}
else{
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0 && !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {


		$error    = none;
	    $balance  = $pt->user->balance;
	    $user_id  = $pt->user->id;
	    $currency = $pt->config->payment_currency;

	    // Check is unprocessed requests exits
	    $db->where('user_id',$user_id);
	    $db->where('status',0);
	    $requests = $db->getValue(T_WITHDRAWAL_REQUESTS, 'count(*)');

	    if (empty($requests)) {
	    	if ($balance >= $_POST['amount']) {
	    		if ($_POST['amount'] >= 50) {
		    		$insert_data    = array(
			            'user_id'   => $user_id,
			            'amount'    => PT_Secure($_POST['amount']),
			            'email'     => PT_Secure($_POST['email']),
			            'requested' => time(),
			            'currency' => $currency,
			        );

			        $insert  = $db->insert(T_WITHDRAWAL_REQUESTS,$insert_data);
			        if (!empty($insert)) {
			        	$response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'monetization',
						    'message'    => 'Your withdrawal request has been successfully sent!'
						);
			        }
		    	}
		    	else{
		    		$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '4',
				            'error_text' => 'The minimum withdrawal request is 50: '.$currency
				        )
					);
		    	}
	    	}
	    	else{
	    		$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'The amount bigger than your balance'
			        )
				);
	    	}
	    }
	    else{
	    	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => 'You can not submit withdrawal request until the previous requests has been approved / rejected'
		        )
			);
	    }
	}
	else{
		$response_data       = array(
	        'api_status'     => '400',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '4',
	            'error_text' => 'Bad Request, Invalid or missing parameter'
	        )
	    );
	}
	




}