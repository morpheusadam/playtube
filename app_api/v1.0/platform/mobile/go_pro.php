<?php
require 'assets/includes/paypal_config.php';
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

$pkgs         = array('pro');
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
else if ($pt->config->go_pro != 'on') {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'Go pro not available at this time'
        )
	);
}
else if (empty($_POST['paymentId']) || empty($_POST['PayerID']) || empty($_POST['status']) || $_POST['status'] != 'success' || empty($_POST['pkg']) || !in_array($_POST['pkg'], $pkgs)) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '3',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else if ($pt->user->is_pro) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '4',
            'error_text' => 'Your profile already upgraded.'
        )
    );
}
else{

	$payment_currency = $pt->config->payment_currency;
	$payer        = new Payer();
	$item         = new Item();
	$itemList     = new ItemList();
	$details      = new Details();
	$amount       = new Amount();
	$transaction  = new Transaction();
	$redirectUrls = new RedirectUrls();
	$payment      = new Payment();
	
	$payer->setPaymentMethod('paypal');
	$sum          = intval($pt->config->pro_pkg_price);


	$paymentId = PT_Secure($_POST['paymentId']);
	$PayerID   = PT_Secure($_POST['PayerID']);
	$payment   = Payment::get($paymentId, $paypal);
    $execute   = new PaymentExecution();
    $execute->setPayerId($PayerID);
    $error = '';

    try{
        $result = $payment->execute($execute, $paypal);
    }

    catch (Exception $e) {
    	$error = json_decode($e->getData());

        if (empty($error)) {
            $error = json_decode($e->getCode());
        }
    }

    if (empty($error)) {
    	$update = array('is_pro' => 1,'verified' => 1);
	    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
	    if ($go_pro === true) {
	    	$pkg_type             = PT_Secure($_POST['pkg']);
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
    else{
    	$response_data       = array(
	        'api_status'     => '400',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '5',
	            'error_text' => $error
	        )
	    );
    }
}