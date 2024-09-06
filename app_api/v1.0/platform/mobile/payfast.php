<?php

require_once 'assets/libs/payfastSDK/vendor/autoload.php';

use PayFast\PayFastPayment;

$types = array('load','wallet');
if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
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

	if ($_POST['type'] == 'load') {
		$response_data['api_status'] = 400;

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

			$callback_url = PT_Link("aj/wallet/success_payfast?user_id=") . $pt->user->id;

			$data = [
			    // Merchant details
			    'return_url' => $callback_url,
			    'cancel_url' => $callback_url,
			    'notify_url' => $callback_url,
			    'amount' => $amount,
			    'item_name' => 'Wallet'
			];

			$htmlForm = $payfast->custom->createFormFields($data, ['value' => 'PLEASE PAY', 'class' => 'button-cta']);

			$response_data['api_status'] = 200;
	        $response_data['html'] = $htmlForm;
	        $response_data['return_url'] = $callback_url;
	        $response_data['cancel_url'] = $callback_url;
	        $response_data['notify_url'] = $callback_url;
	        $response_data['amount'] = $amount;
	        $response_data['item_name'] = 'Wallet';

		} catch (Exception $e) {
			$response_data['message'] = $e->getMessage();
		}
	}
	elseif ($_POST['type'] == 'wallet') {

		$response_data['api_status'] = 400;

		try {
			if (empty($_POST['amount']) || empty($_POST['user_id'])) {
				throw new Exception("amount , user_id can not be empty");
			}

			$amount = PT_Secure($_POST['amount']);

			$user = PT_UserData(PT_Secure($_POST['user_id']));
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

			        $response_data['api_status'] = 200;
			        $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
			        $response_data['message'] = 'paid successful';

			    } else {
			    	$response_data['message'] = 'something went wrong';
			    }
			}
			else{
				$response_data['message'] = 'user not found';
			}
		} catch(Exception $e) {
		    $response_data['message'] = $e->getMessage();
		}
	}
}