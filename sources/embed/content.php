<?php 
if ($pt->config->embed_system == 'off') {
    exit('Embed is disabled');
}
if (empty($_GET['id'])) {
   exit('Invalid URL');
}
$_GET['id'] = strip_tags($_GET['id']);
$id = PT_Secure($_GET['id']);

if (strpos($id, '_') !== false) {
    $id_array = explode('_', $id);
    $id_html  = $id_array[1];
    $id       = str_replace('.html', '', $id_html);
}

$get_video = $db->where('video_id', $id)->getOne(T_VIDEOS);
if (empty($get_video)) {
	exit('Video not found');
}
if ($pt->config->require_login == 'on' && !IS_LOGGED) {
    exit('please login to watch video');
}
if ($get_video->embedding != 0) {
    exit("Video is private");
}


$get_video->age = false;
if ($get_video->age_restriction == 2) {
    exit("Age restrcited videos can't be embeded.");
}

$pt->video_approved = true;

if ($pt->config->approve_videos == 'on') {
    if ($get_video->approved == 0) {
        exit("Video is under review");
    }
}


$pt->video_type = 'public';

if ($get_video->privacy == 1) {
    if (!IS_LOGGED) {
        exit("Video is private");
    } else if (($get_video->user_id != $user->id) && ($user->admin == 0)) {
        exit("Video is private");
    }
} 

if (!empty($get_video->geo_blocking) && $pt->config->geo_blocking == 'on') {
    exit("Video have location blocking it can't be visible");
}

if (!empty($get_video->sell_video)) {
    exit("This Video can't be visible");
}
$pt->continent_hide = false;
if (!empty($get_video->geo_blocking) && $pt->config->geo_blocking == 'on') {
    $blocking_array = json_decode($get_video->geo_blocking);
    if ((empty($_COOKIE['r']) || !in_array(base64_decode($_COOKIE['r']), $pt->continents)) && !PT_IsAdmin() && !$pt->video_owner) {
        $pt->continent_hide = true;
    }
    else if (in_array(base64_decode($_COOKIE['r']), $blocking_array) && !PT_IsAdmin() && !$pt->video_owner) {
        $pt->continent_hide = true;
    }
}
$pt->converted   = true;

if ($pt->config->ffmpeg_system == 'on' && $get_video->converted != 1) {
    $pt->converted = false;
}


if (strpos($get_video->thumbnail, 'upload/photos') !== false) {
    $get_video->thumbnail      = PT_GetMedia($get_video->thumbnail);
    
    $video_type                = 'video/mp4';
    $video_id_                 = $get_video->video_id;
}
if (strpos($get_video->video_location, 'pload/videos') !== false) {
    $get_video->video_location = PT_GetMedia($get_video->video_location);
}
else{
    $get_video->video_location = urldecode($get_video->video_location);
}
if (!empty($get_video->youtube)) {
    $video_type                = 'video/youtube';
    $get_video->video_location = 'https://www.youtube.com/watch?v=' . $get_video->youtube;
    $video_id_                 = $get_video->youtube;
}
if (!empty($get_video->daily)) {
    $video_type = 'video/dailymotion';
    $video_id_  = $get_video->daily;
}
if (!empty($get_video->vimeo)) {
    $video_type = 'video/vimeo';
    $video_id_  = $get_video->vimeo;
}
$pt->get_video   = $get_video;

$pt->autoplay = 0;
if (isset($_GET['autoplay'])) {
    $_GET['autoplay'] = strip_tags($_GET['autoplay']);
    if ($_GET['autoplay'] == 1) {
        $pt->autoplay = 1;
    }
}
$pt->height = "100%";
if (!empty($_GET['height'])) {
    $_GET['height'] = strip_tags($_GET['height']);
    $pt->height = $_GET['height'] . "px";
}
$pt->fullscreen = 1;
if (isset($_GET['fullscreen'])) {
    $_GET['fullscreen'] = strip_tags($_GET['fullscreen']);
    if ($_GET['fullscreen'] == 0) {
        $pt->fullscreen = 0;
    }
}

