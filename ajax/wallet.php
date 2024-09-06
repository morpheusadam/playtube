<?php
use PayFast\PayFastPayment;
if (IS_LOGGED == false && $first != 'success_fortumo' && $first != 'success_aamarpay' && $first != 'cashfree_paid' && $first != 'iyzipay_paid' && $first != 'success_yoomoney') {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}

require 'assets/includes/paypal_config.php';


$payment_currency = $pt->config->payment_currency;
$paypal_currency = $pt->config->paypal_currency;

if ($first == 'replenish') {
	$data    = array('status' => 400);
	$request = (!empty($_POST['amount']) && is_numeric($_POST['amount']));
	if ($request === true) {
		$price = PT_Secure($_POST['amount']);

		$ch = curl_init();

	    curl_setopt($ch, CURLOPT_URL, $url . '/v2/checkout/orders');
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
	      "intent": "CAPTURE",
	      "purchase_units": [
	            {
	                "items": [
	                    {
	                        "name": "Wallet Replenishment",
	                        "description":  "Pay For ' . $pt->config->name.'",
	                        "quantity": "1",
	                        "unit_amount": {
	                            "currency_code": "'.$pt->config->paypal_currency.'",
	                            "value": "'.$price.'"
	                        }
	                    }
	                ],
	                "amount": {
	                    "currency_code": "'.$pt->config->paypal_currency.'",
	                    "value": "'.$price.'",
	                    "breakdown": {
	                        "item_total": {
	                            "currency_code": "'.$pt->config->paypal_currency.'",
	                            "value": "'.$price.'"
	                        }
	                    }
	                }
	            }
	        ],
	        "application_context":{
	            "shipping_preference":"NO_SHIPPING",
	            "return_url": "'.PT_Link("aj/wallet/get_paid?status=success&amount=").$price.'",
	            "cancel_url": "'.PT_Link("aj/wallet/get_paid?status=false").'"
	        }
	    }');

	    $headers = array();
	    $headers[] = 'Content-Type: application/json';
	    $headers[] = 'Authorization: Bearer '.$pt->paypal_access_token;
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	    $result = curl_exec($ch);
	    if (curl_errno($ch)) {
	        echo 'Error:' . curl_error($ch);
	    }
	    curl_close($ch);
	    $result = json_decode($result);
	    if (!empty($result) && !empty($result->links) && !empty($result->links[1]) && !empty($result->links[1]->href)) {
	        $data = array(
		        'status' => 200,
		        'type' => 'SUCCESS',
		        'url' => $result->links[1]->href
		    );
	    }
	    elseif(!empty($result->message)){
	        $data = array(
	            'type' => 'ERROR',
	            'details' => $result->message
	        );
	    }
	    echo json_encode($data);
	    exit();
	}
}

if ($first == 'get_paid') {
	$data['status'] = 500;
	if (!empty($_GET['amount']) && is_numeric($_GET['amount']) && !empty($_GET['token'])) {

		$amount = (int)PT_Secure($_GET['amount']);
		$token = PT_Secure($_GET['token']);

		//include_once('assets/includes/paypal.php');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url . '/v2/checkout/orders/'.$token.'/capture');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Authorization: Bearer '.$pt->paypal_access_token;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
		    header("Location: " . PT_Link('wallet'));
			exit();
		}
		curl_close($ch);
		if (!empty($result)) {
		    $result = json_decode($result);
		    if (!empty($result->status) && $result->status == 'COMPLETED') {


		    	$update  = array('wallet' => ($user->wallet_or += $amount));
				$db->where('id',$user->id)->update(T_USERS,$update);
				$payment_data         = array(
		    		'user_id' => $user->id,
		    		'paid_id'  => $user->id,
		    		'admin_com'    => 0,
		    		'currency'    => $pt->config->paypal_currency,
		    		'time'  => time(),
		    		'amount' => $amount,
		    		'type' => 'ad'
		    	);
				$db->insert(T_VIDEOS_TRSNS,$payment_data);


				$_SESSION['upgraded'] = true;
				$url     = PT_Link('wallet');
				if (!empty($_COOKIE['redirect_page'])) {
		            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
		            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
		        }

		    	header("Location: $url");
		    	exit();
		    }
		}
	}
	header("Location: " . PT_Link('wallet'));
	exit();
}

if ($first == 'checkout_replenish' && $pt->config->checkout_payment == 'yes') {
	if (empty($_POST['card_number']) || empty($_POST['card_cvc']) || empty($_POST['card_month']) || empty($_POST['card_year']) || empty($_POST['token']) || empty($_POST['card_name']) || empty($_POST['card_address']) || empty($_POST['card_city']) || empty($_POST['card_state']) || empty($_POST['card_zip']) || empty($_POST['card_country']) || empty($_POST['card_email']) || empty($_POST['card_phone'])) {
        $data = array(
            'status' => 400,
            'error' => $lang->please_check_details
        );
    }
    else {
		if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
			require_once 'assets/libs/2checkout/lib/Twocheckout.php';
		    Twocheckout::privateKey($pt->config->checkout_private_key);
		    Twocheckout::sellerId($pt->config->checkout_seller_id);
		    if ($pt->config->checkout_mode == 'sandbox') {
		        Twocheckout::sandbox(true);
		    } else {
		        Twocheckout::sandbox(false);
		    }
		    try {
		    	$amount = PT_Secure($_POST['amount']);


		    	$charge  = Twocheckout_Charge::auth(array(
		            "merchantOrderId" => "123",
		            "token" => $_POST['token'],
		            "currency" => $pt->config->checkout_currency,
		            "total" => $amount,
		            "billingAddr" => array(
		                "name" => $_POST['card_name'],
		                "addrLine1" => $_POST['card_address'],
		                "city" => $_POST['card_city'],
		                "state" => $_POST['card_state'],
		                "zipCode" => $_POST['card_zip'],
		                "country" => $countries_name[$_POST['card_country']],
		                "email" => $_POST['card_email'],
		                "phoneNumber" => $_POST['card_phone']
		            )
		        ));
		        if ($charge['response']['responseCode'] == 'APPROVED') {

					$update  = array('wallet' => ($user->wallet += $amount));
					$db->where('id',$user->id)->update(T_USERS,$update);
					$payment_data         = array(
			    		'user_id' => $user->id,
			    		'paid_id'  => $user->id,
			    		'admin_com'    => 0,
			    		'currency'    => $pt->config->checkout_currency,
			    		'time'  => time(),
			    		'amount' => $amount,
			    		'type' => 'ad'
			    	);
					$db->insert(T_VIDEOS_TRSNS,$payment_data);
					$_SESSION['upgraded'] = true;
					$data['status'] = 200;
					$url     = PT_Link('wallet');
					if (!empty($_COOKIE['redirect_page'])) {
			            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
			            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
			        }
					$data['url'] = $url;
		        }
		        else{
		        	$data = array(
		                'status' => 400,
		                'error' => $lang->checkout_declined
		            );
		        }
			}
			catch (Twocheckout_Error $e) {
		        $data = array(
		            'status' => 400,
		            'error' => $e->getMessage()
		        );
		    }
		}
		else{
			$data = array(
	            'status' => 400,
	            'error' => $lang->please_check_details
	        );
		}
	}
}




if ($first == 'bank_replenish' && $pt->config->bank_payment == 'yes') {
	if (empty($_FILES["thumbnail"]) || empty($_POST['amount'])) {
        $error = $lang->please_check_details;
    }
    if (empty($error)) {
    	$amount = PT_Secure($_POST['amount']);
    	$amount = $amount/100;
        $description = 'Wallet';
        $fileInfo      = array(
            'file' => $_FILES["thumbnail"]["tmp_name"],
            'name' => $_FILES['thumbnail']['name'],
            'size' => $_FILES["thumbnail"]["size"],
            'type' => $_FILES["thumbnail"]["type"],
            'types' => 'jpeg,jpg,png,bmp,gif'
        );
        $media         = PT_ShareFile($fileInfo);

        $mediaFilename = $media['filename'];
        if (!empty($mediaFilename)) {

        	$insert_id = $db->insert(T_BANK_TRANSFER,array('user_id' => $pt->user->id,
                                                   'description' => $description,
                                                   'price'       => $amount,
                                                   'receipt_file' => $mediaFilename,
                                                   'mode'         => 'wallet'));
            if (!empty($insert_id)) {
            	$notif_data = array(
                    'recipient_id' => 0,
                    'type' => 'bank',
                    'admin' => 1,
                    'time' => time()
                );

                pt_notify($notif_data);
                $data = array(
                    'message' => $lang->bank_transfer_request,
                    'status' => 200
                );
            }
        }
        else{
            $error = $lang->please_check_details;
            $data = array(
                'status' => 500,
                'message' => $error
            );
        }
    } else {
        $data = array(
            'status' => 500,
            'message' => $error
        );
    }
}



