<?php
if ($pt->config->article_system != 'on' || !IS_LOGGED || $pt->config->all_create_articles != 'on') {
    header('Location: ' .PT_Link('404'));
    exit();
}
// pagination system 
$pt->page_number = isset($_GET['page_id']) && is_numeric($_GET['page_id']) && $_GET['page_id'] > 0 ? $_GET['page_id'] : 1;
if (!empty($_GET['page_id'])) {
    $_GET['page_id'] = strip_tags($_GET['page_id']);
}
$pt->limit_per_page = !empty($pt->config->videos_load_limit) && is_numeric($pt->config->videos_load_limit) && $pt->config->videos_load_limit > 0 ? (int) $pt->config->videos_load_limit : 20;
$db->pageLimit = $pt->limit_per_page;
// pagination system 
$html_posts   = '';
$html_p_posts = '';
$category     = 0;
$query        = false;
$pt->page_url_ = $pt->config->site_url.'/my_articles'.'?page_id='.$pt->page_number;

//$posts   = $db->where('user_id', $pt->user->id)->where('active', '1')->orderBy('id', 'DESC')->get(T_POSTS, 20);
// pagination system 
$posts = $db->where('user_id', $pt->user->id)->where('active', '1')->orderBy('id', 'DESC')->objectbuilder()->paginate(T_POSTS, $pt->page_number);
$pt->total_pages = $db->totalPages;
// pagination system 

$popular_posts = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('views', 'DESC')->get(T_POSTS, 7);


$pt->category = $category;

if (!empty($posts)) {
    foreach ($posts as $key => $post) {
        $html_posts .= PT_LoadPage('my_articles/list', array(
            'ID' => $post->id,
	        'TITLE' => $post->title,
	        'DESC'  => PT_ShortText($post->description,190),
            'VIEWS_NUM' => number_format($post->views),
	        'THUMBNAIL' => PT_GetMedia($post->image),
	        'CAT' => ($post->category),
	        'URL' => PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id)),
	        'TIME' => TranslateDate(date($pt->config->date_style,$post->time)),
	        'ARTICLE_URL' => PT_URLSlug($post->title,$post->id)
        ));
    }
}

foreach ($popular_posts as $key => $post) {
    $post->user_data = PT_UserData($post->user_id);
    $html_p_posts .= PT_LoadPage('articles/popular', array(
        'TITLE' => $post->title,
        'THUMBNAIL' => PT_GetMedia($post->image),
        'URL' => PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id)),
        'ARTICLE_URL' => PT_URLSlug($post->title,$post->id),
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


$pt->title       = $lang->my_articles .' | ' . $pt->config->title;
$pt->page        = "my_articles";
$pt->description = $pt->config->description;
$pt->posts_count = count($posts);
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('my_articles/content', array(
    'POSTS'         => $html_posts,
    'POPULAR_POSTS' => $html_p_posts,
));
