<?php

if ($pt->config->article_system != 'on') {
    header('Location: ' .PT_Link('404'));
    exit();
}
$pt_cats      = array_keys(get_object_vars($pt->categories));
$html_posts   = '';
$html_p_posts = '';
$category     = 0;
$query        = false;
$pt->page_url_ = $pt->config->site_url.'/articles';
if (!empty($_POST['q'])) {
	$keyword = PT_Secure($_POST['q']);
	$sub_sql = '';
	$query   = true;
	
	if (!empty($_GET['category_id']) && in_array($_GET['category_id'],$pt_cats)) {
        $_GET['category_id'] = strip_tags($_GET['category_id']);
		$category = $_GET['category_id'];
		$sub_sql  = " AND `category` = '$category'";
	}

	$sql     = "(`title` LIKE '%$keyword%' OR `description` LIKE '%$keyword%' OR `tags` LIKE '%$keyword%') {$sub_sql} AND user_id NOT IN (".implode(',', $pt->blocked_array).")";
	$db->where($sql);
	$posts   = $db->orderBy('id', 'DESC')->get(T_POSTS,10);
	$pt->page_url_ = $pt->config->site_url.'/articles/category/'.$_GET['category_id'];
}

else{

	if (!empty($_GET['category_id']) && in_array($_GET['category_id'],$pt_cats)) {
		$db->where('category',$_GET['category_id']);
		$category = $_GET['category_id'];
		$pt->page_url_ = $pt->config->site_url.'/articles/category/'.$_GET['category_id'];
	}
	$posts   = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id', 'DESC')->get(T_POSTS, 20);
	
}

$popular_posts = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('views', 'DESC')->get(T_POSTS, 7);


$pt->category = $category;

if (!empty($posts)) {
    foreach ($posts as $key => $post) {
        // $link = PT_Link('articles/read/' . $post->id);
        // $article_link = $post->id;
        // if ($pt->config->seo_link == 'on') {
            $link = PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id));
            $article_link = PT_URLSlug($post->title,$post->id);
        // }
        $html_posts .= PT_LoadPage('articles/list', array(
            'ID' => $post->id,
	        'TITLE' => $post->title,
	        'DESC'  => PT_ShortText($post->description,190),
            'VIEWS_NUM' => number_format($post->views),
	        'THUMBNAIL' => PT_GetMedia($post->image),
	        'CAT' => ($post->category),
	        'URL' => $link,
	        'TIME' => TranslateDate(date($pt->config->date_style,$post->time)),
	        'ARTICLE_URL' => $article_link
        ));
    }
}

foreach ($popular_posts as $key => $post) {
    $post->user_data = PT_UserData($post->user_id);
    // $link = PT_Link('articles/read/' . $post->id);
    // $article_link = $post->id;
    // if ($pt->config->seo_link == 'on') {
        $link = PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id));
        $article_link = PT_URLSlug($post->title,$post->id);
    // }
    $html_p_posts .= PT_LoadPage('articles/popular', array(
        'TITLE' => $post->title,
        'THUMBNAIL' => PT_GetMedia($post->image),
        'URL' => $link,
        'ARTICLE_URL' => $article_link,
        'NAME' => (!empty($post->user_data) ? $post->user_data->name : ''),
        'VIEWS' => (!empty($post->views) ? $post->views : 0),
        'TIME' => TranslateDate(date($pt->config->date_style,$post->time))
    ));
}

if ($query && empty($html_posts)) {
	$html_posts = PT_LoadPage('articles/404',array('QUERY' => $keyword));
}

else if(empty($html_posts)){
	$html_posts = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book-open"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>'.$lang->no_post_found.'</div>';
}
$sidebar_ad = '';
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

$pt->title       = $lang->articles . ' | ' . $pt->config->title;
$pt->page        = "articles";
$pt->description = $pt->config->description;
$pt->posts_count = count($posts);
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('articles/content', array(
    'POSTS'         => $html_posts,
    'POPULAR_POSTS' => $html_p_posts,
    'CATEGORY'      => $category,
    'WATCH_SIDEBAR_AD' => $sidebar_ad
));
