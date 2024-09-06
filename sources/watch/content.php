<?php
if (empty($_GET['id'])) {
    header("Location: " . PT_Link('404'));
    exit();
}

$id = PT_Secure($_GET['id']);
if (strpos($id, '_') !== false) {
    $id_array = explode('_', $id);
    $id_html  = $id_array[1];
    $id       = str_replace('.html', '', $id_html);
}

$_GET['id'] = strip_tags($_GET['id']);
$get_video = PT_GetVideoByID($id, 1, 1);
if ($get_video->is_short == 1) {
    header("Location: " . $get_video->url);
    exit();
}

if (empty($get_video)) {
    header("Location: " . PT_Link('404'));
    exit();
}
if ($get_video->is_movie && $pt->config->movies_videos != 'on') {
    header("Location: " . PT_Link('404'));
    exit();
}

$is_found = $db->where('lang_key',$get_video->category_id)->getValue(T_LANGS,'COUNT(*)');
if ($is_found == 0) {
    $db->where('id',$get_video->id)->update(T_VIDEOS,array('category_id' => 'other','sub_category' => ''));
    $get_video->category_name = "";
    $get_video->category_id = 'other';
}
$get_video->main_video_price = $get_video->sell_video;
if ($pt->config->rent_videos_system != 'on') {
    $get_video->rent_price = 0;
}
if (!empty($get_video->rent_price) && $get_video->rent_price > 0) {
    $get_video->sell_video = $get_video->rent_price;
    //$time = time() - (60*60*24*30);
    $time = time() - (60*60*24*2);
    $expired_videos = $db->where('time',$time,'<=')->where('type','rent')->delete(T_VIDEOS_TRSNS);
}



$pt->page_url_ = $pt->config->site_url.'/watch/'.PT_Slug($get_video->title, $get_video->video_id);
if (empty($get_video->short_id)) {
    $short_id = PT_GenerateKey(6, 6);
    $update_short_id = $db->where('id', $get_video->id)->update(T_VIDEOS, array('short_id' => $short_id));
    $get_video->short_id = $short_id;
}


$get_video->age = false;
if ($get_video->age_restriction == 2) {
    if (!IS_LOGGED) {
        $get_video->age = true;
    } else {
        if (($get_video->user_id != $user->id) && !is_age($user->id)) {
            $get_video->age = true;
        }
    }
}

$pt->video_approved = true;

if ($pt->config->approve_videos == 'on' || ($pt->config->auto_approve_ == 'no' && ($get_video->sell_video || $get_video->rent_price) )) {
    if ($get_video->approved == 0) {
        $pt->video_approved = false;
    }
}

$pt->video_type = 'public';
$pt->video_privacy = $get_video->privacy;
if ($get_video->privacy == 1) {
    if (!IS_LOGGED) {
        $pt->video_type = 'private';
    } else if (($get_video->user_id != $user->id) && ($user->admin == 0)) {
        $pt->video_type = 'private';
    }
}
$pt->is_paid = 0;
$pt->video_end = '';
if ($get_video->sell_video > 0 || $get_video->rent_price > 0) {
    if (!empty($user->id)) {
        $pt->is_paid = $db->where('video_id',$get_video->id)->where('paid_id',$user->id)->getValue(T_VIDEOS_TRSNS,"count(*)");
        if ($pt->is_paid) {
            $rent_video = $db->where('paid_id',$pt->user->id)->where('type','rent')->where('video_id',$get_video->id)->getOne(T_VIDEOS_TRSNS);
            if (!empty($rent_video)) {
                $pt->video_end = date('Y-m-d h:i:sa',$rent_video->time + (60*60*24*2));
            }
        }
    }
    $pt->purchased = $db->where('video_id',$get_video->id)->getValue(T_VIDEOS_TRSNS,"count(*)");
}
$pt->show_user_video = 0;
if ($get_video->owner->subscriber_price > 0) {
    if (IS_LOGGED) {
        $check_if_sub = $db->where('user_id', $get_video->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, 'count(*)');
        if ($check_if_sub > 0) {
            $pt->is_paid = 1;
            $pt->show_user_video = 1;
        }
    }
}
$user_data = $get_video->owner;

$desc = strip_tags($get_video->edit_description);
$desc = str_replace('"', "'", $desc);
$desc = str_replace('<br>', "", $desc);
$desc = str_replace("\n", "", $desc);
$desc = str_replace("\r", "", $desc);

$desc = mb_substr($desc, 0, 220, "UTF-8");

$pt->get_video   = $get_video;
$pt->page        = 'watch';
$pt->title       = $get_video->title;
$pt->description = htmlspecialchars($desc);
$pt->keyword     = $get_video->tags;
$pt->is_list     = false;
$pt->is_wl       = false;
$pt->get_id      = $id;
$pt->list_name   = "";
$list_id         = 0;
$pt->video_owner = (IS_LOGGED && $get_video->user_id == $user->id);
$pt->reported    = false;
$pt->converted   = true;

if ($pt->config->ffmpeg_system == 'on' && $pt->get_video->converted != 1) {
    $pt->converted = false;
}


