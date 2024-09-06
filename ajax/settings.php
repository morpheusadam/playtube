<?php

if (empty($_POST['user_id']) || !IS_LOGGED) {
    exit("Undefined Dolphin.");
}

$is_owner = false;
if ($_POST['user_id'] == $user->id || PT_IsAdmin()) {
    $is_owner = true;
}

if ($first == 'general') {
    if (empty($_POST['username']) OR empty($_POST['email'])) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 

    else {
        $user_data = PT_UserData($_POST['user_id']);
        if (!empty($user_data->id)) {
            if ($_POST['email'] != $user_data->email) {
                if (PT_UserEmailExists($_POST['email'])) {
                    $errors[] = $error_icon . $lang->email_exists;
                }
            }
            if ($_POST['username'] != $user_data->username) {
                $is_exist = PT_UsernameExists($_POST['username']);
                if ($is_exist) {
                    $errors[] = $error_icon . $lang->username_is_taken;
                }
            }
            if (in_array($_POST['username'], $pt->site_pages)) {
                $errors[] = $error_icon . $lang->username_invalid_characters;
            }
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $error_icon . $lang->email_invalid_characters;
            }
            if (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 32) {
                $errors[] = $error_icon . $lang->username_characters_length;
            }
            if (!preg_match('/^[\w]+$/', $_POST['username'])) {
                $errors[] = $error_icon . $lang->username_invalid_characters;
            }
            $active = $user_data->active;
            if (!empty($_POST['activation']) && PT_IsAdmin()) {
                if ($_POST['activation'] == '1') {
                    $active = 1;
                } else {
                    $active = 2;
                }
                if ($active == $user_data->active) {
                    $active = $user_data->active;
                }
            }
            $type = $user_data->admin;
            if (!empty($_POST['type']) && PT_IsAdmin()) {
                if ($_POST['type'] == '2') {
                    $type = 1;
                } 

                else if ($_POST['type'] == '1') {
                    $type = 0;
                }
                if ($type == $user_data->admin) {
                    $type = $user_data->admin;
                }
            }

            $is_pro = $user_data->is_pro;
            if (isset($_POST['is_pro']) && PT_IsAdmin()) {
                if ($_POST['is_pro'] == 1) {
                    $is_pro = 1;
                } 

                else if ($_POST['is_pro'] == 0) {
                    $is_pro = 0;
                }
            }
            
            $gender       = 'male';
            $gender_array = array(
                'male',
                'female'
            );
            if (!empty($_POST['gender'])) {
                if (in_array($_POST['gender'], $gender_array)) {
                    $gender = $_POST['gender'];
                }
            }

            $field_data         = array();
            if (!empty($_POST['cf'])) {
                $fields         = $db->where('placement','general')->get(T_FIELDS);
                foreach ($fields as $key => $field) {
                    $field_id   = $field->id;
                    $field->fid = "fid_$field_id";
                    $name       = $field->fid;
                    if (isset($_POST[$name])) {
                        if (mb_strlen($_POST[$name]) > $field->length) {
                            $errors[] = $error_icon . $field->name . ' field max characters is ' . $field->length;
                        }
                        else{
                            $field_data[] = array(
                                $name => $_POST[$name]
                            );
                        } 
                    }
                }
            }
            
            if (empty($errors)) {
                $update_data = array(
                    'username' => PT_Secure($_POST['username'],1),
                    'email' => PT_Secure($_POST['email']),
                    'gender' => PT_Secure($gender),
                    'country_id' => PT_Secure($_POST['country']),
                    'active' => PT_Secure($active),
                    'admin' => PT_Secure($type),
                    'is_pro' => $is_pro
                );
              
                if (!empty($_POST['verified'])) {
                    if ($_POST['verified'] == 'verified') {
                        $verification = 1;
                    } else {
                        $verification = 0;
                    }
                    if ($verification == $user_data->verified) {
                        $verification = $user_data->verified;
                    }
                    $update_data['verified'] = $verification;
                }
                if ($is_owner == true) {
                    $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                    if ($update){ 
                        if (!empty($field_data)) {
                            $insert = PT_UpdateUserCustomData($_POST['user_id'], $field_data);
                        }

                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $lang->setting_updated
                        );
                    }
                }
            }
        }
    }
}

if ($first == 'profile') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
    if (!empty($_POST['cf'])) {
        $fields         = $db->where('placement',array('profile','social'), 'IN')->get(T_FIELDS);
        foreach ($fields as $key => $field) {
            $field_id   = $field->id;
            $field->fid = "fid_$field_id";
            $name       = $field->fid;
            if (isset($_POST[$name])) {
                if (mb_strlen($_POST[$name]) > $field->length) {
                    $errors[] = $error_icon . $field->name . ' field max characters is ' . $field->length;
                }
                else{
                    $field_data[] = array(
                        $name => $_POST[$name]
                    );
                } 
            }
        }
    }

    if (!empty($user_data->id)) {
        if (empty($errors)) {
            $update_data = array(
                'first_name' => PT_Secure($_POST['first_name'],1),
                'last_name' => PT_Secure($_POST['last_name'],1),
                'about' => PT_Secure($_POST['about'],1),
                'facebook' => PT_Secure($_POST['facebook']),
                'google' => PT_Secure($_POST['google']),
                'twitter' => PT_Secure($_POST['twitter']),
            );
            if ($is_owner == true) {
                $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                if ($update) {
                    if (!empty($field_data)) {
                        $insert = PT_UpdateUserCustomData($_POST['user_id'], $field_data);
                    }

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated
                    );
                }
            }
        }
    }
}

