<?php 

if (!IS_LOGGED || $pt->config->post_system != 'on' || !$pt->config->can_use_post) {
	header('Location: ' . PT_Link('login'));
	exit;
}

$pt->page_url_ = $pt->config->site_url.'/create_post';
$pt->title       = $lang->create_post .' | ' . $pt->config->title;
$pt->page        = "create_post";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('create_post/content');