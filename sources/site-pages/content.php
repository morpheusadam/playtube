<?php
if (empty($_GET['page_name'])) {
	header("Location: " . PT_Link(''));
    exit();
}

$pt->page_data = $db->where('page_name',PT_Secure($_GET['page_name']))->getOne(T_CUSTOM_PAGES);
if (empty($pt->page_data)) {
	header("Location: " . PT_Link(''));
	exit();
}
$pt->page_url_ = $pt->config->site_url.'/custom_page';
$pt->page        = 'custom_page';
$pt->title       = $pt->page_data->page_title . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('custom_page/content');