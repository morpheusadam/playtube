<?php 
if ($pt->config->affiliate_system != 1) {
	header("Location: " . PT_Link('404'));
    exit();
}
$pt->page_url_ = $pt->config->site_url.'/affiliates';
$pt->title       = $lang->affiliates . ' | ' . $pt->config->title;
$pt->page        = "affiliates";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('affiliates/content');