if ($pt->config->ffmpeg_system == 'on') {
    $explode_video = explode('_video', $get_video->video_location);
    if ($get_video->{"240p"} == 1) {
        $get_video->video_location = $explode_video[0] . '_video_240p_converted.mp4';
    }
    if ($get_video->{"360p"} == 1) {
        $get_video->video_location = $explode_video[0] . '_video_360p_converted.mp4';
    }
    if ($get_video->{"480p"} == 1) {
        $get_video->video_location = $explode_video[0] . '_video_480p_converted.mp4';
    }
    if ($get_video->{"720p"} == 1) {
        $get_video->video_location = $explode_video[0] . '_video_720p_converted.mp4';
    }
}







// create Ads

$vast_url = '';
$vast_type = '';
$ad_media = '';
$ad_link = '';
$ad_skip = 0;
$ad_skip_num = 0;
$last_ads = 0;
$is_pro  = false;
$is_video_ad = '';
$ad_desc = '';
$is_vast_ad = '';
$ad_image = '';
$user_ad_trans = '';
$vid_monit = true;
$user_data = PT_UserData($get_video->user_id);
if ($user_data->privacy->who_can_watch_my_videos == 'subscribers') {
    $get_video->is_owner = false;
    if (IS_LOGGED && $get_video->user_id == $pt->user->id) {
        $get_video->is_owner = true;
    }

    if (!$get_video->is_owner) {
        exit("Please subscribe to watch this video");
    }
    
    if (IS_LOGGED && !$pt->get_video->is_owner) {
        $is_sub = $db->where('user_id', $get_video->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
        if ($is_sub == 0) {
            exit("Please subscribe to watch this video");
        }
    }
}
if ($user_data->subscriber_price) {
    exit('please subscribe to watch video');
}

if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1'))) {
    $vid_monit = ($user_data->video_mon == 0) ? false : true;
}
$ads_sys = ($pt->config->user_ads == 'on') ? true : false;
if (!empty($_COOKIE['last_ads_seen'])) {
    if ($_COOKIE['last_ads_seen'] > (time() - 600)) {
        $last_ads = 1;
    }
}
if (IS_LOGGED === true) {
    if ($user->is_pro == 1 && $pt->config->go_pro == 'on') {
        $is_pro = true;
    }
}

if ($last_ads == 0 && $ads_sys && !$is_pro) {
    $rand      = (rand(0,1)) ? rand(0,1) :(rand(0,1) ? : rand(0,1));
    
    if ($rand == 0) {
        $get_random_ad = $db->where('active', 1)->orderBy('RAND()')->getOne(T_VIDEO_ADS);
        $sidebar_ad    = PT_GetAd('watch_side_bar');
        if (!empty($get_random_ad)) {

            if (!empty($get_random_ad->ad_media)) {
                $ad_media = $get_random_ad->ad_media;
                $ad_link = PT_Link('redirect/' . $get_random_ad->id . '?type=video');
                $is_video_ad = ",'ads'";
            }

            if (!empty($get_random_ad->vast_xml_link)) {
                $vast_url = $get_random_ad->vast_xml_link;
                $vast_type = $get_random_ad->vast_type;
                $is_vast_ad = ",'vast'";
            }

            if ($get_random_ad->skip_seconds > 0) {
                $ad_skip = 'true';
                $ad_skip_num = $get_random_ad->skip_seconds;
            }

            if (!empty($get_random_ad->ad_image)) {
                $ad_image = $pt->ad_image = $get_random_ad->ad_image;
                $ad_link = PT_Link('redirect/' . $get_random_ad->id . '?type=image');
            }

            $update_clicks = $db->where('id', $get_random_ad->id)->update(T_VIDEO_ADS, array(
                'views' => $db->inc(1)
            ));
            $cookie_name = 'last_ads_seen';
            $cookie_value = time();
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
        }
    } 

    else if ($rand == 1 && $vid_monit) {
        $user_ads      = pt_get_user_ads();
        // echo  $db->getLastQuery();
        // exit();
        if (!empty($user_ads)) {  
            $get_random_ad =  $user_ads;
            $random_ad_id  = $get_random_ad->id;
            $ad_skip       = 'true';
            $ad_link       = urldecode($get_random_ad->url);
            $ad_skip_num   = 5;
            
            if ($user_ads->type == 1) {
                $user_ad_trans   = "rad-transaction";
                $_SESSION['ua_'] = $random_ad_id;
                $_SESSION['vo_'] = $get_video->user_id;
            }

            else{
                pt_register_ad_views($random_ad_id,$get_video->user_id); 
                $db->insert(T_ADS_TRANS,array('type' => 'view', 'ad_id' => $random_ad_id, 'video_owner' => $get_video->user_id, 'time' => time()));
            }

            if ($user_ads->category == 'video') {
                $ad_media      = PT_GetMedia($get_random_ad->media);
                $is_video_ad   = ",'ads'";
                $ad_desc       = PT_LoadPage("ads/includes/d-overlay",array(
                    "AD_TITLE" => PT_ShortText($user_ads->headline,40),
                    "AD_DESC" => PT_ShortText($user_ads->description,70),
                    "AD_URL" => urldecode($user_ads->url),
                    "AD_URL_NAME" => pt_url_domain(urldecode($user_ads->url)),
                ));
            }
            
            else if ($user_ads->category == 'image') {
                $ad_image = $pt->ad_image = PT_GetMedia($get_random_ad->media);
            }

            
            $cookie_name = 'last_ads_seen';
            $cookie_value = time();
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
        } 
    }
}
$pt->video_240 = 0;
$pt->video_360 = 0;
$pt->video_480 = 0;
$pt->video_720 = 0;
$pt->video_1080 = 0;
$pt->video_2048 = 0;
$pt->video_4096 = 0;

