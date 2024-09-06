<?php
if (!IS_LOGGED) {
    header("Location: " . PT_Link('login'));
    exit();
}
if ($pt->config->developers_page != 'on') {
    header("Location: " . PT_Link('login'));
    exit();
}

$pt->page_url_ = $pt->config->site_url.'/developers';
$pt->page = 'developers';
$pt->title = $lang->developers. ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage('developers/content',['apps_header' => PT_LoadPage('developers/header')]);