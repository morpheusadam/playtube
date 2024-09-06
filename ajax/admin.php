<?php

if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}
if (PT_IsAdmin() == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not admin'
    );
    echo json_encode($data);
    exit();
}

if ($first == 'save-settings') {
    $submit_data = array();
    foreach ($_POST as $key => $settings_to_save) {
        $submit_data[$key] = $settings_to_save;
    }
    $update = false;
    if (!empty($submit_data)) {
        foreach ($submit_data as $key => $value) {
            $update = $db->where('name', $key)->update(T_CONFIG, array('value' => PT_Secure($value, 0)));
        }
    }
    if ($update) {
        $data = array('status' => 200);
    }
}

if ($first == 'delete-user') {
    if (!empty($_POST['id'])) {
        $delete = PT_DeleteUser(PT_Secure($_POST['id']));
        if ($delete) {
            $data = array('status' => 200);
        }
    }
}



if ($first == 'delete-video') {
    if (!empty($_POST['id'])) {
        $delete = PT_DeleteVideo(PT_Secure($_POST['id']));
        if ($delete) {
            $data = array('status' => 200);
        }
    }
}

if ($first == 'delete-video_ad') {
    if (!empty($_POST['id'])) {
        $delete = $db->where('id', PT_Secure($_POST['id']))->delete(T_VIDEO_ADS);
        if ($delete) {
            $data = array('status' => 200);
        }
    }
}

if ($first == 'delete-multi-videos') {
    if (!empty($_POST['ids'])) {
        foreach ($_POST['ids'] as $key => $id) {
            $delete = PT_DeleteVideo(PT_Secure($id));
        }
        if ($delete) {
            $data = array('status' => 200);
        }
    }
}

