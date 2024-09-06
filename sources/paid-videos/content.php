<?php

if (IS_LOGGED == false || $pt->config->sell_videos_system == 'off' ) {
    header("Location: " . PT_Link('login'));
    exit();
}

$list = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_videos_found_paid . '</div>';
$final = '';
if (!empty($_GET['type'])) {
    $_GET['type'] = strip_tags($_GET['type']);
}
$type = 'videos';
if (!empty($_GET['type']) && ($_GET['type'] == 'movies' || $_GET['type'] == 'rented_movies') && $pt->config->movies_videos == 'on') {
    $type = 'movies';
    $rented_movies = " AND `type` != 'rent' ";
    if ($_GET['type'] == 'rented_movies') {
        $rented_movies = " AND `type` = 'rent' ";
        $type = 'rented_movies';
    }
    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` = '.$user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` = 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id '.$rented_movies.' ORDER BY id DESC LIMIT 20');
    

}
else{
    $rented_movies = " AND `type` != 'rent' ";
    if (!empty($_GET['type']) && $_GET['type'] == 'rented_videos') {
        $rented_movies = " AND `type` = 'rent' ";
        $type = 'rented_videos';
    }
    $get = $db->rawQuery('SELECT * FROM '.T_VIDEOS_TRSNS.' t WHERE `paid_id` = '.$user->id.' AND (SELECT id FROM '.T_VIDEOS.' WHERE id = t.video_id AND `is_movie` != 1 AND user_id NOT IN ('.implode(",", $pt->blocked_array).')) = video_id '.$rented_movies.' ORDER BY id DESC LIMIT 20');
   //$get = $db->where('paid_id', $user->id)->orderby('id', 'DESC')->get(T_VIDEOS_TRSNS, 20); 
}
$get_paid_videos = array();
if (!empty($get)) {
    foreach ($get as $key => $video_) {
       $fetched_video = $db->where('id', $video_->video_id)->getOne(T_VIDEOS);
       if (!empty($fetched_video)) {
           $fetched_video->tr_id = $video_->id;
           $fetched_video->user_payed_price = $video_->amount;
           $fetched_video->pay_currency = $video_->currency;
           $fetched_video->rent_video_time = $video_->time;


           $get_paid_videos[] = $fetched_video;


       }
    }
}
if (!empty($get_paid_videos)) {
    $len = count($get_paid_videos);
    foreach ($get_paid_videos as $key => $video) {
        $video = PT_GetVideoByID($video, 0, 0, 0);
        $pt->last_video = false;
        if ($key == $len - 1) {
            $pt->last_video = true;
        }
        $file = 'list';
        $user_payed_price = 0;
        $rent_video_time = '';
        $rent_video_time_start = '';
        if ($type == 'rented_videos' || $type == 'rented_movies') {
            $file = 'rent';
            $rent_video_time = TranslateDate(date($pt->config->date_style,$video->rent_video_time + (60*60*24*2)));
            $rent_video_time_start = TranslateDate(date($pt->config->date_style,$video->rent_video_time));
        }
        $currency  = !empty($pt->config->currency_symbol_array[$video->pay_currency]) ? $pt->config->currency_symbol_array[$video->pay_currency] : '$';
        $final .= PT_LoadPage('paid-videos/'.$file, array(
            'ID' => $video->tr_id,
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'TITLE' => $video->title,
            'DESC' => $video->markup_description,
            'VIEWS' => $video->views,
            'TIME' => $video->time_ago,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif,
            'VIDEO_PRICE' => $video->user_payed_price,
            'CURRENCY' => $currency,
            'RENT_END' => $rent_video_time,
            'RENT_START' => $rent_video_time_start
        ));
    }
}
if (empty($final)) {
	$final = $list;
}
$pt->page_url_ = $pt->config->site_url.'/paid-videos?type='.$type;
$pt->videos      = $get_paid_videos;
$pt->page        = 'paid-videos';
$pt->title       = $lang->paid_videos . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('paid-videos/content', array(
    'PAID_LIST' => $final
));