if (!empty($_GET['list']) && $_GET['list'] == 'wl' && IS_LOGGED) {
    $user_id   = $pt->user->id;
    $pt->is_wl = (($db->where('video_id', $get_video->id)->where('user_id', $user_id)->getValue(T_WLATER, 'count(*)') > 0));
    if (!$pt->is_wl) {
        header("Location: " . PT_Link("watch/$id"));
        exit();
    }
    $pt->page_url_ = $pt->config->site_url.'/watch/'.PT_Slug($get_video->title, $get_video->video_id).'/list/'.$_GET['list'];

}

else if (!empty($_GET['list'])) {
    $list_id     = PT_Secure($_GET['list']);
    $pt->is_list = (
        ($db->where('list_id', $list_id)->getValue(T_LISTS, 'count(*)') > 0) &&
        ($db->where('list_id', $list_id)->where('video_id', $get_video->id)->getValue(T_PLAYLISTS, 'count(*)') > 0)
    );

    if (!$pt->is_list) {
        header("Location: " . PT_Link("watch/$id"));
        exit();
    }
    $pt->page_url_ = $pt->config->site_url.'/watch/'.PT_Slug($get_video->title, $get_video->video_id).'/list/'.$_GET['list'];
}

$related_videos = array();

$not_ids = '';
$not_in = '';

$not_in_query = '';
$next_video     = '';
// $history_ar = array();
// if (IS_LOGGED) {
    
//     $history_videos = $db->where('user_id',$pt->user->id)->where('time',strtotime("-6 hours"),'>=')->orderBy('id','DESC')->get(T_HISTORY);
//     foreach ($history_videos as $key => $value) {
//         $history_ar[] = $value->video_id;
//     }
//     if (!empty($history_ar)) {
//         $not_in = implode(',', $history_ar);
//         $not_in_query .= " AND id NOT IN ($not_in) ";
//     }
// }
// else{
    $next_up_videos = array();
    if (!empty($_COOKIE['next_up_videos'])) {
        $n_videos = json_decode($_COOKIE['next_up_videos'],true);
        foreach ($n_videos as $key => $value) {
            if (is_numeric($value)) {
                $next_up_videos[] = PT_Secure($value);
            }
        }
        if (!in_array($get_video->id, $next_up_videos)) {
            $next_up_videos[] =  $get_video->id;
        }
    }
    else{
       $next_up_videos[] =  $get_video->id;
    }
    if (!empty($next_up_videos)) {
        $not_in = implode(',', $next_up_videos);
        $not_in_query .= " AND id NOT IN ($not_in) ";
    }
    setcookie("next_up_videos", json_encode($next_up_videos), time() + (6 * 60 * 60), "/");
// }

$query_video_title = PT_Secure($get_video->title);

$next_up_videos    = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE MATCH (title) AGAINST ('$query_video_title') AND user_id NOT IN (".implode(',', $pt->blocked_array).") AND id <> '{$get_video->id}' $not_in_query AND is_movie = 0 AND privacy = 0 AND is_short = 0 ORDER BY `id` DESC LIMIT 1");

foreach ($next_up_videos as $key => $next_up_video) {
    $nextVideo  = PT_GetVideoByID($next_up_video, 0, 0, 0);
    $next_video = PT_LoadPage('watch/video-sidebar', array(
        'ID' => $nextVideo->id,
        'TITLE' => $nextVideo->title,
        'URL' => $nextVideo->url,
        'THUMBNAIL' => $nextVideo->thumbnail,
        'USER_NAME' => $nextVideo->owner->name,
        'VIEWS' => number_format($nextVideo->views),
        'TIME' => $nextVideo->time_alpha,
        'V_ID' => $nextVideo->video_id,
        'GIF' => $nextVideo->gif,
        'DURATION' => $nextVideo->duration,
        'USER_DATA' => $nextVideo->owner,
        'CATEGORY' => $nextVideo->category_name,
        'CATEGORY_LINK' => PT_Link('videos/category/'.$nextVideo->category_id)
    ));
}

$related_videos    = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE MATCH (title) AGAINST ('$query_video_title') AND user_id NOT IN (".implode(',', $pt->blocked_array).") AND id <> '{$get_video->id}' AND is_movie = 0 AND privacy = 0 AND is_short = 0 ORDER BY `id` DESC LIMIT 20");

if (empty($related_videos)) {
    // if (!empty($not_in) && !empty($_SESSION['next_video'])) {
    //     $db->where('id', $_SESSION['next_video'], 'NOT IN');
    // }
    // if (!empty($history_ar)) {
    //     $db->where('id', $history_ar, 'NOT IN');
    // }
    $db->where('privacy', 0);
    $related_videos = $db->where('category_id', $get_video->category_id)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie',0)->where('is_short',0)->where('id', $get_video->id, '<>')->get(T_VIDEOS, 20);
}

