<?php
use SecurionPay\SecurionPayGateway;
use SecurionPay\Exception\SecurionPayException;
use SecurionPay\Request\CheckoutRequestCharge;
use SecurionPay\Request\CheckoutRequest;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
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
elseif (empty($_POST['type'])) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'type can not be empty'
        )
	);
}
elseif ($_POST['type'] == 'securionpay_token') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = PT_Secure($_POST['amount']);
		require_once('assets/libs/securionpay/vendor/autoload.php');
        $securionPay = new SecurionPayGateway($pt->config->securionpay_secret_key);
        $user_key = rand(1111,9999).rand(11111,99999);

        $checkoutCharge = new CheckoutRequestCharge();
        $checkoutCharge->amount(($amount * 100))->currency('USD')->metadata(array('user_key' => $user_key));

        $checkoutRequest = new CheckoutRequest();
        $checkoutRequest->charge($checkoutCharge);

        $signedCheckoutRequest = $securionPay->signCheckoutRequest($checkoutRequest);
        if (!empty($signedCheckoutRequest)) {
            //$db->where('id',$pt->user->id)->update(T_USERS,array('securionpay_key' => $user_key));
            $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'success',
                'token'    => $signedCheckoutRequest
            );
        }
        else{
            $response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
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
	            'error_id' => '2',
	            'error_text' => 'amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'securionpay_handle') {
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
                        //if ($pt->user->securionpay_key == $value['metadata']['user_key']) {
                            //$db->where('id',$pt->user->id)->update(T_USERS,array('securionpay_key' => ''));
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
					        $response_data     = array(
				                'api_status'   => '200',
				                'api_version'  => $api_version,
				                'success_type' => 'success',
				                'message'    => 'Payment done'
				            );
				            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
                        //}
                    }
                }
            }
        }
    }
    else{
    	$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'charge can not be empty'
	        )
		);
    }
}
elseif ($_POST['type'] == 'authorize') {
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
                    $response_data     = array(
		                'api_status'   => '200',
		                'api_version'  => $api_version,
		                'success_type' => 'success',
		                'message'    => 'Payment done'
		            );
		            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
                }
                else{
                	$error = $lang->error_msg;
                    if ($trans->getErrors() != null) {
                        $error = $trans->getErrors()[0]->getErrorText();
                    }
                    $response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '5',
				            'error_text' => $error
				        )
					);
                }
            }
            else{
            	$trans = $Aresponse->getTransactionResponse();
                $error = $lang->error_msg;
                if ($trans->getErrors() != null) {
                    $error = $trans->getErrors()[0]->getErrorText();
                }
                $response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => $error
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
		            'error_text' => 'something went wrong'
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
	            'error_text' => 'card_number , card_month , card_year , card_cvc , amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'create_yoomoney') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = PT_Secure($_POST['amount']);
		$order_id = uniqid();
		$yoomoney_hash = rand(11111,99999).rand(11111,99999);
		$receiver = $pt->config->yoomoney_wallet_id;
		$successURL = PT_Link("aj/wallet/success_yoomoney"); 
		$form = '<form id="yoomoney_form" method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">    
					<input type="hidden" name="receiver" value="'.$receiver.'"> 
					<input type="hidden" name="quickpay-form" value="donate"> 
					<input type="hidden" name="targets" value="transaction '.$order_id.'">   
					<input type="hidden" name="paymentType" value="PC"> 
					<input type="hidden" name="sum" value="'.$amount.'" data-type="number"> 
					<input type="hidden" name="successURL" value="'.$successURL.'">
					<input type="hidden" name="label" value="'.$yoomoney_hash.'">
				</form>';
	    $db->where('id',$pt->user->id)->update(T_USERS,array('yoomoney_hash' => $yoomoney_hash));
		$response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'success',
            'form'    => $form,
            'url'    => "https://yoomoney.ru/quickpay/confirm.xml",
            'receiver'    => $receiver,
            'quickpay-form'    => $donate,
            'targets'    => 'transaction '.$order_id,
            'paymentType'    => "PC",
            'sum'    => $amount,
            'successURL'    => $successURL,
            'label'    => $yoomoney_hash,
        );
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'success_yoomoney') {
	if (!empty($_POST['notification_type']) && !empty($_POST['operation_id']) && !empty($_POST['amount']) && !empty($_POST['currency']) && !empty($_POST['datetime']) && !empty($_POST['sender']) && !empty($_POST['codepro']) && !empty($_POST['label']) && !empty($_POST['sha1_hash'])) {
		$hash = sha1($_POST['notification_type'].'&'.
		$_POST['operation_id'].'&'.
		$_POST['amount'].'&'.
		$_POST['currency'].'&'.
		$_POST['datetime'].'&'.
		$_POST['sender'].'&'.
		$_POST['codepro'].'&'.
		$pt->config->yoomoney_notifications_secret.'&'.
		$_POST['label']);

		if ($_POST['sha1_hash'] != $hash || $_POST['codepro'] == true || $_POST['unaccepted'] == true) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => 'something went wrong'
		        )
			);
		}
		else{
			if (!empty($_POST['label'])) {
				$user = $db->objectBuilder()->where('yoomoney_hash',PT_Secure($_POST['label']))->getOne(T_USERS);
				if (!empty($user)) {
					$amount = PT_Secure($_POST['amount']);
					$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount),
				                                                         'yoomoney_hash' => ''));
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
		                'success_type' => 'success',
		                'message'    => 'Payment done'
		            );
		            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
				}
				else{
					$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '4',
				            'error_text' => 'user not found'
				        )
					);
				}
			}
		}
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'notification_type , operation_id , amount , currency , datetime , sender , codepro , label , sha1_hash can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'get_fortumo') {
	$hash = rand(11111,55555).rand(11111,55555);
    $db->where('id',$pt->user->id)->update(T_USERS,array('fortumo_hash' => $hash));
    $response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'success_type' => 'success',
        'url'    => 'https://pay.fortumo.com/mobile_payments/'.$pt->config->fortumo_service_id.'?cuid='.$hash
    );
}
elseif ($_POST['type'] == 'success_fortumo') {
	if (!empty($_POST) && !empty($_POST['amount']) && !empty($_POST['status']) && $_POST['status'] == 'completed' && !empty($_POST['cuid']) && !empty($_POST['price'])) {
        $fortumo_hash = PT_Secure($_POST['cuid']);
        $amount = (int) PT_Secure($_POST['price']);
        $user = $db->objectBuilder()->where('fortumo_hash',$fortumo_hash)->getOne('users');
        if (!empty($user)) {
        	$pt->user = $user;
        	$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount),
		                                                         'fortumo_hash' => ''));
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
                'success_type' => 'success',
                'message'    => 'Payment done'
            );
            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
        }
        else{
        	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
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
	            'error_id' => '2',
	            'error_text' => 'amount , status , cuid , price can not be empty'
	        )
		);
    }
}
elseif ($_POST['type'] == 'get_coinbase') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$amount = (int) PT_Secure($_POST['amount']);
		try {
            $coinbase_hash = rand(1111,9999).rand(11111,99999);
            $redirect_url = PT_Link("aj/wallet/success_coinbase")."?coinbase_hash=".$coinbase_hash; 
            $cancel_url = PT_Link("aj/wallet/cancel_coinbase")."?coinbase_hash=".$coinbase_hash; 
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            $postdata =  array('name' => 'Top Up Wallet','description' => 'Top Up Wallet','pricing_type' => 'fixed_price','local_price' => array('amount' => $amount , 'currency' => $pt->config->payment_currency), 'metadata' => array('coinbase_hash' => $coinbase_hash,'amount' => $amount),"redirect_url" => $redirect_url,'cancel_url' => $cancel_url);


            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Cc-Api-Key: '.$pt->config->coinbase_key;
            $headers[] = 'X-Cc-Version: 2018-03-22';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => curl_error($ch)
			        )
				);
            }
            curl_close($ch);

            $result = json_decode($result,true);
            if (!empty($result) && !empty($result['data']) && !empty($result['data']['hosted_url']) && !empty($result['data']['id']) && !empty($result['data']['code'])) {
                $db->where('id', $pt->user->id)->update(T_USERS, array('coinbase_hash' => $coinbase_hash,
                                                                       'coinbase_code' => $result['data']['code']));
                $response_data     = array(
	                'api_status'   => '200',
	                'api_version'  => $api_version,
	                'success_type' => 'success',
	                'url'    => $result['data']['hosted_url']
	            );
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
        catch (Exception $e) {
            $response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
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
	            'error_id' => '2',
	            'error_text' => 'amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'success_coinbase') {
	if (!empty($_POST['coinbase_hash']) && is_numeric($_POST['coinbase_hash'])) {
	    $coinbase_hash = PT_Secure($_POST['coinbase_hash']);
	    $user           = $db->objectBuilder()->where('coinbase_hash',$coinbase_hash)->getOne(T_USERS);

	    if (!empty($user)) {
	    	$pt->user = $user;
	    	$ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges/'.$user->coinbase_code);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Cc-Api-Key: '.$pt->config->coinbase_key;
            $headers[] = 'X-Cc-Version: 2018-03-22';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => curl_error($ch)
			        )
				);
            }
            curl_close($ch);
            $result = json_decode($result,true);

	    	
            if (!empty($result) && !empty($result['data']) && !empty($result['data']['pricing']) && !empty($result['data']['pricing']['local']) && !empty($result['data']['pricing']['local']['amount']) && !empty($result['data']['payments']) && !empty($result['data']['payments'][0]['status']) && $result['data']['payments'][0]['status'] == 'CONFIRMED') {
            	$amount = (int)$result['data']['pricing']['local']['amount'];
            	$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount),
		                                                             'coinbase_hash' => '',
		                                                             'coinbase_code' => ''));
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
	                'success_type' => 'success',
	                'message'    => 'Payment done'
	            );
	            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
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
	    else{
	    	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
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
	            'error_id' => '2',
	            'error_text' => 'coinbase_hash can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'cancel_coinbase') {
	if (!empty($_POST['coinbase_hash']) && is_numeric($_POST['coinbase_hash'])) {
        $coinbase_hash = PT_Secure($_POST['coinbase_hash']);
        $user = $db->where('coinbase_hash',$coinbase_hash)->getOne('users');
        if (!empty($user)) {
            $db->where('id', $user->id)->update('users', array('coinbase_hash' => '',
                                                               'coinbase_code' => ''));
        }
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'success',
            'success'    => 'success'
        );
    }
    else{
    	$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'coinbase_hash can not be empty'
	        )
		);
    }
}
elseif ($_POST['type'] == 'get_ngenius') {
	if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
		$token = GetNgeniusToken();
		if (!empty($token->message)) {
	        $response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => $token->message
		        )
			);
		}
		elseif (!empty($token->errors) && !empty($token->errors[0]) && !empty($token->errors[0]->message)) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '4',
		            'error_text' => $token->errors[0]->message
		        )
			);
		}
		else{
			$amount = (int) PT_Secure($_POST['amount']);
			$postData = new StdClass();
		    $postData->action = "SALE";
		    $postData->amount = new StdClass();
		    $postData->amount->currencyCode = "AED";
		    $postData->amount->value = $amount;
		    $postData->merchantAttributes = new \stdClass();
	        $postData->merchantAttributes->redirectUrl = PT_Link("aj/wallet/success_ngenius");
	        //$postData->merchantAttributes->redirectUrl = "http://192.168.1.108/playtube/aj/wallet/success_ngenius";
		    $order = CreateNgeniusOrder($token->access_token,$postData);
		    if (!empty($order->message)) {
		    	$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => $order->message
			        )
				);
    		}
    		elseif (!empty($order->errors) && !empty($order->errors[0]) && !empty($order->errors[0]->message)) {
    			$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '6',
			            'error_text' => $order->errors[0]->message
			        )
				);
    		}
    		else{
    			$db->where('id',$pt->user->id)->update(T_USERS,array('ngenius_ref' => $order->reference));
    			$response_data     = array(
	                'api_status'   => '200',
	                'api_version'  => $api_version,
	                'success_type' => 'success',
	                'url'    => $order->_links->payment->href
	            );
    		}
		}
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'success_ngenius') {
	if (!empty($_POST['ref'])) {
		$user = $db->objectBuilder()->where('ngenius_ref',PT_Secure($_POST['ref']))->getOne(T_USERS);
		if (!empty($user)) {
			$token = GetNgeniusToken();
    		if (!empty($token->message)) {
    			$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => $token->message
			        )
				);
    		}
    		elseif (!empty($token->errors) && !empty($token->errors[0]) && !empty($token->errors[0]->message)) {
    			$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => $token->errors[0]->message
			        )
				);
    		}
    		else{
    			$order = NgeniusCheckOrder($token->access_token,$user->ngenius_ref);
    			if (!empty($order->message)) {
    				$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '6',
				            'error_text' => $order->message
				        )
					);
	    		}
	    		elseif (!empty($order->errors) && !empty($order->errors[0]) && !empty($order->errors[0]->message)) {
	    			$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '7',
				            'error_text' => $order->errors[0]->message
				        )
					);
	    		}
	    		else{
	    			if ($order->_embedded->payment[0]->state == "CAPTURED") {
						$amount = PT_Secure($order->amount->value);
						$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount),
				                                                             'ngenius_ref' => ''));
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
			                'success_type' => 'success',
			                'message'    => 'Payment done'
			            );
			            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
	    			}
	    			else{
	    				$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '8',
					            'error_text' => 'something went wrong'
					        )
						);
	    			}
	    		}
    		}
		}
		else{
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
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
	            'error_id' => '2',
	            'error_text' => 'ref can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'get_aamarpay') {
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
            'opt_a' => '',  //optional paramter
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
        //$db->where('id',$pt->user->id)->update(T_USERS,array('aamarpay_tran_id' => $tran_id));
        if ($pt->config->aamarpay_mode == 'sandbox') {
            $base_url = 'https://sandbox.aamarpay.com/'.$url_forward;
        }
        else {
            $base_url = 'https://secure.aamarpay.com/'.$url_forward;
        }
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'success',
            'url'    => $base_url
        );
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'amount , name , email , phone can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'success_aamarpay') {
	if (!empty($_POST['amount']) && !empty($_POST['mer_txnid']) && !empty($_POST['pay_status']) && $_POST['pay_status'] == 'Successful') {
		// $user = $db->objectBuilder()->where('aamarpay_tran_id',PT_Secure($_POST['mer_txnid']))->getOne(T_USERS);
		// if (!empty($user)) {
		// 	$pt->user = $user;
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
	        $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'success',
                'message'    => 'Payment done'
            );
            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
		// }
		// else{
		// 	$response_data    = array(
		// 	    'api_status'  => '400',
		// 	    'api_version' => $api_version,
		// 	    'errors' => array(
		//             'error_id' => '3',
		//             'error_text' => 'user not found'
		//         )
		// 	);
		// }
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'amount , mer_txnid , pay_status can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'cancel_aamarpay') {
	//$db->where('id',$pt->user->id)->update(T_USERS,array('aamarpay_tran_id' => ''));
	$response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'success_type' => 'success',
        'success'    => 'success'
    );
}
elseif ($_POST['type'] == 'get_coinpayments') {
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
            $db->where('id',$pt->user->id)->update(T_USERS,array('coinpayments_txn_id' => $result['data']['txn_id']));
            $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'success',
                'url'    => $result['data']['checkout_url']
            );
        }
        else{
        	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => $result['message']
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
	            'error_text' => 'amount can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'check_coinpayments') {
	if (!empty($pt->user->coinpayments_txn_id)) {
        $result = coinpayments_api_call(array('key' => $pt->config->coinpayments_public_key,
                                              'version' => '1',
                                              'format' => 'json',
                                              'cmd' => 'get_tx_info',
                                              'full' => '1',
                                              'txid' => $pt->user->coinpayments_txn_id));
        if (!empty($result) && $result['status'] == 200) {
            if ($result['data']['status'] == -1) {
                $db->where('id',$pt->user->id)->update(T_USERS,array('coinpayments_txn_id' => ''));
                $notif_data = array(
                    'admin' => 1,
                    'recipient_id' => $pt->user->id,
                    'type' => 'coinpayments_canceled',
                    'url' => "wallet",
                    'time' => time()
                );
                
                pt_notify($notif_data);
                $response_data     = array(
	                'api_status'   => '200',
	                'api_version'  => $api_version,
	                'success_type' => 'success',
	                'message'    => 'coinpayments canceled'
	            );
            }
            elseif ($result['data']['status'] == 100) {
				$amount   = $result['data']['checkout']['amountf'];
				$db->where('id',$pt->user->id)->update(T_USERS,array('wallet' => $db->inc($amount),
		                                                             'coinpayments_txn_id' => ''));
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

		        $notif_data = array(
                    'admin' => 1,
                    'recipient_id' => $pt->user->id,
                    'type' => 'coinpayments_approved',
                    'url' => "wallet",
                    'time' => time()
                );
                
                pt_notify($notif_data);
                $response_data     = array(
	                'api_status'   => '200',
	                'api_version'  => $api_version,
	                'success_type' => 'success',
	                'message'    => 'coinpayments approved'
	            );
	            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
            }
        }
        else{
        	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => 'no pending payment'
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
	            'error_text' => 'no pending payment'
	        )
		);
    }
}
elseif ($_POST['type'] == 'cancel_coinpayments') {
	$db->where('id',$pt->user->id)->update(T_USERS,array('coinpayments_txn_id' => ''));
	$response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'success_type' => 'success',
        'success'    => 'success'
    );
}
elseif ($_POST['type'] == 'wallet_pay') {
	if (!empty($_POST['pay_type']) && in_array($_POST['pay_type'], array('pro','buy','rent','subscribe'))) {
		$price = 0;
		if ($_POST['pay_type'] == 'pro') {
			if ($pt->user->wallet < $pt->config->pro_pkg_price) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '2',
			            'error_text' => 'please top up wallet'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
				exit();
			}
			else{
				$price = $pt->config->pro_pkg_price;
				$update = array('is_pro' => 1,'verified' => 1,'wallet' => $db->dec($price));
			    $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
			    if ($go_pro === true) {
			    	$payment_data         = array(
			    		'user_id' => $pt->user->id,
			    		'type'    => 'pro',
			    		'amount'  => $price,
			    		'date'    => date('n') . '/' . date('Y'),
			    		'expire'  => strtotime("+30 days")
			    	);

			    	$db->insert(T_PAYMENTS,$payment_data);
			    	$db->where('user_id',$pt->user->id)->update(T_VIDEOS,array('featured' => 1));
			    	$response_data     = array(
				        'api_status'   => '200',
				        'api_version'  => $api_version,
				        'success_type' => 'success',
				        'success'    => 'success'
				    );
			    }
			}
		}
		if (($_POST['pay_type'] == 'buy' || $_POST['pay_type'] == 'rent')) {
			if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '2',
			            'error_text' => 'id can not be empty'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
				exit();
			}
			$video = PT_GetVideoByID(PT_Secure($_POST['id']), 1, 1,2);
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
			    	if (!empty($_POST['pay_type']) && $_POST['pay_type'] == 'rent') {
		    			$payment_data['type'] = 'rent';
		    			$total = $video->rent_price;
		    		}
		    		else{
		    			$total = $video->sell_video;
		    		}
		    		if ($pt->user->wallet < $total) {
		    			$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '2',
					            'error_text' => 'please top up wallet'
					        )
						);
						echo json_encode($response_data, JSON_PRETTY_PRINT);
						exit();
					}
		    		$payment_data['amount'] = $total;
		    		$db->insert(T_VIDEOS_TRSNS,$payment_data);
		    		$update = array('wallet' => $db->dec($total));
			        $go_pro = $db->where('id',$pt->user->id)->update(T_USERS,$update);
	    		}
	    		else{

		    		if (!empty($_POST['pay_type']) && $_POST['pay_type'] == 'rent') {
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
				    	if ($pt->user->wallet < $video->rent_price) {
				    		$response_data    = array(
							    'api_status'  => '400',
							    'api_version' => $api_version,
							    'errors' => array(
						            'error_id' => '2',
						            'error_text' => 'please top up wallet'
						        )
							);
							echo json_encode($response_data, JSON_PRETTY_PRINT);
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
				    	if ($pt->user->wallet < $video->sell_video) {
				    		$response_data    = array(
							    'api_status'  => '400',
							    'api_version' => $api_version,
							    'errors' => array(
						            'error_id' => '2',
						            'error_text' => 'please top up wallet'
						        )
							);
							echo json_encode($response_data, JSON_PRETTY_PRINT);
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
	            $response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'success_type' => 'success',
			        'success'    => 'success'
			    );
	    	}
	    	else{
	    		$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '3',
			            'error_text' => 'video not found'
			        )
				);
	    	}
		}
		if ($_POST['pay_type'] == 'subscribe'){
			if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '2',
			            'error_text' => 'id can not be empty'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
				exit();
			}
	    	$user = PT_UserData(PT_Secure($_POST['id']));
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
			    $response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'success_type' => 'success',
			        'success'    => 'success'
			    );
	    	}
	    	else{
	    		$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => 'user not found'
			        )
				);
	    	}
	    }
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'pay_type can not be empty'
	        )
		);
	}
}
elseif ($_POST['type'] == 'fluttewave') {
	if (!empty($_POST['transaction_id'])) {
		$txid = $_POST['transaction_id'];

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
        if(!empty($res->status) && $res->status != 'error'){
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
	        $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'success',
                'message'    => 'Payment done'
            );
            $response_data['wallet'] = $db->where('id',$pt->user->id)->getValue(T_USERS,'wallet');
        }
        else{
        	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '2',
		            'error_text' => 'Something went wrong'
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
	            'error_text' => 'transaction_id can not be empty'
	        )
		);
	}
}