if ($first == 'load-more-youtube')  {
    if (!empty($_POST['query']) && !empty($_POST['pageToken'])) {
        $query = PT_Secure(urlencode($_POST['query']));
        $limit = 50;
        if (!empty($_POST['limit']) && $limit < 51) {
            $limit = (int) PT_Secure($_POST['limit']);
        }
        $token = PT_Secure($_POST['pageToken']);
        try {
            $youtube = new Madcoda\Youtube\Youtube(array('key' => $pt->config->yt_api));
            $get_videos = $youtube->searchAdvanced(array(
                'q'             => $query,
                'type'          => 'video',
                'part'          => 'id',
                'maxResults'    => $limit,
                'pageToken'     => $token,
            ), true);
            if (!empty($get_videos)) {
                if ($get_videos['info']['totalResults'] > 0) {
                    $next_token = $get_videos['info']['nextPageToken'];
                    $ids        = array();
                    foreach ($get_videos['results'] as $key => $video) {
                        $check_if_exists = $db->where('youtube', $video->id->videoId)->getValue(T_VIDEOS, 'count(*)');
                        if ($check_if_exists == 0) {
                            $ids[] = $video->id->videoId;
                        }
                    }
                    $ids_implode = implode(',', $ids);
                    if (!empty($ids_implode)) {
                        $youtube_call_url = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails,snippet&id=$ids_implode&key={$pt->config->yt_api}";
                        $get_videos = connect_to_url($youtube_call_url);
                        $get_videos = json_decode($get_videos);
                        if (!empty($get_videos)) {
                            $videos_html = '';
                            foreach ($get_videos->items as $key => $video) {
                                $thumb = PT_GetMedia('upload/photos/thumbnail.jpg');
                                if (!empty($video->snippet->thumbnails->maxres->url)) {
                                    $thumb = $video->snippet->thumbnails->maxres->url;
                                } else if (!empty($video->snippet->thumbnails->medium->url)) {
                                    $thumb = $video->snippet->thumbnails->medium->url;
                                } 
                                $tags = '';
                                if (!empty($video->snippet->tags)) {
                                    $tags_array = array();
                                    if (is_array($video->snippet->tags)) {
                                        $tag_count = 0;
                                        foreach ($video->snippet->tags as $key => $tag) {
                                            if ($tag_count < 11) {
                                                $tags_array[] = $tag;
                                            }
                                            $tag_count++;
                                        }
                                        $tags = implode(',', $tags_array);
                                    }
                                }
                                $duration = '00:00';
                                if (!empty(covtime($video->contentDetails->duration))) {
                                    $duration = covtime($video->contentDetails->duration);
                                }
                                $array_data = array(
                                    'ID' => $video->id,
                                    'TITLE' => $video->snippet->title,
                                    'DESC' => $video->snippet->description,
                                    'THUMB' => $thumb,
                                    'TAGS' => $tags,
                                    'DURATION' => $duration,
                                    'open_from_home_folder' => 1
                                );
                                $videos_html .= PT_LoadAdminPage('import-from-youtube/list', $array_data);
                            }
                            if (!empty($videos_html)) {
                                $data = array('status' => 200, 'html' => $videos_html, 'token' => $next_token);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            
        }

    }
}
if ($first == 'load-more-daily')  {
    if (!empty($_POST['query']) && !empty($_POST['pageToken'])) {
        $query = PT_Secure(urlencode($_POST['query']));
        $limit = 50;
        if (!empty($_POST['limit']) && $limit < 101) {
            $limit = (int) PT_Secure($_POST['limit']);
        }
        $page_id = PT_Secure($_POST['pageToken']);
        $call_url = "https://api.dailymotion.com/videos/?search=$query&page=$page_id&limit=$limit&fields=thumbnail_1080_url,thumbnail_large_url,title,duration,description,tags,id";
        $get_videos = connect_to_url($call_url);
        $get_videos = json_decode($get_videos);
        if (!empty($get_videos)) {
            if (!empty($get_videos->total)) {
                $ids = array();
                foreach ($get_videos->list as $key => $video) {
                    $check_if_exists = $db->where('daily', $video->id)->getValue(T_VIDEOS, 'count(*)');
                    if ($check_if_exists == 0) {
                        $ids[] = $video->id;
                    }
                }
                $next_token = ($page_id < 100) ? ($page_id + 1) : 1;
                if (!empty($ids)) {
                    $videos_html = '';
                    foreach ($get_videos->list as $key => $video) {
                        $thumb = PT_GetMedia('upload/photos/thumbnail.jpg');
                        if (!empty($video->thumbnail_1080_url)) {
                            $thumb = $video->thumbnail_1080_url;
                        } else if (!empty($video->thumbnail_large_url)) {
                            $thumb = $video->thumbnail_large_url;
                        }
                        $tags = '';
                        if (is_array($video->tags)) {
                            $tags_array = array();
                            $tag_count = 0;
                            foreach ($video->tags as $key => $tag) {
                                if ($tag_count < 11) {
                                    $tags_array[] = $tag;
                                }
                                $tag_count++;
                            }
                            $tags = implode(',', $tags_array);
                        }
                        $duration = '00:00';
                        if (!empty($video->duration)) {
                            $duration = gmdate("i:s", $video->duration);
                        }
                        $array_data = array(
                            'ID' => $video->id,
                            'TITLE' => $video->title,
                            'DESC' => $video->description,
                            'THUMB' => $thumb,
                            'TAGS' => $tags,
                            'DURATION' => $duration,
                            'open_from_home_folder' => 1
                        );
                        $videos_html .= PT_LoadAdminPage('import-from-dailymotion/list', $array_data);
                    }
                    if (!empty($videos_html)) {
                        $data = array('status' => 200, 'html' => $videos_html, 'token' => $next_token);
                    }
                } 
            }
        }
    }
}
if ($first == 'import-daily-videos') {
    if (!empty($_POST['videos'])) {
        $ids = array();
        $category_id = 0;
        if (!empty($_POST['category_id'])) {
            if (in_array($_POST['category_id'], array_keys(ToArray($pt->categories)))) {
                $category_id = $_POST['category_id'];
            }
        }
        foreach ($_POST['videos'] as $key => $data_fro_ajax) {
            $video_id  = PT_GenerateKey(15, 15);
            $video_id_ = $data_fro_ajax['video_id'];
            $title = $data_fro_ajax['title'];
            $description = $data_fro_ajax['description'];
            $duration = $data_fro_ajax['duration'];
            $thumb = $data_fro_ajax['thumb'];
            $tags = $data_fro_ajax['tags'];
            $insert = false;
            if (strpos($thumb, 'upload/photos/thumbnail')) {
                $thumb = 'upload/photos/thumbnail.jpg';
            }
            $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
            if ($check_for_video > 0) {
                $video_id = PT_GenerateKey(15, 15);
            }
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i          = 0;
            preg_match_all($link_regex, $description, $matches);
            foreach ($matches[0] as $match) {
                $match_url           = strip_tags($match);
                $syntax              = '[a]' . urlencode($match_url) . '[/a]';
                $description = str_replace($match, $syntax, $description);
            }
            $data_insert = array(
                'video_id' => $video_id,
                'user_id' => $user->id,
                'title' => PT_Secure($title),
                'description' => PT_Secure($description),
                'tags' => PT_Secure($tags),
                'duration' => $duration,
                'category_id' => $category_id,
                'daily' => $video_id_,
                'thumbnail' => $thumb,
                'time' => time(),
                'registered' => date('Y') . '/' . intval(date('m'))
            );
            $insert      = $db->insert(T_VIDEOS, $data_insert);
            if ($insert) {
                if (empty($_SESSION['imported-videos'])) {
                    $_SESSION['imported-videos'] = 1;
                } else {
                    $_SESSION['imported-videos']++;
                }
            }
        }
        if ($insert) {
            $data = array('status' => 200);
        }
    }
}
if ($first == 'import-youtube-videos') {
    if (!empty($_POST['videos'])) {
        $ids = array();
        $category_id = 0;
        if (!empty($_POST['category_id'])) {
            if (in_array($_POST['category_id'], array_keys(ToArray($pt->categories)))) {
                $category_id = $_POST['category_id'];
            }
        }
        foreach ($_POST['videos'] as $key => $data_fro_ajax) {
            $video_id  = PT_GenerateKey(15, 15);
            $video_id_ = $data_fro_ajax['video_id'];
            $title = $data_fro_ajax['title'];
            $description = $data_fro_ajax['description'];
            $duration = $data_fro_ajax['duration'];
            $thumb = $data_fro_ajax['thumb'];
            $tags = $data_fro_ajax['tags'];
            $insert = false;
            if (strpos($thumb, 'upload/photos/thumbnail')) {
                $thumb = 'upload/photos/thumbnail.jpg';
            }
            $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
            if ($check_for_video > 0) {
                $video_id = PT_GenerateKey(15, 15);
            }
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i          = 0;
            preg_match_all($link_regex, $description, $matches);
            foreach ($matches[0] as $match) {
                $match_url           = strip_tags($match);
                $syntax              = '[a]' . urlencode($match_url) . '[/a]';
                $description = str_replace($match, $syntax, $description);
            }
            $data_insert = array(
                'video_id' => $video_id,
                'user_id' => $user->id,
                'title' => PT_Secure($title),
                'description' => PT_Secure($description),
                'tags' => PT_Secure($tags),
                'duration' => $duration,
                'category_id' => $category_id,
                'youtube' => $video_id_,
                'thumbnail' => $thumb,
                'time' => time(),
                'registered' => date('Y') . '/' . intval(date('m'))
            );
            $insert      = $db->insert(T_VIDEOS, $data_insert);
            if ($insert) {
                if (empty($_SESSION['imported-videos'])) {
                    $_SESSION['imported-videos'] = 1;
                } else {
                    $_SESSION['imported-videos']++;
                }
            }
        }
        if ($insert) {
            $data = array('status' => 200);
        }
    }
}

if ($first == 'create-ads') {
    if (!empty($_POST['type'])) {
        if (empty($_POST['name']) || empty($_POST['link']) || empty($_POST['ad_link']) || empty($_POST['type'])) {
            $errors = 'Please check your details';
        } else {
            if (filter_var($_POST['link'], FILTER_VALIDATE_URL) === FALSE) {
                $errors = 'The Media is invalid';
            }
            if (filter_var($_POST['ad_link'], FILTER_VALIDATE_URL) === FALSE) {
                $errors = 'The URL is invalid';
            }
            if (!is_numeric($_POST['skip_seconds'])) {
                $errors = 'The skip seconds should be numeric';
            }
            if ($_POST['type'] == 'image') {
                if (!preg_match("([^\s]+(\.(?i)(jpe?g|png|gif|bmp))$)", $_POST['link'])) {
                    $errors = 'The image url is invalid';
                }
            }
            if ($_POST['type'] == 'vast') {
                if (!preg_match("([^\s]+(\.(?i)(xml))$)", $_POST['link'])) {
                    //$errors = 'The XML url is invalid';
                }
            }
            if ($_POST['type'] == 'video') {
                if (!preg_match("([^\s]+(\.(?i)(mp4|webp|mp3|mpeg|mov))$)", $_POST['link'])) {
                    $errors = 'The video url is invalid';
                }
            }
        }
        if (empty($errors)) {
            $seconds = 0;
            if (!empty($_POST['skip_seconds'])) {
                $seconds = PT_Secure($_POST['skip_seconds']);
            }
            $insert_array = array(
                'user_id' => $user->id,
                'name' => PT_Secure($_POST['name']),
                'skip_seconds' => $seconds,
                'ad_link' => PT_Secure($_POST['ad_link']),
                'active' => 1,
            );
            if ($_POST['type'] == 'video') {
                $insert_array['ad_media'] = PT_Secure($_POST['link']);
            } elseif ($_POST['type'] == 'image') {
                $insert_array['ad_image'] = PT_Secure($_POST['link']);
            } elseif ($_POST['type'] == 'vast') {
                $insert_array['vast_xml_link'] = PT_Secure($_POST['link']);
                $insert_array['vast_type'] = ($_POST['vast_type'] == 'vast') ? 'vast' : 'vpaid';
            }
            $insert = $db->insert(T_VIDEO_ADS, $insert_array);
            if ($insert) {
                $data = array('status' => 200);
            }
        } else {
            $data = array('status' => 400, 'error' => $errors);
        }
    }
}

if ($first == 'edit-ads') {
    if (!empty($_POST['type']) && !empty($_POST['id'])) {
        $id = PT_Secure($_POST['id']);
        if (empty($_POST['name']) || empty($_POST['link']) || empty($_POST['ad_link']) || empty($_POST['type'])) {
            $errors = 'Please check your details';
        } else {
            if (filter_var($_POST['link'], FILTER_VALIDATE_URL) === FALSE) {
                $errors = 'The Media is invalid';
            }
            if (filter_var($_POST['ad_link'], FILTER_VALIDATE_URL) === FALSE) {
                $errors = 'The URL is invalid';
            }
            if (!is_numeric($_POST['skip_seconds'])) {
                $errors = 'The skip seconds should be numeric';
            }
            if ($_POST['type'] == 'image') {
                if (!preg_match("([^\s]+(\.(?i)(jpe?g|png|gif|bmp))$)", $_POST['link'])) {
                    $errors = 'The image url is invalid';
                }
            }
            if ($_POST['type'] == 'vast') {
                if (!preg_match("([^\s]+(\.(?i)(xml))$)", $_POST['link'])) {
                   // $errors = 'The XML url is invalid';
                }
            }
            if ($_POST['type'] == 'video') {
                if (!preg_match("([^\s]+(\.(?i)(mp4|webp|mp3|mpeg|mov))$)", $_POST['link'])) {
                    $errors = 'The video url is invalid';
                }
            }
        }
        if (empty($errors)) {
            $seconds = 0;
            if (!empty($_POST['skip_seconds'])) {
                $seconds = PT_Secure($_POST['skip_seconds']);
            }
            $insert_array = array(
                'user_id' => $user->id,
                'name' => PT_Secure($_POST['name']),
                'skip_seconds' => $seconds,
                'ad_link' => PT_Secure($_POST['ad_link']),
                'active' => 1,
            );
            if ($_POST['type'] == 'video') {
                $insert_array['ad_media'] = PT_Secure($_POST['link']);
            } elseif ($_POST['type'] == 'image') {
                $insert_array['ad_image'] = PT_Secure($_POST['link']);
            } elseif ($_POST['type'] == 'vast') {
                $insert_array['vast_xml_link'] = PT_Secure($_POST['link']);
                $insert_array['vast_type'] = ($_POST['vast_type'] == 'vast') ? 'vast' : 'vpaid';
            }
            $insert = $db->where('id', $id)->update(T_VIDEO_ADS, $insert_array);
            if ($insert) {
                $data = array('status' => 200);
            }
        } else {
            $data = array('status' => 400, 'error' => $errors);
        }
    }
}

if ($first == 'update-ads') {
    $updated = false;
    foreach ($_POST as $key => $ads) {
        if ($key != 'hash_id') {
            $ad_data = array(
                'code' => htmlspecialchars(base64_decode($ads)),
                'active' => (empty($ads)) ? 0 : 1
            );
            $update = $db->where('placement', PT_Secure($key))->update(T_ADS, $ad_data);
            if ($update) {
                $updated = true;
            }
        }
    }
    if ($updated == true) {
        $data = array(
            'status' => 200
        );
    }
}

if ($first == 'submit-sitemap-settings') {
    if (!file_exists('./sitemaps')) {
        @mkdir('./sitemaps', 0777, true);
    }
    $dom = new DOMDocument();
    $filename = 'sitemaps/sitemap.xml';
    if (isset($_POST['percentage'])) {
        $start_from = (!empty($_POST['start_from'])) ? (int) $_POST['start_from'] : 0;
        $file_number = (!empty($_POST['file_number'])) ? (int) $_POST['file_number'] : 0;
        $added_videos = (!empty($_POST['added_videos'])) ? (int) $_POST['added_videos'] : 0;
        $mysql = $db->where('id', $start_from, '>')->get(T_VIDEOS, 500);
        $total_videos =  $db->getValue(T_VIDEOS, 'count(*)');
        if ($total_videos < 500) {
           $mysql = $db->get(T_VIDEOS);
        }
        
        $percentage = round(($start_from / $total_videos) * 100, 2);
        if ($percentage > 100) {
            $percentage = 100;
        }
        $sitemap_numbers = ceil($total_videos / 20000);
        if ($_POST['percentage'] == 0) {
            $files = glob('./sitemaps/*');
            foreach($files as $file){ 
              if(is_file($file))
                unlink($file);
            }
            for ($i=1; $i <= $sitemap_numbers; $i++) { 
                $open_file = fopen("sitemaps/sitemap-" . $i . ".xml", "w");
            }
        }  else {
            $write_data = file_get_contents('sitemaps/sitemap-' . $file_number . '.xml');
        }
        
        if (filesize('sitemaps/sitemap-' . $file_number . '.xml') < 1) {
            $write_data = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        }
        foreach ($mysql as $key => $video) {
            $video = PT_GetVideoByID($video, 0, 0 , 0);
            $write_data .= '<url>
      <loc>' . $video->url . '</loc>
      <lastmod>' . date('c', $video->time). '</lastmod>
      <changefreq>monthly</changefreq>
      <priority>0.8</priority>
   </url>' . "\n";
            $added_videos++;
        }
        $url_set_end = "\n</urlset>";
        $file_number_final = $file_number;
        $file_ = file_put_contents('sitemaps/sitemap-' . $file_number . '.xml', $write_data);
        if ($percentage == 100) {
            $file_ = file_put_contents('sitemaps/sitemap-' . $file_number . '.xml', file_get_contents('sitemaps/sitemap-' . $file_number . '.xml') . $url_set_end);
            $files = glob('./sitemaps/*');
            $write_final_data = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" >';
            foreach($files as $file){ 
                  if (is_file($file)) {
                      $write_final_data .= "\n<sitemap>
  <loc>" . $pt->config->site_url . '/' . str_replace('./', '', $file) . "</loc>
  <lastmod>" . date('c') . "</lastmod>
</sitemap>";
                  }
            }
            $write_final_data .= '</sitemapindex>';
            $file_final = file_put_contents('sitemap-main.xml', $write_final_data);
        } else if ($added_videos > 20000 && $added_videos < $total_videos) {
            $file_ = file_put_contents('sitemaps/sitemap-' . $file_number . '.xml', file_get_contents('sitemaps/sitemap-' . $file_number . '.xml') . $url_set_end);
            if (file_exists('sitemaps/sitemap-' . ($file_number + 1) . '.xml')) {
                $file_number_final = $file_number + 1;
                $added_videos = 0;
            }
        }
        if ($file_) {
            $data = array('status' => 201, 'start_from' => ($start_from + 500), 'percentage_full' => $percentage . '%', 'percentage' => $percentage, 'file_number' => $file_number_final, 'added_videos' => $added_videos);
            if ($percentage == 100) {
                $data['last_created'] = date('d-m-Y');
                $last_created_update =  $update = $db->where('name', 'last_created_sitemap')->update(T_CONFIG, array('value' => PT_Secure($data['last_created'], 0)));
            }
        }
    }
}

if ($first == 'save-design') {
    $saveSetting = false;
    if (isset($_FILES['logo']['name'])) {
        $fileInfo = array(
            'file' => $_FILES["logo"]["tmp_name"],
            'name' => $_FILES['logo']['name'],
            'size' => $_FILES["logo"]["size"]
        );
        $media    = PT_UploadLogo($fileInfo);
    }
    if (isset($_FILES['light-logo']['name'])) {
        $fileInfo = array(
            'file' => $_FILES["light-logo"]["tmp_name"],
            'name' => $_FILES['light-logo']['name'],
            'size' => $_FILES["light-logo"]["size"],
            'light-logo' => true
        );
        $media    = PT_UploadLogo($fileInfo);
    }
    if (isset($_FILES['favicon']['name'])) {
        $fileInfo = array(
            'file' => $_FILES["favicon"]["tmp_name"],
            'name' => $_FILES['favicon']['name'],
            'size' => $_FILES["favicon"]["size"],
            'favicon' => true
        );
        $media    = PT_UploadLogo($fileInfo);
    }
    $data['status'] = 200;
}

if ($first == 'save-terms') {
    $saveSetting = false;
    foreach ($_POST as $key => $value) {
        if ($key != 'hash_id') {
            $saveSetting = $db->where('type', $key)->update(T_TERMS, array('text' => PT_Secure(base64_decode($value), 0)));
        }
    }
    if ($saveSetting) {
        $data['status'] = 200;
    }
}

if ($first == 'new-article') {
    $error = false;
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['text']) || empty($_POST['tags']) || empty($_FILES["image"])) {
        $error = 400; 
    }
    else{

        if (strlen($_POST['title']) < 5) {
            $error = 401; 
        }

        else if(strlen($_POST['description']) < 15){
            $error = 402; 
        }

        else if(empty($_POST['text'])){
            $error = 403; 
        }

        else if (!empty($_FILES["image"]["error"])) {
            $error = 404; 
        } 
 

        else if (!file_exists($_FILES["image"]["tmp_name"])) {
            $error = 405; 
        }

        else if (file_exists($_FILES["image"]["tmp_name"])) {
            $image = getimagesize($_FILES["image"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP
            ))){
                $error = 405; 
            }
        }

        else if (empty($_POST['category']) || !is_numeric($_POST['category']) || !in_array($_POST['category'],array_keys(get_object_vars($pt->categories)))) {
            $error = 406; 
        }
    }

    if (empty($error)) {

        $file_info   = array(
            'file' => $_FILES['image']['tmp_name'],
            'size' => $_FILES['image']['size'],
            'name' => $_FILES['image']['name'],
            'type' => $_FILES['image']['type'],
            'crop' => array(
                'width' => 600,
                'height' => 400
            )
        );

        $file_upload     = PT_ShareFile($file_info);
        $insert          = false;
        $active          = (isset($_POST['draft'])) ? '0' : '1';

        if (!empty($file_upload['filename'])) {
            $post_image  = PT_Secure($file_upload['filename']);
            $insert_data = array(
                'title' => PT_Secure(PT_ShortText($_POST['title'],150)),
                'description' => PT_Secure(PT_ShortText($_POST['description'],200)),
                'category' => PT_Secure($_POST['category']),
                'image' => $post_image,
                'text' => htmlspecialchars($_POST['text']),
                'tags' => PT_Secure(PT_ShortText($_POST['tags']),250),
                'time' => time(),
                'user_id' => $pt->user->id,
                'active' => $active,
                'views' => 0,
                'shared' => 0,
            );

            $insert     = $db->insert(T_POSTS,$insert_data);
        }

        $data['status'] = ($insert) ? 200 : 500;
        $data['url']    = PT_Link('articles/read/' . PT_URLSlug($insert_data['title'],$insert));
    }

    else{
        $data['status'] = $error;
    }
}

if ($first == 'update-article') {
    $error = false;
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['text']) || empty($_POST['tags'])) {
        $error = 400; 
    }
    else{

        if (strlen($_POST['title']) < 5) {
            $error = 401; 
        }

        else if(strlen($_POST['description']) < 15){
            $error = 402; 
        }

        else if(empty($_POST['text'])){
            $error = 403; 
        }
        else if (!empty($_FILES["image"])) {

            if (!empty($_FILES["image"]["error"])) {
                $error = 404; 
            } 

            else if (!file_exists($_FILES["image"]["tmp_name"])) {
                $error = 405; 
            }

            else if (file_exists($_FILES["image"]["tmp_name"])) {
                $image = getimagesize($_FILES["image"]["tmp_name"]);
                if (!in_array($image[2], array(
                    IMAGETYPE_GIF,
                    IMAGETYPE_JPEG,
                    IMAGETYPE_PNG,
                    IMAGETYPE_BMP
                ))){
                    $error = 405; 
                }
            }
        }



        else if (empty($_POST['category']) || !is_numeric($_POST['category']) || !in_array($_POST['category'],array_keys(get_object_vars($pt->categories)))) {
            $error = 406; 
        }

        else if(empty($_POST['id']) || !is_numeric($_POST['id'])){
            $error = 500; 
        }
    }

    if (empty($error)) {
        $insert      = false;
        $active      = (isset($_POST['draft'])) ? '0' : '1';
        $id          = PT_Secure($_POST['id']);

        $update_data = array(
            'title' => PT_Secure(PT_ShortText($_POST['title'],150)),
            'description' => PT_Secure(PT_ShortText($_POST['description'],200)),
            'category' => PT_Secure($_POST['category']),
            'text' => htmlspecialchars($_POST['text']),
            'tags' => PT_Secure(PT_ShortText($_POST['tags']),250),
            'time' => time(),
            'user_id' => $pt->user->id,
            'active' => $active,
            'views' => 0,
            'shared' => 0,
        );

        if (!empty($_FILES["image"])) {
            $file_info   = array(
                'file' => $_FILES['image']['tmp_name'],
                'size' => $_FILES['image']['size'],
                'name' => $_FILES['image']['name'],
                'type' => $_FILES['image']['type'],
                'crop' => array(
                    'width' => 600,
                    'height' => 400
                )
            );
            $file_upload     = PT_ShareFile($file_info);
            if (!empty($file_upload['filename'])) {
                $update_data['image'] = PT_Secure($file_upload['filename']);  
            }
            else{
                $error = true;
            }
        }

        $insert         = $db->where('id',$id)->update(T_POSTS,$update_data);
        $data['status'] = ($insert && empty($error)) ? 200 : 500;
        $data['url']    = PT_Link('articles/read/' . PT_URLSlug($update_data['title'],$id));
    }

    else{
        $data['status'] = $error;
    }
}

