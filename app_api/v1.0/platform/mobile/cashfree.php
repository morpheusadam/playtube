<?php
$paypal_currency = $pt->config->paypal_currency;
$requests = array('initialize','pro_paid','check_subscribe','buy_video','wallet_paid');
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
elseif (empty($_POST['request']) || (!empty($_POST['request']) && !in_array($_POST['request'], $requests))) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '4',
            'error_text' => 'request can not be empty'
        )
	);
}
else{
	$types = array('pro','subscribe','buy_video','wallet');
	if ($_POST['request'] == 'initialize' && !empty($_POST['type']) && in_array($_POST['type'], $types) && !empty($_POST['phone']) && !empty($_POST['name']) && !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$price = 0;
		if ($_POST['type'] == 'subscribe') {
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
			$user_id = PT_Secure($_POST['subscribe_id']);
			$user = PT_UserData($user_id);
			$price = $user->subscriber_price;
	    	$callback_url = PT_Link("aj/go_pro/check_subscribe?subscribe_id=".$user_id);
		}
		elseif ($_POST['type'] == 'buy_video') {
			if (empty($_POST['video_id']) || !is_numeric($_POST['video_id']) || $_POST['video_id'] < 1) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => 'video_id can not be empty'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
    			exit();
			}
			$video = PT_GetVideoByID($_POST['video_id'], 0,0,2);
			if (empty($video)) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'video not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
    			exit();
			}
			if (!empty($_POST['pay_type']) && $_POST['pay_type'] == 'rent' && !empty($video->rent_price)) {
				$price = $video->rent_price;
				$text = "&pay_type=rent";
			}
			else{
				$price = $video->sell_video;
			}
			$callback_url = PT_Link("aj/go_pro/buy_video?video_id=".$video->id.$text);
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
			$price = PT_Secure($_POST['amount']);
			$callback_url = PT_Link("aj/wallet/wallet_paid?amount=".$price);
		}
		$result = array();
	    $order_id = uniqid();
	    $name = PT_Secure($_POST['name']);
	    $email = PT_Secure($_POST['email']);
	    $phone = PT_Secure($_POST['phone']);
	    
	    if ($_POST['type'] == 'pro') {
	    	$price = intval($pt->config->pro_pkg_price);
	    	$callback_url = PT_Link("aj/go_pro/pro_paid?amount=".$price);
	    }
	    

	    


	    $secretKey = $pt->config->cashfree_secret_key;
		$postData = array( 
		  "appId" => $pt->config->cashfree_client_key, 
		  "orderId" => "order".$order_id, 
		  "orderAmount" => $price, 
		  "orderCurrency" => "INR", 
		  "orderNote" => "", 
		  "customerName" => $name, 
		  "customerPhone" => $phone, 
		  "customerEmail" => $email,
		  "returnUrl" => $callback_url, 
		  "notifyUrl" => $callback_url,
		);
		 // get secret key from your config
		 ksort($postData);
		 $signatureData = "";
		 foreach ($postData as $key => $value){
		      $signatureData .= $key.$value;
		 }
		 $signature = hash_hmac('sha256', $signatureData, $secretKey,true);
		 $signature = base64_encode($signature);
		 $cashfree_link = 'https://test.cashfree.com/billpay/checkout/post/submit';
		 if ($pt->config->cashfree_mode == 'live') {
		 	$cashfree_link = 'https://www.cashfree.com/checkout/post/submit';
		 }

		$response_data = array(
                            'api_status' => 200,
                            'url' => $cashfree_link,
                            'appId' => $pt->config->cashfree_client_key,
                            'orderId' => 'order'.$order_id,
                            'orderAmount' => $price,
                            'orderCurrency' => 'INR',
                            'orderNote' => '',
                            'customerName' => $name,
                            'customerEmail' => $email,
                            'customerPhone' => $phone,
                            'returnUrl' => $callback_url,
                            'notifyUrl' => $callback_url,
                            'signature' => $signature
                        );
	}
	elseif ($_POST['request'] == 'pro_paid') {
		if (empty($_POST['txStatus']) || $_POST['txStatus'] != 'SUCCESS') {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'txStatus can not be empty'
		        )
			);
		}
		else{
			$orderId = $_POST["orderId"];
			$orderAmount = $_POST["orderAmount"];
			$referenceId = $_POST["referenceId"];
			$txStatus = $_POST["txStatus"];
			$paymentMode = $_POST["paymentMode"];
			$txMsg = $_POST["txMsg"];
			$txTime = $_POST["txTime"];
			$signature = $_POST["signature"];
			$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
			$hash_hmac = hash_hmac('sha256', $data, $pt->config->cashfree_secret_key, true) ;
			$computedSignature = base64_encode($hash_hmac);
			if ($signature == $computedSignature) {
				$update = array('is_pro' => 1,'verified' => 1);
			    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
			    if ($go_pro === true) {
			    	$payment_data         = array(
			    		'user_id' => $pt->user->id,
			    		'type'    => 'pro',
			    		'amount'  => $sum,
			    		'date'    => date('n') . '/' . date('Y'),
			    		'expire'  => strtotime("+30 days")
			    	);

			    	$db->insert(T_PAYMENTS,$payment_data);
			    	$db->where('user_id',$pt->user->id)->update(T_VIDEOS,array('featured' => 1));
			    	$_SESSION['upgraded'] = true;
			    	$response_data     = array(
				        'api_status'   => '200',
				        'api_version'  => $api_version,
				        'message' => 'paid successful'
				    );
			    }
			    else{
			    	$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '6',
				            'error_text' => 'something went wrong'
				        )
					);
			    }
		    } else {
		        $response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'something went wrong'
			        )
				);
		    }
		}
	}
	elseif ($_POST['request'] == 'check_subscribe') {
		if (empty($_POST['txStatus']) || $_POST['txStatus'] != 'SUCCESS') {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'txStatus can not be empty'
		        )
			);
		}
		else{
			$user_id       = (!empty($_POST['subscribe_id']) && is_numeric($_POST['subscribe_id'])) ? PT_Secure($_POST['subscribe_id']) : 0;
			$orderId = $_POST["orderId"];
			$orderAmount = $_POST["orderAmount"];
			$referenceId = $_POST["referenceId"];
			$txStatus = $_POST["txStatus"];
			$paymentMode = $_POST["paymentMode"];
			$txMsg = $_POST["txMsg"];
			$txTime = $_POST["txTime"];
			$signature = $_POST["signature"];
			$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
			$hash_hmac = hash_hmac('sha256', $data, $pt->config->cashfree_secret_key, true) ;
			$computedSignature = base64_encode($hash_hmac);
			if ($signature == $computedSignature && !empty($user_id)) {
				$user = PT_UserData($user_id);
		    	if (!empty($user) && $user->subscriber_price > 0) {

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
		    	else{
		    		$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '6',
				            'error_text' => 'user not found'
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
			            'error_text' => 'something went wrong'
			        )
				);
		    }
		}
	}
	elseif ($_POST['request'] == 'buy_video') {
		if (empty($_POST['txStatus']) || $_POST['txStatus'] != 'SUCCESS') {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'txStatus can not be empty'
		        )
			);
		}
		elseif (empty($_POST['video_id']) || !is_numeric($_POST['video_id']) || $_POST['video_id'] < 1) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'video_id can not be empty'
		        )
			);
		}
		else{
			$orderId = $_POST["orderId"];
			$orderAmount = $_POST["orderAmount"];
			$referenceId = $_POST["referenceId"];
			$txStatus = $_POST["txStatus"];
			$paymentMode = $_POST["paymentMode"];
			$txMsg = $_POST["txMsg"];
			$txTime = $_POST["txTime"];
			$signature = $_POST["signature"];
			$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
			$hash_hmac = hash_hmac('sha256', $data, $pt->config->cashfree_secret_key, true) ;
			$computedSignature = base64_encode($hash_hmac);
			if ($signature == $computedSignature) {
				$video_id       = (!empty($_POST['video_id']) && is_numeric($_POST['video_id'])) ? PT_Secure($_POST['video_id']) : 0;

			    if (!empty($video_id)) {
			    	$video = PT_GetVideoByID($video_id, 0,0,2);
			    	if (!empty($video)) {
						$notify_sent = false;
			    		if (!empty($video->is_movie)) {

			    			$payment_data         = array(
					    		'user_id' => $video->user_id,
					    		'video_id'    => $video->id,
					    		'paid_id'  => $pt->user->id,
					    		'admin_com'    => 0,
					    		'currency'    => $paypal_currency,
					    		'time'  => time()
					    	);
					    	if (!empty($_GET['pay_type']) && $_GET['pay_type'] == 'rent') {
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

				    		if (!empty($_GET['pay_type']) && $_GET['pay_type'] == 'rent') {
				    			$admin__com = $pt->config->admin_com_rent_videos;
					    		if ($pt->config->com_type == 1) {
					    			$admin__com = ($pt->config->admin_com_rent_videos * $video->rent_price)/100;
					    			$paypal_currency = $paypal_currency.'_PERCENT';
					    		}
					    		$payment_data         = array(
						    		'user_id' => $video->user_id,
						    		'video_id'    => $video->id,
						    		'paid_id'  => $pt->user->id,
						    		'amount'    => $video->rent_price,
						    		'admin_com'    => $pt->config->admin_com_rent_videos,
						    		'currency'    => $paypal_currency,
						    		'time'  => time(),
						    		'type' => 'rent'
						    	);
						    	$balance = $video->rent_price - $admin__com;
				    		}
				    		else{
				    			$admin__com = $pt->config->admin_com_sell_videos;
					    		if ($pt->config->com_type == 1) {
					    			$admin__com = ($pt->config->admin_com_sell_videos * $video->sell_video)/100;
					    			$paypal_currency = $paypal_currency.'_PERCENT';
					    		}

					    		$payment_data         = array(
						    		'user_id' => $video->user_id,
						    		'video_id'    => $video->id,
						    		'paid_id'  => $pt->user->id,
						    		'amount'    => $video->sell_video,
						    		'admin_com'    => $pt->config->admin_com_sell_videos,
						    		'currency'    => $paypal_currency,
						    		'time'  => time()
						    	);
						    	$balance = $video->sell_video - $admin__com;

				    		}
					    		
					    	$db->insert(T_VIDEOS_TRSNS,$payment_data);
					    	
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
			    	else{
			    		$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '6',
					            'error_text' => 'video not found'
					        )
						);
			    	}
			    }
			    else{
			    	$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '4',
				            'error_text' => 'video_id can not be empty'
				        )
					);
			    }
		    } else {
		        $response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'something went wrong'
			        )
				);
		    }
		}
	}
	elseif ($_POST['request'] == 'wallet_paid') {

		if (empty($_POST['order_id'])) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '5',
		            'error_text' => 'order_id can not be empty'
		        )
			);
		}
		else{
			try {
				$amount = getCashfreeOrder($_POST['order_id']);

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
			} catch (Exception $e) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => $e->getMessage()
			        )
				);
			}
		}
	}
}