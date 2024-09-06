<?php
use AppleSignIn\ASDecoder;
$response_data   = array(
    'api_status' => 400
);
$required_fields = array(
    'access_token',
    'provider'
);
foreach ($required_fields as $key => $value) {
    if (empty($_POST[$value]) && empty($error_code)) {

        $error_code    = 3;
        $error_message = $value . ' (POST) is missing';
    }
}
if (!empty($error_code)) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else {
	$social_id          = 0;
    $access_token       = $_POST['access_token'];
    $provider           = $_POST['provider'];
    if ($provider == 'facebook') {
    	$get_user_details = connect_to_url("https://graph.facebook.com/me?fields=email,id,name,age_range&access_token={$access_token}");
    	$json_data = json_decode($get_user_details);
    	if (!empty($json_data->error)) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => $json_data->error->message
                )
            );
    	} else if (!empty($json_data->id)) {
    		$social_id = $json_data->id;
    		$social_email = $json_data->email;
    		$social_name = $json_data->name;
    		if (empty($social_email)) {
    			$social_email = 'fb_' . $social_id . '@facebook.com';
    		}
    	}
    } else if ($provider == 'google') {
        
		$get_user_details = connect_to_url("https://oauth2.googleapis.com/tokeninfo?id_token={$access_token}");
		$json_data = json_decode($get_user_details);
		if (!empty($json_data->error)) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => $json_data->error
                )
            );
    	} else {
            $social_id = $json_data->sub;
            $social_email = $json_data->email;
            $social_name = $json_data->sub;
            if (empty($social_email)) {
                $social_email = 'go_' . $social_id . '@google.com';
            }
            // if( !empty($json_data->picture) ){
            //     $userData['avatar'] = $json_data->picture;
            // }
            // if( !empty($json_data->given_name) ){
            //     $userData['first_name'] = $json_data->given_name;
            // }
            // if( !empty($json_data->family_name) ){
            //     $userData['last_name'] = $json_data->family_name;
            // }
    		// $social_id = $json_data->id;
    		// $social_email = $json_data->emails[0]->value;
    		// $social_name = $json_data->displayName;
    		// if (empty($social_email)) {
    		// 	$social_email = 'go_' . $social_id . '@google.com';
    		// }
    	}
    }
    else if ($provider == 'wowonder') {
        $udata = json_decode(base64_decode($access_token));
        if(isset($udata->user_data->username)){

            $social_id = (int)PT_Secure($udata->user_data->user_id);
            $social_email = $udata->user_data->email;
            $social_name = trim($udata->user_data->name);
            if (empty($social_email)) {
                $social_email = 'wowonder_' . $social_id . '@wowonder.com';
            }

        }
    }
    elseif ($provider == 'apple') {
        include_once('assets/libs/apple/vendor/autoload.php');
        try{
            $appleSignInPayload = ASDecoder::getAppleSignInPayload($access_token);
            $social_email = $appleSignInPayload->getEmail();
            $social_id = $social_name = $appleSignInPayload->getUser();
        }
        catch(exception $e){
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '4',
                    'error_text' => $e
                )
            );
        }
    }
    if (!empty($social_id)) {
    	$create_session = false;
    	if (PT_UserEmailExists($social_email) === true) {
    		$create_session = true;
    	} else {
    		$str          = md5(microtime());
            $id           = substr($str, 0, 9);
            $user_uniq_id = (PT_UsernameExists($id) === false) ? $id : 'u_' . $id;
            $password = rand(1111, 9999);
            $insert_data      = array(
                'username'    => PT_Secure($user_uniq_id),
                'password'    => PT_Secure(md5($password)),
                'email'       => PT_Secure($social_email),
                'first_name'  => PT_Secure($social_name),
                'src' => PT_Secure($provider),
                'active'      => 1,
                'email_code'  => PT_Secure(md5(time())),
                'last_active' => time(),
                'registered'  => date('Y') . '/' . intval(date('m'))
            );
            // $re_data      = array(
            //     'username' => PT_Secure($user_uniq_id, 0),
            //     'email' => PT_Secure($social_email, 0),
            //     'password' => PT_Secure(md5($password), 0),
            //     'email_code' => PT_Secure(md5(time()), 0),
            //     'first_name' => PT_Secure($social_name),
            //     'src' => PT_Secure($provider),
            //     'lastseen' => time(),
            //     'social_login' => 1,
            //     'active' => '1'
            // );
            $insert_id = $db->insert(T_USERS, $insert_data);
            if ($insert_id) {
            	$create_session = true;
                
                if (!empty($pt->config->auto_subscribe)) {
                    $get_users = explode(',', $pt->config->auto_subscribe);
                    foreach ($get_users as $key => $username) {
                        $user  = $db->where('username', $username)->getOne(T_USERS);
                        if (!empty($user)) {
                            $insert_data         = array(
                                'user_id' => $user->id,
                                'subscriber_id' => $insert_id,
                                'time' => time(),
                                'active' => 1
                            );
                            $create_subscription = $db->insert(T_SUBSCRIPTIONS, $insert_data);
                            if ($create_subscription) {
                                $current_user = $db->where('id', $insert_id)->getOne(T_USERS);
                                $data = array(
                                    'status' => 200
                                );

                                $notif_data = array(
                                    'notifier_id' => $insert_id,
                                    'recipient_id' => $user->id,
                                    'type' => 'subscribed_u',
                                    'url' => ('@' . $current_user->username),
                                    'time' => time()
                                );

                                pt_notify($notif_data);
                            }
                        } 
                    }
                }
            }
    	}

    	if ($create_session == true) {
            $user = $db->where('email', PT_Secure($social_email))->getOne(T_USERS);
            $insert_id = $user->id;

            $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
            $platforms           = array('phone','web');
            foreach ($platforms as $platform_name) {
                $insert_data         = array(
                    'user_id' => $insert_id,
                    'session_id' => $session_id,
                    'time' => time(),
                    'platform'   => $platform_name
                );
                $insert              = $db->insert(T_SESSIONS, $insert_data);
            }
            if (!empty($_POST['device_id'])) {
                $db->where('id',$user->id)->update(T_USERS,array('device_id' => PT_Secure($_POST['device_id'])));
            }
            

            $response_data       = array(
                'api_status'     => '200',
                'api_version'    => $api_version,
                'data'           => array(
                    'session_id' => $session_id,
                    'message'    => 'Successfully logged in, Please wait.',
                    'user_id'    => $insert_id,
                    'cookie'     => $session_id
                ) 
            );


      //       $_SESSION['user_id'] = $session_id;
      //       setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
      //       header("Location: $site_url");
      //       exit();



    		// $user_id        = Wo_UserIdForLogin($social_email);
    		// $time           = time();
      //       $cookie         = '';
      //       $access_token   = sha1(rand(111111111, 999999999)) . md5(microtime()) . rand(11111111, 99999999) . md5(rand(5555, 9999));
      //       $timezone       = 'UTC';
      //       $create_session = mysqli_query($sqlConnect, "INSERT INTO " . T_APP_SESSIONS . " (`user_id`, `session_id`, `platform`, `time`) VALUES ('{$user_id}', '{$access_token}', 'phone', '{$time}')");
      //       $add_timezone = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `timezone` = '{$timezone}' WHERE `user_id` = {$user_id}");
      //       if ($create_session) {
      //           $response_data = array(
      //               'api_status' => 200,
      //               'timezone' => $timezone,
      //               'access_token' => $access_token,
      //               'user_id' => $user_id,
      //           );
      //       }
    	}
    }
}