if ($first == 'delete-article') {
    if (!empty($_POST['id'])) {
        $article = $db->where('id',PT_Secure($_POST['id']))->getOne(T_POSTS);
        if (!empty($article)) {
            if (file_exists($article->image)) {
                unlink($article->image);
            }
            
            else if ($pt->remoteStorage === true) {
                PT_DeleteFromToS3($article->image);
            }
        
            $delete  = $db->where('id',PT_Secure($_POST['id']))->delete(T_POSTS);
            $delete  = $db->where('post_id',PT_Secure($_POST['id']))->delete(T_DIS_LIKES);

            //Delete related data
            $post_comments = $db->where('post_id',PT_Secure($_POST['id']))->get(T_COMMENTS);

            foreach ($post_comments as $comment_data) {
                $delete    = $db->where('comment_id',$comment_data->id)->delete(T_COMMENTS_LIKES);
                $replies   = $db->where('comment_id',$comment_data->id)->get(T_COMM_REPLIES);
                $db->where('comment_id',$comment_data->id)->delete(T_COMM_REPLIES);
                
                foreach ($replies as $comment_reply) {
                    $db->where('reply_id',$comment_reply->id)->delete(T_COMMENTS_LIKES);
                }
            }

            if (!empty($post_comments)) {
                $delete    = $db->where('post_id',PT_Secure($_POST['id']))->delete(T_COMMENTS);   
            }
            
            if ($delete) {
                $data = array('status' => 200);
            }
        }
    }
}

