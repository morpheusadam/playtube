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
	$types = array('pro','subscribe','buy_video','wallet');
	if (!empty($_POST['type']) && in_array($_POST['type'], $types) && !empty($_FILES["thumbnail"])) {
		$amount = 0;
		$description = '';
		$mode = '';
		if ($_POST['type'] == 'pro') {
			$amount = intval($pt->config->pro_pkg_price);
	        $description = 'Pro Member';
	        $mode = 'pro';
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
			$user_id = PT_Secure($_POST['subscribe_id']);
			$user = PT_UserData($user_id);
			if (empty($user->subscriber_price) || !is_numeric($user->subscriber_price)) {
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '5',
			            'error_text' => 'subscribe price not found'
			        )
				);
				echo json_encode($response_data, JSON_PRETTY_PRINT);
    			exit();
			}
			$amount = $user->subscriber_price;
			$description = 'Subscribe';
	        $mode = 'subscribe';
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
				$amount = $video->rent_price;
				$mode = 'rent';
			}
			else{
				$amount = $video->sell_video;
				$mode = 'pay';
			}
			$description = 'Pay to see video';
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
			$amount = PT_Secure($_POST['amount']);
			$description = 'Wallet';
	        $mode = 'wallet';
		}


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
                                                   'mode'         => $mode));
            if (!empty($insert_id)) {
                $response_data     = array(
		            'api_status'   => '200',
		            'api_version'  => $api_version,
		            'message' => "Your request has been successfully sent, we will notify you once it's approved",
		        );
            }
            else{
            	$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '7',
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
		            'error_id' => '6',
		            'error_text' => 'invalid image'
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
	            'error_text' => 'type , thumbnail can not be empty'
	        )
		);
	}
}