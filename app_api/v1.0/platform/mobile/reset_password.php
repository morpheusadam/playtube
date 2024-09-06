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


if (empty($_POST['email'])) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'No user email  sent'
        )
    );
} 

else if (PT_UserEmailExists($_POST['email']) === false) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'E-mail is not exists'
        )
    );
} 

else{

    $email = PT_Secure($_POST['email']);
    $db->where("email", $email);
    $user_id = $db->getValue(T_USERS, "id");

    if (!empty($user_id)) {
        $rest_user   = PT_UserData($user_id);
        $email_code  = sha1(time() + rand(111,999));
        $update_data = array('email_code' => $email_code);

        $db->where('id', $rest_user->id);
        $update      = $db->update(T_USERS, $update_data);

        $update_data['USER_DATA'] = $rest_user;
        $send_email_data = array(
            'from_email' => $pt->config->email,
            'from_name' => $pt->config->name,
            'to_email' => $email,
            'to_name' => $rest_user->name,
            'subject' => 'Reset Password',
            'charSet' => 'UTF-8',
            'message_body' => PT_LoadPage('emails/reset-password', $update_data),
            'is_html' => true
        );

        $send_message = PT_SendMessage($send_email_data);
        if ($send_message) {
            $response_data    = array(
                'api_status'  => '200',
                'api_version' => $api_version,
                'data'        => array(
                    'email'   => $email,
                    'message' => 'A reset password link has been sent to your e-mail address'
                )
            );
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => 'E-mail not sent please try again later'
                )
            );
        }
    }
} 
