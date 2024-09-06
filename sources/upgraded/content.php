<?php
if (!PT_IsUpgraded()) {
	header('Location: ' . PT_Link('404'));
	exit;
}

$pt->page_url_ = $pt->config->site_url.'/upgraded';
$pt->title       = $lang->upgraded . ' | ' . $pt->config->title;
$pt->page        = "upgraded";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('upgraded/content');