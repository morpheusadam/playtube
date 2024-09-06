<?php 
header("HTTP/1.0 404 Not Found");
$pt->page_url_ = $pt->config->site_url.'/404';
$pt->page = '404';
$pt->title = '404 | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage('404/content');