if ($first == 'delete-user-ad') {
    if (!empty($_POST['id'])) {
        $ad_data = $db->where('id',PT_Secure($_POST['id']))->getOne(T_USR_ADS);
        if (!empty($ad_data)) {
            if (file_exists($ad_data->media)) {
                unlink($ad_data->media);
            }
            
            else if ($pt->remoteStorage === true) {
                PT_DeleteFromToS3($ad_data->media);
            }
        
            $delete  = $db->where('id',PT_Secure($_POST['id']))->delete(T_USR_ADS);
            if ($delete) {
                $data = array('status' => 200);
            }
        }
    }
}

if ($first == 'backup') {
    $backup = PT_Backup($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
    if ($backup) {
        $data['status'] = 200;
        $data['date']   = date('d-m-Y');
    }
}



if ($first == 'testS3') {
    try {
        $s3Client = S3Client::factory(array(
            'version' => 'latest',
            'region' => $pt->config->region,
            'credentials' => array(
                'key' => $pt->config->amazone_s3_key,
                'secret' => $pt->config->amazone_s3_s_key
            )
        ));

        $buckets  = $s3Client->listBuckets();
        if (!empty($buckets)) {
            if ($s3Client->doesBucketExist($pt->config->s3_bucket_name)) {
                $data['status'] = 200;
                $array          = array(
                    'upload/photos/d-cover.jpg',
                    'upload/photos/d-avatar.jpg',
                    'upload/photos/thumbnail.jpg',
                );
                foreach ($array as $key => $value) {
                    $upload = PT_UploadToS3($value, array(
                        'delete' => 'no'
                    ));
                }
            } 

            else {
                $data['status'] = 300;
            }
        }
        else {
            $data['status'] = 500;
        }
    }

    catch (Exception $e) {
        $data['status']  = 400;
        $data['message'] = $e->getMessage();
    }
}

if ($first == 'add-field') {
    if (!empty($_POST['name']) && !empty($_POST['type']) && !empty($_POST['description'])) {
        $type              = PT_Secure($_POST['type']);
        $name              = PT_Secure($_POST['name']);
        $description       = PT_Secure($_POST['description']);
        $registration_page = 0;
        if (!empty($_POST['registration_page'])) {
            $registration_page = 1;
        }
        $profile_page = 0;
        if (!empty($_POST['profile_page'])) {
            $profile_page = 1;
        }
        $length = 32;
        if (!empty($_POST['length'])) {
            if (is_numeric($_POST['length']) && $_POST['length'] < 1001) {
                $length = PT_Secure($_POST['length']);
            }
        }
        $placement_array = array(
            'profile',
            'general',
            'social',
            'none'
        );
        $placement       = 'profile';
        if (!empty($_POST['placement'])) {
            if (in_array($_POST['placement'], $placement_array)) {
                $placement = PT_Secure($_POST['placement']);
            }
        }
        $re_data = array(
            'name' => $name,
            'description' => $description,
            'length' => $length,
            'placement' => $placement,
            'registration_page' => $registration_page,
            'profile_page' => $profile_page,
            'active' => '1'
        );
        if (!empty($_POST['options'])) {
            $options              = @explode("\n", trim($_POST['options']));
            $re_data['options']   = PT_Secure(implode($options, ','));
        }

        $re_data['type']    = $type;
        $add                = PT_RegisterNewField($re_data);

        if ($add) {
            $data['status'] = 200;
        }
    }

    else {
        $data = array(
            'status' => 400,
            'message' => 'Please fill all the required fields'
        );
    }
}

if ($first == 'delfield' && !empty($_GET['id'])) {
    $data = array('status' => 304);
    if (pt_delete_field($_GET['id']) === true) {
        $data['status'] = 200;
    }
}

if ($first == 'update-field') {
    if (!empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['id'])) {
        $name              = PT_Secure($_POST['name']);
        $description       = PT_Secure($_POST['description']);
        $registration_page = 0;

        if (!empty($_POST['registration_page'])) {
            $registration_page = 1;
        }
        $profile_page = 0;
        if (!empty($_POST['profile_page'])) {
            $profile_page = 1;
        }
        $active = '0';
        if (!empty($_POST['active'])) {
            $active = '1';
        }
        $length = 32;
        if (!empty($_POST['length'])) {
            if (is_numeric($_POST['length']) && $_POST['length'] < 1001) {
                $length = PT_Secure($_POST['length']);
            }
        }
        $placement_array = array(
            'profile',
            'general',
            'social',
            'none'
        );
        $placement       = 'profile';

        if (!empty($_POST['placement'])) {
            if (in_array($_POST['placement'], $placement_array)) {
                $placement = PT_Secure($_POST['placement']);
            }
        }
        $up_data = array(
            'name' => $name,
            'description' => $description,
            'length' => $length,
            'placement' => $placement,
            'registration_page' => $registration_page,
            'profile_page' => $profile_page,
            'active' => $active
        );

        if (!empty($_POST['options'])) {
            $options            = @explode("\n", trim($_POST['options']));
            $up_data['options'] = implode($options, ',');
            $up_data['type']    = 'select';
        }

        $table = T_FIELDS;
        $add   = $db->where('id',$_POST['id'])->update($table,$up_data);
        if ($add) {
            $data['status'] = 200;
        }
    }

    else{
        $data = array(
            'status' => 400,
            'message' => 'Please fill all the required fields'
        );
    }
}