if (empty($related_videos)) {
    $related_videos_num = $db->getValue(T_VIDEOS, 'count(*)');
    $randomlySelected   = array();
    $count_from         = 5;
    if ($related_videos_num > 9) {
        $count_from = 10;
    }
    for ($a = 0; $a < $count_from; $a++) {
        $rand = rand(1, $related_videos_num);
        if (!in_array($rand, $randomlySelected)) {
            $randomlySelected[] = $rand;
        }
    }
    // if (!empty($not_in) && !empty($_SESSION['next_video'])) {
    //     $db->where('id', $_SESSION['next_video'], 'NOT IN');
    // }
    // if (!empty($history_ar)) {
    //     $db->where('id', $history_ar, 'NOT IN');
    // }
    $db->where('privacy', 0);
    $db->where('is_short', 0);
    $related_videos = $db->where('id', $randomlySelected, 'IN')->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie',0)->where('id', $get_video->id, '<>')->get(T_VIDEOS);
}




$video_sidebar  = '';
$next           = 0;
$list_sidebar   = '';
$list_user_name = '';
$list_count     = 0;
$video_index    = 0;
$pt->list_owner = false;
$playlist_subscribe = '';

if ($pt->is_wl === true && IS_LOGGED === true) {
    $user_id        = $pt->user->id;
    $videos         = $db->where('user_id', $user_id)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_WLATER,null,'video_id');
    $video_list     = array();

    foreach ($videos as $vid) {
        $video_list[] = $vid->video_id;
    }

    $wl_list_videos   = $db->where('id', array_values($video_list), 'IN')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id','asc',array_values($video_list))->get(T_VIDEOS);
    $vid_number       = 1;
    foreach ($wl_list_videos as $key => $pl_vid) {
        $pl_vid         = PT_GetVideoByID($pl_vid, 0, 0, 0);
        $pl_vid->url    = PT_Link('watch/' . PT_Slug($pl_vid->title, $pl_vid->video_id) . "?list=wl");
        $list_sidebar .= PT_LoadPage('watch/wl-list', array(
            'TITLE' => $pl_vid->title,
            'URL' => $pl_vid->url,
            'VID_ID' => $pl_vid->id,
            'ID' => $pl_vid->video_id,
            'THUMBNAIL' => $pl_vid->thumbnail,
            'VID_NUMBER' => ($pl_vid->video_id == $id) ? "<i class='fa fa-circle'></i>" : $vid_number,
            'LIST_ID' => 'wl',
            'VIDEO_ID_' => PT_Slug($pl_vid->title, $pl_vid->video_id)
        ));

        if ($pl_vid->video_id == $id) {
            $video_index = $vid_number;
        }
        $vid_number++;
        $list_count++;
    }
}

else if($pt->is_list === true) {

    $pt->list_data  = $db->where("list_id", $list_id)->where('user_id',$pt->blocked_array , 'NOT IN')->getOne(T_LISTS);
    if ($pt->list_data->privacy == '1' && $pt->config->playlist_subscribe == 'on' && canUseFeature($pt->list_data->user_id,'who_can_playlist')) {
        $playlist_subscribe = PT_GetSubscribePlaylistButton($pt->list_data->user_id,$list_id);
    }
    $pt->list_name  = $pt->list_data->name;
    $videos         = $db->where('list_id', $list_id)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_PLAYLISTS, 300, 'video_id');
    $video_list     = array();
    $list_user_data = PT_UserData($pt->list_data->user_id);

    if (!empty($list_user_data)) {
        $list_user_name = $list_user_data->name;
    }

    if (IS_LOGGED === true && ($pt->list_data->user_id == $pt->user->id)) {
        $pt->list_owner = true;
    }

    foreach ($videos as $vid) {
        $video_list[] = $vid->video_id;
    }

    $play_list_videos = $db->where('id', array_values($video_list), 'IN')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id','asc',array_values($video_list))->get(T_VIDEOS);
    $vid_number       = 1;
    foreach ($play_list_videos as $key => $pl_vid) {
        $pl_vid         = PT_GetVideoByID($pl_vid, 0, 0, 0);
        $pl_vid->url    = PT_Link('watch/' . PT_Slug($pl_vid->title, $pl_vid->video_id) . "/list/$list_id");
        $list_sidebar .= PT_LoadPage('watch/video-list', array(
            'TITLE' => $pl_vid->title,
            'URL' => $pl_vid->url,
            'LIST_ID' => $list_id,
            'VID_ID' => $pl_vid->id,
            'ID' => $pl_vid->video_id,
            'THUMBNAIL' => $pl_vid->thumbnail,
            'VID_NUMBER' => ($pl_vid->video_id == $id) ? "<i class='fa fa-circle'></i>" : $vid_number,
            'VIDEO_ID_' => PT_Slug($pl_vid->title, $pl_vid->video_id),
            'USER_NAME' => $pl_vid->owner->name,
            'DURATION' => $pl_vid->duration,
        ));
        if ($pl_vid->video_id == $id) {
            $video_index = $vid_number;
        }
        $vid_number++;
        $list_count++;
    }
}