if ($first == 'change-pass') {
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($user_data->id)) {
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_new_password'])) {
            $errors[] = $error_icon . $lang->please_check_details;
        } else {
            if ($user_data->password != sha1($_POST['current_password'])) {
                $errors[] = $error_icon . $lang->current_password_dont_match;
            }
            if (strlen($_POST['new_password']) < 4) {
                $errors[] = $error_icon . $lang->password_is_short;
            }
            if ($_POST['new_password'] != $_POST['confirm_new_password']) {
                $errors[] = $error_icon . $lang->new_password_dont_match;
            }
            if (empty($errors)) {
                $update_data = array(
                    'password' => sha1($_POST['new_password'])
                );
                if ($is_owner == true) {
                    $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                    if ($update) {
                       $data = array(
                            'status' => 200,
                            'message' => $success_icon . $lang->setting_updated
                        );
                    }
                }
            }
        }
    }
}

if ($first == 'avatar') {
    $user_data = PT_UserData($_POST['user_id']);
    $update_data = array();
    if (!empty($user_data->id)) {
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file_info = array(
                'file' => $_FILES['avatar']['tmp_name'],
                'size' => $_FILES['avatar']['size'],
                'name' => $_FILES['avatar']['name'],
                'type' => $_FILES['avatar']['type'],
                'crop' => array('width' => 400, 'height' => 400)
            );
            $file_upload = PT_ShareFile($file_info);
            if (!empty($file_upload['filename'])) {
                $update_data['avatar'] = $file_upload['filename'];
            }
        }
        if (!empty($_FILES['cover']['tmp_name'])) {
            $file_info = array(
                'file' => $_FILES['cover']['tmp_name'],
                'size' => $_FILES['cover']['size'],
                'name' => $_FILES['cover']['name'],
                'type' => $_FILES['cover']['type'],
                'crop' => array('width' => 1200, 'height' => 200)
            );
            $file_upload = PT_ShareFile($file_info);
            if (!empty($file_upload['filename'])) {
                $update_data['cover'] = $file_upload['filename'];
            }
        }
    }
    if ($is_owner == true) {
        $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
        if ($update) {
           $data = array(
                'status' => 200,
                'message' => $success_icon . $lang->setting_updated
            );
        }
    }
}

if ($first == 'delete' && $pt->config->delete_account == 'on') {
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($user_data->id)) {
        if ($user_data->password != sha1($_POST['current_password'])) {
            $errors[] = $error_icon . $lang->current_password_dont_match;
        }
        if (empty($errors) && $is_owner == true) {
            $delete = PT_DeleteUser($user_data->id);
            if ($delete) {
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->your_account_was_deleted,
                    'url' => PT_Link('')
                );
            }
        }
    }
}

if ($first == 'video-monetization' && (($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1'))) {
    
    $user_id        = $user->id;
    $video_mon      = ($user->video_mon == 1) ? 0 : 1;
    $update_data    = array(
        'video_mon' => $video_mon
    );

    $db->where('id',$user_id)->update(T_USERS,$update_data);
    $data['status'] = 200;
}

if ($first == 'request-withdrawal' && $pt->config->usr_v_mon == 'on') {

    $error    = none;
    $balance  = $user->balance;
    $user_id  = $user->id;
    $currency = $pt->config->payment_currency;

    // Check is unprocessed requests exits
    $db->where('user_id',$user_id);
    $db->where('status',0);
    $requests = $db->getValue(T_WITHDRAWAL_REQUESTS, 'count(*)');

    if (!empty($requests)) {
        $error = $lang->cant_request_withdrawal;
    }

    else if ($user->balance < $pt->config->m_withdrawal) {
        $error = str_replace("{{BALANCE}}", $balance, $lang->withdrawal_request_amount_is) . " $currency";
    }

    else{

        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error = $lang->please_check_details;
        }

        else if(empty($_POST['amount']) || !is_numeric($_POST['amount'])){
            $error = $lang->please_check_details;
        }

        else if($_POST['amount'] < $pt->config->m_withdrawal){
            $error = $lang->invalid_amount_value_withdrawal . " $currency" . $pt->config->m_withdrawal;
        }
    }

    if (empty($error)) {
        $insert_data    = array(
            'user_id'   => $user_id,
            'amount'    => PT_Secure($_POST['amount']),
            'email'     => PT_Secure($_POST['email']),
            'requested' => time(),
            'currency' => $currency,
        );

        $insert  = $db->insert(T_WITHDRAWAL_REQUESTS,$insert_data);
        if (!empty($insert)) {
            $notif_data = array(
                'recipient_id' => 0,
                'type' => 'with',
                'admin' => 1,
                'time' => time()
            );
            
            pt_notify($notif_data);
            $data['status']  = 200;
            $data['message'] = $lang->withdrawal_request_sent;
        }
    }

    else{
        $data['status']  = 400;
        $data['message'] = $error;
    }
}

header("Content-type: application/json");
if (isset($errors)) {
    echo json_encode(array(
        'errors' => $errors
    ));
    exit();
}
