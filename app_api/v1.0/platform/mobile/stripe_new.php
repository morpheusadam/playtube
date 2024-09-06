<?php

$requests = array('createsession','success');

if (empty($_POST['type']) || (!empty($_POST['type']) && !in_array($_POST['type'], $requests))) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '4',
            'error_text' => 'type can not be empty'
        )
	);
}
else{
	if ($_POST['type'] == 'createsession') {

		$stripe = array(
            'secret_key' => $pt->config->stripe_secret,
            'publishable_key' => $pt->config->stripe_id
        );
        require_once('assets/libs/stripe/vendor/autoload.php');
        $z = \Stripe\Stripe::setApiKey($stripe['secret_key']);
        if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {

        	$amount = 100 * $_POST['amount'];

        	$checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $pt->config->stripe_currency,
                            'product_data' => [ 'name' => 'top up wallet' ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => PT_Link("aj/wallet/success?amount=".$amount),
                'cancel_url' => PT_Link("aj/wallet/stripe_cancel?amount=".$amount),
            ]);

            if (!empty($checkout_session) && !empty($checkout_session['id'])) {
		    	$_SESSION['stripe_session_payment_intent'] = $checkout_session['id'];
		    	$response_data = array(
	                'api_status' => 200,
	                'sessionId' => $checkout_session['id']
	            );
		    }
		    else{
		    	$response_data = array(
	                'api_status' => 400,
	                'errors' => array(
			            'error_id' => '5',
			            'error_text' => $lang->error_msg
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
		            'error_text' => 'amount can not be empty'
		        )
			);
        }
	}
	elseif ($_POST['type'] == 'success') {
		if (!empty($_POST['stripe_session'])) {
			try {
				require_once('assets/libs/stripe/vendor/autoload.php');
				$stripe = array(
				  "secret_key"      =>  $pt->config->stripe_secret,
				  "publishable_key" =>  $pt->config->stripe_id
				);

				\Stripe\Stripe::setApiKey($stripe['secret_key']);
				$checkout_session = \Stripe\Checkout\Session::retrieve($_POST['stripe_session']);
				if ($checkout_session->payment_status == 'paid') {
					$amount = ($checkout_session->amount_total / 100);
					$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
					$payment_data         = array(
			            'user_id' => $pt->user->id,
			            'paid_id'  => $pt->user->id,
			            'admin_com'    => 0,
			            'currency'    => $pt->config->stripe_currency,
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
				else{
					$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '4',
				            'error_text' => 'something went wrong not paid'
				        )
					);
				}
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
		else{
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => 'stripe_session can not be empty'
		        )
			);
		}
	}
}