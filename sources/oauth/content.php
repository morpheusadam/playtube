<?php
if (empty($_GET['app_id'])) {
    header("Location: " . PT_Link('404'));
    exit();
}

$app = $db->where('app_id',PT_Secure($_GET['app_id']))->getOne(T_APPS);
if (empty($app)) {
	header("Location: " . PT_Link('404'));
    exit();
}

$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (!IS_LOGGED) {
    header("Location: " . PT_Link('login?red=' . urlencode($actual_link)));
    exit();
}



$have_permission = $db->where('app_id',$app->id)->where('user_id',$pt->user->id)->getValue(T_APPS_PERMISSION,'COUNT(*)');
if ($have_permission == 0) {
    $pt->page_url_ = $pt->config->site_url.'/oauth';
    $pt->page = 'permission';
    $pt->title = $lang->permission. ' | ' . $pt->config->title;
    $pt->description = $pt->config->description;
    $pt->keyword = $pt->config->keyword;
	$pt->content     = PT_LoadPage('developers/permission',[
		'app_link' => PT_Link("app/".$app->id),
        'id' => $app->id,
        'app_id' => $app->app_id,
        'app_secret' => $app->app_secret,
        'app_name' => $app->app_name,
        'app_website_url' => $app->app_website_url,
        'app_description' => $app->app_description,
        'app_callback_url' => $app->app_callback_url,
        'app_avatar' => PT_GetMedia($app->app_avatar)
	]);
}
else{
	$url = $app->app_website_url;
    if (isset($_GET['red']) && !empty($_GET['red'])) {
        $url = $_GET['red'];
    } else if (!empty($app->app_callback_url)) {
        $url = $app->app_callback_url;
    }
    $import = GenrateCode($pt->user->id, $app->id);
    header("Location: {$url}?code=$import");
    exit();
}