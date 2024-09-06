<?php
if (!IS_LOGGED) {
    header("Location: " . PT_Link('login'));
    exit();
}
if ($pt->config->developers_page != 'on') {
    header("Location: " . PT_Link('login'));
    exit();
}

$pt->page_url_ = $pt->config->site_url.'/create-app';
$pt->page = 'create-app';
$pt->title = $lang->create_app. ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage('developers/create-app');