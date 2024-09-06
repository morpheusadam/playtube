<?php

if ($pt->config->hashtag_system == 'off' || empty($_GET['id'])) {
	header('Location: ' . PT_Link('404'));
	exit;
}
$hashTag = PT_Secure($_GET['id']);
$tag_data = $db->where('tag',$hashTag)->getOne(T_HASHTAGS);
if (empty($tag_data)) {
	header('Location: ' . PT_Link('404'));
	exit;
}
$pt->page_number = isset($_GET['page_id']) && is_numeric($_GET['page_id']) && $_GET['page_id'] > 0 ? $_GET['page_id'] : 1;
$pt->limit_per_page = !empty($pt->config->videos_load_limit) && is_numeric($pt->config->videos_load_limit) && $pt->config->videos_load_limit > 0 ? (int) $pt->config->videos_load_limit : 20;
$db->pageLimit = $pt->limit_per_page;

$videos = $db->where("(title LIKE '%#[".$tag_data->id."]%' OR description LIKE '%#[".$tag_data->id."]%')")->where('privacy', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie',0)->where('live_time',0)->where('approved',1)->where('is_short', 0)->orderBy('views', 'DESC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
$pt->total_pages = $db->totalPages;

$html_videos = '';
if (!empty($videos)) {
    foreach ($videos as $key => $video) {
		$video = $pt->video = PT_GetVideoByID($video, 0, 0, 0);
        	
        $html_videos .= PT_LoadPage('hashtag/list', array(
            'ID' => $video->id,
            'VID_ID' => $video->id,
	        'TITLE' => $video->title,
	        'VIEWS' => $video->views,
            'VIEWS_NUM' => number_format($video->views),
	        'USER_DATA' => $video->owner,
	        'THUMBNAIL' => $video->thumbnail,
	        'URL' => $video->url,
            'ajax_url' => $video->ajax_url,
	        'TIME' => $video->time_ago,
            'DURATION' => $video->duration,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif,
            'DESC' => $video->markup_description,
            'PRICE' => $video->sell_video,
            'CURRENCY' => $pt->config->main_payment_currency,
        ));
    }
}

$videos = $db->where("(title LIKE '%#[".$tag_data->id."]%' OR description LIKE '%#[".$tag_data->id."]%')")->where('privacy', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_movie',0)->where('live_time',0)->where('approved',1)->where('is_short', 1)->orderBy('views', 'DESC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
$pt->total_shorts_pages = $db->totalPages;

$html_short_videos = '';
if (!empty($videos)) {
    foreach ($videos as $key => $video) {
        $video = $pt->video = PT_GetVideoByID($video, 0, 0, 0);
            
        $html_short_videos .= PT_LoadPage('hashtag/shorts_list', array(
            'ID' => $video->id,
            'VID_ID' => $video->id,
            'TITLE' => $video->title,
            'VIEWS' => $video->views,
            'VIEWS_NUM' => number_format($video->views),
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'ajax_url' => $video->ajax_url,
            'TIME' => $video->time_ago,
            'DURATION' => $video->duration,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif,
            'DESC' => $video->markup_description,
            'PRICE' => $video->sell_video,
            'CURRENCY' => $pt->config->main_payment_currency,
        ));
    }
}


$title = '#'.$hashTag;
$pt->stock_link = "";
$pt->page        = 'hashtag';
$pt->page_url_ = $pt->config->site_url.'/hashtag/'.$hashTag.'?page_id='.$pt->page_number;
$pt->videos_count= count(ToArray($videos));
$pt->title       = $title . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('hashtag/content', array(
    'TITLE' => $title,
    'VIDEOS' => PT_LoadPage('hashtag/videos', array('VIDEOS_LIST' => $html_videos,'TITLE' => $title,'TYPE' => $hashTag)),
    'SHORTS' => PT_LoadPage('hashtag/shorts', array('SHORTS_LIST' => $html_short_videos,'TITLE' => $title,'TYPE' => $hashTag)),
    'TYPE' => $hashTag,
));