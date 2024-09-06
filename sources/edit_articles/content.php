<?php
if (IS_LOGGED == false || $pt->config->all_create_articles != 'on') {
    header("Location: " . PT_Link('login'));
    exit();
}
if (empty($_GET['id'])) {
    header("Location: " . PT_Link('login'));
    exit();
}
$_GET['id'] = strip_tags($_GET['id']);
$id    = PT_Secure($_GET['id']);
$article = $db->where('id', $id)->where('active', '1')->getOne(T_POSTS);
if (empty($article) || $article->user_id != $pt->user->id) {
    header("Location: " . PT_Link(''));
    exit();
}



$pt->page_url_ = $pt->config->site_url.'/edit_articles/'.$id;
$pt->article       = $article;
$pt->page        = 'edit_articles';
$pt->title       = $lang->edit_article . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('edit_articles/content',array(
    'ID' => $article->id,
    'TITLE' => $article->title,
    'DESC' => $article->description,
    'IMAGE' => PT_GetMedia($article->image),
    'TEXT' => PT_Decode($article->text),
    'TIME' => date('F/m/Y h:i',$article->time),
    'VIEWS' => number_format($article->views),
    'SHARED' => number_format($article->shared),
    'CATEGORY_ID' => $article->category,
    'TAGS' => $article->tags,
    'POST_ENCODED_URL' => urlencode(PT_Link('articles/read/' . PT_URLSlug($article->title,$article->id))),
));