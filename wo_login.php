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
require_once('assets/init.php');
decryptConfigData();
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $app_id        = $pt->config->wowonder_app_ID;
    $app_secret    = $pt->config->wowonder_app_key;
    $wowonder_url  = $pt->config->wowonder_domain_uri;
    $code          = $_GET['code'];
    $url           = $wowonder_url . "/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
    $get           = file_get_contents($url);
    $wo_json_reply = json_decode($get, true);
    $access_token  = '';
    if (is_array($wo_json_reply) && isset($wo_json_reply['access_token'])) {
        $access_token    = $wo_json_reply['access_token'];
        $type            = "get_user_data";
        $url             = $wowonder_url . "/api_request?access_token={$access_token}&type={$type}";
        $user_data_json  = file_get_contents($url);
        $user_data_array = json_decode($user_data_json, true);
        if (is_array($user_data_array) && !empty($user_data_array) && isset($user_data_array['user_data'])) {
            $user_data  = $user_data_array['user_data'];
            $user_email = $user_data['email'];
            if (PT_UserEmailExists($user_email) === true) {
                $db->where('email', $user_email);
                $login               = $db->getOne(T_USERS);
                $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
                $insert_data         = array(
                    'user_id' => $login->id,
                    'session_id' => $session_id,
                    'time' => time()
                );
                $insert              = $db->insert(T_SESSIONS, $insert_data);
                $_SESSION['user_id'] = $session_id;
                setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
                header("Location: " . PT_Link(''));
                exit();
            } else {
                $str          = md5(microtime());
                $id           = substr($str, 0, 9);
                $user_uniq_id = (PT_UsernameExists($id) === false) ? $id : 'u_' . $id;
                $first_name   = (isset($user_data['first_name'])) ? PT_Secure($user_data['first_name'], 0) : '';
                $last_name    = (isset($user_data['last_name'])) ? PT_Secure($user_data['last_name'], 0) : '';
                $gender       = (isset($user_data['gender'])) ? PT_Secure($user_data['gender'], 0) : 'male';
                $username     = (PT_Secure($user_data['username']));
                $provider     = ($wowonder_url . "/{$username}");
                $re_data      = array(
                    'username' => PT_Secure($user_uniq_id, 0),
                    'email' => PT_Secure($user_email, 0),
                    'password' => PT_Secure(sha1($user_email), 0),
                    'email_code' => PT_Secure(md5($user_uniq_id), 0),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'gender' => PT_Secure($gender),
                    'active' => '1'
                );
                if (!empty($user_data['avatar'])) {
                    $re_data['avatar'] = PT_Secure(PT_ImportImageFromLogin($user_data['avatar']));
                }
                $insert_id = $db->insert(T_USERS, $re_data);
                if ($insert_id) {
                    $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
                    $insert_data         = array(
                        'user_id' => $insert_id,
                        'session_id' => $session_id,
                        'time' => time()
                    );
                    $insert              = $db->insert(T_SESSIONS, $insert_data);
                    $_SESSION['user_id'] = $session_id;
                    setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
                    header("Location: " . PT_Link(''));
                    exit();
                }
            }
        }
    } else {
        echo 'Error found, please try again later.' . "<a href='" . $pt->config->site_url . "'>Return back</a>";
    }
} else {
    echo "<a href='" . $pt->config->site_url . "'>Return back</a>";
}
?>