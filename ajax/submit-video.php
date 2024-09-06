<?php

if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}
$getID3   = new getID3;
$featured = ($user->is_pro == 1) ? 1 : 0;
$filesize = 0;
$trailer = '';
if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_FILES['thumbnail']) || empty($_POST['stars']) || empty($_POST['producer']) || empty($_POST['country']) || empty($_POST['quality']) || empty($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 10 || empty($_POST['release']) || empty($_POST['category']) || !in_array($_POST['category'], array_keys($pt->movies_categories))) {
        $error = $error_icon . $lang->please_check_details;
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
    // $cover = getimagesize($_FILES["thumbnail"]["tmp_name"]);
    // if ($cover[0] > 400 || $cover[1] > 570) {
    //     $error = $lang->cover_size;
    // }
    $pt->config->hashtag_system = 'off';
}
else{
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['tags']) || empty($_FILES['thumbnail'])) {
        $error = $lang->please_check_details;
    }
    if (empty($_POST['video-location'])) {
        $error = $lang->video_not_found_please_try_again;
    }

    if (!empty($_FILES['thumbnail']['tmp_name'])) {
        if ($_FILES['thumbnail']['size'] > $pt->config->max_upload && $pt->config->max_upload > 0) {
            $max   = pt_size_format($pt->config->max_upload);
            $error = $lang->file_is_too_big .": $max";
        }
    }
    if (!empty($_POST['set_p_v'])) {
        if (($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->sell_videos_system == 'on' && $pt->user->admin) && !empty($_POST['set_p_v'])) {
            if (!empty($_POST['set_p_v']) || (in_array('set_p_v', array_keys($_POST)) && $_POST['set_p_v'] < 0)) {
                if (!is_numeric($_POST['set_p_v']) || $_POST['set_p_v'] < 0 || (($pt->config->com_type == 0 && $_POST['set_p_v'] <= $pt->config->admin_com_sell_videos)) ) {
                    $error = $lang->video_price_error." ".($pt->config->com_type == 0 ? $pt->config->admin_com_sell_videos : 0);
                }
            }
        }
    }


    if (!empty($_POST['rent_price'])) {
        if (($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->rent_videos_system == 'on' && $pt->user->admin)) {
            if (!empty($_POST['rent_price']) || (in_array('rent_price', array_keys($_POST)) && $_POST['rent_price'] < 0)) {
                if (!is_numeric($_POST['rent_price']) || $_POST['rent_price'] < 0 || (($pt->config->com_type == 0 && $_POST['rent_price'] <= $pt->config->admin_com_rent_videos)) ) {
                    $error = $lang->video_rent_price_error." ".($pt->config->com_type == 0 ? $pt->config->admin_com_rent_videos : 0);
                }
            }
        }
    }

}
if (!empty($_POST['date'])) {
    $date = explode('-', $_POST['date']);
    if (strlen($date[0]) != 4 || !is_numeric($date[0]) ||  strlen($date[1]) != 2 || !is_numeric($date[1]) ||  strlen($date[2]) != 2 || !is_numeric($date[2])) {
        $error = $lang->wrong_date_format;
    }
}
if (!empty($_POST['privacy']) && $_POST['privacy'] == 3 && (empty($_POST['date']) || empty($_POST['hour']) || !in_array($_POST['hour'], array_keys($pt->config->hours)))) {
    $error = $lang->wrong_date_format;
}
if (empty($error)) {
    $embedding = 0;
    if ($pt->config->restrict_embedding_system == 'on' && !empty($_POST['embedding']) && $_POST['embedding'] == 'yes') {
        $embedding = 1;
    }
    $file     = $getID3->analyze($_POST['video-location']);
    $duration = '00:00';
    if (!empty($file['playtime_string']) ) {
        $duration = PT_Secure($file['playtime_string']);
    }
    if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
        if (!empty($file) && !empty($file['playtime_seconds']) && $file['playtime_seconds'] > $pt->config->shorts_duration) {
            $data = array('status' => 400,'message' => str_replace('{D}', $pt->config->shorts_duration, $pt->all_lang->max_video_duration));
            echo json_encode($data);
            exit();
        }
    }
    PT_UploadToS3(PT_Secure($_POST['video-location'], 0));

    if (!empty($file['filesize'])) {
        $filesize = $file['filesize'];
    }

    $video_id        = PT_GenerateKey(15, 15);
    $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
    if ($check_for_video > 0) {
        $video_id = PT_GenerateKey(15, 15);
    }
    $thumbnail = 'upload/photos/thumbnail.jpg';
    if (!empty($_FILES['thumbnail']['tmp_name'])) {
        if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
            $file_info   = array(
                'file' => $_FILES['thumbnail']['tmp_name'],
                'size' => $_FILES['thumbnail']['size'],
                'name' => $_FILES['thumbnail']['name'],
                'type' => $_FILES['thumbnail']['type']
            );
        }
        else{
            $file_info   = array(
                'file' => $_FILES['thumbnail']['tmp_name'],
                'size' => $_FILES['thumbnail']['size'],
                'name' => $_FILES['thumbnail']['name'],
                'type' => $_FILES['thumbnail']['type'],
                'crop' => array(
                    'width' => 1076,
                    'height' => 604
                )
            );
            if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
                $file_info['crop'] = array('width' => 604,
                                           'height' => 1076);
            }
        }

        $file_upload = PT_ShareFile($file_info);
        if (!empty($file_upload['filename'])) {
            $thumbnail = PT_Secure($file_upload['filename'], 0);
        }
    }
    // ******************************
    if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {

        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;
        preg_match_all($link_regex, PT_Secure($_POST['description']), $matches);
        foreach ($matches[0] as $match) {
            $match_url            = strip_tags($match);
            $syntax               = '[a]' . urlencode($match_url) . '[/a]';
            $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
        }

        $video_title = PT_Secure(addToHashTags($_POST['title'],1));
        $video_description = PT_Secure(addToHashTags($_POST['description'],1));

        $data_insert = array(
            'title' =>  $video_title,
            'category_id' => PT_Secure($_POST['category']),
            'stars' => PT_Secure($_POST['stars']),
            'producer' => PT_Secure($_POST['producer']),
            'country' => PT_Secure($_POST['country']),
            'movie_release' => PT_Secure($_POST['release']),
            'quality' => PT_Secure($_POST['quality']),
            'duration' => $duration,
            'description' => $video_description,
            'rating' => PT_Secure($_POST['rating']),
            'is_movie' => 1,
            'video_id' => $video_id,
            'converted' => '2',
            'size' => $filesize,
            'thumbnail' => $thumbnail,
            'user_id' => $user->id,
            'time' => time(),
            'registered' => date('Y') . '/' . intval(date('m')),
            'video_location' => PT_Secure($_POST['video-location'], 0),
            'trailer' => $trailer,
            'embedding' => $embedding
        );
        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && !empty($_POST['buy_vce']) && is_numeric($_POST['buy_price']) && $_POST['buy_price'] > 0) {
            $data_insert['sell_video'] = PT_Secure($_POST['buy_price']);
        }
        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && !empty($_POST['movie_rent_price']) && is_numeric($_POST['movie_rent_price']) && $_POST['movie_rent_price'] > 0) {
            $data_insert['rent_price'] = PT_Secure($_POST['movie_rent_price']);
        }
    }
    else{

        $category_id = 0;

        if (!empty($_POST['category_id'])) {
            if (in_array($_POST['category_id'], array_keys(get_object_vars($pt->categories)))) {
                $category_id = PT_Secure($_POST['category_id']);
            }
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i          = 0;
        preg_match_all($link_regex, PT_Secure($_POST['description'],1), $matches);
        foreach ($matches[0] as $match) {
            $match_url           = strip_tags($match);
            $syntax              = '[a]' . urlencode($match_url) . '[/a]';
            $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
        }
        $video_privacy = 0;
        if (!empty($_POST['privacy'])) {
            if (in_array($_POST['privacy'], array(0, 1, 2, 3,4))) {
                //if ($_POST['privacy'] < 3) {
                    $video_privacy = PT_Secure($_POST['privacy']);
                // }
                // else{

                // }

            }
        }
        $age_restriction = 1;
        if (!empty($_POST['age_restriction'])) {
            if (in_array($_POST['age_restriction'], array(1, 2))) {
                $age_restriction = PT_Secure($_POST['age_restriction']);
            }
        }

        $sub_category = 0;

        if (!empty($_POST['sub_category_id'])) {
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
        $publication_date = 0;
        if (!empty($_POST['date']) && !empty($_POST['hour'])) {
            $publication_date = strtotime($_POST['date']." ".$pt->config->hours[$_POST['hour']]);
            $video_privacy = 1;
        }
        $is_short = 0;
        if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
            $is_short = 1;
            $_POST['set_p_v'] = 0;
            $_POST['rent_price'] = 0;
            $_POST['monetization'] = 0;
            $pt->config->hashtag_system = 'off';
        }
        $video_title = PT_Secure(addToHashTags($_POST['title'],1));
        $video_description = PT_Secure(addToHashTags($_POST['description'],1));

        $data_insert = array(
            'video_id' => $video_id,
            'user_id' => $user->id,
            'title' => $video_title,
            'description' => $video_description,
            'tags' => PT_Secure($_POST['tags'],1),
            'duration' => $duration,
            'video_location' => PT_Secure($_POST['video-location'], 0),
            'category_id' => $category_id,
            'thumbnail' => $thumbnail,
            'is_short' => $is_short,
            'time' => time(),
            'registered' => date('Y') . '/' . intval(date('m')),
            'featured' => $featured,
            'size' => $filesize,
            'privacy' => $video_privacy,
            'age_restriction' => $age_restriction,
            'sub_category' => $sub_category,
            'geo_blocking' => (!empty($continents_list) ? json_encode($continents_list) : ''),
            'embedding' => $embedding,
            'publication_date' => $publication_date
        );
        if (!empty($_POST['date']) && !empty($_POST['hour'])) {
            $data_insert['time'] = $publication_date;
        }
        if (!empty($_POST['set_p_v']) && is_numeric($_POST['set_p_v']) && $_POST['set_p_v'] > 0) {
            $data_insert['sell_video'] = PT_Secure($_POST['set_p_v']);
        }
        if (!empty($_POST['rent_price']) && is_numeric($_POST['rent_price']) && $_POST['rent_price'] > 0) {
            $data_insert['rent_price'] = PT_Secure($_POST['rent_price']);
        }


        if ( ($pt->config->approve_videos == 'on' && !PT_IsAdmin()) || ($pt->config->auto_approve_ == 'no' && $pt->config->sell_videos_system == 'on' && !PT_IsAdmin() && !empty($data_insert['sell_video']) ) ) {
            $data_insert['approved'] = 0;
        }
        if ( ($pt->config->approve_videos == 'on' && !PT_IsAdmin()) || ($pt->config->auto_approve_ == 'no' && $pt->config->rent_videos_system == 'on' && !PT_IsAdmin() &&  !empty($data_insert['rent_price'])) ) {
            $data_insert['approved'] = 0;
        }

        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && $pt->user->video_mon == '1' && in_array($_POST['monetization'], array('0','1'))) {
            $data_insert['monetization'] = PT_Secure($_POST['monetization']);
        }
    }
    $insert      = $db->insert(T_VIDEOS, $data_insert);

    if ($insert) {
        if ($video_privacy == 0) {
            pt_push_channel_notifiations($video_id);
        }
        $getFileName = substr($_POST['video-location'], strrpos($_POST['video-location'], '/') + 1);
        $getFileName = str_replace("_shorts", "", $getFileName);
        if (!empty($getFileName)) {
            $db->where("user_id", $pt->user->id)->where("filename", PT_Secure($getFileName))->delete(T_UPLOADED_CUNKS);
        }
        $get_video = $db->where('id', $insert)->getOne(T_VIDEOS);
        $video_url                = PT_Link('watch/' . $get_video->video_id);
        if ($pt->config->seo_link == 'on') {
            $video_url                = PT_Link('watch/' . PT_Slug($get_video->title, $get_video->video_id));
        }
        if ($get_video->is_short == 1) {
            $video_url                = PT_Link('shorts/' . $get_video->video_id);
            if ($pt->config->seo_link == 'on') {
                $video_url                = PT_Link('shorts/' . PT_Slug($get_video->title, $get_video->video_id));
            }
        }
        $data = array(
            'status' => 200,
            'video_id' => $video_id,
            'link' => $video_url
        );


        if (!empty($_POST['uploaded_id']) && is_numeric($_POST['uploaded_id']) && $_POST['uploaded_id'] > 0) {
            $db->where('id',PT_Secure($_POST['uploaded_id']))->where('user_id',$pt->user->id)->where('path',PT_Secure($_POST['video-location']))->delete(T_UPLOADED);
        }
        RegisterPoint($insert, "upload");
    }
}
else {
    $data = array(
        'status' => 400,
        'message' => $error_icon . $error
    );
}
?>
