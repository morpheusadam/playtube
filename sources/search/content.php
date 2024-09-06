<?php
if (empty($_GET['keyword'])) {
	header("Location: " . PT_Link('login'));
    exit();
}
$_GET['keyword'] = strip_tags($_GET['keyword']);
$_GET['keyword'] = filter_var ( $_GET['keyword'], FILTER_UNSAFE_RAW);
$keyword = PT_Secure($_GET['keyword']);


$list = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_videos_found_for_now . '</div>';
$list2 = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg></div>';
$final = '';
$final2 = '';
$category = '';
$date = '';
$category_id = '';
// pagination system 
if (!empty($_GET['page_id'])) {
    $_GET['page_id'] = strip_tags($_GET['page_id']);
}
$pt->page_number = isset($_GET['page_id']) && is_numeric($_GET['page_id']) && $_GET['page_id'] > 0 ? $_GET['page_id'] : 1;
$pt->limit_per_page = !empty($pt->config->videos_load_limit) && is_numeric($pt->config->videos_load_limit) && $pt->config->videos_load_limit > 0 ? (int) $pt->config->videos_load_limit : 20;
$db->pageLimit = $pt->limit_per_page;
// pagination system 
if (isset($_POST['category']) && !empty($_POST['category'])) {
    if (is_array($_POST['category']) && count($_POST['category']) > 1) {
        $cat_id = "'".implode("','", $_POST['category'])."'";
       // $cat_id = PT_Secure($cat_id);
        $category = " AND category_id IN (".$cat_id.") ";
    }
    else{
        $cat_id = PT_Secure($_POST['category'][0]);
        $category = " AND category_id = '".$cat_id."' ";
    }
    $category_id = $cat_id;
}
if (isset($_POST['date']) && !empty($_POST['date'])) {
    if ($_POST['date'] == 'last_hour') {
        $time = time()-(60*60);
        $date = " AND time >= ".$time." ";
    }
    elseif ($_POST['date'] == 'today') {
        $time = time()-(60*60*24);
        $date = " AND time >= ".$time." ";
    }
    elseif ($_POST['date'] == 'this_week') {
        $time = time()-(60*60*24*7);
        $date = " AND time >= ".$time." ";
    }
    elseif ($_POST['date'] == 'this_month') {
        $time = time()-(60*60*24*30);
        $date = " AND time >= ".$time." ";
    }
    elseif ($_POST['date'] == 'this_year') {
        $time = time()-(60*60*24*365);
        $date = " AND time >= ".$time." ";
    }
}

if ($pt->config->total_videos > 1000000) {

    // $get_videos = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE MATCH (title) AGAINST ('$keyword') AND privacy = 0 ".$category.$date." ORDER BY id ASC LIMIT 20");

    // pagination system 
    // $get_videos = $db->where("title LIKE '%$keyword%' AND privacy = 0  ".$category.$date)->orderBy('id', 'ASC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
    $get_videos = $db->where("MATCH (title) AGAINST ('$keyword') AND privacy = 0  ".$category.$date)->where('approved',1)->orderBy('id', 'ASC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
    $pt->total_pages = $db->totalPages;
    // pagination system
} else {
    //$get_videos = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE title LIKE '%$keyword%' AND privacy = 0 ".$category.$date." ORDER BY id ASC LIMIT 20");
    
    // pagination system 
    $get_videos = $db->where("(title LIKE '%$keyword%' OR tags LIKE '%$keyword%' OR description LIKE '%$keyword%') AND privacy = 0  ".$category.$date)->where('approved',1)->orderBy('id', 'ASC')->objectbuilder()->paginate(T_VIDEOS, $pt->page_number);
    $pt->total_pages = $db->totalPages;
    // pagination system
}
if (!empty($get_videos)) {
    $len = count($get_videos);
    foreach ($get_videos as $key => $video) {
        $video = PT_GetVideoByID($video, 0, 0, 0);
        $pt->last_video = false;
        if ($key == $len - 1) {
            $pt->last_video = true;
        }
        $final .= PT_LoadPage('search/list', array(
            'ID' => $video->id,
            'USER_DATA' => $video->owner,
            'THUMBNAIL' => $video->thumbnail,
            'URL' => $video->url,
            'ajax_url' => $video->ajax_url,
            'TITLE' => $video->title,
            'DESC' => $video->markup_description,
            'VIEWS' => $video->views,
            'VIEWS_NUM' => number_format($video->views),
            'TIME' => $video->time_ago,
            'DURATION' => $video->duration,
            'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
            'GIF' => $video->gif
        ));
    }
}
if (empty($final)) {
	$final = $list;
}

$get_users = $db->rawQuery("SELECT * FROM " . T_USERS . " WHERE ((`username` LIKE '%$keyword%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%$keyword%') ORDER BY id ASC LIMIT 50");
if (!empty($get_users)) {
    $len = count($get_users);
    foreach ($get_users as $key => $user) {
        $user = PT_UserData($user, array('data' => true));
        $pt->last_user = false;
        if ($key == $len - 1) {
            $pt->last_user = true;
        }
        $final2 .= PT_LoadPage('search/user-list', array(
            'ID' => $user->id,
            'USER_DATA' => $user,
        ));
    }
}
// print_r($_POST);
// exit();
if (empty($final2)) {
    $final2 = $list2;
}


$pt->videos      = $get_videos;
$pt->users      = $get_users;
$pt->page        = 'search';
$pt->title       = $lang->search . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('search/content', array(
    'VIDEOS' => $final,
    'USERS' => $final2,
    'KEYWORD' => $keyword,
    'CAT' => $category_id
));