<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+

if (empty($_POST['username']) || empty($_POST['password'])) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Please enter your username and password'
        )
    );
}

else {
    if ($pt->config->prevent_system == 1) {
        if (!CheckCanLogin()) {
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '2',
                    'error_text' => 'Too many login attempts please try again later'
                )
            );
            echo json_encode($response_data, JSON_PRETTY_PRINT);
            exit();
        }
    }
    $username        = PT_Secure($_POST['username']);
    $password        = PT_Secure($_POST['password']);
    //$password_hashed = sha1($password);

    $db->where("(username = ? or email = ? or phone_number = ?)", array($username,$username,$username));
    //$db->where("password", $password_hashed);
    $user = $db->getOne(T_USERS);

    if (!empty($user)) {
        $hash                = 'sha1';
        if (strlen($user->password) == 60) {
            $hash = 'password_hash';
        }

        $logged = false;
        if ($hash == 'password_hash') {
            if (password_verify($password, $user->password)) {
                $logged = true;
            }
        } else {
            $login_password = $hash(PT_Secure($password));
            $is_logged = $db->where("(username = ? or email = ?)", array(
                            $username,
                            $username
                        ))->where("password", $login_password)->getOne(T_USERS);
            if (!empty($is_logged)) {
                $new_password = PT_Secure(password_hash($password, PASSWORD_DEFAULT));
                $db->where('id',$is_logged->id)->update(T_USERS,array('password' => $new_password));
                $logged = true;
            }
        }

        if ($logged) {
            if ($user->active == 0) {
                $response_data       = array(
                    'api_status'     => '304',
                    'api_version'    => $api_version,
                    'success_type'   => 'confirm_account',
                    'errors'         => array(
                        'error_id'   => '2',
                        'error_text' => 'Your account is not active yet, please confirm your E-mail',
                        'email'      => $user->email
                    ) 
                );
            } 
            elseif ($pt->config->two_factor_setting == 'on' && $user->two_factor == 1) {
                $email        = $user->email;
                $db->where("email", $email);
                $user_id = $db->getValue(T_USERS, "id");
                if (!empty($user_id)) {
                   $rest_user = PT_UserData($user_id);
                   $email_code = rand(111111, 999999);
                   $hash_code = md5($email_code);
                   $update_data = array('email_code' => $hash_code);
                   $db->where('id', $rest_user->id);
                   $update = $db->update(T_USERS, $update_data);
                   $update_data['USER_DATA'] = $rest_user;
                   $message = "Your confirmation code is: $email_code";
                   $send_email_data = array(
                        'from_email' => $pt->config->email,
                        'from_name' => $pt->config->name,
                        'to_email' => $email,
                        'to_name' => $rest_user->name,
                        'subject' => 'Confirmation code',
                        'charSet' => 'UTF-8',
                        'message_body' => $message,
                        'is_html' => true
                    );
                    $send_message = PT_SendMessage($send_email_data);
                    if ($send_message) {
                        $response_data     = array(
                            'api_status'   => '200',
                            'api_version'  => $api_version,
                            'success_type' => 'confirmation_email',
                            'user_id' => $user_id,
                            'message'      => 'Please enter your confirmation code.'
                        );
                    }
                }
            }
            
            else {
                if (!empty($_POST['device_id'])) {
                    $db->where('id',$user->id)->update(T_USERS,array('device_id' => PT_Secure($_POST['device_id'])));
                }
                $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
                $platforms           = array('phone','web');
                foreach ($platforms as $platform_name) {
                    $insert_data     = array(
                        'user_id'    => $user->id,
                        'session_id' => $session_id,
                        'time'       => time(),
                        'platform'   => $platform_name
                    );

                    $insert = $db->insert(T_SESSIONS, $insert_data);
                }

                $response_data       = array(
                    'api_status'     => '200',
                    'api_version'    => $api_version,
                    'success_type'   => 'logged_in',
                    'data'           => array(
                        'session_id' => openssl_encrypt($session_id, "AES-128-ECB", $siteEncryptKey),
                        'message'    => 'Successfully logged in, Please wait.',
                        'user_id'    => openssl_encrypt($user->id, "AES-128-ECB", $siteEncryptKey),
                        'cookie'     => openssl_encrypt($session_id, "AES-128-ECB", $siteEncryptKey)
                    ) 
                );
            }

        }
        else{
            $response_data           = array(
                'api_status'         => '400',
                'api_version'        => $api_version,
                'errors'             => array(
                    'error_id'       => '3',
                    'error_text'     => 'Invalid username or password'
                ) 
            );
            if ($pt->config->prevent_system == 1) {
                AddBadLoginLog();
            }
        }
    } 
    else {
        $response_data           = array(
            'api_status'         => '400',
            'api_version'        => $api_version,
            'errors'             => array(
                'error_id'       => '3',
                'error_text'     => 'Invalid username or password'
            ) 
        );
        if ($pt->config->prevent_system == 1) {
            AddBadLoginLog();
        }
    }
}