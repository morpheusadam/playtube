<?php
if (!IS_LOGGED) {
    header("Location: " . PT_Link('login'));
    exit();
}
if ($pt->config->developers_page != 'on') {
    header("Location: " . PT_Link('login'));
    exit();
}

$pt->my_apps = $db->where('app_user_id',$pt->user->id)->orderBy('id','DESC')->get(T_APPS);
$html = '<div class="col-md-12"><div class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" class="feather"><path d="M7 11.5C4.51472 11.5 2.5 9.48528 2.5 7C2.5 4.51472 4.51472 2.5 7 2.5C9.48528 2.5 11.5 4.51472 11.5 7C11.5 9.48528 9.48528 11.5 7 11.5ZM7 21.5C4.51472 21.5 2.5 19.4853 2.5 17C2.5 14.5147 4.51472 12.5 7 12.5C9.48528 12.5 11.5 14.5147 11.5 17C11.5 19.4853 9.48528 21.5 7 21.5ZM17 11.5C14.5147 11.5 12.5 9.48528 12.5 7C12.5 4.51472 14.5147 2.5 17 2.5C19.4853 2.5 21.5 4.51472 21.5 7C21.5 9.48528 19.4853 11.5 17 11.5ZM17 21.5C14.5147 21.5 12.5 19.4853 12.5 17C12.5 14.5147 14.5147 12.5 17 12.5C19.4853 12.5 21.5 14.5147 21.5 17C21.5 19.4853 19.4853 21.5 17 21.5Z" fill="currentColor"></path></svg>'.$lang->no_apps_found.'</div></div>';
if (!empty($pt->my_apps)) {
    $html = '';
    foreach ($pt->my_apps as $key => $app) {
        $html .= PT_LoadPage('developers/apps_list',[
            'app_link' => PT_Link("/app/".$app->id),
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
}


$pt->page_url_ = $pt->config->site_url.'/my_apps';
$pt->page = 'my_apps';
$pt->title = $lang->my_apps. ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content     = PT_LoadPage('developers/my_apps',[
    'html' => $html,
    'apps_header' => PT_LoadPage('developers/header')
]);