$pt->have_video_sidebar = true;
if (!empty($next_up_videos) && !empty($next_up_videos[0])) {
    array_unshift($related_videos , $next_up_videos[0]);
}
foreach ($related_videos as $key => $related_video) {
    if (empty($next_up_videos) || (!empty($next_up_videos) && !empty($next_up_videos[0]) && $next_up_videos[0]->id != $related_video->id)) {
        $related_video  = PT_GetVideoByID($related_video, 0, 0, 0);
        if (empty($next_video)) {
            $next_video = PT_LoadPage('watch/video-sidebar', array(
                'ID' => $related_video->id,
                'TITLE' => $related_video->title,
                'URL' => $related_video->url,
                'THUMBNAIL' => $related_video->thumbnail,
                'USER_NAME' => $related_video->owner->name,
                'VIEWS' => number_format($related_video->views),
                'TIME' => $related_video->time_alpha,
                'V_ID' => $related_video->video_id,
                'GIF' => $related_video->gif,
                'DURATION' => $related_video->duration,
                'USER_DATA' => $related_video->owner,
                'CATEGORY' => $related_video->category_name,
                'CATEGORY_LINK' => PT_Link('videos/category/'.$related_video->category_id)
            ));
        }
        else{
            $video_sidebar .= PT_LoadPage('watch/video-sidebar', array(
                'ID' => $related_video->id,
                'TITLE' => $related_video->title,
                'URL' => $related_video->url,
                'THUMBNAIL' => $related_video->thumbnail,
                'USER_NAME' => $related_video->owner->name,
                'VIEWS' => number_format($related_video->views),
                'TIME' => $related_video->time_alpha,
                'V_ID' => $related_video->video_id,
                'GIF' => $related_video->gif,
                'DURATION' => $related_video->duration,
                'USER_DATA' => $related_video->owner,
                'CATEGORY' => $related_video->category_name,
                'CATEGORY_LINK' => PT_Link('videos/category/'.$related_video->category_id)
            ));
        }
    }
        
    // if ($next == 0 &&  $pt->config->autoplay_system == 'on') {
    //     $next_video = $video_sidebar;
    //     $video_sidebar = '';
    // }
    $next++;
}
if (empty($video_sidebar)) {
    $pt->have_video_sidebar = false;
}


$comments = '';
$db->where('video_id', $get_video->id);
$db->where('pinned', '1','<>')->where('user_id',$pt->blocked_array , 'NOT IN');
$db->orderBy('id', 'DESC');
$pt->config->comments_default_num = 5;
$comments_limit     = $pt->config->comments_default_num;

if (!empty($_GET['cl']) || !empty($_GET['rl'])) {
    $comments_limit = null;
    $get_video_comments = $db->get(T_COMMENTS);
}
else{
    $get_video_comments = $db->get(T_COMMENTS,$comments_limit);
}

if (!empty($get_video_comments)) {
    $comments = '';
    foreach ($get_video_comments as $key => $comment) {
        $comment->text = PT_Duration($comment->text);
        $is_liked_comment = 0;
        $pt->is_comment_owner = false;
        $replies              = "";
        $pt->pin              = false;
        $comment_replies      = $db->where('comment_id', $comment->id)->where('user_id',$pt->blocked_array , 'NOT IN')->get(T_COMM_REPLIES);
        $is_liked_comment     = '';
        $is_comment_disliked  = '';
        $comment_user_data    = PT_UserData($comment->user_id);
        $pt->is_verified      = ($comment_user_data->verified == 1) ? true : false;
        foreach ($comment_replies as $reply) {

            $pt->is_reply_owner = false;
            $pt->is_ro_verified = false;
            $reply_user_data    = PT_UserData($reply->user_id);
            $is_liked_reply     = '';
            $is_disliked_reply  = '';
            if (IS_LOGGED == true) {
                $is_reply_owner = $db->where('id', $reply->id)->where('user_id', $user->id)->getValue(T_COMM_REPLIES, 'count(*)');
                if ($is_reply_owner || $pt->video_owner) {
                    $pt->is_reply_owner = true;
                }

                //Check is this reply  voted by logged-in user
                $db->where('reply_id', $reply->id);
                $db->where('user_id', $user->id);
                $db->where('type', 1);
                $is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

                $db->where('reply_id', $reply->id);
                $db->where('user_id', $user->id);
                $db->where('type', 2);
                $is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';
            }

            if ($reply_user_data->verified == 1) {
                $pt->is_ro_verified = true;
            }

            //Get related to reply likes
            $db->where('reply_id', $reply->id);
            $db->where('type', 1);
            $reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

            $db->where('reply_id', $reply->id);
            $db->where('type', 2);
            $reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');
            $reply->text = PT_Duration($reply->text);

            $replies    .= PT_LoadPage('watch/replies', array(
                'ID' => $reply->id,
                'TEXT' => PT_Markup($reply->text),
                'TIME' => PT_Time_Elapsed_String($reply->time),
                'USER_DATA' => $reply_user_data,
                'COMM_ID' => $comment->id,
                'LIKES'  => $reply_likes,
                'DIS_LIKES' => $reply_dislikes,
                'LIKED' => $is_liked_reply,
                'DIS_LIKED' => $is_disliked_reply,
            ));
        }

        if (IS_LOGGED == true) {
            $is_liked_comment = $db->where('comment_id', $comment->id)->where('user_id', $user->id)->getValue(T_COMMENTS_LIKES, 'count(*)');

            //Check is comment voted by logged-in user
            $db->where('comment_id', $comment->id);
            $db->where('user_id', $user->id);
            $db->where('type', 1);
            $is_liked_comment   = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

            $db->where('comment_id', $comment->id);
            $db->where('user_id', $user->id);
            $db->where('type', 2);
            $is_comment_disliked = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 'active' : '';

            if ($user->id == $comment->user_id || $pt->video_owner) {
                $pt->is_comment_owner = true;
            }
        }
        if (!empty($get_video->stream_name) && $comment->time <= $get_video->live_time) {
            $video_time = GetVideoTime($get_video->time,$comment->time);
            $current_time = '<span class="time pointer" onclick="go_to_duration('.$video_time['current_time'].')"><a href="javascript:void(0)">'.$video_time['time'].'</a> </span>';
        }
        else{
            $current_time = PT_Time_Elapsed_String($comment->time);
        }

        $comments     .= PT_LoadPage('watch/comments', array(
            'ID' => $comment->id,
            'TEXT' => PT_Markup($comment->text),
            'TIME' => $current_time,
            'USER_DATA' => $comment_user_data,
            'LIKES' => $comment->likes,
            'DIS_LIKES' => $comment->dis_likes,
            'LIKED' => $is_liked_comment,
            'DIS_LIKED' => $is_comment_disliked,
            'COMM_REPLIES' => $replies,
            'VID_ID' => $get_video->id
        ));
    }
}


