<?php

$pt->page        = 'home';
$pt->title       = $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pro_users       = array();
$pro_system      = ($pt->config->go_pro == 'on');

$pt->page_url_ = $pt->config->site_url;

// $home_top_videos = $db->where('privacy', 0)->orderby('views', 'DESC')->get(T_VIDEOS, 6);
// $top_videos_html = '';

// foreach ($home_top_videos as $key => $video) {
//     $video = PT_GetVideoByID($video, 0, 0, 0);
//     $top_videos_html .= PT_LoadPage('home/top-videos', array(
//         'ID' => $video->id,
//         'TITLE' => $video->title,
//         'VIEWS' => $video->views,
//         'USER_DATA' => $video->owner,
//         'THUMBNAIL' => $video->thumbnail,
//         'URL' => $video->url,
//     ));
// }

$limit = ($pt->theme_using == 'youplay') ? 10 : 5;
$pt->videos_array = array();
$db->where('converted', '2','<>');

$video_obj = $db->where('featured', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie', 0)->where('privacy', 0)->where('live_time', 0)->where('approved',1)->where('is_short', 0)->orderBy('RAND()')->getOne(T_VIDEOS);
$get_video = PT_GetVideoByID($video_obj, 0, 1, 0);

if (empty($get_video)) {

    $db->where('converted', '2','<>');
    $get_video = PT_GetVideoByID($db->where('privacy', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie', 0)->where('live_time', 0)->where('approved',1)->where('is_short', 0)->orderBy('id', 'DESC')->getOne(T_VIDEOS), 0, 1, 0);
}

if (empty($pt->videos_array) && !empty($get_video) && $pt->theme_using == 'default') {
    $pt->videos_array[] = $get_video;
}

if (empty($get_video)) {
    $pt->content = PT_LoadPage('home/no-content');
    return;
}

$user_data_f   = $get_video->owner;
$save_button = '<i class="fa fa-floppy-o fa-fw"></i> ' . $lang->save;
$is_saved    = 0;


if (IS_LOGGED == true) {
    $db->where('video_id', $get_video->id);
    $db->where('user_id', $user->id);
    $is_saved = $db->getValue(T_SAVED, "count(*)");
}

if ($is_saved > 0) {
    $save_button = '<i class="fa fa-check fa-fw"></i> ' . $lang->saved;
}

$trending_list = '';

if (!empty($pro_users)) {
    $db->where('user_id', $pro_users, 'IN');
    $db->where('time', time() - 172800, '>');
    $db->where('privacy', 0);
    $trending_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('live_time',0)->where('is_short', 0)->orderBy('views', 'DESC')->get(T_VIDEOS, $limit);
}

if (empty($trending_data)) {
    $db->where('time', time() - 172800, '>');
    $db->where('privacy', 0);
    $trending_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('live_time',0)->where('is_short', 0)->orderBy('views', 'DESC')->get(T_VIDEOS, $limit);
}

foreach ($trending_data as $key => $video) {
    $video = $pt->video =  PT_GetVideoByID($video, 0, 0, 0);
    $trending_list .= PT_LoadPage('home/list', array(
        'ID' => $video->id,
        'TITLE' => $video->title,
        'VIEWS' => number_format($video->views),
        'VIEWS_NUM' => number_format($video->views),
        'USER_DATA' => $video->owner,
        'THUMBNAIL' => $video->thumbnail,
        'URL' => $video->url,
        'ajax_url' => $video->ajax_url,
        'TIME' => $video->time_ago,
        'DURATION' => $video->duration,
        'VIDEO_ID' => $video->video_id_,
        'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
        'GIF' => $video->gif
    ));
}

$top_list = '';

if (!empty($pro_users)){
    $db->where('user_id', $pro_users, 'IN');
    $db->where('privacy', 0);
    $db->orderBy('views', 'DESC');
    $top_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->get(T_VIDEOS, 4);
}

if (empty($top_data)) {
    $db->where('privacy', 0);
    $top_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->orderBy('views', 'DESC')->get(T_VIDEOS, $limit);
}

foreach ($top_data as $key => $video) {
    $video = $pt->video =  PT_GetVideoByID($video, 0, 0, 0);
    $top_list .= PT_LoadPage('home/list', array(
        'ID' => $video->id,
        'TITLE' => $video->title,
        'VIEWS' => number_format($video->views),
        'VIEWS_NUM' => number_format($video->views),
        'USER_DATA' => $video->owner,
        'THUMBNAIL' => $video->thumbnail,
        'URL' => $video->url,
        'ajax_url' => $video->ajax_url,
        'TIME' => $video->time_ago,
        'DURATION' => $video->duration,
        'VIDEO_ID' => $video->video_id_,
        'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
        'GIF' => $video->gif
    ));
}

$live_list = '';
$pt->have_live = false;
if ($pt->config->live_video == 1) {
    if (!empty($pro_users)){
        $db->where('user_id', $pro_users, 'IN');
        $db->where('privacy', 0);
        $db->orderBy('live_time', 'DESC');
        $live_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0,'>')->get(T_VIDEOS, 4);
    }

    if (empty($live_data)) {
        $db->where('privacy', 0);
        $live_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0,'>')->orderBy('live_time', 'DESC')->get(T_VIDEOS, $limit);
    }

    foreach ($live_data as $key => $video) {
        $video = $pt->video = PT_GetVideoByID($video, 0, 0, 0);
        $live_list .= PT_LoadPage('home/list', array(
            'ID' => $video->id,
            'TITLE' => $video->title,
            'VIEWS' => number_format($video->views),
            'VIEWS_NUM' => number_format($video->views),
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'ajax_url' => $video->ajax_url,
            'TIME' => $video->time_ago,
            'DURATION' => $video->duration,
            'VIDEO_ID' => $video->video_id_,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif
        ));
    }
    if (!empty($live_list)) {
        $pt->have_live = true;
    }
}
$html_posts = '';
if ($pt->config->show_articles == 'on') {
    $posts   = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id', 'DESC')->get(T_POSTS, 4);
    if (!empty($posts)) {
        foreach ($posts as $key => $post) {
            $user_data = PT_UserData($post->user_id);
            $html_posts .= PT_LoadPage('home/article_list', array(
                'ID' => $post->id,
                'TITLE' => $post->title,
                'DESC'  => PT_ShortText($post->description,190),
                'VIEWS_NUM' => number_format($post->views),
                'THUMBNAIL' => PT_GetMedia($post->image),
                'CAT' => ($post->category),
                'URL' => PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id)),
                'TIME' => TranslateDate(date($pt->config->date_style,$post->time)),
                'ARTICLE_URL' => PT_URLSlug($post->title,$post->id),
                'USER_DATA' => $user_data
            ));
        }
    }
}

