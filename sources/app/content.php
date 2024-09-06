<?php


if (!IS_LOGGED || empty($_GET['id'])) {
    header("Location: " . PT_Link('404'));
    exit();
}
if ($pt->config->developers_page != 'on') {
    header("Location: " . PT_Link('404'));
    exit();
}

$id = PT_Secure($_GET['id']);

$app          = $db->where('id',$id)->where('app_user_id',$pt->user->id)->getOne(T_APPS);

if (empty($app)) {
    header("Location: " . PT_Link('404'));
    exit();
}

$pt->page_url_ = $pt->config->site_url.'/app/'.$app->id;
$pt->page = 'app';
$pt->title = $lang->app. ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content     = PT_LoadPage('developers/app',array(
    'id' => $app->id,
    'app_id' => $app->app_id,
    'app_secret' => $app->app_secret,
    'app_name' => $app->app_name,
    'app_website_url' => $app->app_website_url,
    'app_description' => $app->app_description,
    'app_callback_url' => $app->app_callback_url,
    'app_avatar' => PT_GetMedia($app->app_avatar)
));