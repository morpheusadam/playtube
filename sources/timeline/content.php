<?php
if (empty($_GET['id'])) {
    header("Location: " . PT_Link(''));
    exit();
}
$_GET['id'] = strip_tags($_GET['id']);
$username = PT_Secure($_GET['id']);
$user_id  = $db->where('username', $username)->getOne(T_USERS);

$lists    = false;
if (empty($user_id)) {
    header("Location: " . PT_Link(''));
    exit();
}
if ($pt->config->shorts_system == 'on' && !canUseFeature($user_id->id,'who_can_shorts')) {
    $pt->config->shorts_system = 'off';
}
$pt->page_url_ = $pt->config->site_url.'/@'.$username ;
$pt->second_page = 'videos';
if (!empty($_GET['page'])) {
    switch ($_GET['page']) {
        case 'liked-videos':
            $pt->second_page = 'liked-videos';
            break;
        case 'shorts':
            $pt->second_page = 'shorts';
            break;
        case 'about':
            $pt->second_page = 'about';
            break;
        case 'play-lists':
            $pt->second_page = 'play-lists';
            $lists           = true;
            break;
        case 'activities':
            $pt->second_page = 'activities';
            break;

    }
    $pt->page_url_ = $pt->config->site_url.'/@'.$username."?page=".$pt->second_page;
}
if ($pt->second_page == 'activities' && $pt->config->post_system == 'off') {
    $pt->second_page = 'videos';
}
if (!canUseFeature($user_id->id,'who_can_post')) {
    if ($pt->second_page == 'activities') {
        $pt->second_page = 'videos';
    }
    $pt->config->post_system = 'off';
}

$user_data   = PT_UserData($user_id, array(
    'data' => true
));
$pt->isowner = false;
if (IS_LOGGED == true) {
    if ($user_data->id == $user->id) {
        $pt->isowner = true;
    }
}
$videos_html       = '';
$videos_count      = 0;
$get_video_query   = 1;
$watch_later_list  = 0;


