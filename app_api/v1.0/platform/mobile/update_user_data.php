<?php

if (!IS_LOGGED) {
    $response_data = array(
        'api_status' => '400',
        'api_version' => $api_version,
        'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
    );
}

if (empty($_POST['settings_type'])) {
    $response_data = array(
        'api_status' => '400',
        'api_version' => $api_version,
        'errors' => array(
            'error_id' => '2',
            'error_text' => 'settings_type is not sent'
        )
    );
}

$response_data['status'] = 400;

$is_owner = false;
if (!empty($user->id)) {
    $is_owner = true;
}

if ($_POST['settings_type'] == 'general') {
    if (empty($_POST['username']) OR empty($_POST['email'])) {
        $errors = $lang->please_check_details;
    } 

    else {
        $user_data = PT_UserData($user->id);
        if (!empty($user_data->id)) {
            if ($_POST['email'] != $user_data->email) {
                if (PT_UserEmailExists($_POST['email'])) {
                    $errors = $lang->email_exists;
                }
            }
            if ($_POST['username'] != $user_data->username) {
                $is_exist = PT_UsernameExists($_POST['username']);
                if ($is_exist) {
                    $errors = $lang->username_is_taken;
                }
            }
            if (in_array($_POST['username'], $pt->site_pages)) {
                $errors = $lang->username_invalid_characters;
            }
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors = $lang->email_invalid_characters;
            }
            if (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 32) {
                $errors = $lang->username_characters_length;
            }
            if (!preg_match('/^[\w]+$/', $_POST['username'])) {
                $errors = $lang->username_invalid_characters;
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
            $age = $user_data->age;
            $age_changed = $user_data->age_changed;
            if (($_POST['age'] >= 0 && $_POST['age'] < 100) && $age != $_POST['age']) {
                if (!PT_IsAdmin()) {
                    if ($user_data->age_changed > 1) {
                        $errors = $lang->not_allowed_change_age;
                    } else {
                        $age = PT_Secure($_POST['age']);
                        $age_changed = $user_data->age_changed + 1;
                    }
                } else {
                    $age = PT_Secure($_POST['age']);
                }
            }

            $field_data         = array();
            if (empty($errors)) {
                $category = array();
                if (!empty($_POST['fav_category'])) {
                    $_POST['fav_category'] = explode(",",$_POST['fav_category']);
                    
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
                $country_id = $user_data->country_id;
                if (!empty($_POST['country']) && in_array($_POST['country'], array_keys($countries_name))) {
                    $country_id = PT_Secure($_POST['country']);
                }

                $update_data = array(
                    'username' => PT_Secure($_POST['username']),
                    'email' => PT_Secure($_POST['email']),
                    'gender' => PT_Secure($gender),
                    'first_name' => PT_Secure($_POST['first_name']),
	                'last_name' => PT_Secure($_POST['last_name']),
	                'about' => PT_Secure($_POST['about']),
                    'facebook' => !empty($_POST['facebook']) ? PT_Secure($_POST['facebook']) : '',
                    'google' => !empty($_POST['google']) ? PT_Secure($_POST['google']) : '',
                    'twitter' => !empty($_POST['twitter']) ? PT_Secure($_POST['twitter']) : '',
                    'instagram' => !empty($_POST['twitter']) ? PT_Secure($_POST['instagram']) : '',
                    'fav_category' => $category,
                    'age' => $age,
                    'age_changed' => $age_changed,
                    'country_id' => $country_id
                );
                $update_data['total_ads'] = 0;
                if (!empty($_POST['total_ads']) && is_numeric($_POST['total_ads']) && $_POST['total_ads'] > 0) {
                    $update_data['total_ads'] = PT_Secure($_POST['total_ads']);
                }
                if ($is_owner == true) {
                    $update = $db->where('id', PT_Secure($user->id))->update(T_USERS, $update_data);
                    if ($update){ 
                        $response_data = array(
	                        'api_status' => '200',
	                        'api_version' => $api_version,
	                        'message' => $lang->setting_updated
	                    );
                    }
                }
            } else {
            	$response_data = array(
                    'api_status' => '400',
                    'api_version' => $api_version,
                    'errors' => array(
                        'error_id' => '2',
                        'error_text' => $errors
                    )
                );
            }
        }
    }
}
if ($_POST['settings_type'] == 'pause_history') {
    if ($is_owner == true) {
        $pause_history = 0;
        if ($user->pause_history == 0) {
            $pause_history = 1;
        }
        $update = $db->where('id', PT_Secure($user->id))->update(T_USERS, array('pause_history' => $pause_history));
        $response_data = array(
            'api_status' => '200',
            'api_version' => $api_version,
            'message' => $lang->setting_updated,
            'pause_history' => $pause_history
        );
    }
    else{
        $response_data = array(
            'api_status' => '400',
            'api_version' => $api_version,
            'errors' => array(
                'error_id' => '2',
                'error_text' => 'you are not the owner'
            )
        );
    }
}
if ($_POST['settings_type'] == 'avatar') {
    $user_data = PT_UserData($user->id);
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
        $update = $db->where('id', PT_Secure($user->id))->update(T_USERS, $update_data);
        if ($update) {
           $response_data = array(
                'api_status' => '200',
                'api_version' => $api_version,
                'message' => $lang->setting_updated
            );
        }
    }
}

if ($_POST['settings_type'] == 'two_factor') {
    if (in_array($_POST['two_factor'],array('0','1'))) {
        $update = $db->where('id', PT_Secure($user->id))->update(T_USERS, array('two_factor' => PT_Secure($_POST['two_factor'])));
        $response_data = array(
            'api_status' => '200',
            'api_version' => $api_version,
            'message' => $lang->setting_updated
        );
    }
    else{
        $response_data = array(
            'api_status' => '400',
            'api_version' => $api_version,
            'errors' => array(
                'error_id' => '3',
                'error_text' => 'two_factor can not be empty'
            )
        );
    }
}