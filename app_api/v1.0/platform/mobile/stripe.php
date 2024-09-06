<?php
$paypal_currency = $pt->config->paypal_currency;
$requests = array('pro','buy_video','rent_video','wallet','subscribe');
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
elseif (empty($_POST['type']) || (!empty($_POST['type']) && !in_array($_POST['type'], $requests))) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '4',
            'error_text' => 'type can not be empty'
        )
	);
}
elseif (empty($_POST['token'])) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '5',
            'error_text' => 'token can not be empty'
        )
	);
}
else{
	if ($_POST['type'] == 'buy_video' || $_POST['type'] == 'rent_video') {
		if (empty($_POST['video_id'])) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '6',
		            'error_text' => 'video_id can not be empty'
		        )
			);
			echo json_encode($response_data, JSON_PRETTY_PRINT);
		    exit();
		}
		else{
			$video_id = PT_Secure($_POST['video_id']);
			$video = PT_GetVideoByID($video_id, 0,0,2);
			if (empty($video)) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '7',
			            'error_text' => 'video not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
			    exit();
			}
			elseif ($_POST['type'] == 'buy_video' && empty($video->sell_video)) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '8',
			            'error_text' => 'video price not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
			    exit();
			}
			elseif ($_POST['type'] == 'rent_video' && empty($video->rent_price)) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '8',
			            'error_text' => 'video price not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
			    exit();
			}
		}
		
	}
	elseif ($_POST['type'] == 'wallet') {
		if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] < 1) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'amount can not be empty'
		        )
			);
			echo json_encode($response_data, JSON_PRETTY_PRINT);
			exit();
		}
	}
	elseif ($_POST['type'] == 'subscribe') {
		if (empty($_POST['subscribe_id']) || !is_numeric($_POST['subscribe_id']) || $_POST['subscribe_id'] < 1) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'subscribe_id can not be empty'
		        )
			);
			echo json_encode($response_data, JSON_PRETTY_PRINT);
			exit();
		}
		else{
			$user_id       = PT_Secure($_POST['subscribe_id']);
			$user = PT_UserData($user_id);
	    	if (empty($user) || empty($user->subscriber_price)) {
	    		$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'user not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
				exit();

	    	}
		}
	}
	require_once('assets/libs/stripe/vendor/autoload.php');
	$stripe = array(
	  "secret_key"      =>  $pt->config->stripe_secret,
	  "publishable_key" =>  $pt->config->stripe_id
	);

	\Stripe\Stripe::setApiKey($stripe['secret_key']);


    $token = $_POST['token'];
    try {
        $customer = \Stripe\Customer::create(array(
            'source' => $token
        ));

        $final_amount = $amount = intval($pt->config->pro_pkg_price);
        if ($_POST['type'] == 'rent_video' && !empty($video->rent_price)) {
			$final_amount = $amount = $video->rent_price;
		}
		else{
			$final_amount = $amount = $video->sell_video;
		}
		if ($_POST['type'] == 'wallet') {
			$final_amount = $amount = PT_Secure($_POST['amount']);
		}
		if ($_POST['type'] == 'subscribe') {
			$final_amount = $amount = $user->subscriber_price;
		}
        $final_amount = $final_amount*100;
        $charge   = \Stripe\Charge::create(array(
            'customer' => $customer->id,
            'amount' => $final_amount,
            'currency' => $pt->config->stripe_currency
        ));

        if ($charge) {
        	if ($_POST['type'] == 'pro') {
        		$update = array('is_pro' => 1,'verified' => 1);
			    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
			    if ($go_pro === true) {
			    	$payment_data         = array(
			    		'user_id' => $pt->user->id,
			    		'type'    => 'pro',
			    		'amount'  => $amount,
			    		'date'    => date('n') . '/' . date('Y'),
			    		'expire'  => strtotime("+30 days")
			    	);

			    	$db->insert(T_PAYMENTS,$payment_data);
			    	$db->where('user_id',$pt->user->id)->update(T_VIDEOS,array('featured' => 1));
			    	$response_data     = array(
				        'api_status'   => '200',
				        'api_version'  => $api_version,
				        'message' => 'paid successful'
				    );
			    }
        	}
        	elseif ($_POST['type'] == 'buy_video' || $_POST['type'] == 'rent_video') {
        		$notify_sent = false;
	    		if (!empty($video->is_movie)) {

	    			$payment_data         = array(
			    		'user_id' => $video->user_id,
			    		'video_id'    => $video->id,
			    		'paid_id'  => $pt->user->id,
			    		'admin_com'    => 0,
			    		'currency'    => $pt->config->stripe_currency,
			    		'time'  => time()
			    	);
			    	if (!empty($_POST['type']) && $_POST['type'] == 'rent_video') {
		    			$payment_data['type'] = 'rent';
		    			$total = $video->rent_price;
		    		}
		    		else{
		    			$total = $video->sell_video;
		    		}
			    	
		    		$payment_data['amount'] = $total;
		    		$db->insert(T_VIDEOS_TRSNS,$payment_data);
	    		}
	    		else{
	    			$payment_currency = $pt->config->stripe_currency;


	    			if (!empty($_POST['type']) && $_POST['type'] == 'rent_video') {
		    			$admin__com = $pt->config->admin_com_rent_videos;
			    		if ($pt->config->com_type == 1) {
			    			$admin__com = ($pt->config->admin_com_rent_videos * $video->rent_price)/100;
			    			$payment_currency = $pt->config->stripe_currency.'_PERCENT';
			    		}
			    		$payment_data         = array(
				    		'user_id' => $video->user_id,
				    		'video_id'    => $video->id,
				    		'paid_id'  => $pt->user->id,
				    		'amount'    => $video->rent_price,
				    		'admin_com'    => $pt->config->admin_com_rent_videos,
				    		'currency'    => $payment_currency,
				    		'time'  => time(),
				    		'type' => 'rent'
				    	);
				    	$balance = $video->rent_price - $admin__com;
		    		}
		    		else{
		    			$admin__com = $pt->config->admin_com_sell_videos;
			    		if ($pt->config->com_type == 1) {
			    			$admin__com = ($pt->config->admin_com_sell_videos * $video->sell_video)/100;
			    			$payment_currency = $pt->config->stripe_currency.'_PERCENT';
			    		}

			    		$payment_data         = array(
				    		'user_id' => $video->user_id,
				    		'video_id'    => $video->id,
				    		'paid_id'  => $pt->user->id,
				    		'amount'    => $video->sell_video,
				    		'admin_com'    => $pt->config->admin_com_sell_videos,
				    		'currency'    => $payment_currency,
				    		'time'  => time()
				    	);
				    	$balance = $video->sell_video - $admin__com;

		    		}



	    		
		    		// $admin__com = $pt->config->admin_com_sell_videos;
		    		
		    		// if ($pt->config->com_type == 1) {
		    		// 	$admin__com = ($pt->config->admin_com_sell_videos * $video->sell_video)/100;
		    		// 	$payment_currency = $pt->config->stripe_currency.'_PERCENT';
		    		// }
		    		// $payment_data         = array(
			    	// 	'user_id' => $video->user_id,
			    	// 	'video_id'    => $video->id,
			    	// 	'paid_id'  => $pt->user->id,
			    	// 	'amount'    => $video->sell_video,
			    	// 	'admin_com'    => $pt->config->admin_com_sell_videos,
			    	// 	'currency'    => $payment_currency,
			    	// 	'time'  => time()
			    	// );
			    	$db->insert(T_VIDEOS_TRSNS,$payment_data);
			    	//$balance = $video->sell_video - $admin__com;
			    	$db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' , `verified` = 1 WHERE `id` = '".$video->user_id."'");
			    }
			    if ($notify_sent == false) {
			    	$uniq_id = $video->video_id;
	                $notif_data = array(
	                    'notifier_id' => $pt->user->id,
	                    'recipient_id' => $video->user_id,
	                    'type' => 'paid_to_see',
	                    'url' => "watch/$uniq_id",
	                    'video_id' => $video->id,
	                    'time' => time()
	                );
	                
	                pt_notify($notif_data);
			    }
			    $response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'message' => 'paid successful'
			    );
        	}
        	elseif ($_POST['type'] == 'wallet') {
        		$amount = PT_Secure($_POST['amount'] / 100);
				$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
				$payment_data         = array(
		            'user_id' => $pt->user->id,
		            'paid_id'  => $pt->user->id,
		            'admin_com'    => 0,
		            'currency'    => $pt->config->payment_currency,
		            'time'  => time(),
		            'amount' => $amount,
		            'type' => 'ad'
		        );
		        $db->insert(T_VIDEOS_TRSNS,$payment_data);
		        $response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'message' => 'paid successful'
			    );
			    $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
        	}
        	elseif ($_POST['type'] == 'subscribe') {
        		$admin__com = ($pt->config->admin_com_subscribers * $user->subscriber_price)/100;
	    		$paypal_currency = $paypal_currency.'_PERCENT';
	    		$payment_data         = array(
		    		'user_id' => $user_id,
		    		'video_id'    => 0,
		    		'paid_id'  => $pt->user->id,
		    		'amount'    => $user->subscriber_price,
		    		'admin_com'    => $pt->config->admin_com_subscribers,
		    		'currency'    => $paypal_currency,
		    		'time'  => time(),
		    		'type' => 'subscribe'
		    	);
		    	$db->insert(T_VIDEOS_TRSNS,$payment_data);
		    	$balance = $user->subscriber_price - $admin__com;
		    	$db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' WHERE `id` = '".$user_id."'");
		    	$insert_data         = array(
		            'user_id' => $user_id,
		            'subscriber_id' => $pt->user->id,
		            'time' => time(),
		            'active' => 1
		        );
		        $create_subscription = $db->insert(T_SUBSCRIPTIONS, $insert_data);
		        if ($create_subscription) {

		            $notif_data = array(
		                'notifier_id' => $pt->user->id,
		                'recipient_id' => $user_id,
		                'type' => 'subscribed_u',
		                'url' => ('@' . $pt->user->username),
		                'time' => time()
		            );

		            pt_notify($notif_data);
		        }

		    	$response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'message' => 'paid successful'
			    );

        	}
        }
    }
    catch (Exception $e) {
        $response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '4',
	            'error_text' => $e->getMessage()
	        )
		);
		echo json_encode($response_data, JSON_PRETTY_PRINT);
		exit();
    }

}