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
else if ($pt->user->verified) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '7',
            'error_text' => 'Your account already verified'
        )
	);
}
else{
	$error          = false;
	$user_id        = $pt->user->id;
	$request_exists = ($db->where('user_id',$user_id)->getValue(T_VERIF_REQUESTS,'count(*)'));
	$post           = (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['message']) || empty($_FILES['identity']));
	$image = getimagesize($_FILES["identity"]["tmp_name"]);

	if ($request_exists == 0) {
		if (!empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['message']) && !empty($_FILES['identity'])) {
			if (($_FILES["identity"]["size"] < $pt->config->max_upload || $pt->config->max_upload == 0)) {
				if ((strlen($_POST['first_name']) > 4 || strlen($_POST['first_name']) < 32) && (strlen($_POST['last_name']) > 4 || strlen($_POST['last_name']) < 32)) {
					if (in_array($image[2], array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP))) {
			            $file_info = array(
				            'file' => $_FILES['identity']['tmp_name'],
				            'size' => $_FILES['identity']['size'],
				            'name' => $_FILES['identity']['name'],
				            'type' => $_FILES['identity']['type']
				        );

				        $upload          = PT_ShareFile($file_info);
				    	$re_data         = array(
				            'user_id'    => $user_id,
				            'name'       => PT_Secure($_POST['first_name']) . ' ' . PT_Secure($_POST['last_name']),
				            'message'    => PT_Secure($_POST['message']),
				            'time'       => time(),
				            'media_file' => $upload['filename']
				        );

				    	$insert = $db->insert(T_VERIF_REQUESTS,$re_data);

				    	if ($insert) {
				    		$response_data     = array(
							    'api_status'   => '200',
							    'api_version'  => $api_version,
							    'success_type' => 'verification',
							    'message'      => 'Your request was successfully sent and will be in the near future reviwed!'
							);
				    	}
			        }
			        else{
			        	$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '6',
					            'error_text' => 'The passport/ID picture must be an image'
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
				            'error_text' => 'first_name and last_name must be between 5 / 32'
				        )
					);
				}
			}
			else{
				$max   = pt_size_format($pt->config->max_upload);
	        	$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => 'File is too big, Max upload size is : '.$max
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
		            'error_text' => 'Bad Request, Invalid or missing parameter'
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
	            'error_text' => 'You can not submit verification request until the previous requests has been accepted / rejected'
	        )
		);
	}





	















}