$db->where('video_id', $get_video->id);
$db->where('pinned', '1');
$pinned_comments     = "";
$pinned_comm_data    = $db->getOne(T_COMMENTS);

if (!empty($pinned_comm_data)) {
    $pinned_comments = pt_comm_object_data($pinned_comm_data,true);
}


$pt->count_comments  = $db->where('video_id', $get_video->id)->where('user_id',$pt->blocked_array , 'NOT IN')->getValue(T_COMMENTS, 'count(*)');

$save_button = '<i class="fa fa-floppy-o fa-fw"></i> ' . $lang->save;
$is_saved = 0;
if (IS_LOGGED == true) {
    $db->where('video_id', $get_video->id);
    $db->where('user_id', $user->id);
    $is_saved = $db->getValue(T_SAVED, "count(*)");

    if ($pt->config->history_system == 'on' && $user->pause_history == 0) {
        $history = $db->where('video_id', $get_video->id)->where('user_id', $user->id)->getOne(T_HISTORY);
        if (!empty($history)) {
            $db->where('id', $history->id)->delete(T_HISTORY);
        }
        $insert_to_history = array(
            'user_id' => $user->id,
            'video_id' => $get_video->id,
            'time' => time()
        );
        $insert_to_history_query = $db->insert(T_HISTORY, $insert_to_history);
    }

    $db->where('video_id', $get_video->id);
    $db->where('user_id', $user->id);
    $pt->reported = ($db->getValue(T_REPORTS, "count(*)") > 0);

}

if ($is_saved > 0) {
    $save_button = '<i class="fa fa-check fa-fw"></i> ' . $lang->saved;
}
$checked = '';
if (!empty($_COOKIE['autoplay'])) {
    if ($_COOKIE['autoplay'] == 2) {
        $checked = 'checked';
    }
}
$ad_media = '';
$ad_link = '';
$ad_skip = 'false';
$ad_skip_num = 0;
$is_video_ad = '';
$is_vast_ad = '';
$vast_url = '';
$vast_type = '';
$last_ads = 0;
$ad_image = '';
$ad_link = '';
$sidebar_ad = PT_GetAd('watch_side_bar');
$is_pro  = false;
$user_ad_trans = '';
$ad_desc = '';
$ads_sys = ($pt->config->user_ads == 'on') ? true : false;
$vid_monit = false;
if ($pt->config->usr_v_mon == 'on' && $get_video->monetization == 1 && $user_data->video_mon == 1) {
    $vid_monit = true;
    // if ($pt->config->usr_v_mon == 'on') {
    //     $vid_monit = ($user_data->video_mon == 0) ? false : true;
    // }
}
if (IS_LOGGED === true) {
    if ($user->is_pro == 1 && $pt->config->go_pro == 'on') {
        $is_pro = true;
        $sidebar_ad = '';
    }
}