$latest_list = '';
if (!empty($pro_users)) {
    $db->where('user_id', $pro_users, 'IN');
    $db->where('privacy', 0);
    $db->orderBy('id', 'DESC');
    $latest_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->get(T_VIDEOS, $limit);
}

if (empty($latest_data)) {
    $db->where('privacy', 0);
    $latest_data = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->orderBy('id', 'DESC')->get(T_VIDEOS, $limit);
}

foreach ($latest_data as $key => $video) {
    $video = $pt->video =  PT_GetVideoByID($video, 0, 0, 0);
    $latest_list .= PT_LoadPage('home/list', array(
        'ID' => $video->id,
        'TITLE' => $video->title,
        'VIEWS' => number_format($video->views),
        'VIEWS_NUM' => number_format($video->views),
        'USER_DATA' => $video->owner,
        'THUMBNAIL' => $video->thumbnail,
        'URL' => $video->url,
        'ajax_url' => $video->ajax_url,
        'TIME' => $video->time_ago,
        'DURATION' => $video->duration,
        'VIDEO_ID' => $video->video_id_,
        'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
        'GIF' => $video->gif
    ));
}

$video_categories_html = '';

foreach ($categories as $cat_key => $cat_name) {
    if (IS_LOGGED == true && !empty($pt->user->fav_category)) {
        if (in_array($cat_key, $pt->user->fav_category)) {
            $db->where("category_id = '$cat_key'");
            $db->where('privacy', 0);
            $db->orderBy('id', 'DESC');
            $pt->cat_videos = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->get(T_VIDEOS, 10);
            if (!empty($pt->cat_videos)) {
                $video_categories_html .= PT_LoadPage('home/categories',array(
                    'CATEGORY_ONE_NAME' => $cat_name,
                    'CATEGORY_ONE_ID' => $cat_key
                ));
            }
        }
    }
    else{
        if (!empty($pt->config->fav_category) && is_array($pt->config->fav_category)) {
            if (in_array($cat_key, $pt->config->fav_category)) {
                $db->where("category_id = '$cat_key'");
                $db->where('privacy', 0);
                $db->orderBy('id', 'DESC');
                $pt->cat_videos = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->get(T_VIDEOS, 10);
                if (!empty($pt->cat_videos)) {
                    $video_categories_html .= PT_LoadPage('home/categories',array(
                        'CATEGORY_ONE_NAME' => $cat_name,
                        'CATEGORY_ONE_ID' => $cat_key
                    ));
                }
            }
        }
        else{
            $db->where("category_id = '$cat_key'");
            $db->where('privacy', 0);
            $db->orderBy('id', 'DESC');
            $pt->cat_videos = $db->where('is_movie', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->where('is_short', 0)->where('live_time',0)->get(T_VIDEOS, 10);
            if (!empty($pt->cat_videos)) {
                $video_categories_html .= PT_LoadPage('home/categories',array(
                    'CATEGORY_ONE_NAME' => $cat_name,
                    'CATEGORY_ONE_ID' => $cat_key
                ));
            }
        }
    }
}
$pt->video_240 = 0;
$pt->video_360 = 0;
$pt->video_480 = 0;
$pt->video_720 = 0;

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
}

