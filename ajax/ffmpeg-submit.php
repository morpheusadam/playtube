<?php
if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
} else if ($pt->config->ffmpeg_system != 'on') {
    $data = array(
        'status' => 402
    );
    echo json_encode($data);
    exit();
} else {
    $getID3    = new getID3;
    $featured  = ($user->is_pro == 1) ? 1 : 0;
    $filesize  = 0;
    $error     = false;

$is_stock = 0;
$trailer = '';
$pt->config->p240 = "on";
if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
    if (empty($_POST['movie_title']) || empty($_POST['movie_description']) || empty($_POST['stars']) || empty($_POST['producer']) || empty($_POST['country']) || empty($_POST['quality']) || empty($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 10 || empty($_POST['release']) || empty($_POST['category']) || !in_array($_POST['category'], array_keys($pt->movies_categories))) {
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
    $pt->config->hashtag_system = 'off';
    // $cover = getimagesize($_FILES["movie_thumbnail"]["tmp_name"]);
    // if ($cover[0] > 400 || $cover[1] > 570) {
    //     $error = $lang->cover_size;
    // }
}
elseif (!empty($_POST['is_movie']) && $_POST['is_movie'] == 2 && $pt->config->stock_videos == 'on') {
    $license_array = array('rights_managed_license','editorial_use_license','royalty_free_license','royalty_free_extended_license','creative_commons_license','public_domain');
    $request   = array();
    $request[] = (empty($_POST['title']) || empty($_POST['description']));
    $request[] = (empty($_POST['tags']) || empty($_POST['video-thumnail']));
    $request[] = (!in_array($_POST['video-location'], $_SESSION['uploads']['videos']));
    $request[] = (!in_array($_POST['video-thumnail'], (is_array($_SESSION['ffempg_uploads'])) ? $_SESSION['ffempg_uploads'] : []));
    $request[] = (!file_exists($_POST['video-location']));
    $request[] = (!empty($_POST['license']) && !in_array($_POST['license'], $license_array));
    $request[] = (!empty($_POST['set_p_v']) && !is_numeric($_POST['set_p_v']));
    $request[] = (!empty($_POST['set_p_v']) && !$_POST['set_p_v'] < 0);
    if (in_array(true, $request)) {
        $error = $lang->please_check_details;
    }
    $_POST['privacy'] = 0;
    $_POST['age_restriction'] = 1;
    $_POST['continents-list'] = array();
    $_POST['rent_price'] = 0;
    $_POST['monetization'] = 0;
    $is_stock = 1;
}
else{
    $request   = array();
    $request[] = (empty($_POST['title']) || empty($_POST['description']));
    $request[] = (empty($_POST['tags']) || empty($_POST['video-thumnail']));
    if (in_array(true, $request)) {
        $error = $lang->please_check_details;
    } else if (empty($_POST['video-location'])) {
        $error = $lang->video_not_found_please_try_again;
    }
     else {
        $request   = array();
        $request[] = (!in_array($_POST['video-location'], $_SESSION['uploads']['videos']));
        $request[] = (!in_array($_POST['video-thumnail'], (!empty($_SESSION['ffempg_uploads']) && is_array($_SESSION['ffempg_uploads'])) ? $_SESSION['ffempg_uploads'] : []));
        $request[] = (!file_exists($_POST['video-location']));
        if (in_array(true, $request)) {
            $error = $lang->error_msg;
        }
    }
    if (!empty($_POST['set_p_v'])) {
        if (($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->sell_videos_system == 'on' && $pt->user->admin) && !empty($_POST['set_p_v'])) {
            if (!empty($_POST['set_p_v']) || $_POST['set_p_v'] < 0) {
                if (!is_numeric($_POST['set_p_v']) || $_POST['set_p_v'] < 0 || (($pt->config->com_type == 0 && $_POST['set_p_v'] <= $pt->config->admin_com_sell_videos)) ) {
                    $error = $lang->video_price_error." ".($pt->config->com_type == 0 ? $pt->config->admin_com_sell_videos : 0);
                }
            }
        }
    }
    if (!empty($_POST['rent_price'])) {
        if (($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->rent_videos_system == 'on' && $pt->user->admin)) {
            if (!empty($_POST['rent_price']) || $_POST['rent_price'] < 0) {
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
        $is_short = 0;
        $short_res = 0;
        if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
            //$pt->config->hashtag_system = 'off';
            $short_video = $getID3->analyze($_POST['video-location']);
            $short_res = (!empty($short_video['video']['resolution_x'])) ? $short_video['video']['resolution_x'] : 426;
            $_POST['set_p_v'] = 0;
            $_POST['rent_price'] = 0;
            $_POST['monetization'] = 0;
            $start = 0;
            $end = $pt->config->shorts_duration;
            if (!empty($_POST['start']) && is_numeric($_POST['start']) && !empty($_POST['end']) && is_numeric($_POST['end']) && ($_POST['end'] - $_POST['start']) <= $pt->config->shorts_duration) {
                $start = PT_Secure($_POST['start']);
                $end = PT_Secure($_POST['end']);
            }
            $ffmpeg_b                   = $pt->config->ffmpeg_binary_file;
            $full_dir                   = str_replace('ajax', '/', __DIR__);
            $is_short = 1;
            $sh_video_time = ' -ss '.$start.'  -to '.$end.' -async 1';
            $ex                   = explode('.', $_POST['video-location']);
            $video_output_full_path_144 = $ex[0] . "_144p_converted.mp4";
            if (file_exists($video_output_full_path_144)) {
                unlink($video_output_full_path_144);
            }
            if ($pt->remoteStorage) {
                PT_DeleteFromToS3($video_output_full_path_144);
            }
            $video_output_full_path__ = $full_dir . $ex[0] . "_shorts.".$ex[1];
            $shell     = shell_exec("$ffmpeg_b -y -i ".$full_dir . $_POST['video-location']." -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=426:-2 -crf 26 $sh_video_time ".$video_output_full_path__." 2>&1");
            @unlink($_POST['video-location']);
            $_POST['video-location'] = $ex[0] . "_shorts.".$ex[1];
        }
        $file = $duration_file     = $getID3->analyze($_POST['video-location']);
        $duration = '00:00';
        if (!empty($file['playtime_string'])) {
            $duration = PT_Secure($file['playtime_string']);
        }
        // if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
        //     if (!empty($file) && !empty($file['playtime_seconds']) && $file['playtime_seconds'] > $pt->config->shorts_duration) {
        //         $data = array('status' => 400,'message' => str_replace('{D}', $pt->config->shorts_duration, $pt->all_lang->max_video_duration));
        //         echo json_encode($data);
        //         exit();
        //     }
        // }
        if (!empty($file['filesize'])) {
            $filesize = $file['filesize'];
        }
        $video_res = (!empty($file['video']['resolution_x'])) ? $file['video']['resolution_x'] : 0;
        if ($is_short == 1) {
            $video_res = $short_res;
        }
        $video_id        = PT_GenerateKey(15, 15);
        $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
        if ($check_for_video > 0) {
            $video_id = PT_GenerateKey(15, 15);
        }
        // if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
        //     $thumbnail = 'upload/photos/thumbnail.jpg';
        //     if (!empty($_FILES['movie_thumbnail']['tmp_name'])) {
        //         $file_info   = array(
        //             'file' => $_FILES['movie_thumbnail']['tmp_name'],
        //             'size' => $_FILES['movie_thumbnail']['size'],
        //             'name' => $_FILES['movie_thumbnail']['name'],
        //             'type' => $_FILES['movie_thumbnail']['type']
        //         );
        //         $file_upload = PT_ShareFile($file_info);
        //         $thumbnail = PT_Secure($file_upload['filename'], 0);
        //         // if (!empty($file_upload['filename'])) {
        //         //     $thumbnail = PT_Secure($file_upload['filename'], 0);
        //         //     $upload = PT_UploadToS3($thumbnail);
        //         // }
        //     }
        // }
        // else{
            if (empty($_POST['video-thumnail'])) {
                $thumbnail = 'upload/photos/thumbnail.jpg';
            }
            $thumbnail = PT_Secure($_POST['video-thumnail'], 0);
            if (file_exists($thumbnail)) {
                $upload = PT_UploadToS3($thumbnail);
            }
        // }

        $category_id = 0;
        $convert     = true;
        $thumbnail   = substr($thumbnail, strpos($thumbnail, "upload"), 120);
        // ******************************
        if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {


            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i          = 0;
            preg_match_all($link_regex, PT_Secure($_POST['movie_description']), $matches);
            foreach ($matches[0] as $match) {
                $match_url            = strip_tags($match);
                $syntax               = '[a]' . urlencode($match_url) . '[/a]';
                $_POST['movie_description'] = str_replace($match, $syntax, $_POST['movie_description']);
            }
            $video_title = PT_Secure(addToHashTags($_POST['movie_title'],1));
            $video_description = PT_Secure(addToHashTags($_POST['movie_description'],1));
            if (!empty($_POST['category'])) {
                if (in_array($_POST['category'], array_keys(get_object_vars($pt->categories)))) {
                    $category_id = ($_POST['category'] == 'other') ? 0 : PT_Secure($_POST['category']);
                }
            }
            $data_insert = array(
                'title' =>  $video_title,
                'category_id' => $category_id,
                'stars' => PT_Secure($_POST['stars']),
                'producer' => PT_Secure($_POST['producer']),
                'country' => PT_Secure($_POST['country']),
                'movie_release' => PT_Secure($_POST['release']),
                'quality' => PT_Secure($_POST['quality']),
                'duration' => $duration,
                'description' => $video_description,
                'rating' => PT_Secure($_POST['rating']),
                'is_movie' => '1',
                'video_id' => $video_id,
                'converted' => '2',
                'size' => $filesize,
                'thumbnail' => $thumbnail,
                'user_id' => $user->id,
                'time' => time(),
                'registered' => date('Y') . '/' . intval(date('m')),
                'trailer' => $trailer,
                'embedding' => $embedding
            );


            if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && !empty($_POST['buy_price']) && is_numeric($_POST['buy_price']) && $_POST['buy_price'] > 0) {
                $data_insert['sell_video'] = PT_Secure($_POST['buy_price']);
            }
            if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && !empty($_POST['movie_rent_price']) && is_numeric($_POST['movie_rent_price']) && $_POST['movie_rent_price'] > 0) {
                $data_insert['rent_price'] = PT_Secure($_POST['movie_rent_price']);
            }
        }
        else{
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i          = 0;
            preg_match_all($link_regex, PT_Secure($_POST['description']), $matches);
            foreach ($matches[0] as $match) {
                $match_url            = strip_tags($match);
                $syntax               = '[a]' . urlencode($match_url) . '[/a]';
                $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
            }

            if (!empty($_POST['category_id'])) {
                if (in_array($_POST['category_id'], array_keys(get_object_vars($pt->categories)))) {
                    $category_id = PT_Secure($_POST['category_id']);
                }
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
            $video_title = PT_Secure(addToHashTags($_POST['title'],1));
            $video_description = PT_Secure(addToHashTags($_POST['description'],1));
            $data_insert = array(
                'video_id' => $video_id,
                'user_id' => $user->id,
                'title' => $video_title,
                'description' => $video_description,
                'tags' => PT_Secure($_POST['tags'],1),
                'duration' => $duration,
                'video_location' => '',
                'category_id' => $category_id,
                'thumbnail' => $thumbnail,
                'is_short' => $is_short,
                'time' => time(),
                'registered' => date('Y') . '/' . intval(date('m')),
                'featured' => $featured,
                'converted' => '2',
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
            $data_insert['sell_video'] = 0;
            if (!empty($_POST['is_movie']) && $_POST['is_movie'] == 2 && $pt->config->stock_videos == 'on' && !empty($_POST['set_p_v']) && is_numeric($_POST['set_p_v']) && $_POST['set_p_v'] > 0) {
                $data_insert['sell_video'] = PT_Secure($_POST['set_p_v']);
            }
            if (!empty($_POST['set_p_v']) && is_numeric($_POST['set_p_v']) && $_POST['set_p_v'] > 0) {
                $data_insert['sell_video'] = PT_Secure($_POST['set_p_v']);
            }
            if (!empty($_POST['rent_price']) && is_numeric($_POST['rent_price']) && $_POST['rent_price'] > 0) {
                $data_insert['rent_price'] = PT_Secure($_POST['rent_price']);
            }
            if ( ($pt->config->approve_videos == 'on' && !PT_IsAdmin()) || ($pt->config->auto_approve_ == 'no' && $pt->config->sell_videos_system == 'on' && !PT_IsAdmin() && !empty($data_insert['sell_video'])) ) {
                $data_insert['approved'] = 0;
            }
            if ( ($pt->config->approve_videos == 'on' && !PT_IsAdmin()) || ($pt->config->auto_approve_ == 'no' && $pt->config->rent_videos_system == 'on' && !PT_IsAdmin() &&  !empty($data_insert['rent_price'])) ) {
                $data_insert['approved'] = 0;
            }
            $data_insert['license'] = '';
            if (!empty($_POST['is_movie']) && $_POST['is_movie'] == 2 && $pt->config->stock_videos == 'on' && !empty($_POST['license']) && in_array($_POST['license'], $license_array)) {
                $data_insert['license'] = PT_Secure($_POST['license']);
            }

            if ($is_stock == 1) {
                $data_insert['is_stock'] = 1;
            }
        }
        // ******************************
        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && $pt->user->video_mon == '1' && !empty($_POST['monetization']) && in_array($_POST['monetization'], array('0','1'))) {
            $data_insert['monetization'] = PT_Secure($_POST['monetization']);
        }


        $insert      = $db->insert(T_VIDEOS, $data_insert);

        if ($insert) {
            $getFileName = substr($_POST['video-location'], strrpos($_POST['video-location'], '/') + 1);
            $getFileName = str_replace("_shorts", "", $getFileName);
            $getThumbnailName = substr($thumbnail, strrpos($thumbnail, '/') + 1);
            
            $delete_files = array();
            if (!empty($_SESSION['ffempg_uploads'])) {
                if (is_array($_SESSION['ffempg_uploads'])) {
                    foreach ($_SESSION['ffempg_uploads'] as $key => $file) {
                        if ($thumbnail != $file) {
                            $delete_files[] = $file;
                            unset($_SESSION['ffempg_uploads'][$key]);
                        }
                    }
                }
            }
            if (!empty($delete_files)) {
                foreach ($delete_files as $key => $file2) {
                    unlink($file2);
                }
            }
            if (isset($_SESSION['ffempg_uploads'])) {
                unset($_SESSION['ffempg_uploads']);
            }
            if (!empty($_POST['uploaded_id']) && is_numeric($_POST['uploaded_id']) && $_POST['uploaded_id'] > 0) {
                $db->where('id',PT_Secure($_POST['uploaded_id']))->where('user_id',$pt->user->id)->where('path',PT_Secure($_POST['video-location']))->delete(T_UPLOADED);
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


            $processing_count = $db->where('processing',1)->getValue(T_QUEUE,'COUNT(*)');

            $have_demo = false;

            if ($processing_count < $pt->config->queue_count ||  $pt->config->queue_count == 0) {
                if ($pt->config->queue_count > 0) {
                    if ($processing_count < $pt->config->queue_count) {
                        $db->insert(T_QUEUE, array('video_id' => $insert,
                                                   'video_res' => $video_res,
                                                   'processing' => 1));
                    }
                }

                $ffmpeg_b                   = $pt->config->ffmpeg_binary_file;
                $filepath                   = explode('.', $_POST['video-location'])[0];
                $time                       = time();
                $full_dir                   = str_replace('ajax', '/', __DIR__);
                

                $video_file_full_path       = $full_dir . $_POST['video-location'];
                // demo Video
                $video_time = '';
                $demo_video = '';
                $gif_video = '';
                $gif_time = 3;
                $gif_video_time = '-t '.$gif_time.'  -async 1';
                if ($pt->config->demo_video == 'on' && !empty($data_insert['sell_video'])) {
                    if (!empty($duration_file['playtime_seconds']) && $duration_file['playtime_seconds'] > 0) {
                        $video_time = round((10 * round($duration_file['playtime_seconds'],0)) / 100,0);
                        $video_time = '-t '.$video_time.'  -async 1';
                        $have_demo = true;
                    }
                }

                // gif Video
                if ($pt->config->gif_system == 'on' && empty($gif_video) && empty($_POST['is_movie'])) {
                    $gif_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_small_video_.gif";

                    $shell     = shell_exec("$ffmpeg_b $gif_video_time -y -i $video_file_full_path ".$full_dir . $gif_video);

                    $upload_s3 = PT_UploadToS3($gif_video);
                    $db->where('id', $insert);
                    $db->update(T_VIDEOS, array(
                        'gif' => $gif_video
                    ));
                }
                if ($is_short == 1) {
                    
                    turnOffVideoQuality($video_res);
                    
                }


                // gif Video

                if ($pt->config->p240 == "on") {

                    $video_output_full_path_240 = $full_dir . $filepath . "_240p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_240,
                        'filepath' => $filepath . "_240p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 426,
                        'col' => '240p',
                    ]);


                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_240p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 426,
                        ]);
                    }
                }
                if ($is_stock == 1 && !empty($data_insert['sell_video'])) {
                    $water = $full_dir."/themes/youplay/img/icon.png";
                    $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_demo.mp4";

                    createDemoSellVideo([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'water' => $water,
                        'demo_video' => $demo_video,
                        'video_id' => $insert,
                    ]);

                }

                if ($video_res >= 3840 && $pt->config->p4096 == "on") {

                    $video_output_full_path_4096 = $full_dir . $filepath . "_4096p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_4096,
                        'filepath' => $filepath . "_4096p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 3840,
                        'col' => '4096p',
                    ]);

                    // demo Video

                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_4096p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 3840,
                        ]);

                    }
                    // demo Video

                    // gif video
                    if ($pt->config->gif_system == 'on' && empty($gif_video)) {
                        $gif_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_4096p_gif.mp4";

                        createGifVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'gif_video_time' => $gif_video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'gif_video_full_path' => $full_dir . $gif_video,
                            'video_id' => $insert,
                        ]);

                    }
                    // gif video
                }
                if ($video_res >= 2048 && $pt->config->p2048 == "on") {

                    $video_output_full_path_2048 = $full_dir . $filepath . "_2048p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_2048,
                        'filepath' => $filepath . "_2048p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 2048,
                        'col' => '2048p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_2048p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 2048,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 1920 || $video_res == 0) && $pt->config->p1080 == "on") {

                    $video_output_full_path_1080 = $full_dir . $filepath . "_1080p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_1080,
                        'filepath' => $filepath . "_1080p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 1920,
                        'col' => '1080p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_1080p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 1920,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 1280 || $video_res == 0) && $pt->config->p720 == "on") {

                    $video_output_full_path_720 = $full_dir . $filepath . "_720p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_720,
                        'filepath' => $filepath . "_720p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 1280,
                        'col' => '720p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_720p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 1280,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 854 || $video_res == 0) && $pt->config->p480 == "on") {

                    $video_output_full_path_480 = $full_dir . $filepath . "_480p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_480,
                        'filepath' => $filepath . "_480p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 854,
                        'col' => '480p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_480p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 854,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 640 || $video_res == 0) && $pt->config->p360 == "on") {

                    $video_output_full_path_360 = $full_dir . $filepath . "_360p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_360,
                        'filepath' => $filepath . "_360p_converted.mp4",
                        'video_id' => $insert,
                        'scale' => 640,
                        'col' => '360p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_360p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $insert,
                            'scale' => 640,
                        ]);

                    }
                    // demo Video
                }
                $db->where('video_id',$insert)->delete(T_QUEUE);

                // demo Video

                // demo Video

                if (!empty($getFileName)) {
                    $db->where("user_id", $pt->user->id)->where("filename", PT_Secure($getFileName))->delete(T_UPLOADED_CUNKS);
                }

                if (!empty($getThumbnailName)) {
                    $db->where("user_id", $pt->user->id)->where("filename", PT_Secure($getThumbnailName))->delete(T_UPLOADED_CUNKS);
                }

                if (file_exists($_POST['video-location'])) {
                    unlink($_POST['video-location']);
                }


                if ($video_privacy == 0) {
                    pt_push_channel_notifiations($video_id);
                }
                $_SESSION['uploads'] = array();
            }
            else{
                $db->insert(T_QUEUE, array('video_id' => $insert,
                                           'video_res' => $video_res,
                                           'processing' => 0));
                $db->where('id', $insert);
                $db->update(T_VIDEOS, array(
                    'video_location' => $_POST['video-location']
                ));
            }
            RegisterPoint($insert, "upload");
            exit();
        }
    } else {
        $data = array(
            'status' => 400,
            'message' => $error_icon . $error
        );
    }
}