if (!empty($_COOKIE['last_ads_seen']) && !$is_pro) {
    if ($_COOKIE['last_ads_seen'] > (time() - 600)) {
        $last_ads = 1;
    }
}
if ($last_ads == 0 && !$is_pro && $ads_sys) {
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
                EarnFromView();
            }

            else{
                $_SESSION['ua_'] = $random_ad_id;
                $_SESSION['vo_'] = $get_video->user_id;
                EarnFromView();
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
    $rand2      = (rand(0,1)) ? rand(0,1) :(rand(0,1) ? : rand(0,1));
    $sidebar_ad    = PT_GetAd('watch_side_bar');
    // Get side bar ads
    if ($rand2 == 1){
        $sidebarAd              = pt_get_user_ads(2);
        if (!empty($sidebarAd)) {
            $get_random_ad      = $sidebarAd;
            $random_ad_id       = $get_random_ad->id;
            $_SESSION['pagead'] = $random_ad_id;
            $sidebar_ad    = PT_LoadPage('ads/includes/sidebar',array(
                'ID' => $random_ad_id,
                'IMG' => PT_GetMedia($get_random_ad->media),
                'TITLE' => PT_ShortText($get_random_ad->headline,30),
                'NAME' => PT_ShortText($get_random_ad->name,20),
                'DESC' => PT_ShortText($get_random_ad->description,70),
                'URL' => PT_Link("redirect/$random_ad_id?type=pagead"),
                'URL_NAME' => pt_url_domain(urldecode($get_random_ad->url))
            ));
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
// demo video
if ($pt->config->ffmpeg_system == 'on') {
    $explode_video = explode('_video', $get_video->video_location);
    if ($pt->get_video->sell_video == 0 || PT_IsAdmin() || $pt->get_video->is_owner || ($pt->get_video->sell_video > 0 && $pt->is_paid > 0 && $pt->config->sell_videos_system == 'on')) {
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
    else if ($pt->get_video->sell_video > 0 && !$pt->is_paid && $pt->config->sell_videos_system == 'on' && !empty($get_video->demo)) {
        $quality_set = false;
        if ($get_video->{"4096p"} == 1) {
            $pt->video_quality = '4K';
            $pt->video_res = '4096';
            $quality_set = true;
        }
        if ($get_video->{"2048p"} == 1 && $quality_set == false) {
            $pt->video_quality = '2K';
            $pt->video_res = '2048';
            $quality_set = true;
        }
        if ($get_video->{"1080p"} == 1 && $quality_set == false) {
            $pt->video_quality = '1080p';
            $pt->video_res = '1080';
            $quality_set = true;
        }
        if ($get_video->{"720p"} == 1 && $quality_set == false) {
            $pt->video_quality = '720p';
            $pt->video_res = '720';
            $quality_set = true;
        }
        if ($get_video->{"480p"} == 1 && $quality_set == false) {
            $pt->video_quality = '480p';
            $pt->video_res = '480';
            $quality_set = true;
        }
        if ($get_video->{"360p"} == 1 && $quality_set == false) {
            $pt->video_quality = '360p';
            $pt->video_res = '360';
            $quality_set = true;
        }
        if ($get_video->{"240p"} == 1 && $quality_set == false) {
            $pt->video_quality = '240p';
            $pt->video_res = '240';
            $quality_set = true;
        }
        $get_video->video_location = PT_GetMedia($get_video->demo);
    }
    elseif ($pt->get_video->rent_price == 0 || PT_IsAdmin() || $pt->get_video->is_owner || ($pt->get_video->rent_price > 0 && $pt->is_paid > 0 && $pt->config->rent_videos_system == 'on')) {
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
    else if ($pt->get_video->rent_price > 0 && !$pt->is_paid && $pt->config->rent_videos_system == 'on' && !empty($get_video->demo)) {
        $quality_set = false;
        if ($get_video->{"4096p"} == 1) {
            $pt->video_quality = '4K';
            $pt->video_res = '4096';
            $quality_set = true;
        }
        if ($get_video->{"2048p"} == 1 && $quality_set == false) {
            $pt->video_quality = '2K';
            $pt->video_res = '2048';
            $quality_set = true;
        }
        if ($get_video->{"1080p"} == 1 && $quality_set == false) {
            $pt->video_quality = '1080p';
            $pt->video_res = '1080';
            $quality_set = true;
        }
        if ($get_video->{"720p"} == 1 && $quality_set == false) {
            $pt->video_quality = '720p';
            $pt->video_res = '720';
            $quality_set = true;
        }
        if ($get_video->{"480p"} == 1 && $quality_set == false) {
            $pt->video_quality = '480p';
            $pt->video_res = '480';
            $quality_set = true;
        }
        if ($get_video->{"360p"} == 1 && $quality_set == false) {
            $pt->video_quality = '360p';
            $pt->video_res = '360';
            $quality_set = true;
        }
        if ($get_video->{"240p"} == 1 && $quality_set == false) {
            $pt->video_quality = '240p';
            $pt->video_res = '240';
            $quality_set = true;
        }
        $get_video->video_location = PT_GetMedia($get_video->demo);
    }

    if ($pt->config->stock_videos == 'on' && !empty($get_video->demo) && !empty($get_video->sell_video) && $get_video->is_stock == 1 && !$pt->is_paid && !$pt->video_owner) {
        $get_video->video_location = PT_GetMedia($get_video->demo);
        $pt->video_240 = 0;
        $pt->video_360 = 0;
        $pt->video_480 = 0;
        $pt->video_720 = 0;
        $pt->video_1080 = 0;
        $pt->video_2048 = 0;
        $pt->video_4096 = 0;
    }
}
else{
    if ($pt->get_video->sell_video > 0 && !$pt->is_paid && $pt->config->sell_videos_system == 'on' && !empty($get_video->demo)) {
        $quality_set = false;
        if ($get_video->{"4096p"} == 1) {
            $pt->video_quality = '4K';
            $pt->video_res = '4096';
            $quality_set = true;
        }
        if ($get_video->{"2048p"} == 1 && $quality_set == false) {
            $pt->video_quality = '2K';
            $pt->video_res = '2048';
            $quality_set = true;
        }
        if ($get_video->{"1080p"} == 1 && $quality_set == false) {
            $pt->video_quality = '1080p';
            $pt->video_res = '1080';
            $quality_set = true;
        }
        if ($get_video->{"720p"} == 1 && $quality_set == false) {
            $pt->video_quality = '720p';
            $pt->video_res = '720';
            $quality_set = true;
        }
        if ($get_video->{"480p"} == 1 && $quality_set == false) {
            $pt->video_quality = '480p';
            $pt->video_res = '480';
            $quality_set = true;
        }
        if ($get_video->{"360p"} == 1 && $quality_set == false) {
            $pt->video_quality = '360p';
            $pt->video_res = '360';
            $quality_set = true;
        }
        if ($get_video->{"240p"} == 1 && $quality_set == false) {
            $pt->video_quality = '240p';
            $pt->video_res = '240';
            $quality_set = true;
        }
        $get_video->video_location = PT_GetMedia($get_video->demo);
    }
    elseif ($pt->get_video->rent_price > 0 && !$pt->is_paid && $pt->config->rent_videos_system == 'on' && !empty($get_video->demo)) {
        $quality_set = false;
        if ($get_video->{"4096p"} == 1) {
            $pt->video_quality = '4K';
            $pt->video_res = '4096';
            $quality_set = true;
        }
        if ($get_video->{"2048p"} == 1 && $quality_set == false) {
            $pt->video_quality = '2K';
            $pt->video_res = '2048';
            $quality_set = true;
        }
        if ($get_video->{"1080p"} == 1 && $quality_set == false) {
            $pt->video_quality = '1080p';
            $pt->video_res = '1080';
            $quality_set = true;
        }
        if ($get_video->{"720p"} == 1 && $quality_set == false) {
            $pt->video_quality = '720p';
            $pt->video_res = '720';
            $quality_set = true;
        }
        if ($get_video->{"480p"} == 1 && $quality_set == false) {
            $pt->video_quality = '480p';
            $pt->video_res = '480';
            $quality_set = true;
        }
        if ($get_video->{"360p"} == 1 && $quality_set == false) {
            $pt->video_quality = '360p';
            $pt->video_res = '360';
            $quality_set = true;
        }
        if ($get_video->{"240p"} == 1 && $quality_set == false) {
            $pt->video_quality = '240p';
            $pt->video_res = '240';
            $quality_set = true;
        }
        $get_video->video_location = PT_GetMedia($get_video->demo);
    }
}
// demo video
$content_page = (($pt->is_list === true) ? "playlist" : (($pt->is_wl === true) ? "watch-later" : "content"));
$content_page = (($pt->is_list === true) ? "content" : (($pt->is_wl === true) ? "content" : "content"));
if (!empty($get_video->youtube)) {
    $vast_url = '';
    $vast_type = '';
    $ad_media = '';
    $ad_link = '';
    $ad_skip = 0;
    $ad_skip_num = 0;
    $is_video_ad = '';
    $ad_desc = '';
    $is_vast_ad = '';
    $ad_image = '';
    $pt->ad_image = '';
    $user_ad_trans = '';
}

$payment_currency = $pt->config->payment_currency;
$pt->currency = $currency         = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';
// if ($payment_currency == "USD") {
//     $currency     = "$";
// }
// else if($payment_currency == "EUR"){
//     $currency     = "€";
// }

$pt->user_data = $user_data;

$pt->in_queue = false;
if ($get_video->converted != 1) {

    $is_in_queue = $db->where('video_id',$get_video->id)->getValue(T_QUEUE,'COUNT(*)');

    if ($is_in_queue > 0) {
        $pt->in_queue = true;
    }

}

$pt->sub_category = '';
if (!empty($get_video->sub_category)) {
    foreach ($pt->sub_categories as $cat_key => $subs) {
        foreach ($subs as $sub_key => $sub_value) {
            if ($get_video->sub_category == array_keys($sub_value)[0]) {
                $pt->sub_category = $sub_value[array_keys($sub_value)[0]];
            }
        }
    }
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

$video_type = 'video/mp4';

if (!empty($get_video->youtube)) {
    $video_type = 'video/youtube';
}
if (strpos($get_video->video_location, ".m3u8") !== false) {
    $video_type = 'application/x-mpegURL';
    if ($pt->config->player_type == 'plyr') {
        $pt->config->player_type = 'default';
    }
}

$pt->is_movie = false;
if ($get_video->is_movie && $pt->config->movies_videos == 'on') {
    $content_page = 'movie';
    $content_page = 'content';
    $pt->is_movie = true;
}

$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = '';
    if (IS_LOGGED) {
        $selected = ($key == $pt->user->country_id) ? 'selected' : '';
    }
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}
$pt->vast_url = $vast_url;
$pt->ad_media = $ad_media;


if (!empty($get_video->twitch) || !empty($get_video->daily) || !empty($get_video->vimeo) || !empty($get_video->ok) || !empty($get_video->facebook) || !empty($get_video->youtube)) {
    $pt->config->donate_system = 'off';
}
if (!canUseFeature($get_video->user_id,'who_can_donate')) {
    $pt->config->donate_system = 'off';
}

$get_video->is_still_live = false;
$get_video->live_sub_users = 0;
if (!empty($get_video->stream_name) && !empty($get_video->live_time) && $get_video->live_time >= (time() - 10) && $get_video->live_ended == 0) {
    $get_video->is_still_live = true;
    $get_video->live_sub_users = $db->where('post_id',$get_video->id)->where('time',time()-6,'>=')->getValue(T_LIVE_SUB,'COUNT(*)');
}
if (!empty($get_video->stream_name) && !empty($get_video->video_location)) {

    $get_video->video_location = "https://" . $pt->config->bucket_name_2 . ".s3.amazonaws.com/" . substr($get_video->video_location, strpos($get_video->video_location, 'upload/'));
    $video_type = 'application/x-mpegURL';
}
if (!empty($vast_url)) {
    // $pt->vast_url = $vast_url;
    // $vast_url = '';
    // $ad_link = '';
    // $ad_skip = 'false';
    // $ad_skip_num = 0;
    // $is_vast_ad = '';
    // $vast_url = '';
    // $vast_type = '';
    // $last_ads = 0;
}
$pt->cards = $db->where('video_id',$get_video->id)->get(T_CARDS);

$pt->showSubscripation = true;
if ((IS_LOGGED && $pt->config->require_subcription == 'on' && !$pt->user->is_pro && !PT_IsAdmin()) || (!IS_LOGGED && $pt->config->require_subcription == 'on')) {
    $pt->showSubscripation = false;
}
$pt->shouldSubscribers = false;
if ($get_video->owner->privacy->who_can_watch_my_videos == 'subscribers') {
    if (!$pt->get_video->is_owner) {
        $pt->shouldSubscribers = true;
    }
    
    if (IS_LOGGED && !$pt->get_video->is_owner) {
        $is_sub = $db->where('user_id', $get_video->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
        if ($is_sub == 0) {
            $pt->shouldSubscribers = true;
        }
        else{
            $pt->shouldSubscribers = false;
        }
    }
}
if ($pt->get_video->converted != 1) {
    $pt->in_queue = true;
    $pt->converted = false;
}
$SUBS = $db->where('user_id', $user_data->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
$pt->content = PT_LoadPage("watch/$content_page", array(
    'ID' => $get_video->id,
    'KEY' => $get_video->video_id,
    'THUMBNAIL' => $get_video->thumbnail,
    'TITLE' => $get_video->markup_title,
    'DESC' => $get_video->markup_description,
    'URL' => $get_video->url,
    'VIDEO_LOCATION_240' => $pt->video_240,
    'VIDEO_LOCATION' => $get_video->video_location,
    'VIDEO_LOCATION_360' => $pt->video_360,
    'VIDEO_LOCATION_480' => $pt->video_480,
    'VIDEO_LOCATION_720' => $pt->video_720,
    'VIDEO_LOCATION_1080' => $pt->video_1080,
    'VIDEO_LOCATION_4096' => $pt->video_4096,
    'VIDEO_LOCATION_2048' => $pt->video_2048,
    'VIDEO_TYPE' => $video_type,
    'VIDEO_MAIN_ID' => $get_video->video_id,
    'VIDEO_ID' => $get_video->video_id_,
    'USER_DATA' => $user_data,
    'SUBSCIBE_BUTTON' => PT_GetSubscribeButton($user_data->id),
    'PLAYLIST_SUBSCRIBE' => $playlist_subscribe,
    'VIDEO_SIDEBAR' => $video_sidebar,
    'LIST_SIDEBAR' => $list_sidebar,
    'LIST_OWNERNAME' => $list_user_name,
    'VID_INDEX' => $video_index,
    'LIST_COUNT' => $list_count,
    'LIST_NAME' => $pt->list_name,
    'VIDEO_NEXT_SIDEBAR' => $next_video,
    'COOKIE' => $checked,
    'VIEWS' => number_format($get_video->views),
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

    'VIDEO_COMMENTS' => PT_LoadPage('watch/video-comments',array(
        'COUNT_COMMENTS' => $pt->count_comments,
        'COMMENTS' => $comments,
        'PINNED_COMMENTS' => $pinned_comments,
        'URL' => $get_video->url,
        'VIDEO_ID' => $get_video->id
    )),

    'SAVED_BUTTON' => $save_button,
    'IS_SAVED' => ($is_saved > 0) ? 'saved="true"' : '',
    'ENCODED_URL' => urlencode($get_video->url),
    'CATEGORY' => $get_video->category_name,
    'CATEGORY_ID' => $get_video->category_id,
    'TIME' => $get_video->time_alpha,
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
    'COMMENT_AD' => PT_GetAd('watch_comments'),
    'WATCH_SIDEBAR_AD' => $sidebar_ad,
    'USR_AD_TRANS' => $user_ad_trans,
    'CURRENCY'   => $currency,
    'SUB_CATEGORY' => $pt->sub_category,
    'VIDEO_ID_' => $get_video->video_id,
    'COUNTRIES' => $countries,
    'NOTIFY_BUTTON' => PT_GetNotifyButton($get_video->user_id),
    'PLEASE_LOGIN_LINK' => PT_Link("login?red=" . urlencode(PT_Link("v/$get_video->short_id"))),
    'SUBS' => $user_data->privacy->show_subscriptions_count == 'yes' && $SUBS > 0 ? number_format($SUBS) : ''

));
