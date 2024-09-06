<?php
if (IS_LOGGED == false || $pt->config->import_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

$getID3 = new getID3;
$trailer = '';
if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['tags']) || empty($_POST['thumbnail-image'])) {
    $error = $lang->please_check_details;
}

else if (empty($_POST['video-id']) || empty($_POST['video-type'])) {
    $error = $lang->video_not_found_please_try_again;
}
elseif ($pt->config->embed_videos == 'on' && $_POST['video-type'] == 'embed' && empty($_FILES['thumbnail'])) {
    $error  = $lang->ivalid_thumb_file;
}
else if (!empty($_FILES['thumbnail'])) {
    $media_file = getimagesize($_FILES["thumbnail"]["tmp_name"]);
    $img_types  = array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP,IMAGETYPE_WEBP);

    if (!in_array($media_file[2],$img_types)) {
        $error  = $lang->ivalid_thumb_file;
    }
}
elseif (!empty($_POST['duration']) && $_POST['video-type'] == 'mp4' && !preg_match('/[0-9]*:[0-9]{2}/i', $_POST['duration'])) {
   $error = $lang->duration_fromat;
}
elseif (!empty($_POST['video_type']) && $_POST['video_type'] == 'movie') {
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['stars']) || empty($_POST['producer']) || empty($_POST['country']) || empty($_POST['quality']) || empty($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 10 || empty($_POST['release']) || empty($_POST['category']) || !in_array($_POST['category'], array_keys($pt->movies_categories))) {
        $error = $lang->please_check_details;
    }
    if ($pt->config->trailer_system == 'on' && !empty($_FILES['trailer'])) {
        $file_info = array(
            'file' => $_FILES['trailer']['tmp_name'],
            'size' => $_FILES['trailer']['size'],
            'name' => $_FILES['trailer']['name'],
            'type' => $_FILES['trailer']['type'],
            'allowed' => 'mp4,mov,webm,mpeg'
        );
        $file_upload = PT_ShareFile($file_info);
        if (!empty($file_upload['filename'])) {
            $trailer = $file_upload['filename'];
        }
        else if (!empty($file_upload['error'])) {
            $error = $file_upload['error'];
        }
    }
}
if (empty($error)) {
    if (in_array($_POST['video_type'], ['movie'])) {
        $pt->config->hashtag_system = 'off';
    }

	$duration        = '00:00';
    $video_id        = PT_GenerateKey(15, 15);
    $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
    $thumbnail       = PT_Secure($_POST['thumbnail-image'], 0);
    $category_id     = 0;
    $type            = "";
    $link_regex      = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i               = 0;
    $video_ok        = false;

    if (!empty($_POST['duration'])) {
        $duration = PT_Secure($_POST['duration']);
    }
    
    if ($check_for_video > 0) {
        $video_id = PT_GenerateKey(15, 15);
    }
    
    if (!empty($_FILES['thumbnail']['tmp_name'])) {
        $file_info = array(
            'file' => $_FILES['thumbnail']['tmp_name'],
            'size' => $_FILES['thumbnail']['size'],
            'name' => $_FILES['thumbnail']['name'],
            'type' => $_FILES['thumbnail']['type'],
            'allowed'    => 'jpg,png,jpeg,gif,webp',
            'crop'       => array(
                'width'  => 1920,
                'height' => 1080
            )
        );

        $file_upload   = PT_ShareFile($file_info);
        if (!empty($file_upload['filename'])) {
            $thumbnail = PT_Secure($file_upload['filename'], 0);
        }
        // print_r($thumbnail);
    }

    if (!empty($_POST['category_id']) && $_POST['video_type'] != 'movie') {
        if (in_array($_POST['category_id'], array_keys(get_object_vars($pt->categories)))) {
            $category_id = PT_Secure($_POST['category_id']);
        }
    }
    else if (!empty($_POST['category']) && $_POST['video_type'] == 'movie') {
        $category_id = PT_Secure($_POST['category']);
    }

    
    preg_match_all($link_regex, PT_Secure($_POST['description'],1), $matches);
    foreach ($matches[0] as $match) {
        $match_url            = strip_tags($match);
        $syntax               = '[a]' . urlencode($match_url) . '[/a]';
        $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
    }
    $video_privacy = 0;
    if (!empty($_POST['privacy'])) {
        if (in_array($_POST['privacy'], array(0, 1, 2))) {
            $video_privacy = PT_Secure($_POST['privacy']);
        }
    }
    $age_restriction = 1;
    if (!empty($_POST['age_restriction'])) {
        if (in_array($_POST['age_restriction'], array(1, 2))) {
            $age_restriction = PT_Secure($_POST['age_restriction']);
        }
    }
    $sub_category = 0;

    if (!empty($_POST['sub_category_id']) && $_POST['video_type'] != 'movie') {
        $is_found = $db->where('type',PT_Secure($_POST['category_id']))->where('lang_key',PT_Secure($_POST['sub_category_id']))->getValue(T_LANGS,'COUNT(*)');
        if ($is_found > 0) {
            $sub_category = PT_Secure($_POST['sub_category_id']);
        }
    }
    $continents_list = array();
    if (!empty($_POST['continents-list'])) {
        foreach ($_POST['continents-list'] as $key => $value) {
            if (in_array($value, $pt->continents)) {
                $continents_list[] = $value;
            }
        }
    }
    $video_title = PT_Secure(addToHashTags($_POST['title'],1));
    $video_description = PT_Secure(addToHashTags($_POST['description'],1));
    $data_insert      = array(
        'video_id'    => $video_id,
        'user_id'     => $user->id,
        'title'       => $video_title,
        'description' => $video_description,
        'tags'        => PT_Secure($_POST['tags'],1),
        'duration'    => $duration,
        'category_id' => $category_id,
        'thumbnail'   => $thumbnail,
        'time'        => time(),
        'registered'  => date('Y') . '/' . intval(date('m')),
        'type'        => $type,
        'privacy' => $video_privacy,
        'age_restriction' => $age_restriction,
        'sub_category' => $sub_category,
        'geo_blocking' => (!empty($continents_list) ? json_encode($continents_list) : '')
    );

    if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && $pt->user->video_mon == '1' && in_array($_POST['monetization'], array('0','1'))) {
        $data_insert['monetization'] = PT_Secure($_POST['monetization']);
    }

    if ($pt->config->approve_videos == 'on' && !PT_IsAdmin()) {
        $data_insert['approved'] = 0;
    }



    
    if ($_POST['video-type'] == 'youtube') {
    	$data_insert['youtube'] = PT_Secure($_POST['video-id']);
    	$video_ok = true;
    }

    if ($_POST['video-type'] == 'vimeo') {
    	$data_insert['vimeo'] = PT_Secure($_POST['video-id']);
    	$video_ok = true;
    }

    if ($_POST['video-type'] == 'daily') {
        $data_insert['daily'] = PT_Secure($_POST['video-id']);
        $video_ok = true;
    }

    if ($_POST['video-type'] == 'ok') {
        $data_insert['ok'] = PT_Secure($_POST['video-id']);
        $video_ok = true;
    }

    if ($_POST['video-type'] == 'm3u8') {
        $data_insert['video_location'] = urlencode($_POST['video-id']);
        $data_insert['type'] = 4;
        $video_ok            = true;
    }

    if ($_POST['video-type'] == 'facebook') {
        $data_insert['facebook'] = urlencode($_POST['video-id']);
        $video_ok            = true;
    }

    if ($_POST['video-type'] == 'mp4') {
        $data_insert['video_location'] = urlencode($_POST['video-id']);
    	$data_insert['type'] = 4;
    	$video_ok            = true;
    }
    if ($_POST['video-type'] == 'twitch') {
        $data_insert['twitch'] = PT_Secure($_POST['video-id']);
        $data_insert['twitch_type'] = PT_Secure($_POST['twitch_type']);
        $video_ok = true;
    }
    if ($_POST['video-type'] == 'tiktok' && !empty($_POST['video_url']) && $pt->config->tiktok_import == 'on') {

        $links = GetTiktokVideoDownloadLink($_POST['video_url']);
        if ($links['status'] == 400) {
            $data = $links;
            header('Content-Type: application/json');
            echo json_encode($data);
            exit();
        }
        else{
            $video_url = SaveTiktokVideo($links['video_url']);
            if (!empty($_POST['thumbnail-image']) && empty($_FILES['thumbnail'])) {
                $thumbnail = SaveTiktokImage($links['cover']);
            }
            $file     = $getID3->analyze($video_url);
            if (!empty($file['playtime_string']) ) {
                $duration = PT_Secure($file['playtime_string']);
            }

            $data_insert      = array(
                'video_id'    => $video_id,
                'user_id'     => $user->id,
                'title'       => $video_title,
                'description' => $video_description,
                'tags'        => PT_Secure($_POST['tags'],1),
                'duration'    => $duration,
                'video_location'    => $video_url,
                'category_id' => 0,
                'thumbnail'   => $thumbnail,
                'time'        => time(),
                'registered'  => date('Y') . '/' . intval(date('m')),
                'is_short'        => (!empty($_POST['video_type']) && $_POST['video_type'] == 'short') ? 1 : 0,
                'privacy' => $video_privacy,
            );
            $video_ok = true;
        }
    }
    if ( $_POST['video-type'] == 'short' && !empty($_POST['video_url']) && preg_match("/(youtu.*be.*)\/(shorts)\/(.*?((?=[&#?])|$))/m", $_POST['video_url'], $match) && !empty($match[3]) && $pt->config->youtube_short == 'on' ) {

        $data_insert      = array(
            'video_id'    => $video_id,
            'user_id'     => $user->id,
            'title'       => $video_title,
            'description' => $video_description,
            'tags'        => PT_Secure($_POST['tags'],1),
            'duration'    => $duration,
            'category_id' => 0,
            'converted' => 2,
            'time'        => time(),
            'registered'  => date('Y') . '/' . intval(date('m')),
            'is_short'        => (!empty($_POST['video_type']) && $_POST['video_type'] == 'short') ? 1 : 0,
            'privacy' => $video_privacy,
        );
        $video_ok = true;
    }
    if ($_POST['video-type'] == 'short' && !empty($_POST['video_url']) && preg_match('~https?:\/\/www\.facebook\.com.*\/(reel)\/([0-9]*)~m', $_POST['video_url'], $matches) && !empty($matches) && !empty($matches[2])) {
        $facebookData = importFacebookVideo('https://www.facebook.com/watch/?v='.$matches[2]);
        if (!is_array($facebookData) || $facebookData == false) {
            $data = array('status' => 400, 'message' => $error_icon . $lang->error_msg);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit();
        }
        if (!empty($_POST['thumbnail-image']) && empty($_FILES['thumbnail'])) {
            $newThumbnail = 'upload/photos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".jpg";
            file_put_contents($newThumbnail, file_get_contents($facebookData['thumbnail']));
            $thumbnail = $newThumbnail;
        }
        $video_url = 'upload/videos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".mp4";
        file_put_contents($video_url, file_get_contents($facebookData['video']));
        $file     = $getID3->analyze($video_url);
        if (!empty($file['playtime_string']) ) {
            $duration = PT_Secure($file['playtime_string']);
        }

        $data_insert      = array(
            'video_id'    => $video_id,
            'user_id'     => $user->id,
            'title'       => $video_title,
            'description' => $video_description,
            'tags'        => PT_Secure($_POST['tags'],1),
            'duration'    => $duration,
            'video_location'    => $video_url,
            'category_id' => 0,
            'thumbnail'   => $thumbnail,
            'time'        => time(),
            'registered'  => date('Y') . '/' . intval(date('m')),
            'is_short'        => (!empty($_POST['video_type']) && $_POST['video_type'] == 'short') ? 1 : 0,
            'privacy' => $video_privacy,
        );
        $video_ok = true;
    }
    if ($_POST['video-type'] == 'short' && !empty($_POST['video_url']) && preg_match('~https?:\/\/www\.instagram\.com.*\/(reel|reels\/videos)\/([A-Za-z0-9-_.]*)~m', $_POST['video_url'], $matches) && !empty($matches)) {
        $instagramData = importInstagramVideo($_POST['video_url']);
        if (!is_array($instagramData) || $instagramData == false) {
            $data = array('status' => 400, 'message' => $error_icon . $lang->error_msg);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit();
        }
        if (!empty($_POST['thumbnail-image']) && empty($_FILES['thumbnail'])) {
            $newThumbnail = 'upload/photos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".jpg";
            file_put_contents($newThumbnail, file_get_contents($instagramData['thumbnail']));
            $thumbnail = $newThumbnail;
        }
        $video_url = 'upload/videos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".mp4";
        file_put_contents($video_url, file_get_contents($instagramData['video']));
        $file     = $getID3->analyze($video_url);
        if (!empty($file['playtime_string']) ) {
            $duration = PT_Secure($file['playtime_string']);
        }

        $data_insert      = array(
            'video_id'    => $video_id,
            'user_id'     => $user->id,
            'title'       => $video_title,
            'description' => $video_description,
            'tags'        => PT_Secure($_POST['tags'],1),
            'duration'    => $duration,
            'video_location'    => $video_url,
            'category_id' => 0,
            'thumbnail'   => $thumbnail,
            'time'        => time(),
            'registered'  => date('Y') . '/' . intval(date('m')),
            'is_short'        => (!empty($_POST['video_type']) && $_POST['video_type'] == 'short') ? 1 : 0,
            'privacy' => $video_privacy,
        );
        $video_ok = true;
    }
    if ($pt->config->movies_videos == 'on' && !empty($_POST['video_type']) && $_POST['video_type'] == 'movie') {
        $data_insert['title'] = PT_Secure($_POST['title'],1);
        $data_insert['description'] = PT_Secure($_POST['description'],1);
        $data_insert['is_movie'] = 1;
        $data_insert['stars'] = PT_Secure($_POST['stars']);
        $data_insert['producer'] = PT_Secure($_POST['producer']);
        $data_insert['country'] = PT_Secure($_POST['country']);
        $data_insert['movie_release'] = PT_Secure($_POST['release']);
        $data_insert['quality'] = PT_Secure($_POST['quality']);
        $data_insert['rating'] = PT_Secure($_POST['rating']);
        $data_insert['trailer'] = $trailer;
    }

    if ($pt->config->embed_videos == 'on' && $_POST['video-type'] == 'embed' && !empty($_POST['video_url'])){
        $data_insert['embed'] = 1;
        $data_insert['video_location'] = PT_Secure($_POST['video_url']);
        $video_ok = true;
        if ($pt->config->review_embed_videos == 'on' && !PT_IsAdmin()) {
            $data_insert['approved'] = 0;
            $notif_data = array(
                'recipient_id' => 0,
                'type' => 'approve',
                'admin' => 1,
                'time' => time()
            );

            pt_notify($notif_data);
        }
    }
    // echo "<br>";
    // print_r($data_insert['thumbnail']);
    // exit();

    if ($video_ok == true) {
    	$insert   = $db->insert(T_VIDEOS, $data_insert);

	    if ($insert) {
	        $data          = array(
	            'status'   => 200,
	            'video_id' => $video_id,
	            'link'     => PT_Link("watch/$video_id")
	        );


            if ($_POST['video-type'] == 'short' && !empty($_POST['video_url']) && preg_match("/(youtu.*be.*)\/(shorts)\/(.*?((?=[&#?])|$))/m", $_POST['video_url'], $match) && !empty($match[3]) && $pt->config->youtube_short == 'on') {
                
                $id = PT_Secure($match[3]);
                $YP_url = '';
                try {
                    $YP_url = getYotubeRapidAPIData($id);
                } catch (Exception $e) {
                    PT_DeleteVideo($insert);
                    $result = array('status' => 400,
                                    'message' => $e->getMessage());
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit();
                }
                
                if (empty($YP_url)) {
                    //PT_DeleteVideo($insert);
                    $error = $lang->video_not_found_please_try_again;
                    $data = array(
                        'status'  => 400,
                        'message' => $error_icon . $error
                    );
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    exit();
                }
                else{

                    ob_end_clean();
                    header("Content-Encoding: none");
                    header("Connection: close");
                    ignore_user_abort();
                    ob_start();
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    $size = ob_get_length();
                    header("Content-Length: $size");
                    ob_end_flush();
                    flush();
                    session_write_close();
                    if (is_callable('fastcgi_finish_request')) {
                        fastcgi_finish_request();
                    }
                    if (is_callable('litespeed_finish_request')) {
                        litespeed_finish_request();
                    }

                    $videoContent = downloadYPVideo($YP_url);
                    if (empty($videoContent) || $videoContent['status'] != 200) {
                        //PT_DeleteVideo($insert);
                        $error = $lang->video_not_found_please_try_again;
                    }

                    if (!empty($videoContent) && $videoContent['status'] == 200) {
                        $update = array();
                        $video_url = 'upload/videos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".mp4";
                        file_put_contents($video_url, $videoContent['data']);
                        PT_UploadToS3($video_url);
                        $update['video_location'] = $video_url;
                        $update['converted'] = 1;
                        if (empty($_FILES['thumbnail'])) {
                            $newThumbnail = 'upload/photos/' . date('Y') . '/' . date('m').'/' .PT_GenerateKey() . ".jpg";
                            file_put_contents($newThumbnail, file_get_contents($thumbnail));
                            PT_UploadToS3($newThumbnail);
                            $update['thumbnail'] = $newThumbnail;
                        }
                        else{
                            $update['thumbnail'] = $thumbnail;
                        }
                        $db->where('id',$insert)->update(T_VIDEOS,$update);
                    }
                }
                    
            }
	    }
    }
} 

else {
    $data = array(
        'status'  => 400,
        'message' => $error_icon . $error
    );
}
