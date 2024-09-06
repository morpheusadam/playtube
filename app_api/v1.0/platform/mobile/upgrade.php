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
	$sum          = intval($pt->config->pro_pkg_price);
	$update = array('is_pro' => 1,'verified' => 1);
    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
    if ($go_pro === true) {
    	$pkg_type             = 'pro';
    	$payment_data         = array(
    		'user_id' => $pt->user->id,
    		'type'    => $pkg_type,
    		'amount'  => $sum,
    		'date'    => date('n') . '/' . date('Y'),
    		'expire'  => strtotime("+30 days")
    	);

    	$db->insert(T_PAYMENTS,$payment_data);
    	$db->where('user_id',$pt->user->id)->update(T_VIDEOS,array('featured' => 1));
    	$response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'go_pro',
		    'message'    => 'Your profile successfully upgraded.'
		);

    }
}