if ($first == 'withdrawal-requests' && !empty($_POST['id']) && !empty($_POST['a'])) {
    $request = (is_numeric($_POST['id']) && is_numeric($_POST['a']) && in_array($_POST['a'], array(1,2,3)));

    if ($request === true) {
        $request_id = PT_Secure($_POST['id']);
        if ($_POST['a'] == 1) {
            $request_data = $db->where('id',$request_id)->getOne(T_WITHDRAWAL_REQUESTS);
            if (!empty($request_data) && $request_data->status != 1) {
                $requiring = $db->where('id',$request_data->user_id)->getOne(T_USERS);
                if (!empty($requiring)) {
                    $db->where('id',$request_data->user_id)->update(T_USERS,array(
                        'balance' => ($requiring->balance -= 50)
                    ));
                }
            }

            $db->where('id',$request_id)->update(T_WITHDRAWAL_REQUESTS,array('status' => 1));
        }

        else if ($_POST['a'] == 2) {
            $db->where('id',$request_id)->update(T_WITHDRAWAL_REQUESTS,array('status' => 2));
        }

        else if ($_POST['a'] == 3) {
            $db->where('id',$request_id)->delete(T_WITHDRAWAL_REQUESTS);
        }

        $data['status'] = 200;
    }
}

if ($first == 'verification' && !empty($_POST['id']) && !empty($_POST['a'])) {
    $request = (is_numeric($_POST['id']) && is_numeric($_POST['a']) && in_array($_POST['a'], array(1,2,3)));

    if ($request === true) {

        $request_id    = PT_Secure($_POST['id']);
        $request_data  = $db->where('id',$request_id)->getOne(T_VERIF_REQUESTS);

        if ($_POST['a'] == 1 && !empty($request_data)) {
            $up_data = array(
                'verified' => 1
            );

            $db->where('id',$request_data->user_id)->update(T_USERS,$up_data);
            $db->where('id',$request_id)->delete(T_VERIF_REQUESTS);
            $data['status'] = 200;
        }

        else if ($_POST['a'] == 2 && !empty($request_data)) {
            $user_data      = PT_UserData($request_data->user_id);
            $data['status'] = 200;
            $data['html']   = PT_LoadAdminPage('verification-requests/view',array(
                'ID' => $request_data->id,
                'USERNAME' => $request_data->name,
                'USER_AVATAR' => $user_data->avatar,
                'TEXT' => $request_data->message,
                'DATE' => date("Y-F-d",$request_data->time),
                'IMG' => PT_GetMedia($request_data->media_file),
            ));
        }

        else if ($_POST['a'] == 3) {
            $db->where('id',$request_id)->delete(T_VERIF_REQUESTS);
            $data['status'] = 200;
        }
    }
}