if ($pt->config->ffmpeg_system == 'on') {
    $explode_video = explode('_video', $get_video->video_location);
    if ($get_video->{"240p"} == 1) {
        $pt->video_240 = $explode_video[0] . '_video_240p_converted.mp4';
    }
    if ($get_video->{"360p"} == 1) {
        $pt->video_360 = $explode_video[0] . '_video_360p_converted.mp4';
    }
    if ($get_video->{"480p"} == 1) {
        $pt->video_480 = $explode_video[0] . '_video_480p_converted.mp4';
    }
    if ($get_video->{"720p"} == 1) {
        $pt->video_720 = $explode_video[0] . '_video_720p_converted.mp4';
    }
    if ($get_video->{"1080p"} == 1) {
        $pt->video_1080 = $explode_video[0] . '_video_1080p_converted.mp4';
    }
    if ($get_video->{"4096p"} == 1) {
        $pt->video_4096 = $explode_video[0] . '_video_4096p_converted.mp4';
    }
    if ($get_video->{"2048p"} == 1) {
        $pt->video_2048 = $explode_video[0] . '_video_2048p_converted.mp4';
    }
}
// create Ads

echo PT_LoadPage('embed/content', array('ID' => $get_video->id,
    'THUMBNAIL' => $get_video->thumbnail,
    'TITLE' => $get_video->title,
    'DESC' => $get_video->description,
    'URL' => PT_Link('watch/' . PT_Slug($get_video->title, $get_video->video_id)),
    'VIDEO_LOCATION_240' => $pt->video_240,
    'VIDEO_LOCATION' => $get_video->video_location,
    'VIDEO_LOCATION_360' => $pt->video_360,
    'VIDEO_LOCATION_480' => $pt->video_480,
    'VIDEO_LOCATION_720' => $pt->video_720,
    'VIDEO_LOCATION_1080' => $pt->video_1080,
    'VIDEO_LOCATION_4096' => $pt->video_4096,
    'VIDEO_LOCATION_2048' => $pt->video_2048,
    'VIDEO_TYPE' => $video_type,
    'VIDEO_ID' => $video_id_,
    'VAST_URL' => $vast_url,
    'VAST_TYPE' => $vast_type,
    'AD_MEDIA' => "'$ad_media'",
    'AD_LINK' => "'$ad_link'",
    'AD_P_LINK' => "$ad_link",
    'AD_SKIP' => $ad_skip,
    'AD_SKIP_NUM' => $ad_skip_num,
    'ADS' => $is_video_ad,
    'USER_ADS_DESC_OVERLAY' => $ad_desc,
    'VAT' => $is_vast_ad,
    'AD_IMAGE' => $ad_image,
    'USR_AD_TRANS' => $user_ad_trans
));
exit();
?>