<?php
if (!empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = '';
    unset($_SESSION['user_id']);
}
if (!empty($_COOKIE['user_id'])) {
    $_COOKIE['user_id'] = '';
    unset($_COOKIE['user_id']);
    setcookie('user_id', null, -1);
    setcookie('user_id', null, -1, '/');
}
if (!IS_LOGGED && $pt->config->plus_login == 'on' && !empty($pt->config->google_app_ID) && !empty($pt->config->google_app_key) && !empty($_POST['id_token'])) {
    $data['status']   = 400;
    $access_token     = $_POST['id_token'];
    $get_user_details = fetchDataFromURL("https://oauth2.googleapis.com/tokeninfo?id_token={$access_token}");
    $json_data        = json_decode($get_user_details);
    if (!empty($json_data->error)) {
        $data['message'] = $error_icon . $json_data->error;
    } else if (!empty($json_data->kid)) {
        $social_id    = $json_data->kid;
        $social_email = $json_data->email;
        $social_name  = $json_data->name;
        if (empty($social_email)) {
            $social_email = 'go_' . $social_id . '@google.com';
        }
    }
    if (!empty($social_id)) {
        $create_session = false;
        if (PT_UserEmailExists($social_email) === true) {
            $insert_id = $db->where('email', $social_email)->getValue(T_USERS,'id');
            $create_session = true;
        } else {
            $str          = md5(microtime());
            $id           = substr($str, 0, 9);
            $user_uniq_id = (empty($db->where('username', $id)->getValue(T_USERS, 'id'))) ? $id : 'u_' . $id;
            $password     = substr(md5(time()), 0, 9);
            $re_data      = array(
                'username' => PT_Secure($user_uniq_id, 0),
                'email' => PT_Secure($social_email, 0),
                'password' => PT_Secure(sha1($password), 0),
                'email_code' => PT_Secure(sha1($user_uniq_id), 0),
                'first_name' => PT_Secure($social_name),
                'src' => 'Google',
                'active' => '1'
            );
            $re_data['language'] = $pt->config->language;
            if (!empty($_SESSION['lang'])) {
                if (in_array($_SESSION['lang'], $langs)) {
                    $re_data['language'] = $_SESSION['lang'];
                }
            }
            $insert_id = $db->insert(T_USERS, $re_data);
            if (!empty($insert_id)) {
                $create_session = true;
            }
        }
        if ($create_session == true) {

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

            $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
            $insert_data         = array(
                'user_id' => $insert_id,
                'session_id' => $session_id,
                'time' => time()
            );
            $insert              = $db->insert(T_SESSIONS, $insert_data);
            $_SESSION['user_id'] = $session_id;
            setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
            $data['status'] = 200;
            $data['location'] = $site_url;
        } else {
            $data['message'] = $lang->error_msg;
        }
    } else {
        $data['message'] = $lang->error_msg;
    }
}
header("Content-type: application/json");
echo json_encode($data);
exit();