if ($first == 'add_announcement') {
    $text           = (!empty($_POST['text'])) ? PT_Secure($_POST['text']) : "";
    $data['status'] = 400;
    $re_data        = array(
        'text'      => $text,
        'active'    => '1',
        'time'      => time()
    );

    $insert_id          = $db->insert(T_ANNOUNCEMENTS,$re_data);

    if (!empty($insert_id)) {
        $announcement   = $db->where('id',$insert_id)->getOne(T_ANNOUNCEMENTS);
        $data['status'] = 200;
        $data['html']   =  PT_LoadAdminPage("manage-announcements/active",array(
            'ANN_ID'    => $announcement->id,
            'ANN_VIEWS' => 0,
            'ANN_TEXT'  => PT_Decode($announcement->text),
            'ANN_TIME'  => PT_Time_Elapsed_String($announcement->time),
        ));
    }
}


if ($first == 'delete-announcement') {
    $request        = (!empty($_POST['id']) && is_numeric($_POST['id']));
    $data['status'] = 400;
    if ($request === true) {
        $announcement_id = PT_Secure($_POST['id']);
        $db->where('id',$announcement_id)->delete(T_ANNOUNCEMENTS);
        $data['status'] = 200;
    }
}

if ($first == 'toggle-announcement') {
    $request        = (!empty($_POST['id']) && is_numeric($_POST['id']));
    $data['status'] = 400;

    if ($request === true) {

        $announcement_id    = PT_Secure($_POST['id']);
        $announcement       = $db->where('id',$announcement_id)->getOne(T_ANNOUNCEMENTS);
        if (!empty($announcement)) {
            $status         = ($announcement->active == 1) ? '0' : '1';

            $db->where('id',$announcement_id)->update(T_ANNOUNCEMENTS,array('active' => $status));
            $data['status'] = 200;
            echo $status;
            exit();
        }

    }
}

