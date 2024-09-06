<?php

//$max_user_upload = $pt->config->user_max_upload;

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

// else if ($pt->user->is_pro != 1 && $pt->user->uploads >= $max_user_upload && $pt->config->go_pro == 1){
//     $data = array('status' => 401);
//     echo json_encode($data);
//     exit();
// }
else if($pt->config->ffmpeg_system != 'on'){
    $data = array('status' => 402);
    echo json_encode($data);
    exit();
}

else{
    if ($pt->config->shorts_system == 'on' && !canUseFeature($pt->user->id,'who_can_shorts')) {
        $pt->config->shorts_system = 'off';
    }
    if (empty($_SESSION['fileSize'])) {
        $_SESSION['fileSize'] = 0;
    }
    $_SESSION['fileSize'] = $_SESSION['fileSize'] + $_FILES['video']['size'];
    if (!empty($_FILES['video']['tmp_name'])) {
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
        $allowed           = 'mp4,mov,webm,mpeg,3gp,avi,flv,ogg,mkv,mk3d,mks,wmv';

        if (!empty($_REQUEST["name"])) {
            $_FILES['video']['name'] = $_REQUEST["name"];
        }
        $new_string        = pathinfo($_FILES['video']['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $extension_allowed = explode(',', $allowed);
        $file_extension    = pathinfo($new_string, PATHINFO_EXTENSION);
        if (!in_array($file_extension, $extension_allowed)) {
            $data = array('status' => 400, 'error' => $lang->file_not_supported);
            echo json_encode($data);
            exit();
        }

        $file_info    = array(
            'file'    => $_FILES['video']['tmp_name'],
            'size'    => $_SESSION['fileSize'],
            'name'    => $_FILES['video']['name'],
            'type'    => $_FILES['video']['type'],
            'allowed' => 'mp4,mov,webm,mpeg,3gp,avi,flv,ogg,mkv,mk3d,mks,wmv'
        );

        $default_amazon = $pt->config->s3_upload;

        $pt->remoteStorage = false;
        $file_upload   = PT_ShareFile($file_info);
        $getID3        = new getID3;
        $images        = array();
        $video         = false;
        if (!empty($file_upload['filename'])) {
            $analyze   = $getID3->analyze($file_upload['filename']);
            if (empty($analyze['error']) && !empty($analyze['fileformat'])) {
                $video = $analyze;
            }

            $filename  = $file_upload['filename'];
            $_SESSION['uploads']['videos'][] = $filename;
        }

        if (!empty($video)) {
            $pt->config->s3_upload = $default_amazon;
            $ffmpeg_b      = $pt->config->ffmpeg_binary_file;
            $total_seconds = ffmpeg_duration($filename);
            $thumb_1_duration = (int) ($total_seconds > 10) ? 11 : 1;
            $thumb_2_duration = (int) ($total_seconds > 24) ? 25 : 15;
            $thumb_3_duration = (int) $total_seconds - 1;
            $thumb_4_duration = (int) $total_seconds / 2;
            $thumb_5_duration = (int) ($total_seconds / 2) * 1.3;
            $thumb_6_duration = (int) $total_seconds / 3;
            $uniq_id = rand(1111,9999);
            $img_pos       = array($thumb_1_duration, $thumb_2_duration, $thumb_3_duration, $thumb_4_duration, $thumb_5_duration, $thumb_6_duration);
            if (!file_exists('upload/photos/' . date('Y'))) {
                @mkdir('upload/photos/' . date('Y'), 0777, true);
            }

            if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
                @mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
            }
            $dir      = "upload/photos/" . date('Y') . '/' . date('m');
            foreach ($img_pos as $i) {
                $hash     = sha1(time() + time() - rand(9999,9999)) . PT_GenerateKey();
                $thumbFileName = "$hash.video_thumb_$uniq_id" . "_$i.jpeg";

                $thumb    = "$dir/$thumbFileName";
                $full_dir = str_replace('ajax', '/', __DIR__);

                $input_path = $full_dir . $file_upload['filename'];
                $output_path = $full_dir . $thumb;

                $output_thumb = shell_exec("$ffmpeg_b -ss \"$i\" -i $input_path -vframes 1 -f mjpeg $output_path 2<&1");

                if (file_exists($thumb) && !empty(@getimagesize($thumb))) {
                    if ($pt->config->shorts_system == 'on' && !empty($_REQUEST['is_short']) && $_REQUEST['is_short'] == 'yes') {
                        PT_Resize_Crop_Image(604, 1076, $thumb, $thumb, 80);
                    }
                    else{
                        PT_Resize_Crop_Image(1076, 604, $thumb, $thumb, 80);
                    }

                    $images[] = $thumb;
                    $db->insert(T_UPLOADED_CUNKS, [
                        "filename" => $thumbFileName, 
                        "user_id" => $pt->user->id, 
                        "folderpath" => $dir, 
                        "status" => "completed", 
                        "type" => "thumbnail"
                    ]);
                    $_SESSION['ffempg_uploads'][] = $thumb;
                } else {
                    @unlink($thumb);
                }
            }
            $explode3  = @explode('.', $file_upload['name']);
            $file_upload['name'] = $explode3[0];

            $full_dir                   = str_replace('ajax', '/', __DIR__);
            $filepath                   = explode('.', $filename)[0];
            $video_file_full_path       = $full_dir . $filename;
            // if (!empty($_REQUEST['is_short']) && $_REQUEST['is_short'] == 'yes') {
            //   $video_output_full_path_144 = $full_dir . $filepath . "_144p_converted.mp4";
            //   $shell     = shell_exec("$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=256:-2 -crf 26 $video_output_full_path_144 2>&1");
            //   $data['full_file_path'] = $pt->config->site_url . '/' . ($filepath . "_144p_converted.mp4");

            //   $getFileName = substr($filepath . "_144p_converted.mp4", strrpos($filepath . "_144p_converted.mp4", '/') + 1);
            //   $folderName = str_replace("/" .$getFileName, "", $filepath . "_144p_converted.mp4");
              
            //   $db->insert(T_UPLOADED_CUNKS, [
            //         "filename" => $getFileName, 
            //         "user_id" => $pt->user->id, 
            //         "folderpath" => $folderName, 
            //         "status" => "completed", 
            //         "type" => "short_video"
            //     ]);
            // }


            $data['status']    = 200;
            $data['file_path'] = $filename;
            $data['full_file_path'] = $pt->config->site_url . '/' . $filename;

            $data['file_name'] = $file_upload['name'];
            $data['images']    = $images;

            $update = array(
                'uploads' => ($pt->user->uploads += $file_info['size'])
            );

            $db->where('id',$pt->user->id)->update(T_USERS,$update);
            $data['uploaded_id'] = $db->insert(T_UPLOADED,array('user_id' => $pt->user->id,
                                                                'path' => $filename,
                                                                'time' => time()));
        }

        else if (!empty($file_upload['error'])) {
            $data = array('status' => 400, 'error' => $file_upload['error']);
        }
    }
}

?>