if ($first == 'get_modal') {
	$types = array('pro','wallet','pay','subscribe','rent');
	$data['status'] = 400;
	if (!empty($_POST['type']) && in_array($_POST['type'], $types)) {
		$user = $db->where('id',$pt->user->id)->getOne(T_USERS);

		$price = 0;
		$video_id = 0;
		$user_id = 0;
		if (!empty($_POST['price'])) {
			$price = PT_Secure($_POST['price']);
		}
		if (!empty($_POST['video_id'])) {
			$video_id = PT_Secure($_POST['video_id']);
		}
		if (!empty($_POST['user_id'])) {
			$user_id = PT_Secure($_POST['user_id']);
		}

		$pt->show_wallet = 0;
		if (!empty($user) && $_POST['type'] == 'pro' && $user->wallet >= intval($pt->config->pro_pkg_price)) {
			$pt->show_wallet = 1;
		}
		elseif (!empty($user) && $_POST['type'] == 'pay' && !empty($video_id)) {
			$video = $db->where('id',$video_id)->getOne(T_VIDEOS);
			if ($user->wallet >= $video->sell_video) {
				$pt->show_wallet = 1;
			}
		}
		elseif (!empty($user) && $_POST['type'] == 'rent' && !empty($video_id)) {
			$video = $db->where('id',$video_id)->getOne(T_VIDEOS);
			if ($user->wallet >= $video->rent_price) {
				$pt->show_wallet = 1;
			}
		}

		if ($_POST['type'] == 'subscribe' && !empty($user_id)) {
			$new_user = $db->where('id',$user_id)->getOne(T_USERS);
			if (!empty($new_user) && $new_user->subscriber_price > 0 && $user->wallet >= $new_user->subscriber_price) {
				$pt->show_wallet = 1;
			}
		}
		if ($_POST['type'] == 'subscribe') {
			$price = $new_user->subscriber_price;
		}
		elseif ($_POST['type'] == 'pro') {
			$price = intval($pt->config->pro_pkg_price);
		}
		elseif ($_POST['type'] == 'rent') {
			$price = $video->rent_price;
		}
		elseif ($_POST['type'] == 'pay') {
			$price = $video->sell_video;
		}

		$html = PT_LoadPage('modals/payment_modal',array('TYPE' => PT_Secure($_POST['type']),'PRICE' => $price,'VIDEO_ID' => $video_id,'USER_ID' => $user_id));
		if (!empty($html)) {
			$data['status'] = 200;
			$data['html'] = $html;
		}
	}
}
if ($first == 'paystack') {

	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0 && !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$price = $_POST['amount'] * 100;

		$callback_url = PT_Link("aj/wallet/paystack_paid?type=wallet&amount=".$price);
		$result = array();
	    $reference = uniqid();

		//Set other parameters as keys in the $postdata array
		$postdata =  array('email' => $_POST['email'], 'amount' => $price,"reference" => $reference,'callback_url' => $callback_url);
		$url = "https://api.paystack.co/transaction/initialize";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));  //Post Fields
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$headers = [
		  'Authorization: Bearer '.$pt->config->paystack_secret_key,
		  'Content-Type: application/json',

		];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$request = curl_exec ($ch);

		curl_close ($ch);

		if ($request) {
		    $result = json_decode($request, true);
		    if (!empty($result)) {
				 if (!empty($result['status']) && $result['status'] == 1 && !empty($result['data']) && !empty($result['data']['authorization_url']) && !empty($result['data']['access_code'])) {
				  	$data['status'] = 200;
				  	$data['url'] = $result['data']['authorization_url'];
				}
				else{
			        $data['message'] = $result['message'];
				}
			}
			else{
				$data['message'] = $lang->error_msg;
			}
		}
		else{
			$data['message'] = $lang->error_msg;
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'paystack_paid') {
	$payment  = CheckPaystackPayment($_GET['reference']);
	if ($payment) {
		$amount = PT_Secure($_GET['amount'] / 100);
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
        $url     = PT_Link('wallet');
		if (!empty($_COOKIE['redirect_page'])) {
            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
        }
        header('Location: ' . $url);
        exit();
    } else {
        header('Location: ' . PT_Link('wallet'));
        exit();
    }
}
if ($first == 'cashfree' && $pt->config->cashfree_payment == 'yes') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0 && !empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && !empty($_POST['cashfree_card_number']) && !empty($_POST['cashfree_card_expiry_mm']) && !empty($_POST['cashfree_card_expiry_yy']) && !empty($_POST['cashfree_card_cvv'])) {
		$data['status'] = 400;

		try {
			$result = array();
		    $order_id = uniqid();
		    $name = PT_Secure($_POST['name']);
		    $email = PT_Secure($_POST['email']);
		    $phone = PT_Secure($_POST['phone']);
		    $price = PT_Secure($_POST['amount']);
		    $card_number = PT_Secure($_POST['cashfree_card_number']);
		    $card_expiry_mm = PT_Secure($_POST['cashfree_card_expiry_mm']);
		    $card_expiry_yy = PT_Secure($_POST['cashfree_card_expiry_yy']);
		    $card_cvv = PT_Secure($_POST['cashfree_card_cvv']);

		    $return_url = PT_Link("aj/wallet/cashfree_paid?order_id={order_id}&user_id=".$pt->user->id);
		    $notify_url = PT_Link("aj/wallet/cashfree_paid?user_id=".$pt->user->id);

		    $payment_session_id =   createCashfreeOrder([
								    	'email' => $email,
								    	'phone' => $phone,
								    	'amount' => $price,
								    	'return_url' => $return_url,
								    	'notify_url' => $notify_url,
								    ]);

		    $url =  payCashfreeOrder([
				    	'payment_session_id' => $payment_session_id,
				    	'card_number' => $card_number,
				    	'card_holder_name' => $name,
				    	'card_expiry_mm' => $card_expiry_mm,
				    	'card_expiry_yy' => $card_expiry_yy,
				    	'card_cvv' => $card_cvv
				    ]);

		    $data['status'] = 200;
		    $data['url'] = $url;
		} catch (Exception $e) {
			$data['message'] = $e->getMessage();
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'cashfree_paid' && $pt->config->cashfree_payment == 'yes') {
	if (empty($_GET['order_id']) || empty($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
		header('Location: ' . PT_Link('wallet'));
        exit();
	}
	$user = $db->where("id", PT_Secure($_GET["user_id"]))->getOne(T_USERS);
	if (!empty($user)) {
		try {
			$amount = getCashfreeOrder($_GET['order_id']);

			$db->where('id',$user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
			$payment_data         = array(
	            'user_id' => $user->id,
	            'paid_id'  => $user->id,
	            'admin_com'    => 0,
	            'currency'    => $pt->config->payment_currency,
	            'time'  => time(),
	            'amount' => $amount,
	            'type' => 'ad'
	        );
	        $db->insert(T_VIDEOS_TRSNS,$payment_data);
	        $url     = PT_Link('wallet');
			if (!empty($_COOKIE['redirect_page'])) {
	            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
	            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
	        }
	        header('Location: ' . $url);
	        exit();
		} catch (Exception $e) {
			header('Location: ' . PT_Link('wallet'));
	        exit();
		}
	}
	header('Location: ' . PT_Link('wallet'));
	exit();
}
if ($first == 'razorpay' && $pt->config->razorpay_payment == 'yes') {
	if (!empty($_POST['payment_id']) && !empty($_POST['order_id']) && !empty($_POST['merchant_amount']) && !empty($_POST['currency'])) {

		$payment_id = PT_Secure($_POST['payment_id']);
		$price    = PT_Secure($_POST['merchant_amount']);
		$currency_code = "INR";
	    $check = array(
		    'amount' => $price,
		    'currency' => $currency_code,
		);
		$json = CheckRazorpayPayment($payment_id,$check);
		if (!empty($json) && empty($json->error_code)) {
			$price = $price / 100;

			$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($price)));
			$payment_data         = array(
	            'user_id' => $pt->user->id,
	            'paid_id'  => $pt->user->id,
	            'admin_com'    => 0,
	            'currency'    => $pt->config->payment_currency,
	            'time'  => time(),
	            'amount' => $price,
	            'type' => 'ad'
	        );
	        $db->insert(T_VIDEOS_TRSNS,$payment_data);
	        $data['status'] = 200;
	        $url     = PT_Link('wallet');
			if (!empty($_COOKIE['redirect_page'])) {
	            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
	            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
	        }
		    $data['url'] = $url;
		}
		else{
	    	$data['message'] = $json->error_description;
	    }
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'paysera' && $pt->config->paysera_payment == 'yes') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$price = PT_Secure($_POST['amount']);
		$callback_url = PT_Link("aj/wallet/paysera_paid?amount=".$price);
		require_once 'assets/libs/Paysera.php';

	    $request = WebToPay::redirectToPayment(array(
		    'projectid'     => $pt->config->paysera_project_id,
		    'sign_password' => $pt->config->paysera_sign_password,
		    'orderid'       => rand(111111,999999),
		    'amount'        => $price,
		    'currency'      => $pt->config->payment_currency,
		    'country'       => 'LT',
		    'accepturl'     => $callback_url,
		    'cancelurl'     => $callback_url,
		    'callbackurl'   => $callback_url,
		    'test'          => $pt->config->paysera_mode,
		));
		$data = array('status' => 200,
	                  'url' => $request);
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'paysera_paid' && $pt->config->paysera_payment == 'yes') {
	require_once 'assets/libs/Paysera.php';
	try {
        $response = WebToPay::checkResponse($_GET, array(
            'projectid'     => $pt->config->paysera_project_id,
            'sign_password' => $pt->config->paysera_sign_password,
        ));

        // if ($response['test'] !== '0') {
        //     throw new Exception('Testing, real payment was not made');
        // }
        if ($response['type'] !== 'macro') {
        	header('Location: ' . PT_Link('ads'));
	        exit();
            //throw new Exception('Only macro payment callbacks are accepted');
        }
        $amount = $response['amount'] / 100;
        $currency = $response['currency'];

        if ($currency != $pt->config->payment_currency) {
        	header('Location: ' . PT_Link('ads'));
	        exit();
        }
        else{
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
	        $url     = PT_Link('wallet');
			if (!empty($_COOKIE['redirect_page'])) {
	            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
	            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
	        }
		    header('Location: ' . $url);
		    exit();
        }
	} catch (Exception $e) {
	    header('Location: ' . PT_Link('wallet'));
        exit();
	}
}
if ($first == 'fluttewave') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && !empty($_POST['email'])) {
		$email = $_POST['email'];
	    $amount = $_POST['amount'];

	    //* Prepare our rave request
	    $request = [
	        'tx_ref' => time(),
	        'amount' => $amount,
	        'currency' => 'NGN',
	        'payment_options' => 'card',
	        'redirect_url' => PT_Link('aj/wallet/fluttewave_success'),
	        'customer' => [
	            'email' => $email,
	            'name' => 'user_'.uniqid()
	        ],
	        'meta' => [
	            'price' => $amount
	        ],
	        'customizations' => [
	            'title' => 'Top Up Wallet',
	            'description' => 'Top Up Wallet'
	        ]
	    ];

	    //* Ca;; f;iterwave emdpoint
	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	    CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_ENCODING => '',
	    CURLOPT_MAXREDIRS => 10,
	    CURLOPT_TIMEOUT => 0,
	    CURLOPT_FOLLOWLOCATION => true,
	    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	    CURLOPT_CUSTOMREQUEST => 'POST',
	    CURLOPT_POSTFIELDS => json_encode($request),
	    CURLOPT_HTTPHEADER => array(
	        'Authorization: Bearer '.$pt->config->fluttewave_secret_key,
	        'Content-Type: application/json'
	    ),
	    ));

	    $response = curl_exec($curl);

	    curl_close($curl);
	    
	    $res = json_decode($response);
	    if($res->status == 'success')
	    {
	    	$data['status'] = 200;
	        $data['url'] = $res->data->link;
	    }
	    else
	    {
	        $data['message'] = $lang->error_msg;
	    }
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'fluttewave_success') {
	if (!empty($_GET['status']) && $_GET['status'] == 'successful' && !empty($_GET['transaction_id'])) {
		$txid = $_GET['transaction_id'];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/{$txid}/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$pt->config->fluttewave_secret_key
            ),
        ));
          
        $response = curl_exec($curl);
          
        curl_close($curl);
          
        $res = json_decode($response);
        if($res->status){
            $amount = $res->data->charged_amount;

            $updateUser = $db
                    ->where("id", $pt->user->id)
                    ->update(T_USERS, ["wallet" => $db->inc($amount)]);
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
        }
	}
	
    $url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
    header('Location: ' . $url);
    exit();
}
if ($first == 'iyzipay') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		require_once 'assets/libs/iyzipay/samples/config.php';
		$amount = PT_Secure($_POST['amount']);
		$callback_url = PT_Link("aj/wallet/iyzipay_paid?amount=$amount&ConversationId=$ConversationId&user_id={$pt->user->id}");


		$request->setPrice($amount);
		$request->setPaidPrice($amount);
		$request->setCallbackUrl($callback_url);


		$basketItems = array();
		$firstBasketItem = new \Iyzipay\Model\BasketItem();
		$firstBasketItem->setId("BI".rand(11111111,99999999));
		$firstBasketItem->setName("wallet");
		$firstBasketItem->setCategory1("wallet");
		$firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
		$firstBasketItem->setPrice($amount);
		$basketItems[0] = $firstBasketItem;
		$request->setBasketItems($basketItems);
		$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, Config::options());
		$content = $checkoutFormInitialize->getCheckoutFormContent();
		if (!empty($content)) {
			$data['html'] = $content;
			$data['status'] = 200;
		}
		else{
			$data['message'] = $lang->please_check_details;
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'iyzipay_paid') {
	if (!empty($_POST['token']) && !empty($_GET['ConversationId']) && !empty($_GET['amount']) && !empty($_GET['user_id']) && is_numeric($_GET['amount']) && $_GET['amount'] > 0) {
		require_once('assets/libs/iyzipay/samples/config.php');

    $_GET['ConversationId'] = PT_Secure($_GET['ConversationId']);
    $_GET['user_id'] = PT_Secure($_GET['user_id']);

		# create request class
		$request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
		$request->setLocale(\Iyzipay\Model\Locale::TR);
		$request->setConversationId($_GET['ConversationId']);
		$request->setToken($_POST['token']);

		# make request
		$checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, Config::options());

		# print result
		if ($checkoutForm->getPaymentStatus() == 'SUCCESS') {
			$amount = PT_Secure($_GET['amount']);
			$db->where('id',$_GET['user_id'])->update(T_USERS,array('wallet' => $db->inc($amount)));
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
	        $url     = PT_Link('wallet');
			if (!empty($_COOKIE['redirect_page'])) {
	            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
	            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
	        }
		    header('Location: ' . $url);
		    exit();
		}
		else{
			header('Location: ' . PT_Link('wallet'));
	        exit();
		}
	}
	else{
		header('Location: ' . PT_Link('wallet'));
	    exit();
	}
}
if ($first == 'move_to_wallet') {
	if ($pt->user->balance < 1) {
		$data['message'] = $lang->no_balance_to_move;
	}
	elseif (empty($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] < 1) {
		$data['message'] = $lang->please_check_details;
	}
	elseif ($_POST['amount'] > $pt->user->balance_or) {
		$data['message'] = $lang->more_than_balance;
	}
	else{
		$amount = PT_Secure($_POST['amount']);
		$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
		$db->where('id',$pt->user->id)->update(T_USERS,array('balance' => $db->dec($amount)));
		$data['status'] = 200;
	}
}
if ($first == 'stripe_session') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = $_POST['amount'] * 100;
		$payment_method_types = array('card');
		$callback_url = PT_Link("aj/wallet/stripe_paid?amount=".$amount);
		try {
			require_once('assets/libs/stripe/vendor/autoload.php');
			$stripe = array(
			  "secret_key"      =>  $pt->config->stripe_secret,
			  "publishable_key" =>  $pt->config->stripe_id
			);

			\Stripe\Stripe::setApiKey($stripe['secret_key']);
			$checkout_session = \Stripe\Checkout\Session::create([
			    'payment_method_types' => [implode(',', $payment_method_types)],
			    'line_items' => [[
			      'price_data' => [
			        'currency' => $pt->config->stripe_currency,
			        'product_data' => [
			          'name' => 'Top Up Wallet',
			        ],
			        'unit_amount' => $amount,
			      ],
			      'quantity' => 1,
			    ]],
			    'mode' => 'payment',
			    'success_url' => PT_Link("aj/wallet/stripe_paid?amount=".$amount),
			    'cancel_url' => PT_Link("aj/wallet/stripe_cancel?amount=".$amount),
		    ]);
		    if (!empty($checkout_session) && !empty($checkout_session['id'])) {
		    	$_SESSION['stripe_session_payment_intent'] = $checkout_session['id'];
		    	$data = array(
	                'status' => 200,
	                'sessionId' => $checkout_session['id']
	            );
		    }
		    else{
		    	$data = array(
	                'status' => 400,
	                'message' => $lang->error_msg
	            );
		    }
		}
		catch (Exception $e) {
			$data = array(
                'status' => 400,
                'message' => $e->getMessage()
            );
		}
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'stripe_paid') {
	if (!empty($_SESSION['stripe_session_payment_intent']) && !empty($_GET['amount']) && is_numeric($_GET['amount'])) {
		try {
			require_once('assets/libs/stripe/vendor/autoload.php');
			$stripe = array(
			  "secret_key"      =>  $pt->config->stripe_secret,
			  "publishable_key" =>  $pt->config->stripe_id
			);

			\Stripe\Stripe::setApiKey($stripe['secret_key']);
			$checkout_session = \Stripe\Checkout\Session::retrieve($_SESSION['stripe_session_payment_intent']);
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
			}
			$url     = PT_Link('wallet');
			if (!empty($_COOKIE['redirect_page'])) {
	            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
	            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
	        }
			header("Location: " . $url);
	        exit();
		} catch (Exception $e) {
			header("Location: " . PT_Link('wallet'));
	        exit();
		}
	}
	else{
		header('Location: ' . PT_Link('wallet'));
	    exit();
	}
}
if ($first == 'stripe_cancel') {
	header('Location: ' . PT_Link('wallet'));
	exit();
}
use SecurionPay\SecurionPayGateway;
use SecurionPay\Exception\SecurionPayException;
use SecurionPay\Request\CheckoutRequestCharge;
use SecurionPay\Request\CheckoutRequest;
if ($first == 'securionpay_token') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = PT_Secure($_POST['amount']);
		require_once('assets/libs/securionpay/vendor/autoload.php');
        $securionPay = new SecurionPayGateway($pt->config->securionpay_secret_key);

        $checkoutCharge = new CheckoutRequestCharge();
        $checkoutCharge->amount(($amount * 100))->currency('USD')->metadata(array('user_key' => $pt->user->id));

        $checkoutRequest = new CheckoutRequest();
        $checkoutRequest->charge($checkoutCharge);

        $signedCheckoutRequest = $securionPay->signCheckoutRequest($checkoutRequest);
        if (!empty($signedCheckoutRequest)) {
            $data['status'] = 200;
            $data['token'] = $signedCheckoutRequest;
        }
        else{
            $data['message'] = $lang->error_msg;
        }
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'securionpay_handle') {
	$data['status'] = 400;
	if (!empty($_POST) && !empty($_POST['charge']) && !empty($_POST['charge']['id'])) {
        $url = "https://api.securionpay.com/charges?limit=10";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $pt->config->securionpay_secret_key.":password");
        $resp = curl_exec($curl);
        curl_close($curl);
        $resp = json_decode($resp,true);
        if (!empty($resp) && !empty($resp['list'])) {
            foreach ($resp['list'] as $key => $value) {
                if ($value['id'] == $_POST['charge']['id']) {
                	if (!empty($value['metadata']) && !empty($value['metadata']['user_key']) && !empty($value['amount'])) {
                        if ($pt->user->id == $value['metadata']['user_key']) {
                            $amount = PT_Secure($value['amount'] / 100);
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
					        $url     = PT_Link('wallet');
							if (!empty($_COOKIE['redirect_page'])) {
					            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
					            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
					        }
					        $data['url'] = $url;
                            $data['status'] = 200;
                        }
                    }
                }
            }
        }
    }
    else{
    	$data['message'] = $lang->error_msg;
    }
}
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
if ($first == 'authorize') {
	$data['status'] = 400;
	if (!empty($_POST['card_number']) && !empty($_POST['card_month']) && !empty($_POST['card_year']) && !empty($_POST['card_cvc']) && !empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = PT_Secure($_POST['amount']);
		require_once('assets/libs/authorize/vendor/autoload.php');

        $APILoginId = $pt->config->authorize_login_id;
        $APIKey = $pt->config->authorize_transaction_key;
        $refId = 'ref' . time();
        define("AUTHORIZE_MODE", $pt->config->authorize_test_mode);

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($APILoginId);
        $merchantAuthentication->setTransactionKey($APIKey);

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($_POST['card_number']);
        $creditCard->setExpirationDate($_POST['card_year'] . "-" . $_POST['card_month']);
        $creditCard->setCardCode($_POST['card_cvc']);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setPayment($paymentType);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new AnetController\CreateTransactionController($request);
        if ($pt->config->authorize_test_mode == 'SANDBOX') {
            $Aresponse = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        }
        else{
            $Aresponse = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
        }
        if ($Aresponse != null) {
            if ($Aresponse->getMessages()->getResultCode() == 'Ok') {
                $trans = $Aresponse->getTransactionResponse();
                if ($trans != null && $trans->getMessages() != null) {
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
			        $url     = PT_Link('wallet');
					if (!empty($_COOKIE['redirect_page'])) {
			            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
			            $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
			        }
			        $data['url'] = $url;
                    $data['status'] = 200;
                }
                else{
                	$error = $lang->error_msg;
                    if ($trans->getErrors() != null) {
                        $error = $trans->getErrors()[0]->getErrorText();
                    }
                    $data['message'] = $error;
                }
            }
            else{
            	$trans = $Aresponse->getTransactionResponse();
                $error = $lang->error_msg;
                if ($trans->getErrors() != null) {
                    $error = $trans->getErrors()[0]->getErrorText();
                }
                $data['message'] = $error;
            }
        }
        else{
        	$data['message'] = $lang->error_msg;
        }
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'create_yoomoney') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = PT_Secure($_POST['amount']);
		$order_id = uniqid();
		$receiver = $pt->config->yoomoney_wallet_id;
		$successURL = PT_Link("aj/wallet/success_yoomoney");
		$form = '<form id="yoomoney_form" method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
					<input type="hidden" name="receiver" value="'.$receiver.'">
					<input type="hidden" name="quickpay-form" value="donate">
					<input type="hidden" name="targets" value="transaction '.$order_id.'">
					<input type="hidden" name="paymentType" value="PC">
					<input type="hidden" name="sum" value="'.$amount.'" data-type="number">
					<input type="hidden" name="successURL" value="'.$successURL.'">
					<input type="hidden" name="label" value="'.$pt->user->id.'">
				</form>';
		$data['status'] = 200;
		$data['html'] = $form;
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'success_yoomoney') {
	$hash = sha1($_POST['notification_type'].'&'.
	$_POST['operation_id'].'&'.
	$_POST['amount'].'&'.
	$_POST['currency'].'&'.
	$_POST['datetime'].'&'.
	$_POST['sender'].'&'.
	$_POST['codepro'].'&'.
	$pt->config->yoomoney_notifications_secret.'&'.
	$_POST['label']);

	$_POST['codepro'] = (is_string($_POST['codepro']) && strtolower($_POST['codepro']) == 'true' ? true : false);

	if ($_POST['sha1_hash'] != $hash || $_POST['codepro'] == true) {
		header('Location: ' . PT_Link('wallet'));
    	exit();
	}
	else{
		if (!empty($_POST['label'])) {
			$user = $db->objectBuilder()->where('id',PT_Secure($_POST['label']))->getOne(T_USERS);
			if (!empty($user)) {
				$amount = PT_Secure($_POST['amount']);
				$db->where('id',$user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
				$payment_data         = array(
		            'user_id' => $user->id,
		            'paid_id'  => $user->id,
		            'admin_com'    => 0,
		            'currency'    => $pt->config->payment_currency,
		            'time'  => time(),
		            'amount' => $amount,
		            'type' => 'ad'
		        );
		        $db->insert(T_VIDEOS_TRSNS,$payment_data);
			}
		}
	}
	$url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
	header('Location: ' . $url);
    exit();
}
if ($first == 'get_fortumo') {
    $data['status'] = 200;
	$data['url'] = 'https://pay.fortumo.com/mobile_payments/'.$pt->config->fortumo_service_id.'?cuid='.$pt->user->id;
}
if ($first == 'success_fortumo') {
	if (!empty($_GET) && !empty($_GET['amount']) && !empty($_GET['status']) && $_GET['status'] == 'completed' && !empty($_GET['cuid']) && !empty($_GET['price'])) {
        $user_id = PT_Secure($_GET['cuid']);
        $amount = (int) PT_Secure($_GET['price']);
        $user = $db->objectBuilder()->where('id',$user_id)->getOne('users');
        if (!empty($user)) {
        	$pt->user = $user;
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
        }
    }
    $url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
    header('Location: ' . $url);
    exit();
}
if ($first == 'get_coinbase') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = (int) PT_Secure($_POST['amount']);
		try {
            $redirect_url = PT_Link("aj/wallet/success_coinbase")."?user_id=".$pt->user->id;
            $cancel_url = PT_Link("aj/wallet/cancel_coinbase")."?user_id=".$pt->user->id;
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            $postdata =  array('name' => 'Top Up Wallet','description' => 'Top Up Wallet','pricing_type' => 'fixed_price','local_price' => array('amount' => $amount , 'currency' => $pt->config->payment_currency), 'metadata' => array('user_id' => $pt->user->id,'amount' => $amount),"redirect_url" => $redirect_url,'cancel_url' => $cancel_url);


            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Cc-Api-Key: '.$pt->config->coinbase_key;
            $headers[] = 'X-Cc-Version: 2018-03-22';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $data = array(
                    'status' => 400,
                    'message' => curl_error($ch)
                );
            }
            curl_close($ch);

            $result = json_decode($result,true);
            if (!empty($result) && !empty($result['data']) && !empty($result['data']['hosted_url']) && !empty($result['data']['id']) && !empty($result['data']['code'])) {
            	$db->insert(T_PENDING_PAYMENTS,array('user_id' => $pt->user->id,
                                                     'payment_data' => $result['data']['code'],
                                                     'method_name' => 'coinbase',
                                                     'time' => time()));
                $data['status'] = 200;
                $data['url'] = $result['data']['hosted_url'];
            }
        }
        catch (Exception $e) {
            $data = array(
                'status' => 400,
                'message' => $e->getMessage()
            );
        }

	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'success_coinbase') {
	if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
		$user = '';
        $coinbase_code = '';
        $user_id = PT_Secure($_GET['user_id']);
	    $payment_data           = $db->objectBuilder()->where('user_id',$user_id)->where('method_name', 'coinbase')->orderBy('id','DESC')->getOne(T_PENDING_PAYMENTS);
        if (!empty($payment_data)) {
            $user           = $db->objectBuilder()->where('id',$user_id)->getOne(T_USERS);
            $coinbase_code = $payment_data->payment_data;
        }

	    if (!empty($user)) {
	    	$pt->user = $user;
	    	$ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges/'.$coinbase_code);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Cc-Api-Key: '.$pt->config->coinbase_key;
            $headers[] = 'X-Cc-Version: 2018-03-22';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                header('Location: ' . PT_Link('wallet'));
                exit();
            }
            curl_close($ch);
            $result = json_decode($result,true);


            if (!empty($result) && !empty($result['data']) && !empty($result['data']['pricing']) && !empty($result['data']['pricing']['local']) && !empty($result['data']['pricing']['local']['amount']) && !empty($result['data']['payments']) && !empty($result['data']['payments'][0]['status']) && $result['data']['payments'][0]['status'] == 'CONFIRMED') {
            	$amount = (int)$result['data']['pricing']['local']['amount'];
            	$db->where('user_id', $pt->user->id)->where('payment_data', $coinbase_code)->delete(T_PENDING_PAYMENTS);
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
            }
	    }
	}
	$url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
	header('Location: ' . $url);
    exit();
}
if ($first == 'cancel_coinbase') {
	if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $user_id = PT_Secure($_GET['user_id']);
        $user = $db->where('id',$user_id)->getOne(T_USERS);
        if (!empty($user)) {
            $db->where('user_id', $user->id)->where('method_name', 'coinbase')->delete(T_PENDING_PAYMENTS);
        }
    }
    header('Location: ' . PT_Link('wallet'));
    exit();
}
if ($first == 'get_ngenius') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$token = GetNgeniusToken();
		if (!empty($token->message)) {
			$data['status'] = 400;
	        $data['message'] = $token->message;
		}
		elseif (!empty($token->errors) && !empty($token->errors[0]) && !empty($token->errors[0]->message)) {
			$data['status'] = 400;
	        $data['message'] = $token->errors[0]->message;
		}
		else{
			$amount = (int) PT_Secure($_POST['amount']);
			$postData = new StdClass();
		    $postData->action = "SALE";
		    $postData->amount = new StdClass();
		    $postData->amount->currencyCode = "AED";
		    $postData->amount->value = $amount;
		    $postData->merchantAttributes = new \stdClass();
	        $postData->merchantAttributes->redirectUrl = PT_Link("aj/wallet/success_ngenius?user_id=") . $pt->user->id;
	        //$postData->merchantAttributes->redirectUrl = "http://192.168.1.108/playtube/aj/wallet/success_ngenius";
		    $order = CreateNgeniusOrder($token->access_token,$postData);
		    if (!empty($order->message)) {
    			$data['status'] = 400;
		        $data['message'] = $order->message;
    		}
    		elseif (!empty($order->errors) && !empty($order->errors[0]) && !empty($order->errors[0]->message)) {
    			$data['status'] = 400;
		        $data['message'] = $order->errors[0]->message;
    		}
    		else{
    			$data['status'] = 200;
		        $data['url'] = $order->_links->payment->href;
    		}
		}
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'success_ngenius') {
	if (!empty($_GET['ref']) && !empty($_GET['user_id'])) {
		$user = $db->objectBuilder()->where('id',PT_Secure($_GET['user_id']))->getOne(T_USERS);
		if (!empty($user)) {
			$pt->user = $user;
			$token = GetNgeniusToken();
    		if (!empty($token->message)) {
    			header('Location: ' . PT_Link('wallet'));
	        	exit();
    		}
    		elseif (!empty($token->errors) && !empty($token->errors[0]) && !empty($token->errors[0]->message)) {
    			header('Location: ' . PT_Link('wallet'));
	        	exit();
    		}
    		else{
    			$order = NgeniusCheckOrder($token->access_token,$_GET['ref']);
    			if (!empty($order->message)) {
	    			header('Location: ' . PT_Link('wallet'));
		        	exit();
	    		}
	    		elseif (!empty($order->errors) && !empty($order->errors[0]) && !empty($order->errors[0]->message)) {
	    			header('Location: ' . PT_Link('wallet'));
		        	exit();
	    		}
	    		else{
	    			if ($order->_embedded->payment[0]->state == "CAPTURED") {
						$amount = PT_Secure($order->amount->value);
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
	    			}
	    		}
    		}
		}
	}
	$url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
	header('Location: ' . $url);
    exit();
}
if ($first == 'get_aamarpay') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0 && !empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['phone'])) {
		$amount   = (int)PT_Secure($_POST[ 'amount' ]);
		$name   = PT_Secure($_POST[ 'name' ]);
		$email   = PT_Secure($_POST[ 'email' ]);
		$phone   = PT_Secure($_POST[ 'phone' ]);
        if ($pt->config->aamarpay_mode == 'sandbox') {
            $url = 'https://sandbox.aamarpay.com/request.php'; // live url https://secure.aamarpay.com/request.php
        }
        else {
            $url = 'https://secure.aamarpay.com/request.php';
        }
        $tran_id = rand(1111111,9999999);
        $fields = array(
            'store_id' => $pt->config->aamarpay_store_id, //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
            'amount' => $amount, //transaction amount
            'payment_type' => 'VISA', //no need to change
            'currency' => 'BDT',  //currenct will be USD/BDT
            'tran_id' => $tran_id, //transaction id must be unique from your end
            'cus_name' => $name,  //customer name
            'cus_email' => $email, //customer email address
            'cus_add1' => '',  //customer address
            'cus_add2' => '', //customer address
            'cus_city' => '',  //customer city
            'cus_state' => '',  //state
            'cus_postcode' => '', //postcode or zipcode
            'cus_country' => 'Bangladesh',  //country
            'cus_phone' => $phone, //customer phone number
            'cus_fax' => 'Not¬Applicable',  //fax
            'ship_name' => '', //ship name
            'ship_add1' => '',  //ship address
            'ship_add2' => '',
            'ship_city' => '',
            'ship_state' => '',
            'ship_postcode' => '',
            'ship_country' => 'Bangladesh',
            'desc' => 'top up wallet',
            'success_url' => PT_Link("aj/wallet/success_aamarpay"), //your success route
            'fail_url' => PT_Link("aj/wallet/cancel_aamarpay"), //your fail route
            'cancel_url' => PT_Link("aj/wallet/cancel_aamarpay"), //your cancel url
            'opt_a' => $pt->user->id,  //optional paramter
            'opt_b' => '',
            'opt_c' => '',
            'opt_d' => '',
            'signature_key' => $pt->config->aamarpay_signature_key //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key
        );
        $fields_string = http_build_query($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $url_forward = str_replace('"', '', stripslashes($result));
        curl_close($ch);
        if ($pt->config->aamarpay_mode == 'sandbox') {
            $base_url = 'https://sandbox.aamarpay.com/'.$url_forward;
        }
        else {
            $base_url = 'https://secure.aamarpay.com/'.$url_forward;
        }
        $data['status'] = 200;
		$data['url'] = $base_url;
	}
}
if ($first == 'success_aamarpay') {
	if (!empty($_POST['amount']) && !empty($_POST['mer_txnid']) && !empty($_POST['opt_a']) && !empty($_POST['pay_status']) && $_POST['pay_status'] == 'Successful') {
		$user = $db->objectBuilder()->where('id',PT_Secure($_POST['opt_a']))->getOne(T_USERS);
		if (!empty($user)) {
			$pt->user = $user;
			$amount   = (int)PT_Secure($_POST['amount']);
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
		}
	}
	$url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
	header('Location: ' . $url);
    exit();
}
if ($first == 'cancel_aamarpay') {
	header('Location: ' . PT_Link('wallet'));
    exit();
}
if ($first == 'get_coinpayments') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount   = (int)PT_Secure($_POST[ 'amount' ]);
		if (empty($pt->config->coinpayments_coin)) {
            $pt->config->coinpayments_coin = 'BTC';
        }
        $result = coinpayments_api_call(array('key' => $pt->config->coinpayments_public_key,
                                              'version' => '1',
                                              'format' => 'json',
                                              'cmd' => 'create_transaction',
                                              'amount' => $amount,
                                              'currency1' => $pt->config->payment_currency,
                                              'currency2' => $pt->config->coinpayments_coin,
                                              'custom' => $amount,
                                              'cancel_url' => PT_Link("aj/wallet/cancel_coinpayments"),
                                              'buyer_email' => $pt->user->email));


        if (!empty($result) && $result['status'] == 200) {
        	$db->insert(T_PENDING_PAYMENTS,array('user_id' => $pt->user->id,
                                                 'payment_data' => $result['data']['txn_id'],
                                                 'method_name' => 'coinpayments',
                                                 'time' => time()));
            $data = array(
                'status' => 200,
                'url' => $result['data']['checkout_url']
            );
        }
        else{
            $data = array(
                'status' => 400,
                'message' => $result['message']
            );
        }
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'cancel_coinpayments') {
	$db->where('user_id', $pt->user->id)->where('method_name', 'coinpayments')->delete(T_PENDING_PAYMENTS);
    header('Location: ' . PT_Link('wallet'));
    exit();
}
use Mantoufan\model\CustomerBelongsTo;
use Mantoufan\model\TerminalType;
if ($first == 'alipay_pay') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount   = (int)PT_Secure($_POST[ 'amount' ]);

		require 'assets/libs/alipay/vendor/autoload.php';
		$alipayGlobal = new Mantoufan\AliPayGlobal(array(
		    'client_id' => $pt->config->alipay_client_id, // Client ID
		    'endpoint_area' => 'EUROPE', // Optional: NORTH_AMERIA / ASIA / EUROPE
		    'merchantPrivateKey' => $pt->config->alipay_private_key, // Merchant Private Key
		    'alipayPublicKey' => $pt->config->alipay_public_key, // Alipay Public Key
		    'is_sandbox' => true, // Whether to use the Sandbox environment
		));
		
		try {
		  $result = $alipayGlobal->payCashier(array(
		      'customer_belongs_to' => CustomerBelongsTo::ALIPAY_CN, // * Users pay with Alipay Chinese wallet，Optional: ALIPAY_CN / ALIPAY_HK / TRUEMONEY / TNG / GCASH / DANA / KAKAOPAY / EASYPAISA / BKASH
		      'notify_url' => PT_Link("aj/wallet/alipay_return"), // Asynchronous callback Url
		      'return_url' => PT_Link("aj/wallet/alipay_return"), // Synchronize callback Url
		      'amount' => array(
		          'currency' => $pt->config->payment_currency, // Currency of payment
		          'value' => $amount, // Amount of payment
		      ),
		      'order' => array(
		          'id' => null, // Order No
		          'desc' => 'Top Up Wallet', // Order Description
		          'extend_info' => array(
		              'china_extra_trans_info' => array(
		                  'business_type' => 'MEMBERSHIP', // Business Type of Order
		              ),
		          ),
		      ),
		      'payment_request_id' => null, // Cash payments could be null
		      'settlement_strategy' => array(
		          'currency' => $pt->config->payment_currency, // Currency used for settlement
		      ),
		      'terminal_type' => TerminalType::WEB, // * Optional: WEB / WAP / APP
		      'os_type' => null, // OS System Type
		  ));
		  if (!empty($result->normalUrl)) {
		  	setcookie('apy',$amount,time() + (10 * 60), "/");
		  	$data = array(
	            'status' => 200,
	            'url' => $result->normalUrl
	        );
		  }
		  elseif (!empty($result->result) && !empty($result->result->resultMessage)) {
		  	$data = array(
	            'status' => 400,
	            'message' => $result->result->resultMessage
	        );
		  }
		  else{
		  	$data = array(
	            'status' => 400,
	            'message' => $lang->error_msg
	        );
		  }
		  // /header('Location: ' . $result->normalUrl); // Return URL of the alipay cashier
		} catch (Exception $e) {
			$data = array(
	            'status' => 400,
	            'message' => $e->getMessage()
	        );
		}
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'alipay_return') {
	try {
	    /* Get Asynchronous Payment Notifications */
	    $notify = $alipayGlobal->getNotify();
	    if (!empty($notify) && !empty($_COOKIE['apy']) && !empty($pt->user)) {
			$amount   = (int)PT_Secure($_COOKIE['apy']);
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
	    }
	} catch (Exception $e) {
	    echo $e->getMessage(); // Output Error
	}
	$url     = PT_Link('wallet');
	if (!empty($_COOKIE['redirect_page'])) {
        $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
        $url = preg_replace('/\((.*?)\)/m', '', $redirect_page);
    }
	header('Location: ' . $url);
    exit();
}
if ($first == 'set') {
	if (!empty($_GET['page_type']) && in_array($_GET['page_type'], array(
        'pro',
        'buy',
        'rent',
        'subscribe'
    ))) {
        if ($_GET['page_type'] == 'pro') {
            setcookie("redirect_page", PT_Link('go_pro'), time() + (60 * 60), '/');
        } else if (($_GET['page_type'] == 'buy' || $_GET['page_type'] == 'rent') && !empty($_GET['id']) && is_numeric($_GET['id'])) {
        	$video = PT_GetVideoByID(PT_Secure($_GET['id']), 1, 1,2);
        	if (!empty($video)) {
        		setcookie("redirect_page", $video->url, time() + (60 * 60), '/');
        	}
        } else if ($_GET['page_type'] == 'subscribe' && !empty($_GET['id']) && is_numeric($_GET['id'])){
        	$user = PT_UserData(PT_Secure($_GET['id']));
        	if (!empty($user)) {
        		setcookie("redirect_page", $user->url, time() + (60 * 60), '/');
        	}
        }
    }
	$data['status']  = 200;
}
if ($first == 'wallet_pay') {
	$data['status'] = 400;
	$price = 0;
	if ($_GET['pay_type'] == 'pro' && !empty($_GET['id']) && is_numeric($_GET['id']) && in_array($_GET['id'], array_keys($pt->pro_packages))) {
		$package = $pt->pro_packages[$_GET['id']];
		$price = $package['price'];

		if ($pt->user->wallet_or < $price) {
			$data['message'] = "<a href='" . PT_Link('wallet') . "'>" . $lang->please_top_up_wallet . "</a>";
		}
		else{
			
			$update = array('is_pro' => 1,'wallet' => $db->dec($price),'pro_type' => $package['id']);
			if ($package['verified_badge'] == 1) {
				$update['verified'] = 1;
			}
		    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
		    if ($go_pro === true) {
		    	if ($pt->config->affiliate_system == 1 && !empty($pt->user->ref_user_id) && $pt->user->ref_type == 'affiliate_pro') {
		    		addNewRefUser($price,$pt->config->amount_percent_ref);
		    	}
		    	$payment_data         = array(
		    		'user_id' => $pt->user->id,
		    		'type'    => 'pro',
		    		'amount'  => $price,
		    		'date'    => date('n') . '/' . date('Y'),
		    		'expire'  => (time() + $package['ex_time'])
		    	);

		    	$db->insert(T_PAYMENTS,$payment_data);
		    	$db->where('user_id',$pt->user->id)->update(T_VIDEOS,array('featured' => 1));
		    	$data['status'] = 200;
		    	$data['url'] = PT_Link('go_pro');
		    }
		}
	}
	if (($_GET['pay_type'] == 'buy' || $_GET['pay_type'] == 'rent') && !empty($_GET['id']) && is_numeric($_GET['id'])) {
		$video = PT_GetVideoByID(PT_Secure($_GET['id']), 1, 1,2);
    	if (!empty($video)) {
    		if (!empty($video->is_movie)) {
    			$payment_data         = array(
		    		'user_id' => $video->user_id,
		    		'video_id'    => $video->id,
		    		'paid_id'  => $pt->user->id,
		    		'admin_com'    => 0,
		    		'currency'    => $pt->config->payment_currency,
		    		'time'  => time()
		    	);
		    	if (!empty($_GET['pay_type']) && $_GET['pay_type'] == 'rent') {
	    			$payment_data['type'] = 'rent';
	    			$total = $video->rent_price;

	    			$admin__com = $pt->config->admin_com_rent_videos;
		    		if ($pt->config->com_type == 1) {
		    			$admin__com = ($pt->config->admin_com_rent_videos * $video->rent_price)/100;
		    			$payment_data['currency'] = $pt->config->payment_currency.'_PERCENT';
		    		}
		    		$balance = $video->rent_price - $admin__com;
	    		}
	    		else{
	    			$total = $video->sell_video;

	    			$admin__com = $pt->config->admin_com_sell_videos;
		    		if ($pt->config->com_type == 1) {
		    			$admin__com = ($pt->config->admin_com_sell_videos * $video->sell_video)/100;
		    			$payment_data['currency'] = $pt->config->payment_currency.'_PERCENT';
		    		}
		    		$balance = $video->sell_video - $admin__com;
	    		}
	    		if ($pt->user->wallet_or < $total) {
					$data['message'] = "<a href='" . PT_Link('wallet') . "'>" . $lang->please_top_up_wallet . "</a>";
					header('Content-Type: application/json');
					echo json_encode($data);
					exit();
				}
	    		$payment_data['amount'] = $total;
	    		$db->insert(T_VIDEOS_TRSNS,$payment_data);
	    		$update = array('wallet' => $db->dec($total));
		        $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
		        $db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' , `verified` = 1 WHERE `id` = '".$video->user_id."'");
    		}
    		else{

	    		if (!empty($_GET['pay_type']) && $_GET['pay_type'] == 'rent') {
	    			$admin__com = $pt->config->admin_com_rent_videos;
		    		if ($pt->config->com_type == 1) {
		    			$admin__com = ($pt->config->admin_com_rent_videos * $video->rent_price)/100;
		    			$pt->config->payment_currency = $pt->config->payment_currency.'_PERCENT';
		    		}
		    		$payment_data         = array(
			    		'user_id' => $video->user_id,
			    		'video_id'    => $video->id,
			    		'paid_id'  => $pt->user->id,
			    		'amount'    => $video->rent_price,
			    		'admin_com'    => $pt->config->admin_com_rent_videos,
			    		'currency'    => $pt->config->payment_currency,
			    		'time'  => time(),
			    		'type' => 'rent'
			    	);
			    	if ($pt->user->wallet_or < $video->rent_price) {
						$data['message'] = "<a href='" . PT_Link('wallet') . "'>" . $lang->please_top_up_wallet . "</a>";
						header('Content-Type: application/json');
						echo json_encode($data);
						exit();
					}
			    	$balance = $video->rent_price - $admin__com;
			    	$update = array('wallet' => $db->dec($video->rent_price));
		            $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
	    		}
	    		else{
	    			$admin__com = $pt->config->admin_com_sell_videos;
		    		if ($pt->config->com_type == 1) {
		    			$admin__com = ($pt->config->admin_com_sell_videos * $video->sell_video)/100;
		    			$pt->config->payment_currency = $pt->config->payment_currency.'_PERCENT';
		    		}

		    		$payment_data         = array(
			    		'user_id' => $video->user_id,
			    		'video_id'    => $video->id,
			    		'paid_id'  => $pt->user->id,
			    		'amount'    => $video->sell_video,
			    		'admin_com'    => $pt->config->admin_com_sell_videos,
			    		'currency'    => $pt->config->payment_currency,
			    		'time'  => time()
			    	);
			    	if ($pt->user->wallet_or < $video->sell_video) {
						$data['message'] = "<a href='" . PT_Link('wallet') . "'>" . $lang->please_top_up_wallet . "</a>";
						header('Content-Type: application/json');
						echo json_encode($data);
						exit();
					}
			    	$balance = $video->sell_video - $admin__com;
			    	$update = array('wallet' => $db->dec($video->sell_video));
		            $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);

	    		}

		    	$db->insert(T_VIDEOS_TRSNS,$payment_data);

		    	$db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' , `verified` = 1 WHERE `id` = '".$video->user_id."'");
		    }
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
		    $data['status'] = 200;
		    $data['url'] = $video->url;
		    if ($pt->config->affiliate_system == 1 && !empty($pt->user->ref_user_id) && $pt->user->ref_type == 'affiliate_buy_rent') {
		    	$price = $video->sell_video;
		    	if (!empty($_GET['pay_type']) && $_GET['pay_type'] == 'rent') {
		    		$price = $video->rent_price;
		    	}
	    		addNewRefUser($price,$pt->config->amount_percent_buy_rent);
	    	}
    	}
	}
	if ($_GET['pay_type'] == 'subscribe' && !empty($_GET['id']) && is_numeric($_GET['id'])){
    	$user = PT_UserData(PT_Secure($_GET['id']));
    	if (!empty($user)) {
            $user_id = $user->id;
    		$admin__com = ($pt->config->admin_com_subscribers * $user->subscriber_price)/100;
    		$pt->config->payment_currency = $pt->config->payment_currency.'_PERCENT';
    		$payment_data         = array(
	    		'user_id' => $user_id,
	    		'video_id'    => 0,
	    		'paid_id'  => $pt->user->id,
	    		'amount'    => $user->subscriber_price,
	    		'admin_com'    => $pt->config->admin_com_subscribers,
	    		'currency'    => $pt->config->payment_currency,
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
	        $update = array('wallet' => $db->dec($user->subscriber_price));
		    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
	        $data['status'] = 200;
		    $data['url'] = $user->url;
		    if ($pt->config->affiliate_system == 1 && !empty($pt->user->ref_user_id) && $pt->user->ref_type == 'affiliate_subscribe') {
	    		addNewRefUser($user->subscriber_price,$pt->config->amount_percent_subscribe);
	    	}
    	}
    }
}
if ($first == 'wallet_update') {
	$data['status'] = 200;
	$data['wallet'] = 0;
	$data['price'] = 0;
	if (!empty($pt->user) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
		$user = PT_UserData(PT_Secure($_GET['id']));
    	if (!empty($user)) {
    		$data['price'] = $user->subscriber_price;
    	}
		$data['wallet'] = $pt->user->wallet_or;
	}
}
if ($first == 'qiwi') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount   = (int)PT_Secure($_POST[ 'amount' ]);

		require 'assets/libs/qiwi/vendor/autoload.php';

		$billPayments = new Qiwi\Api\BillPayments($pt->config->qiwi_private_key);


		$billId = $billPayments->generateId();

		$params = [
		  'publicKey' => $pt->config->qiwi_public_key,
		  'amount' => $amount,
		  'billId' => $billId,
		  'successUrl' => PT_Link("aj/wallet/success_qiwi?user_id=") . $pt->user->id,
		];

		$link = $billPayments->createPaymentForm($params);

		$data['status'] = 200;
		$data['url'] = $link;
	}
	else{
		$data = array(
            'status' => 400,
            'message' => $lang->empty_amount
        );
	}
}
if ($first == 'success_qiwi') {
	if (empty($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
		header('Location: ' . PT_Link('wallet'));
        exit();
	}
	$user = $db->where("id", PT_Secure($_GET["user_id"]))->getOne(T_USERS);
	if (!empty($user)) {
		$sign   = array_key_exists( 'HTTP_X_API_SIGNATURE_SHA256', $_SERVER ) ? stripslashes_deep( $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] ) : '';
		$notice = json_decode( $_POST, true );

		require 'assets/libs/qiwi/vendor/autoload.php';

		$billPayments = new Qiwi\Api\BillPayments($pt->config->qiwi_private_key);
		$result = $billPayments->checkNotificationSignature( $sign, $notice, $pt->config->qiwi_private_key );

		if (!$result) {
			header('Location: ' . PT_Link('wallet'));
			exit();
		}

		if ($notice['bill']['status']['value'] == 'PAID') {
			$amount = $notice['bill']['amount']['value'];
			$db->where('id',$user->id)->update(T_USERS,array('wallet' => $db->inc($amount)));
			$payment_data         = array(
	            'user_id' => $user->id,
	            'paid_id'  => $user->id,
	            'admin_com'    => 0,
	            'currency'    => $notice['bill']['amount']['currency'],
	            'time'  => time(),
	            'amount' => $amount,
	            'type' => 'ad'
	        );
	        $db->insert(T_VIDEOS_TRSNS,$payment_data);
		}
	}
	header('Location: ' . PT_Link('wallet'));
	exit();
}
if ($first == 'payfast') {
	require_once 'assets/libs/payfastSDK/vendor/autoload.php';
	$data['status'] = 400;

	try {

		if (empty($_POST['amount']) || !is_numeric($_POST['amount'])) {
			throw new Exception("amount can not be empty");
		}

		$amount = PT_Secure($_POST['amount']);

		$payfast = new PayFastPayment(
		    [
		        'merchantId' => $pt->config->payfast_merchant_id,
		        'merchantKey' => $pt->config->payfast_merchant_key,
		        'passPhrase' => '',
		        'testMode' => ($pt->config->payfast_mode == 'sandbox') ? true : false
		    ]
		);

		$callback_url = PT_Link("aj/wallet/success_payfast?user_id=" . $pt->user->id . "&amount=" . $amount);

		$data = [
		    // Merchant details
		    'return_url' => $callback_url,
		    'cancel_url' => $callback_url,
		    'notify_url' => $callback_url,
		    'amount' => $amount,
		    'item_name' => 'Wallet'
		];

		$htmlForm = $payfast->custom->createFormFields($data, ['value' => 'PLEASE PAY', 'class' => 'button-cta']);

		$data['status'] = 200;
        $data['html'] = $htmlForm;

	} catch (Exception $e) {
		$data['message'] = $e->getMessage();
	}

    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($first == 'success_payfast') {
	require_once 'assets/libs/payfastSDK/vendor/autoload.php';
	if (empty($_GET['amount']) || empty($_GET['user_id'])) {
		header("Location: " . PT_Link('wallet'));
        exit();
	}

	$amount = PT_Secure($_GET['amount']);

	try {
		$user = PT_UserData(PT_Secure($_GET['user_id']));
		if (!empty($user)) {

		    $payfast = new PayFastPayment(
			    [
			        'merchantId' => $pt->config->payfast_merchant_id,
			        'merchantKey' => $pt->config->payfast_merchant_key,
			        'passPhrase' => '',
			        'testMode' => ($pt->config->payfast_mode == 'sandbox') ? true : false
			    ]
			);

		    $notification = $payfast->notification->isValidNotification($_POST, ['amount_gross' => $amount]);
		    if($notification === true) {

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

		        header("Location: " . PT_Link('wallet'));
        		exit();

		    } else {
		        header("Location: " . PT_Link('wallet'));
	            exit();
		    }
		}
		else{
			header("Location: " . PT_Link('wallet'));
	        exit();
		}
	} catch(Exception $e) {
	    header("Location: " . PT_Link('wallet'));
        exit();
	}
}
if ($first == 'get_braintree') {
	$data['status'] = 400;

	if ($pt->config->braintree_payment == 'yes') {

		try {
			require_once 'assets/libs/braintree/vendor/autoload.php';

		    $gateway = new Braintree\Gateway([
		        'environment' => $pt->config->braintree_mode,
		        'merchantId' => $pt->config->braintree_merchant_id,
		        'publicKey' => $pt->config->braintree_public_key,
		        'privateKey' => $pt->config->braintree_private_key
		    ]);

		    $clientToken = $gateway->clientToken()->generate();
		    $data['status'] = 200;
		    $data['braintree_token'] = $clientToken;
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				$data['message'] = $e->getMessage();
			}
			else{
				$data['message'] = $lang->error_msg;
			}
		} 
	}
	else{
		$data['message'] = $lang->braintree_not_active;
	}

}
if ($first == 'braintree') {
	$data['status'] = 400;
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0 && !empty($_POST['nonce'])) {
		require_once 'assets/libs/braintree/vendor/autoload.php';

		$gateway = new Braintree\Gateway([
		    'environment' => $pt->config->braintree_mode,
		    'merchantId' => $pt->config->braintree_merchant_id,
		    'publicKey' => $pt->config->braintree_public_key,
		    'privateKey' => $pt->config->braintree_private_key
		]);


		$amount = $_POST["amount"];
		$nonce = $_POST["nonce"];

		$result = $gateway->transaction()->sale([
		    'amount' => $amount,
		    'paymentMethodNonce' => $nonce,
		    'options' => [
		        'submitForSettlement' => true
		    ]
		]);

		if ($result->success || !is_null($result->transaction)) {
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

			$data['status'] = 200;
			$data['url'] = PT_Link('wallet');

		} else {
		    $errorString = "";

		    foreach($result->errors->deepAll() as $error) {
		        $errorString .= 'Error: ' . $error->code . ": " . $error->message . "\n";
		    }

		    $data['message'] = $errorString;
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}