if ($first == 'banip' && !empty($_POST['ip'])) {
    $data        = array('status' => 400);
    $request     = filter_var($_POST['ip'], FILTER_VALIDATE_IP);
    if (!empty($request)){
        $table   = T_BANNED_IPS;
        $re_data = array(
            'ip_address' => $_POST['ip'],
            'time'       => time()
        );

        $ban_id  =  $db->insert($table,$re_data);
        $ban_ip  = $db->where('id',$ban_id)->getOne($table);
        
        if (!empty($ban_ip)) {
            $data['status']       = 200;
            $data['html']         = PT_LoadAdminPage("ban-users/list",array(
                'BANNEDIP_ID'     => $ban_ip->id,
                'BANNEDIP_TIME'   => PT_Time_Elapsed_String($ban_ip->time),
                'BANNEDIP_ADDR'   => $ban_ip->ip_address,
            ));
        }
    }
}

if ($first == 'unbanip') {
    $data    = array('status' => 400);
    $request = (!empty($_POST['id']) && is_numeric($_POST['id']));
    if (!empty($request)){
        $table  = T_BANNED_IPS;
        $ban_id = PT_Secure($_POST['id']);
        $db->where('id',$ban_id)->delete($table);
        $data['status'] = 200;
    }
}

if ($first == 'save-custom-design-settings') {
    $data     = array('status' => 200);
    $code     = array(); 
    $code[]   = (!empty($_POST['header_js']))  ? $_POST['header_js']  : "";
    $code[]   = (!empty($_POST['footer_js']))  ? $_POST['footer_js']  : "";
    $code[]   = (!empty($_POST['css_styles'])) ? $_POST['css_styles'] : "";
    $errors   = pt_custom_design('save',$code);

    if (!empty($errors)) {
        $data = array('status' => 500,'errors' => $errors);
    }
}

if ($first == 'reset_apps_key') {
    $app_key     = sha1(microtime());
    $data_config = array(
        'apps_api_key' => $app_key
    );

    foreach ($data_config as $name => $value) {
        $db->where('name', $name)->update(T_CONFIG, array('value' => PT_Secure($value, 0)));
    }

    $data['status']  = 200;
    $data['app_key'] = $app_key;
}
