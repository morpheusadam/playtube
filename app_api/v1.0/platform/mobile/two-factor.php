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


if (empty($_POST['code'])) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'code can not be empty'
        )
    );
} 
elseif (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'user_id can not be empty'
        )
    );
}
else{
    $code = md5($_POST['code']);
    $db->where("email_code", $code);
    $db->where("id", PT_Secure($_POST['user_id']));
    $login = $db->getOne(T_USERS);
    if (!empty($login)) {

        if (!empty($_POST['device_id'])) {
            $db->where('id',$login->id)->update(T_USERS,array('device_id' => PT_Secure($_POST['device_id'])));
        }
        $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
        $platforms           = array('phone','web');
        foreach ($platforms as $platform_name) {
            $insert_data     = array(
                'user_id'    => $login->id,
                'session_id' => $session_id,
                'time'       => time(),
                'platform'   => $platform_name
            );

            $insert = $db->insert(T_SESSIONS, $insert_data);
        }

        $response_data       = array(
            'api_status'     => '200',
            'api_version'    => $api_version,
            'data'           => array(
                'session_id' => openssl_encrypt($session_id, "AES-128-ECB", $siteEncryptKey),
                'message'    => 'Successfully logged in, Please wait.',
                'user_id'    => openssl_encrypt($login->id, "AES-128-ECB", $siteEncryptKey),
                'cookie'     => openssl_encrypt($session_id, "AES-128-ECB", $siteEncryptKey)
            ) 
        );
    }
    else{
        $response_data           = array(
            'api_status'         => '400',
            'api_version'        => $api_version,
            'errors'             => array(
                'error_id'       => '3',
                'error_text'     => 'Wrong code'
            ) 
        );
    }
} 
