<?php 
if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}
if ($pt->user->suspend_upload) {
    $data = array('status' => 400);
    echo json_encode($data);
    exit();
}


// $max_user_upload = $pt->config->user_max_upload;
// if ($pt->user->is_pro != 1 && $pt->user->uploads >= $max_user_upload && $pt->config->go_pro == 1){
//     $data = array('status' => 401);
//     echo json_encode($data);
//     exit();
// }

if ($pt->config->shorts_system == 'on' && !canUseFeature($pt->user->id,'who_can_shorts')) {
    $pt->config->shorts_system = 'off';
}
if (!empty($_FILES['video']['tmp_name'])) {
    if (empty($_SESSION['fileSize'])) {
        $_SESSION['fileSize'] = 0;
    }
    $_SESSION['fileSize'] = $_SESSION['fileSize'] + $_FILES['video']['size'];
    if ($pt->config->shorts_system == 'on' && !empty($_REQUEST['is_short']) && $_REQUEST['is_short'] == 'yes') {
        try {
            $getID3   = new getID3;
            $file     = $getID3->analyze($_FILES['video']['tmp_name']);
            if (!empty($file) && !empty($file['playtime_seconds']) && $file['playtime_seconds'] > $pt->config->shorts_duration) {
                $data = array('status' => 402,'message' => str_replace('{D}', $pt->config->shorts_duration, $pt->all_lang->max_video_duration));
                echo json_encode($data);
                exit();
            }
        } catch (Exception $e) {
            $data = array('status' => 402,'message' => $e->getMessage());
            echo json_encode($data);
            exit();
        }
    }
    if (!PT_IsAdmin()) {
        if ($pt->user->user_upload_limit != '0') {
            if ($pt->user->user_upload_limit != 'unlimited') {
                if (($pt->user->uploads + $_SESSION['fileSize']) >= $pt->user->user_upload_limit) {
                    $max  = pt_size_format($pt->user->user_upload_limit);
                    $data = array('status' => 402,'message' => ($lang->file_is_too_big .": $max"));
                    echo json_encode($data);
                    exit();
                }
            }
        }
        else{
            if ($pt->config->upload_system_type == '0') {
                if ($pt->config->max_upload_all_users != '0' && ($pt->user->uploads + $_SESSION['fileSize']) >= $pt->config->max_upload_all_users) {
                    $max  = pt_size_format($pt->config->max_upload_all_users);
                    $data = array('status' => 402,'message' => ($lang->file_is_too_big .": $max"));
                    echo json_encode($data);
                    exit();
                }
            }
            elseif ($pt->config->upload_system_type == '1') {
                if ($pt->user->is_pro == '0' && ($pt->user->uploads + $_SESSION['fileSize']) >= $pt->config->max_upload_free_users && $pt->config->max_upload_free_users != 0) {
                    $max  = pt_size_format($pt->config->max_upload_free_users);
                    $data = array('status' => 402,'message' => ($lang->file_is_too_big .": $max"));
                    echo json_encode($data);
                    exit();
                }
                elseif ($pt->user->is_pro > '0' && ($pt->user->uploads + $_SESSION['fileSize']) >= $pt->config->max_upload_pro_users && $pt->config->max_upload_pro_users != 0) {
                    $max  = pt_size_format($pt->config->max_upload_pro_users);
                    $data = array('status' => 402,'message' => ($lang->file_is_too_big .": $max"));
                    echo json_encode($data);
                    exit();
                }
            }
        }
    }

    // if ($_SESSION['fileSize'] > $pt->config->max_upload) {
    //     $max  = pt_size_format($pt->config->max_upload);
    //     $data = array('status' => 402,'message' => ($lang->file_is_too_big .": $max"));
    //     echo json_encode($data);
    //     exit();
    // }

    if (!empty($_REQUEST["name"])) {
        $_FILES['video']['name'] = $_REQUEST["name"];
    }
        
    $allowed           = 'mp4,mov,webm,mpeg';

    $new_string        = pathinfo($_FILES['video']['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        $data = array('status' => 400, 'error' => $lang->file_not_supported);
        echo json_encode($data);
        exit();
    }

	$file_info = array(
        'file' => $_FILES['video']['tmp_name'],
        'size' => $_SESSION['fileSize'],
        'name' => $_FILES['video']['name'],
        'type' => $_FILES['video']['type'],
        'allowed' => 'mp4,mov,webm,mpeg'
    );
    $pt->remoteStorage = false;
    $file_upload = PT_ShareFile($file_info);
    if (!empty($file_upload['filename'])) {
        $explode3  = @explode('.', $file_upload['name']);
        $file_upload['name'] = $explode3[0];
    	$data   = array('status' => 200, 'file_path' => $file_upload['filename'], 'file_name' => $file_upload['name']);
        $update = array('uploads' => ($pt->user->uploads += $file_info['size']));
        $db->where('id',$pt->user->id)->update(T_USERS,$update);
        $data['uploaded_id'] = $db->insert(T_UPLOADED,array('user_id' => $pt->user->id,
                                                            'path' => $file_upload['filename'],
                                                            'time' => time()));

    } 

    else if (!empty($file_upload['error'])) {
        $data = array('status' => 400, 'error' => $file_upload['error']);
    }
}
?>