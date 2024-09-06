<?php
if($first == 'update_info'){
    PT_RunInBackground(array('status' => 200));
    $is_there_scheduled = $db->where('privacy',0,'!=')->where('publication_date',0,'!=')->where('publication_date',time(),'<=')->update(T_VIDEOS,array('privacy' => 0));
}
if ($first == 'download_user_info' && IS_LOGGED) {
    $data['status'] = 200;
    if(!empty($pt->user->info_file)){
       // Get parameters
       $file = $pt->user->info_file;
       $filepath = $file; // upload/files/2019/20/adsoasdhalsdkjalsdjalksd.html

       // Process download
       if(file_exists($filepath)) {
           header('Content-Description: File Transfer');
           header('Content-Type: application/octet-stream');
           // rename the file to username
           header('Content-Disposition: attachment; filename="'.$pt->user->username.'.html"');
           header('Expires: 0');
           header('Cache-Control: must-revalidate');
           header('Pragma: public');
           header('Content-Length: ' . filesize($filepath));
           flush(); // Flush system output buffer
           readfile($filepath);
           // delete the file
           unlink($filepath);
           // remove user data
          $db->where('id', $pt->user->id)->update(T_USERS, array('info_file' => ''));
          header("Location: " . PT_Link(''));
           exit;
       }
    }
    header("Location: " . PT_Link(''));
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}

if (empty($_POST['user_id']) || !IS_LOGGED) {
    exit("Undefined Dolphin.");
}

$is_owner = false;
if ($_POST['user_id'] == $user->id || PT_IsAdmin()) {
    $is_owner = true;
}
if ($first == 'change_price') {
    if (!empty($_POST['subscriber_price']) && (!is_numeric($_POST['subscriber_price']) || $_POST['subscriber_price'] < 0)) {
        $errors[] = $error_icon . $lang->please_check_details;
    }
    if (empty($errors)) {
        $update_data = array();
        $update_data['subscriber_price'] = 0;
        if ($pt->config->payed_subscribers == 'on' && !empty($_POST['subscriber_price']) && is_numeric($_POST['subscriber_price']) && $_POST['subscriber_price'] > 0 && canUseFeature($_POST['user_id'],'who_can_payed_subscribers')) {
            $update_data['subscriber_price'] = PT_Secure($_POST['subscriber_price']);
        }
        if ($is_owner == true) {
            $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
        }
        $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated
                );
    }
    
}