if ($pt->second_page == 'videos') {
    if (IS_LOGGED == true) {
        if ($user_data->id != $user->id) {
            $db->where('privacy', 0);
        }
    } else {
        $db->where('privacy', 0);
    }
    $videos = $db->where('user_id', $user_data->id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie',0)->where('is_short',0)->orderBy('id', 'DESC')->get(T_VIDEOS, 20, 'video_id');
}
if ($pt->second_page == 'shorts') {
    if (IS_LOGGED == true) {
        if ($user_data->id != $user->id) {
            $db->where('privacy', 0);
        }
    } else {
        $db->where('privacy', 0);
    }
    $videos = $db->where('user_id', $user_data->id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy('id', 'DESC')->get(T_VIDEOS, 20, 'video_id');
}

if ($pt->second_page == 'liked-videos') {
    $videos = $db->where('user_id', $user_data->id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('type', 1)->where('video_id', 0,'<>')->orderBy('id', 'DESC')->get(T_DIS_LIKES, 20);
    $get_video_query = 2;
}

if ($pt->second_page == 'play-lists') {

    if ($pt->isowner === true) {
        $playlists   = $db->where('user_id', $user_data->id)->get(T_LISTS);
        $watch_later = $db->where('user_id', $user_data->id)->orderBy('id', 'ASC')->get(T_WLATER);
        //$wl_count    = $db->where('user_id', $user_data->id)->getValue(T_WLATER, 'count(*)');
        $wl_count    = 0;
        $wl_html     = '';
        foreach ($watch_later as $key => $value) {
            if (!empty($value)) {
                $wl_video = PT_GetVideoByID($value->video_id, 0, 0, 2);
                if (!empty($wl_video) && empty($wl_html)) {

                    foreach ($watch_later as $key2 => $value2) {
                        $video_get2 = $db->where('user_id',$pt->blocked_array , 'NOT IN')->where('id', $value2->video_id)->getOne(T_VIDEOS);
                        if (!empty($video_get2)) {
                            $wl_count = $wl_count + 1;
                        }
                    }


                    $wl_video_id  = $value->video_id;
                    $videos_html .= PT_LoadPage('playlist/wl-list', array(
                        'TITLE' => "Watch Later",
                        'THUMBNAIL' => $wl_video->thumbnail,
                        'COUNT' => $wl_count,
                        'URL' => PT_Link('watch/' . PT_Slug($wl_video->title, $wl_video->video_id) . "?list=wl"),
                        'LIST_ID' => 'wl',
                        'VIDEO_ID_' => PT_Slug($wl_video->title, $wl_video->video_id)
                    ));
                    $wl_html = 1;
                }
            }
        }

    }

    else{
      $playlists   =  $db->where('user_id', $user_data->id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('privacy', 1)->get(T_LISTS);
    }

}

if (!empty($videos) && !$lists) {
    $videos_count = count($videos);
    foreach ($videos as $key => $video) {
        $video_get = $pt->video = PT_GetVideoByID($video->video_id, 0, 0, $get_video_query);
        $video_id  = $video_get->id;
        if ($get_video_query == 2) {
            $video_id = $video->id;
        }
        if ($pt->second_page == 'shorts') {
            $video_get->url = $video_get->url."?user=".$user_id->username;
            $video_get->ajax_url = $video_get->ajax_url."&user=".$user_id->username;
        }
        $videos_html .= PT_LoadPage('videos/list', array(
            'ID' => $video_id,
            'VID_ID' => $video_get->id,
            'TITLE' => $video_get->title,
            'VIEWS' => $video_get->views,
            'VIEWS_NUM' => number_format($video_get->views),
            'USER_DATA' => $video_get->owner,
            'THUMBNAIL' => $video_get->thumbnail,
            'URL' => $video_get->url,
            'ajax_url' => $video_get->ajax_url,
            'TIME' => $video_get->time_ago,
            'DURATION' => $video_get->duration,
            'VIDEO_ID_' => PT_Slug($video_get->title, $video_get->video_id),
            'GIF' => $video_get->gif,
            'DESC' => $video_get->markup_description,
            'PRICE' => $video_get->sell_video,
            'CURRENCY' => $pt->config->main_payment_currency,
        ));
    }
}

elseif(!empty($playlists) && $lists){

    foreach ($playlists as $key => $list) {
        $list_html     = '';
        $list_id       = $list->list_id;
        $video         = $db->where('list_id', $list->list_id)->orderBy('id', 'asc')->get(T_PLAYLISTS);
        $vid_count     = 0;
        foreach ($video as $key => $value) {

            if (isset($value->video_id)) {
                $video_get = PT_GetVideoByID($value->video_id, 0, 0, 2);
                //$vid_count = $db->where('user_id', $user_id->id)->where('list_id', $list_id)->getValue(T_PLAYLISTS, 'count(*)');

                if (!empty($video_get) && empty($list_html)) {
                    foreach ($video as $key2 => $value2) {
                        $video_get2 = $db->where('user_id',$pt->blocked_array , 'NOT IN')->where('id', $value2->video_id)->getOne(T_VIDEOS);
                        if (!empty($video_get2)) {
                            $vid_count = $vid_count + 1;
                        }
                    }
                    $videos_html .= PT_LoadPage('playlist/list', array(
                        'ID' => $list->id,
                        'TITLE' => $list->name,
                        'THUMBNAIL' => $video_get->thumbnail,
                        'COUNT' => $vid_count,
                        'URL' => PT_Link('watch/' . PT_Slug($video_get->title, $video_get->video_id) . "/list/$list_id"),
                        'LIST_ID' => $list_id,
                        'VIDEO_ID_' => PT_Slug($video_get->title, $video_get->video_id)
                    ));
                    $list_html = 1;
                }
            }
        }
    }
}


if ($pt->second_page == 'activities' && $pt->config->post_system == 'on') {
    $posts = $db->where('user_id',$user_data->id)->orderBy('id', 'DESC')->get(T_ACTIVITES,30);
    $pt->posts_count = count($posts);
    if (!empty($posts)) {
        foreach ($posts as $key => $post) {
            $post->is_owner = false;
            if (IS_LOGGED) {
                if ($post->user_id == $pt->user->id || PT_IsAdmin()) {
                    $post->is_owner = true;
                }
            }

            $post_likes     = $db->where('activity_id', $post->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
            $post_dislikes  = $db->where('activity_id', $post->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
            $liked          = '';
            $disliked       = '';

            if (IS_LOGGED === true) {
                $u_like     = $db->where('activity_id', $post->id)->where('user_id', $pt->user->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
                $liked      = ($u_like > 0) ? 'active' : '';

                $u_dislike  = $db->where('activity_id', $post->id)->where('user_id', $pt->user->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
                $disliked   = ($u_dislike > 0) ? 'active' : '';
            }

            $pt->post = $post;
            //date('d-F-Y',$post->time)

            $videos_html .= PT_LoadPage('timeline/includes/post_list', array(
                'ID' => $post->id,
                'TITLE' => PT_ShortText($post->text,190),
                'THUMBNAIL' => PT_GetMedia($post->image),
                'URL' => PT_Link('post/' . PT_URLSlug($post->text,$post->id)),
                'TIME' => PT_Time_Elapsed_String($post->time),
                'ARTICLE_URL' => PT_URLSlug($post->text,$post->id),
                'LIKES' => $post_likes,
                'DIS_LIKES' => $post_dislikes,
                'LIKED' => $liked,
                'DIS_LIKED' => $disliked
            ));




        }
    }
}




if(empty($videos_html)){
    $text_show = $lang->no_videos_found_for_now;
    if ($pt->second_page == 'activities') {
        $text_show = $lang->no_activities_found_for_now;
    }
    $videos_html = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $text_show . '</div>';
}

$pt->profile_fields = null;
$user__fields       = $db->where('profile_page','1')->where('active','1')->get(T_FIELDS);
$pt->profile_fields = !empty($user__fields) ? $user__fields : null;
if (IS_LOGGED) {
    $pt->user->fields   = $db->where('user_id',$user_data->id)->getOne(T_USR_PROF_FIELDS);
    $pt->user->fields   = (is_object($pt->user->fields)) ? get_object_vars($pt->user->fields) : array();
}

$pt->custom_fields  = "";

if (!empty($pt->profile_fields)) {
    foreach ($pt->profile_fields as $field_data) {
        $field_data->fid  = 'fid_' . $field_data->id;
        $field_data->name = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
            return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
        }, $field_data->name);

        $field_data->description = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
            return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
        }, $field_data->description);

        if (!empty($pt->user->fields[$field_data->fid])) {
            $fid     = $pt->user->fields[$field_data->fid];
            $pt->fid = $fid;
            if ($field_data->type == 'select') {
                $options = @explode(',', $field_data->options);
                $fid     = $options[$pt->user->fields[$field_data->fid] - 1];
            }


            $pt->custom_fields .= PT_LoadPage('timeline/includes/custom-fields',array(
                "FID"  => $fid,
                "NAME" => $field_data->name,
                "DESC" => $field_data->description,
            ));
        }
    }
}
$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = '';
    if (IS_LOGGED) {
        $selected = ($key == $pt->user->country_id) ? 'selected' : '';
    }
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}
$subs = '';
if (IS_LOGGED) {
    if ($user_data->privacy->show_subscriptions_count == 'yes') {
        $subs = number_format($db->where('user_id', $user_data->id)->getValue(T_SUBSCRIPTIONS, "count(*)"));
    }
}
$showMessage = true;
if ($user_data->privacy->who_can_message_me == 'no_one') {
    $showMessage = false;
}
else if ($user_data->privacy->who_can_message_me == 'subscribers') {
    $is_sub = $db->where('user_id', $user_data->id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
    if ($is_sub == 0) {
        $showMessage = false;
    }
}

if ($pt->second_page == 'about') {
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    preg_match_all($link_regex, $user_data->about, $matches);
    foreach ($matches[0] as $match) {
        $match_decode = urldecode($match);
        $match_decode_url = $match_decode;
        $count_url = mb_strlen($match_decode);
        if ($count_url > 50) {
            $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
        }
        $match_url = $match_decode;
        if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
            $match_url = 'http://' . $match_url;
        }
        $syntax = '<a href="' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>';
        $user_data->about = str_replace($match, $syntax, $user_data->about);
    }
}

$pt->profile_user  = $user_data;
$pt->videos_count  = $videos_count;
$pt->page          = 'timeline';
$pt->title         = $user_data->name . ' | ' . $pt->config->title;
$pt->description   = $pt->config->description;
$pt->keyword       = $pt->config->keyword;
$pt->content       = PT_LoadPage('timeline/content', array(
    'USER_DATA'       => $user_data,
    'SUBSCIBE_BUTTON' => (!in_array($user_data->id, $pt->blocked_array) ? PT_GetSubscribeButton($user_data->id) : '') ,
    'MESSAGE_BUTTON'  => (!in_array($user_data->id, $pt->blocked_array) && $showMessage ? PT_GetMessageButton($user_data->username) : ''),
    'BLOCK_BUTTON'  => PT_GetBlockButton($user_data->id),
    'NOTIFY_BUTTON'  => PT_GetNotifyButton($user_data->id),
    'SUBS'  => $subs,
    'COUNTRIES' => $countries,
    'SECOND_PAGE'     => PT_LoadPage('timeline/pages/' . $pt->second_page, array(
        'VIDEOS'      => $videos_html,
        'USER_DATA'   => $user_data,
        'CUSTOM_FIELDS'   => $pt->custom_fields,
    ))
));
