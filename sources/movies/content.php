<?php 
if ($pt->config->movies_videos != 'on') {
    header('Location: ' .PT_Link('404'));
    exit();
}
// pagination system 
$pt->page_number = isset($_GET['page_id']) && is_numeric($_GET['page_id']) && $_GET['page_id'] > 0 ? $_GET['page_id'] : 1;
$pt->limit_per_page = !empty($pt->config->videos_load_limit) && is_numeric($pt->config->videos_load_limit) && $pt->config->videos_load_limit > 0 ? (int) $pt->config->videos_load_limit : 20;
$db->pageLimit = $pt->limit_per_page;
if (!empty($_GET['page_id'])) {
	$_GET['page_id'] = strip_tags($_GET['page_id']);
}
// pagination system 
$final = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_movies_found . '</div>';
$db->where('is_movie', 1);
if (!empty($_GET['category_']) && $_GET['category_'] != 'all') {
	$_GET['category_'] = strip_tags($_GET['category_']);
	$db->where('category_id', PT_Secure($_GET['category_']));
}
if (!empty($_GET['rating'])) {
	$_GET['rating'] = strip_tags($_GET['rating']);
	$db->where('rating', PT_Secure($_GET['rating']));
}
if (!empty($_GET['release'])) {
	$_GET['release'] = strip_tags($_GET['release']);
	$db->where('movie_release', PT_Secure($_GET['release']));
}
if (!empty($_GET['country'])) {
	$_GET['country'] = strip_tags($_GET['country']);
	$db->where('country', PT_Secure($_GET['country']));
}
if (!empty($_GET['keyword'])) {
	$_GET['keyword'] = strip_tags($_GET['keyword']);
	$db->where('title', '%'.PT_Secure($_GET['keyword']).'%','LIKE');
}
$videos = $db->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->orderBy('id', 'DESC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
$pt->total_pages = $db->totalPages;
if (!empty($videos)) {
	$final = '';
	$len = count($videos);
	foreach ($videos as $key => $video) {
		$video = PT_GetVideoByID($video, 0, 1, 0);
	    $pt->last_video = false;
	    if ($key == $len - 1) {
	        $pt->last_video = true;
	    }
	    $final .= PT_LoadPage('movies/list', array(
			        'ID' => $video->id,
			        'USER_DATA' => $video->owner,
			        'THUMBNAIL' => $video->thumbnail,
			        'URL'       => $video->url,
			        'TITLE'     => $video->title,
			        'DESC'      => $video->markup_description,
			        'VIEWS'     => number_format($video->views),
			        'TIME'      => $video->time_ago,
			        'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
			        'V_ID'      => $video->video_id,
			        'STARS'     => strlen($video->stars) > 30 ? substr($video->stars, 0,27).'..' : $video->stars,
			        'CAT'       => $pt->movies_categories[$video->category_id],
			        'PRODUCER'  => strlen($video->producer) > 30 ? substr($video->producer, 0,27).'..' : $video->producer,
			        'RATING'    => !empty($video->rating) ? round($video->rating) : 0 ,
			        'COUNTRY'   => !empty($countries_name[$video->country]) ? $countries_name[$video->country] : '',
			        'YEAR'   => !empty($video->movie_release) ? $video->movie_release : '',
			        'QUALITY'   => !empty($video->quality) ? $video->quality : ''
			    ));
	}
}
$featured = '';
if ($pt->config->theme == 'default') {
	$featuredMovie = $db->where('is_movie', 1)->where('featured_movie',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('approved',1)->orderBy('id', 'DESC')->objectbuilder()->getOne(T_VIDEOS,'id');

	if (!empty($featuredMovie)) {
		$featuredMovie           = PT_GetVideoByID($featuredMovie->id, 0, 0, 2);

		$featured = PT_LoadPage('movies/featured', array(
			        'ID' => $featuredMovie->id,
			        'USER_DATA' => $featuredMovie->owner,
			        'THUMBNAIL' => $featuredMovie->thumbnail,
			        'URL'       => $featuredMovie->url,
			        'TITLE'     => $featuredMovie->title,
			        'DESC'      => $featuredMovie->markup_description,
			        'VIEWS'     => number_format($featuredMovie->views),
			        'TIME'      => $featuredMovie->time_ago,
			        'VIDEO_ID_' => PT_Slug($featuredMovie->title, $featuredMovie->video_id),
			        'V_ID'      => $featuredMovie->video_id,
			        'STARS'     => strlen($featuredMovie->stars) > 30 ? substr($featuredMovie->stars, 0,27).'..' : $featuredMovie->stars,
			        'CAT'       => $pt->movies_categories[$featuredMovie->category_id],
			        'PRODUCER'  => strlen($featuredMovie->producer) > 30 ? substr($featuredMovie->producer, 0,27).'..' : $featuredMovie->producer,
			        'RATING'    => !empty($featuredMovie->rating) ? round($featuredMovie->rating) : 0 ,
			        'COUNTRY'   => !empty($countries_name[$featuredMovie->country]) ? $countries_name[$featuredMovie->country] : '',
			        'YEAR'   => !empty($featuredMovie->movie_release) ? $featuredMovie->movie_release : '',
			        'QUALITY'   => !empty($featuredMovie->quality) ? $featuredMovie->quality : '',
			        'CATEGORY_ID' => $featuredMovie->category_id,
			        'CATEGORY' => $featuredMovie->category_name,
			    ));
	}
}


$pt->page_url_ = $pt->config->site_url.'/movies';
$pt->page = 'movies';
$pt->videos  = $videos;
$pt->title = $lang->movies . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage('movies/content', array('VIDEOS' => $final,'featured' => $featured));