<?php
if (IS_LOGGED == false || $pt->config->history_system != 'on') {
    header("Location: " . PT_Link('login'));
    exit();
}

$list = '<div class="col-md-12"><div class="box_light_black"><div class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_videos_found_history . '</div></div></div>';
$final = '';

$blocked_videos = $db->where('user_id',$pt->blocked_array , 'IN')->get(T_VIDEOS,null,'id');
$blocked_videos_array = array(0);
foreach ($blocked_videos as $key => $value) {
    $blocked_videos_array[] = $value->id;
}

$get_history_videos = array();
$get = $db->where('user_id', $user->id)->where('video_id',$blocked_videos_array , 'NOT IN')->orderby('id', 'DESC')->get(T_HISTORY, 10);

if (!empty($get)) {
    foreach ($get as $key => $video_) {
       $fetched_video = $db->where('id', $video_->video_id)->where('approved',1)->getOne(T_VIDEOS);
       if (!empty($fetched_video)) {
           $fetched_video->history_id = $video_->id;
           $get_history_videos[] = $fetched_video;
       }
       
    }
}
if (!empty($get_history_videos)) {
    $len = count($get_history_videos);
    foreach ($get_history_videos as $key => $video) {
        $video = $pt->video = PT_GetVideoByID($video, 0, 0, 0);
        $pt->last_video = false;
        if ($key == $len - 1) {
            $pt->last_video = true;
        }
        $final .= PT_LoadPage('history/list', array(
            'ID' => $video->history_id,
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'ajax_url' => $video->ajax_url,
            'TITLE' => $video->title,
            'DESC' => $video->markup_description,
            'VIEWS' => $video->views,
            'TIME' => $video->time_ago,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id)
        ));
    }
}
if (empty($final)) {
	$final = $list;
}
$pt->page_url_ = $pt->config->site_url.'/history';
$pt->videos      = $get_history_videos;
$pt->page        = 'history';
$pt->title       = $lang->history . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('history/content', array(
    'HISTORY_LIST' => $final
));