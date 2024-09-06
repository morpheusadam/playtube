<?php
if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}

$list = '<div class="text-center no-content-found">' . $lang->no_videos_found_history . '</div>';
$final = '';
$get_history_videos = array();
$get = $db->where('user_id', $user->id)->orderby('id', 'DESC')->get(T_SAVED, 20);
if (!empty($get)) {
    foreach ($get as $key => $video_) {
       $fetched_video = $db->where('id', $video_->video_id)->where('approved',1)->getOne(T_VIDEOS);
       $fetched_video->history_id = $video_->id;
       $get_history_videos[] = $fetched_video;
    }
}
if (!empty($get_history_videos)) {
    $len = count($get_history_videos);
    foreach ($get_history_videos as $key => $video) {
        $video = PT_GetVideoByID($video, 0, 0, 0);
        $pt->last_video = false;
        if ($key == $len - 1) {
            $pt->last_video = true;
        }
        $final .= PT_LoadPage('saved/list', array(
            'ID' => $video->history_id,
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
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
$pt->videos      = $get_history_videos;
$pt->page        = 'saved';
$pt->title       = $lang->history . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('saved/content', array(
    'SAVED_LIST' => $final
));