if ($first == 'save_ads') {
    if (!empty($_POST['total_ads']) && !is_numeric($_POST['total_ads'])) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 
    else{
        $update_data = array();
        $update_data['total_ads'] = 0;
        if (!empty($_POST['total_ads']) && is_numeric($_POST['total_ads']) && $_POST['total_ads'] > 0) {
            $update_data['total_ads'] = PT_Secure($_POST['total_ads']);
        }
        if ($is_owner == true) {
            $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
            if ($update){ 

                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated
                );
            }
        }
    }
    
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
            if (!empty($_POST['donation_paypal_email'])) {
                if (!filter_var($_POST['donation_paypal_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $error_icon . $lang->email_invalid_characters;
                }
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
                    if ($user_data->admin != 1) {
                        $db->where('user_id',$user_data->id)->update(T_VIDEOS,array('featured' => 0));
                    }
                }
            }
            $wallet = $user_data->wallet;
            if (isset($_POST['wallet']) && PT_IsAdmin()) {
                if (is_numeric($_POST['wallet'])) {
                    $wallet = $_POST['wallet'];
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
            $age = $user_data->age;
            $age_changed = $user_data->age_changed;
            if (($_POST['age'] >= 0 && $_POST['age'] < 100) && $age != $_POST['age']) {
                if (!PT_IsAdmin()) {
                    if ($user_data->age_changed > 1) {
                        $errors[] = $error_icon . $lang->not_allowed_change_age;
                    } else {
                        $age = PT_Secure($_POST['age']);
                        $age_changed = $user_data->age_changed + 1;
                    }
                } else {
                    $age = PT_Secure($_POST['age']);
                }
            }
            
            if (empty($errors)) {
                $newsletters = 0;
                if (!empty($_POST['newsletters']) && in_array($_POST['newsletters'], array(1,2))) {
                    if ($_POST['newsletters'] == 1) {
                        $newsletters = 0;
                    }
                    if ($_POST['newsletters'] == 2) {
                        $newsletters = 1;
                    }
                }
                

                $update_data = array(
                    'username' => PT_Secure($_POST['username']),
                    'gender' => PT_Secure($gender),
                    'country_id' => PT_Secure($_POST['country']),
                    'active' => PT_Secure($active),
                    'admin' => PT_Secure($type),
                    'is_pro' => $is_pro,
                    'age' => $age,
                    'wallet' => $wallet,
                    'age_changed' => $age_changed,
                    'newsletters' => $newsletters,
                    'donation_paypal_email' => PT_Secure($_POST['donation_paypal_email'])
                );
                

                $show_modal = false;

                if ($pt->config->validation == 'on' && !empty($_POST['email']) && $user_data->email != $_POST['email']) {
                    $code = rand(111111, 999999);
                    $hash_code = md5($code);
                    $update_data = array('email_code' => $hash_code);
                    $db->where('id', $user_data->id);
                    $update = $db->update(T_USERS, $update_data);
                    $message = "Your confirmation code is: $code";
                    $send_email_data = array(
                        'from_email' => $pt->config->email,
                        'from_name' => $pt->config->name,
                        'to_email' => $_POST['email'],
                        'to_name' => $user_data->name,
                        'subject' => 'Please verify that it’s you',
                        'charSet' => 'UTF-8',
                        'message_body' => $message,
                        'is_html' => true
                    );
                    $send_message = PT_SendMessage($send_email_data);
                    $send_message = true;
                    if ($send_message) {
                        $show_modal = true;
                        $success = $success_icon . $lang->email_sent;
                        $update_data['new_email'] = PT_Secure($_POST['email']);
                    }
                }
                else{
                    $update_data['email'] = PT_Secure($_POST['email']);
                }
                

                // user max upload 
                $limit_array = array('0','2000000','6000000','12000000','24000000','48000000','96000000','256000000','512000000','1000000000','10000000000','unlimited');
                if (isset($_POST['user_upload_limit']) && PT_IsAdmin()) {
                    if (in_array($_POST['user_upload_limit'], $limit_array)) {
                        $update_data['user_upload_limit'] = PT_Secure($_POST['user_upload_limit']);
                    } 
                }
                
                // user max upload 
                if (PT_IsAdmin()) {
                    if (!empty($_POST['suspend_upload']) && in_array($_POST['suspend_upload'], array('suspend','unsuspend'))) {
                        if ($_POST['suspend_upload'] == 'suspend') {
                            $update_data['suspend_upload'] = 1;
                        }
                        elseif ($_POST['suspend_upload'] == 'unsuspend') {
                            $update_data['suspend_upload'] = 0;
                        }
                    }
                    if (!empty($_POST['suspend_import']) && in_array($_POST['suspend_import'], array('suspend','unsuspend'))) {
                        if ($_POST['suspend_import'] == 'suspend') {
                            $update_data['suspend_import'] = 1;
                        }
                        elseif ($_POST['suspend_import'] == 'unsuspend') {
                            $update_data['suspend_import'] = 0;
                        }
                    }
                }
              
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
                            'message' => $success_icon . $lang->setting_updated,
                            'show_modal' => $show_modal
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
            $category = array();
            if (!empty($_POST['fav_category'])) {
                
                foreach ($_POST['fav_category'] as $key => $value) {
                    if (in_array($value, array_keys(get_object_vars($pt->categories)))) {
                        $category[] = PT_Secure($value);
                    }
                } 
            }

            if (!empty($category)) {
                $category = json_encode($category);
            }
            else{
                $category = '';
            }
            $google_tracking_code = '';
            if ($pt->config->pro_google == 'on' && $user_data->is_pro && !empty($_POST['google_tracking_code'])) {
                $_POST['google_tracking_code'] = strip_tags($_POST['google_tracking_code']);
                $_POST['google_tracking_code'] = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_POST['google_tracking_code']);
                $_POST['google_tracking_code'] = htmlspecialchars($_POST['google_tracking_code']);
                $google_tracking_code = PT_Secure($_POST['google_tracking_code']);
            }
            $update_data = array(
                'first_name' => PT_Secure($_POST['first_name']),
                'last_name' => PT_Secure($_POST['last_name']),
                'about' => PT_Secure($_POST['about']),
                'facebook' => PT_Secure($_POST['facebook']),
                'google' => PT_Secure($_POST['google']),
                'twitter' => PT_Secure($_POST['twitter']),
                'instagram' => PT_Secure($_POST['instagram']),
                'fav_category' => $category,
                'google_tracking_code' => $google_tracking_code,
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
        if (((!PT_IsAdmin() || (PT_IsAdmin() && $user_data->admin == 1)) && empty($_POST['current_password'])) || empty($_POST['new_password']) || empty($_POST['confirm_new_password'])) {
            $errors[] = $error_icon . $lang->please_check_details;
        } else {
            if (!PT_IsAdmin() || (PT_IsAdmin() && $user_data->admin == 1)) {
                $password = $_POST['current_password'];
                $hash                = 'sha1';
                if (strlen($user_data->password) == 60) {
                    $hash = 'password_hash';
                }
                $logged = false;
                if ($hash == 'password_hash') {
                    if (password_verify(PT_Secure($password), $user_data->password)) {
                        $logged = true;
                    }
                    else{
                        $errors[] = $error_icon . $lang->current_password_dont_match;
                    }
                } else {
                    $login_password = $hash(PT_Secure($password));
                    if ($user_data->password != $login_password) {
                        $errors[] = $error_icon . $lang->current_password_dont_match;
                    }
                }
            }

                
            if (strlen($_POST['new_password']) < 4) {
                $errors[] = $error_icon . $lang->password_is_short;
            }
            if ($_POST['new_password'] != $_POST['confirm_new_password']) {
                $errors[] = $error_icon . $lang->new_password_dont_match;
            }
            if (empty($errors)) {
                $update_data = array(
                    'password' => password_hash(PT_Secure($_POST['new_password']), PASSWORD_DEFAULT)
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
            if (!empty($user_data->ex_avatar) && $user_data->ex_avatar != 'upload/photos/d-avatar.jpg' && $user_data->ex_avatar != 'upload/photos/f-avatar.png') {
                PT_DeleteFromToS3($user_data->ex_avatar);
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
            $file_upload = PT_ShareFile($file_info,2);
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

        $hash                = 'sha1';
        if (strlen($user_data->password) == 60) {
            $hash = 'password_hash';
        }
        if ($hash == 'sha1') {
            if ($user_data->password != sha1($_POST['current_password'])) {
                $errors[] = $error_icon . $lang->current_password_dont_match;
            }
        }
        else{
            if (!password_verify($_POST['current_password'], $user_data->password)) {
                $errors[] = $error_icon . $lang->current_password_dont_match;
            }
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

if ($first == 'request-withdrawal') {
    $error    = none;
    if (empty($_POST['withdraw_method']) || !in_array($_POST['withdraw_method'], array_keys($pt->config->withdrawal_payment_method)) || $pt->config->withdrawal_payment_method[$_POST['withdraw_method']] != 1) {
        $error = $lang->please_select_payment_method;
    }
    elseif ($_POST['withdraw_method'] == 'bank') {
        if (empty($_POST['iban']) || empty($_POST['country']) || empty($_POST['full_name']) || empty($_POST['swift_code']) || empty($_POST['address'])) {
            $error = $lang->please_check_details;
        }
    }
    elseif ($_POST['withdraw_method'] == 'paypal') {
        if (empty($_POST['paypal_email'])) {
            $error = $lang->please_check_details;
        } elseif (!empty($_POST['paypal_email']) && !filter_var($_POST['paypal_email'], FILTER_VALIDATE_EMAIL)) {
            $error = $lang->email_invalid_characters;
        }
    }
    else {
        if (empty($_POST['transfer_to'])) {
            $error = $lang->please_check_details;
        }
    }

    
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

    else if ($user->balance_or < $_POST['amount']) {
        $error = $lang->please_check_details;
    }

    else{

        if(empty($_POST['amount']) || !is_numeric($_POST['amount'])){
            $error = $lang->please_check_details;
        }

        else if($_POST['amount'] < $pt->config->m_withdrawal){
            $error = $lang->invalid_amount_value_withdrawal . " $currency" . $pt->config->m_withdrawal;
        }
    }

    if (empty($error)) {
        $insert_array = array('type' => PT_Secure($_POST['withdraw_method']));

        if (!empty($_POST['paypal_email']) && $_POST['withdraw_method'] == 'paypal') {
            $insert_array['transfer_info']       = PT_Secure($_POST['paypal_email']);
        }
        else if ($_POST['withdraw_method'] == 'bank' && !empty($_POST['iban']) && !empty($_POST['country']) && !empty($_POST['full_name']) && !empty($_POST['swift_code']) && !empty($_POST['address'])) {
            $insert_array['iban']       = PT_Secure($_POST['iban']);
            $insert_array['country']    = PT_Secure($_POST['country']);
            $insert_array['full_name']  = PT_Secure($_POST['full_name']);
            $insert_array['swift_code'] = PT_Secure($_POST['swift_code']);
            $insert_array['address']    = PT_Secure($_POST['address']);
        }
        else{
            $insert_array['transfer_info']       = PT_Secure($_POST['transfer_to']);
        }
        $insert_array['user_id']       = $user_id;
        $insert_array['amount']       = PT_Secure($_POST['amount']);
        $insert_array['requested']       = time();
        $insert_array['currency']       = $currency;

        $insert  = $db->insert(T_WITHDRAWAL_REQUESTS,$insert_array);
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
if ($first == 'get_more_subscribers_' && !empty($_POST['id']) && is_numeric($_POST['id'])) {
    $id = PT_Secure($_POST['id']);
    $subscribers_ = $db->rawQuery("SELECT * FROM ".T_SUBSCRIPTIONS." WHERE subscriber_id = '".$user->id."' AND id < '".$id."' ORDER BY id DESC LIMIT 6");
    $html = '';
    if (!empty($subscribers_)) {
        foreach ($subscribers_ as $key => $user_) {
            $user_subscribers_ = PT_UserData($user_->user_id);
            if (!empty($user_subscribers_)) {
                $html .= '<li data_subscriber_id="'.$user_->id.'" class="subscribers_"><a  href="'.$user_subscribers_->url.'" data-load="?link1=timeline&id='.$user_subscribers_->username.'"><img class="header-image subscribers_img_" src="'.$user_subscribers_->avatar.'" alt="'.$user_subscribers_->name.' avatar" />'.substr($user_subscribers_->name, 0,20)."..".'</a></li>';
            }
        }
    }
    $data['status'] = 200;
    $data['html'] = $html;
}
if ($first == 'authy_register') {
    if (empty($_POST['email'])) {
        $data['status'] = 400;
        $data['message'] = $lang->empty_email;
    }
    if (empty($_POST['phone'])) {
        $data['status'] = 400;
        $data['message'] = $lang->empty_phone;
    }
    if (empty($_POST['country_code'])) {
        $data['status'] = 400;
        $data['message'] = $lang->empty_country_code;
    }

    if (empty($data['message'])) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.authy.com/protected/json/users/new');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "user[email]=".$_POST['email']."&user[cellphone]=".$_POST['phone']."&user[country_code]=".$_POST['country_code']);

        $headers = array();
        $headers[] = 'X-Authy-Api-Key: '.$pt->config->authy_token;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $data['status'] = 400;
            $data['message'] = curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result);
        if (!empty($result) && !empty($result->user) && !empty($result->user->id)) {
            $db->where('id', $user->id)->update(T_USERS, ['authy_id' => $result->user->id]);
            $QR = getAuthyQR($result->user->id);
            if (!empty($QR)) {
                $data['qr'] = $QR;
            }
            $data['status'] = 200;
            $data['message'] = $lang->authy_registered;
        }
        else{
            $data['status'] = 400;
            $data['message'] = $result->message;
        }
    }
}
if ($first == 'verify_code') {
    if (empty($_POST['code'])) {
        $data['status'] = 400;
        $data['message'] = $lang->empty_code;
    }
    elseif (empty($_POST['factor_method']) || !in_array($_POST['factor_method'],array('two_factor','google','authy'))) {
        $data['status'] = 400;
        $data['message'] = $lang->select_two_factor_method;
    }

    if (empty($data['message'])) {
        if ($_POST['factor_method'] == 'google') {
            require_once 'assets/libs/google_auth/vendor/autoload.php';
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            if ($google2fa->verifyKey($user->google_secret, $_POST['code'])) {
                $db->where('id', $user->id)->update(T_USERS, ['two_factor' => 1,
                                                              'two_factor_method' => 'google']);
             
                $data['status'] = 200;
                $data['message'] = $success_icon . $lang->setting_updated;
            } else {
                $data['status'] = 400;
                $data['message'] = $lang->wrong_confirm_code;
            }
        }
        elseif ($_POST['factor_method'] == 'authy') {
            if (verifyAuthy($_POST['code'],$user->authy_id)) {
                $db->where('id', $user->id)->update(T_USERS, ['two_factor' => 1,
                                                              'two_factor_method' => 'authy']);
                $data['status'] = 200;
                $data['message'] = $success_icon . $lang->setting_updated;
            }
            else{
                $data['status'] = 400;
                $data['message'] = $lang->wrong_confirm_code;
            }
        }
        else{
            if ($user->email_code == md5($_POST['code'])) {
                $db->where('id', $user->id)->update(T_USERS, ['two_factor' => 1,
                                                              'two_factor_method' => 'two_factor']);
                $data['status'] = 200;
                $data['message'] = $success_icon . $lang->setting_updated;
            }
            else{
                $data['status'] = 400;
                $data['message'] = $lang->wrong_confirm_code;
            }
        }
    }
}
if ($first == 'request_code' && !empty($_POST['factor_method']) && in_array($_POST['factor_method'],array('two_factor'))) {
    $email_code = rand(111111, 999999);
    $hash_code = md5($email_code);
    $update_data = array('email_code' => $hash_code);
    $db->where('id', $user->id);
    $update = $db->update(T_USERS, $update_data);
    $message = "Your confirmation code is: $email_code";
    $send_email_data = array(
        'from_email' => $pt->config->email,
        'from_name' => $pt->config->name,
        'to_email' => $user->email,
        'to_name' => $user->name,
        'subject' => 'Confirmation code',
        'charSet' => 'UTF-8',
        'message_body' => $message,
        'is_html' => true
    );
    $send_message = PT_SendMessage($send_email_data);
    if ($send_message) {
        $data['status'] = 200;
        $data['message'] = $lang->email_sent;
    }
    else{
        $data['status'] = 400;
        $data['message'] = $lang->error_msg;
    }
}
if ($first == 'two_factor' && in_array($_POST['two_factor'],array('0','1'))) {
    $user_id = $user->id;
    if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0) {
        if (PT_IsAdmin()) {
            $user_id = PT_Secure($_POST['user_id']);
        }
    }
    if ($_POST['two_factor'] == '0') {
        $update = $db->where('id', $user_id)->update(T_USERS, array('two_factor' => PT_Secure($_POST['two_factor'])));
    }
    $data['status'] = 200;
    $data['message'] = $success_icon . $lang->setting_updated;
}

if ($first == 'block') {
    if (empty($_POST['user_id']) || !is_numeric($_POST['user_id']) || $_POST['user_id'] < 1 || empty(PT_UserData($_POST['user_id']))) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 
    else {
        $user_id = PT_Secure($_POST['user_id']);
        $check_if_admin = $db->where('id', $user_id)->where('admin', 0,'>')->getValue(T_USERS, 'count(*)');
        if ($check_if_admin == 0) {
            $check_if_block = $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->getValue(T_BLOCK, 'count(*)');
            if ($check_if_block > 0) {
                $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->delete(T_BLOCK);
                $data['message'] = $lang->block;
            }
            else{
                $db->insert(T_BLOCK,array('user_id' => $pt->user->id,
                                      'blocked_id' => $user_id,
                                      'time' => time()));
                $data['message'] = $lang->unblock;
            }
            $data['status'] = 200;
        }
        else{
            $data['status'] = 400;
        }
    }
}

if ($first == 'remove_session') {
    if (!empty($_POST['id'])) {
        $id = PT_Secure($_POST['id']);
    }
    $check_session = $db->where('id', $id)->getOne(T_SESSIONS);
    if (!empty($check_session)) {
        $data['reload'] = false;
        if (($check_session->user_id == $pt->user->id) || PT_IsAdmin()) {
            if ((!empty($_SESSION['user_id']) && $_SESSION['user_id'] == $check_session->session_id) || (!empty($_COOKIE['user_id']) && $_COOKIE['user_id'] == $check_session->session_id)) {
                session_destroy();
                unset($_COOKIE['user_id']);
                setcookie('user_id', null, -1,'/');
                $_SESSION = array();
                unset($_SESSION);
                $data['reload'] = true;
            }
            $delete_session = $db->where('id', $id)->delete(T_SESSIONS);
            if ($delete_session) {
                $data['status'] = 200;
            }
        }
    }
}

if ($first == 'verify_email') {
    $data['status'] = 400;
    if (!empty($_POST['code'])) {
        $code = md5(PT_Secure($_POST['code']));
        $db->where('email_code', $code);
        $user_data = $db->getOne(T_USERS);
        if (!empty($user_data->id) && $user_data->id == $pt->user->id) {
            $update = $db->where('id', $user_data->id)->update(T_USERS, array('email' => $user_data->new_email,
                                                                              'new_email' => ''));
            $data['status'] = 200;
        }
        else{
            $data['message'] = $lang->wrong_code;
        }
    }
    else{
        $data['message'] =  $lang->please_check_details;
    }
}
if ($first == 'info') {
    $data['status'] = 400;
    if (!empty($_POST['my_information']) || !empty($_POST['videos']) || !empty($_POST['subscribe']) || !empty($_POST['posts']) || !empty($_POST['history'])) {
        $pt->user_info = new stdClass();
        if (!empty($_POST['my_information'])) {
            $pt->user_info->setting = $pt->user;
            $pt->user_info->setting->session = PT_GetUserSessions($pt->user->id);
            $pt->user_info->setting->block = GetBlockedUsers();
            $pt->user_info->setting->trans        = $db->where('user_id',$pt->user->id)->where('type','ad','!=')->orderBy('id','DESC')->get(T_VIDEOS_TRSNS);
        }
        if (!empty($_POST['videos'])) {
            $pt->user_info->videos        = $db->where('user_id',$pt->user->id)->orderBy('id','DESC')->get(T_VIDEOS);
        }
        if (!empty($_POST['subscribe'])) {
            $pt->user_info->subscribe = $db->where('subscriber_id', $pt->user->id)->where('id',$pt->blocked_array , 'NOT IN')->get(T_SUBSCRIPTIONS);
        }
        if (!empty($_POST['posts'])) {
            $pt->user_info->posts = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id', 'DESC')->get(T_POSTS);
        }
        if (!empty($_POST['history'])) {
            $blocked_videos = $db->where('user_id',$pt->blocked_array , 'IN')->get(T_VIDEOS,null,'id');
            $blocked_videos_array = array(0);
            foreach ($blocked_videos as $key => $value) {
                $blocked_videos_array[] = $value->id;
            }
            $pt->user_info->history = $db->where('user_id', $pt->user->id)->where('video_id',$blocked_videos_array , 'NOT IN')->orderby('id', 'DESC')->get(T_HISTORY);
        }
        $lang_array['copyright'] = str_replace('{{DATE}}', date('Y'), $lang_array['copyright']);
        $html = PT_LoadPage('settings/includes/user_info');
        if (!file_exists('upload/files/' . date('Y'))) {
            @mkdir('upload/files/' . date('Y'), 0777, true);
        }
        if (!file_exists('upload/files/' . date('Y') . '/' . date('m'))) {
            @mkdir('upload/files/' . date('Y') . '/' . date('m'), 0777, true);
        }

        $folder   = 'files';
        $fileType = 'file';
        $dir         = "upload/files/" . date('Y') . '/' . date('m');
        $hash    = $dir . '/' . PT_GenerateKey() . '_' . date('d') . '_' . md5(time()) . "_file.html";
        $file = fopen($hash, 'w');
        fwrite($file, $html);
        fclose($file);
        $update = $db->where('id', $pt->user->id)->update(T_USERS, array('info_file' => $hash));
        $data['status'] = 200;
        $data['message'] = $lang->file_ready;

    }
    else{
        $errors[] =  $lang->please_check_details;
    }
}

if ($first == 're_cover') {
    $data['status'] = 400;
    if ($is_owner) {
        $user_data = PT_UserData($_POST['user_id']);
        $from_top             = abs($_POST['pos']);
        $cover_image          = $user_data->ex_cover;
        $full_url_image       = $user_data->cover;
        $default_image        = explode('.', $user_data->ex_cover);
        $default_image        = $default_image[0] . '_full.' . $default_image[1];
        $get_default_image    = file_put_contents($default_image, file_get_contents($user_data->full_cover));
        $default_cover_width  = 1200;
        $default_cover_height = 200;
        require_once("assets/libs/thumbncrop.inc.php");
        $tb = new ThumbAndCrop();
        $tb->openImg($default_image);
        $newHeight = $tb->getRightHeight($default_cover_width);
        $tb->creaThumb($default_cover_width, $newHeight);
        $tb->setThumbAsOriginal();
        $tb->cropThumb($default_cover_width, 200, 0, $from_top);
        $tb->saveThumb($cover_image);
        $tb->resetOriginal();
        $tb->closeImg();
        $upload_s3        = PT_UploadToS3($cover_image);
        $data = array(
           'status' => 200,
           'url' => $full_url_image . '?timestamp=' . md5(time())
        );
        $update_data = $db->where('id', $user_data->id)->update(T_USERS, ['last_active' => time()]);
    }
}

if ($first == 'notify') {
    $data['status'] = 400;
    $data['message'] = $lang->error_msg;
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($user_data)) {
        $sub = $db->where('user_id', $user_data->id)->where('subscriber_id', $pt->user->id)->getOne(T_SUBSCRIPTIONS);
        if (!empty($sub)) {
            if ($sub->notify != 1) {
                $db->where('user_id', $user_data->id)->where('subscriber_id', $pt->user->id)->update(T_SUBSCRIPTIONS,['notify' => 1]);
            }
            else{
                $db->where('user_id', $user_data->id)->where('subscriber_id', $pt->user->id)->update(T_SUBSCRIPTIONS,['notify' => 0]);
            }
            $data = [
                'status' => 200,
                'html' => PT_GetNotifyButton($user_data->id)
            ];
        }
    }
}
if ($first == 'privacy') {
    $data['status'] = 400;
    $data['message'] = $lang->error_msg;
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($_POST['show_subscriptions_count']) && in_array($_POST['show_subscriptions_count'], ['yes','no'])) {
        $user_data->privacy->show_subscriptions_count = PT_Secure($_POST['show_subscriptions_count']);
    }
    if (!empty($_POST['who_can_message_me']) && in_array($_POST['who_can_message_me'], ['all','subscribers','no_one'])) {
        $user_data->privacy->who_can_message_me = PT_Secure($_POST['who_can_message_me']);
    }
    if (!empty($_POST['who_can_watch_my_videos']) && in_array($_POST['who_can_watch_my_videos'], ['all','subscribers','only_me'])) {
        $user_data->privacy->who_can_watch_my_videos = PT_Secure($_POST['who_can_watch_my_videos']);
        if ($_POST['who_can_watch_my_videos'] == 'all') {
            $db->where('user_id', $user_data->id)->update(T_VIDEOS, ['privacy' => '0']);
        }
        else if ($_POST['who_can_watch_my_videos'] == 'subscribers') {
            $db->where('user_id', $user_data->id)->update(T_VIDEOS, ['privacy' => '0']);
        }
        else if ($_POST['who_can_watch_my_videos'] == 'only_me') {
            $db->where('user_id', $user_data->id)->update(T_VIDEOS, ['privacy' => '1']);
        }
    }
    $db->where('id', $user_data->id)->update(T_USERS, ['privacy' => json_encode($user_data->privacy)]);
    $data['status'] = 200;
    $data['message'] = $success_icon . $lang->setting_updated;
}

if ($first == 'backup_codes') {
    $codes = $db->where('user_id',$pt->user->id)->getOne(T_BACKUP_CODES);
    $filename = 'backup-codes.txt';
    if (!empty($codes)) {
        $backupCodes = json_decode($codes->codes,true);
        createBackupCodesFile($backupCodes,$filename);
    }
    else{
        $backupCodes = createBackupCodes();
        createBackupCodesFile($backupCodes,$filename);

        $id = $db->insert(T_BACKUP_CODES,[
            'user_id' => $pt->user->id,
            'codes' => json_encode($backupCodes)
        ]);
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');
    exit;
}

header("Content-type: application/json");
if (isset($errors)) {
    echo json_encode(array(
        'errors' => $errors
    ));
    exit();
}