$pt->subscriptions = false;
$get_subscriptions_videos_html = '';
if (IS_LOGGED == true) {
    $get = $db->where('subscriber_id', $user->id)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_SUBSCRIPTIONS);
    $userids = array();
    foreach ($get as $key => $userdata) {
        $userids[] = $userdata->user_id;
    }
    $get_subscriptions_videos = false;
    $userids = implode(',', ToArray($userids));
    if (!empty($userids)) {
        $get_subscriptions_videos = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE user_id IN ($userids) AND is_movie = 0 AND privacy = 0 AND live_time = 0 ORDER BY `id` DESC LIMIT $limit");
    }
    if (!empty($get_subscriptions_videos)) {
        $pt->subscriptions = true;
        $pt->cat_videos = $get_subscriptions_videos;
        $get_subscriptions_videos_html = PT_LoadPage('home/categories',array(
            'CATEGORY_ONE_NAME' => $lang->subscriptions,
            'CATEGORY_ONE_ID' => 'subscriptions'
        ));

    }
}
$shorts_html = '';
if ($pt->config->shorts_system == 'on') {
    $videos = $db->where('is_short',1)->where('privacy',0)->where('time',time() - (60 * 60),'>')->orderBy('views','DESC')->get(T_VIDEOS,6);
    if (!empty($videos) && count($videos) < 5) {
        $ids = array();
        foreach ($videos as $key => $value) {
            $ids[] = $value->id;
        }
        $new_videos = $db->where('is_short',1)->where('privacy',0)->where('id',$ids,'NOT IN')->orderBy('id','DESC')->get(T_VIDEOS,6 - count($videos));
        foreach ($new_videos as $key => $value) {
            $videos[] = $value;
        }
    }
    if (empty($videos)) {
        $videos = $db->where('is_short',1)->where('privacy',0)->orderBy('id','DESC')->get(T_VIDEOS,6);
    }

    foreach ($videos as $key => $video) {
        $video = PT_GetVideoByID($video, 0, 0, 0);
        $video->url                = PT_Link('shorts/' . $video->video_id);
        $video->load_ajax = $video->video_id;
        if ($pt->config->seo_link == 'on') {
            $video->url                = PT_Link('shorts/' . PT_Slug($video->title, $video->video_id));
            $video->load_ajax = PT_Slug($video->title, $video->video_id);
        }

        $pt->video = $video;
        $shorts_html .= PT_LoadPage('home/shorts_list', array(
            'ID' => $video->id,
            'TITLE' => $video->title,
            'VIEWS' => number_format($video->views),
            'VIEWS_NUM' => number_format($video->views),
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'TIME' => $video->time_ago,
            'DURATION' => $video->duration,
            'VIDEO_ID' => $video->video_id_,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif,
            'LOAD_AJAX' => $video->load_ajax
        ));
    }
}
$pt->content = PT_LoadPage('home/content', array(
    'ID' => $get_video->id,
    'THUMBNAIL' => $get_video->thumbnail,
    'DURATION' => $get_video->duration,
    'TITLE' => $get_video->title,
    'DESC' => $get_video->markup_description,
    'URL' => $get_video->url,
    'VIDEO_LOCATION_240' => $pt->video_240,
    'VIDEO_LOCATION' => $get_video->video_location,
    'VIDEO_LOCATION_480' => $pt->video_480,
    'VIDEO_LOCATION_720' => $pt->video_720,
    'VIDEO_TYPE' => $get_video->video_type,
    'VIDEO_MAIN_ID' => $get_video->video_id,
    'VIDEO_ID' => $get_video->video_id_,
    'USER_DATA' => $user_data_f,
    'SUBSCIBE_BUTTON' => PT_GetSubscribeButton($user_data_f->id),
    'VIEWS' => $get_video->views,
    'LIKES' => number_format($get_video->likes),
    'DISLIKES' => number_format($get_video->dislikes),
    'LIKES_P' => $get_video->likes_percent,
    'DISLIKES_P' => $get_video->dislikes_percent,
    'RAEL_LIKES' => $get_video->likes,
    'RAEL_DISLIKES' => $get_video->dislikes,
    'ISLIKED' => ($get_video->is_liked > 0) ? 'liked="true"' : '',
    'ISDISLIKED' => ($get_video->is_disliked > 0) ? 'disliked="true"' : '',
    'LIKE_ACTIVE_CLASS' => ($get_video->is_liked > 0) ? 'active' : '',
    'DIS_ACTIVE_CLASS' => ($get_video->is_disliked > 0) ? 'active' : '',
    'SAVED_BUTTON' => $save_button,
    'IS_SAVED' => ($is_saved > 0) ? 'saved="true"' : '',
    'ENCODED_URL' => urlencode($get_video->url),
    'CATEGORY' => $get_video->category_name,
    'TIME' => $get_video->time_alpha,
    'TRENDING_LIST' => $trending_list,
    'TOP_LIST' => $top_list,
    'LIVE_LIST' => $live_list,
    'LATEST_LIST' => $latest_list,
    'HOME_PAGE_VIDEOS' => $video_categories_html,
    'SUBSC_HTML' => $get_subscriptions_videos_html,
    'VIDEO_ID_' => PT_Slug($get_video->title, $get_video->video_id),
    'HTML_POSTS' => $html_posts,
    'SHORTS_HTML' => $shorts_html,
    'ENCODED_URL' => urlencode($get_video->url),
    'SHORT_ID' => $get_video->short_id,
));
